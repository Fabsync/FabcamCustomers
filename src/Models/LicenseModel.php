<?php

class LicenseModel extends BaseModel {

    private static bool $amcSynced = false;

    public function __construct() {
        parent::__construct();
        if (!self::$amcSynced) {
            $this->expireOverdueAmc();
            self::$amcSynced = true;
        }
    }

    // Active AMCs whose expiry date has been reached (days left <= 0) become expired
    public function expireOverdueAmc(): int {
        return $this->pdo->exec(
            "UPDATE licenses SET amc_status = 'expired'
             WHERE amc_status = 'active'
               AND expiry_date IS NOT NULL
               AND expiry_date <= CURDATE()"
        );
    }

    public function getAll(array $filters = [], int $limit = 0, int $offset = 0, string $sort = 'expiry_date', string $dir = 'asc'): array {
        [$whereSql, $params] = $this->buildWhere($filters);

        $allowed  = [
            'company_name'   => 'c.company_name',
            'product_name'   => 'p.product_name',
            'license_type'   => 'l.license_type',
            'expiry_date'    => 'l.expiry_date',
            'days_left'      => 'DATEDIFF(l.expiry_date, CURDATE())',
            'license_status' => 'l.license_status',
            'amc_status'     => 'l.amc_status',
        ];
        $orderCol    = $allowed[$sort] ?? 'l.expiry_date';
        $orderDir    = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';
        $paginateSql = $limit > 0 ? " LIMIT {$limit} OFFSET {$offset}" : '';

        $sql = "SELECT l.*, DATEDIFF(l.expiry_date, CURDATE()) AS days_left,
                       c.company_name, c.customer_id AS cust_code,
                       p.product_name, u.name AS updated_by_name
                FROM licenses l
                JOIN customers c ON c.id = l.customer_id
                JOIN products  p ON p.id = l.product_id
                LEFT JOIN users u ON u.id = l.updated_by
                {$whereSql}
                ORDER BY {$orderCol} {$orderDir}{$paginateSql}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCount(array $filters = []): int {
        [$whereSql, $params] = $this->buildWhere($filters);
        $sql  = "SELECT COUNT(*) FROM licenses l
                 JOIN customers c ON c.id = l.customer_id
                 JOIN products  p ON p.id = l.product_id
                 {$whereSql}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private function buildWhere(array $filters): array {
        $where  = [];
        $params = [];

        $allowed_status = ['active', 'expired', 'grace', 'revoked'];
        if (!empty($filters['status']) && in_array($filters['status'], $allowed_status)) {
            $where[]  = 'l.license_status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['product_id'])) {
            $where[]  = 'l.product_id = ?';
            $params[] = (int) $filters['product_id'];
        }
        if (!empty($filters['customer_id'])) {
            $where[]  = 'l.customer_id = ?';
            $params[] = (int) $filters['customer_id'];
        }
        if (!empty($filters['q'])) {
            $like     = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $where[]  = '(c.company_name LIKE ? OR c.customer_id LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }
        $allowed_amc = ['active', 'expired', 'not_applicable'];
        if (!empty($filters['amc_status']) && in_array($filters['amc_status'], $allowed_amc)) {
            $where[]  = 'l.amc_status = ?';
            $params[] = $filters['amc_status'];
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }

    public function findById(int $id): array|false {
        $stmt = $this->pdo->prepare(
            'SELECT l.*, DATEDIFF(l.expiry_date, CURDATE()) AS days_left,
                    c.company_name, c.customer_id AS cust_code,
                    p.product_name, u.name AS updated_by_name
             FROM licenses l
             JOIN customers c ON c.id = l.customer_id
             JOIN products  p ON p.id = l.product_id
             LEFT JOIN users u ON u.id = l.updated_by
             WHERE l.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getExpiringSoon(int $days = 30): array {
        $stmt = $this->pdo->prepare(
            "SELECT l.*, DATEDIFF(l.expiry_date, CURDATE()) AS days_left,
                    c.company_name, c.customer_id AS cust_code, p.product_name
             FROM licenses l
             JOIN customers c ON c.id = l.customer_id
             JOIN products  p ON p.id = l.product_id
             WHERE l.license_status IN ('active','grace')
               AND DATEDIFF(l.expiry_date, CURDATE()) BETWEEN 0 AND ?
             ORDER BY l.expiry_date ASC"
        );
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    public function getStatCounts(): array {
        $row = $this->pdo->query(
            "SELECT
               COUNT(DISTINCT c.id)                                                AS total_customers,
               SUM(CASE WHEN l.license_status = 'active'  THEN 1 ELSE 0 END)      AS active_licenses,
               SUM(CASE WHEN l.license_status = 'expired' THEN 1 ELSE 0 END)      AS expired_licenses,
               SUM(CASE WHEN l.license_status = 'grace'   THEN 1 ELSE 0 END)      AS grace_licenses,
               SUM(CASE WHEN l.license_status = 'revoked' THEN 1 ELSE 0 END)      AS revoked_licenses,
               SUM(CASE WHEN l.license_status IN ('active','grace')
                         AND DATEDIFF(l.expiry_date, CURDATE()) BETWEEN 0 AND 30
                         THEN 1 ELSE 0 END)                                        AS expiring_soon,
               SUM(CASE WHEN l.amc_status = 'active'         THEN 1 ELSE 0 END)   AS amc_active,
               SUM(CASE WHEN l.amc_status = 'expired'        THEN 1 ELSE 0 END)   AS amc_expired,
               SUM(CASE WHEN l.amc_status = 'not_applicable' THEN 1 ELSE 0 END)   AS amc_na,
               COALESCE(SUM(CASE WHEN l.amc_status = 'expired' THEN l.amc_cost ELSE 0 END), 0)
                                                                                   AS total_amc_revenue
             FROM licenses l
             JOIN customers c ON c.id = l.customer_id"
        )->fetch();

        // customers total may not include those with no licenses
        $totalCustomers = (int) $this->pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();
        $row['total_customers'] = $totalCustomers;
        return $row;
    }

    public function insert(array $d, int $userId): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO licenses
             (customer_id, product_id, license_type, server_code, lock_code, machine_name,
              purchase_price, purchase_date, expiry_date, license_status,
              amc_cost, renewal_date, amc_status, remarks, updated_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $d['customer_id'],
            $d['product_id'],
            $d['license_type'],
            $d['server_code']    ?: null,
            $d['lock_code']      ?: null,
            $d['machine_name']   ?: null,
            $d['purchase_price'] ?: null,
            $d['purchase_date']  ?: null,
            $d['expiry_date']    ?: null,
            $d['license_status'],
            $d['amc_cost']       ?: null,
            $d['renewal_date']   ?: null,
            $d['amc_status'],
            $d['remarks']        ?: null,
            $userId,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $d, int $userId): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE licenses SET
             customer_id=?, product_id=?, license_type=?, server_code=?, lock_code=?,
             machine_name=?, purchase_price=?, purchase_date=?, expiry_date=?,
             license_status=?, amc_cost=?, renewal_date=?, amc_status=?, remarks=?, updated_by=?
             WHERE id=?'
        );
        return $stmt->execute([
            $d['customer_id'],
            $d['product_id'],
            $d['license_type'],
            $d['server_code']    ?: null,
            $d['lock_code']      ?: null,
            $d['machine_name']   ?: null,
            $d['purchase_price'] ?: null,
            $d['purchase_date']  ?: null,
            $d['expiry_date']    ?: null,
            $d['license_status'],
            $d['amc_cost']       ?: null,
            $d['renewal_date']   ?: null,
            $d['amc_status'],
            $d['remarks']        ?: null,
            $userId,
            $id,
        ]);
    }

    public function delete(int $id): bool {
        return $this->pdo->prepare('DELETE FROM licenses WHERE id = ?')->execute([$id]);
    }

    public function deleteByCustomer(int $customerId): bool {
        return $this->pdo->prepare('DELETE FROM licenses WHERE customer_id = ?')->execute([$customerId]);
    }
}
