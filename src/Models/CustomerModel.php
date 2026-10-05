<?php

class CustomerModel extends BaseModel {

    public function getAll(string $search = ''): array {
        if ($search) {
            $like = '%' . $search . '%';
            $stmt = $this->pdo->prepare(
                'SELECT c.*, u.name AS created_by_name
                 FROM customers c
                 LEFT JOIN users u ON u.id = c.created_by
                 WHERE c.company_name LIKE ? OR c.customer_id LIKE ? OR c.contact_person LIKE ?
                 ORDER BY c.company_name'
            );
            $stmt->execute([$like, $like, $like]);
            return $stmt->fetchAll();
        }
        return $this->pdo->query(
            'SELECT c.*, u.name AS created_by_name
             FROM customers c
             LEFT JOIN users u ON u.id = c.created_by
             ORDER BY c.company_name'
        )->fetchAll();
    }

    public function getCount(string $search = ''): int {
        if ($search) {
            $like = '%' . $search . '%';
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM customers
                 WHERE company_name LIKE ? OR customer_id LIKE ? OR contact_person LIKE ?'
            );
            $stmt->execute([$like, $like, $like]);
        } else {
            $stmt = $this->pdo->query('SELECT COUNT(*) FROM customers');
        }
        return (int) $stmt->fetchColumn();
    }

    public function getPaginated(string $search, int $offset, int $limit, string $sort = 'company_name', string $dir = 'asc'): array {
        $allowed  = ['company_name' => 'c.company_name', 'customer_id' => 'c.customer_id', 'contact_person' => 'c.contact_person', 'created_at' => 'c.created_at'];
        $orderCol = $allowed[$sort] ?? 'c.company_name';
        $orderDir = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';
        $orderSql = "{$orderCol} {$orderDir}";

        if ($search) {
            $like = '%' . $search . '%';
            $stmt = $this->pdo->prepare(
                "SELECT c.*, u.name AS created_by_name
                 FROM customers c
                 LEFT JOIN users u ON u.id = c.created_by
                 WHERE c.company_name LIKE ? OR c.customer_id LIKE ? OR c.contact_person LIKE ?
                 ORDER BY {$orderSql}
                 LIMIT {$limit} OFFSET {$offset}"
            );
            $stmt->execute([$like, $like, $like]);
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT c.*, u.name AS created_by_name
                 FROM customers c
                 LEFT JOIN users u ON u.id = c.created_by
                 ORDER BY {$orderSql}
                 LIMIT {$limit} OFFSET {$offset}"
            );
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }

    public function findById(int $id): array|false {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.name AS created_by_name
             FROM customers c
             LEFT JOIN users u ON u.id = c.created_by
             WHERE c.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function insert(array $d, int $userId): int {
        $customerId = $this->nextCustomerId();
        $stmt = $this->pdo->prepare(
            'INSERT INTO customers (customer_id, company_name, contact_person, mobile, email, gst_number, address, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $customerId,
            $d['company_name'],
            $d['contact_person'] ?? '',
            $d['mobile']         ?? '',
            $d['email']          ?? '',
            $d['gst_number']     ?? '',
            $d['address']        ?? '',
            $userId,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $d): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE customers SET company_name=?, contact_person=?, mobile=?, email=?, gst_number=?, address=? WHERE id=?'
        );
        return $stmt->execute([
            $d['company_name'],
            $d['contact_person'] ?? '',
            $d['mobile']         ?? '',
            $d['email']          ?? '',
            $d['gst_number']     ?? '',
            $d['address']        ?? '',
            $id,
        ]);
    }

    public function delete(int $id): bool {
        return $this->pdo->prepare('DELETE FROM customers WHERE id = ?')->execute([$id]);
    }

    /** Deletes the given customers and all their licenses in one transaction. Returns customers deleted. */
    public function deleteManyWithLicenses(array $ids): int {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($i) => $i > 0)));
        if (!$ids) return 0;
        $in = implode(',', array_fill(0, count($ids), '?'));

        $this->pdo->beginTransaction();
        try {
            // FK is RESTRICT — delete child licenses first, then the customers
            $this->pdo->prepare("DELETE FROM licenses WHERE customer_id IN ($in)")->execute($ids);
            $stmt = $this->pdo->prepare("DELETE FROM customers WHERE id IN ($in)");
            $stmt->execute($ids);
            $this->pdo->commit();
            return $stmt->rowCount();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function findByCode(string $code): array|false {
        $stmt = $this->pdo->prepare('SELECT * FROM customers WHERE customer_id = ? LIMIT 1');
        $stmt->execute([$code]);
        return $stmt->fetch();
    }

    public function insertWithCode(string $code, array $d, int $userId): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO customers (customer_id, company_name, contact_person, mobile, email, gst_number, address, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $code,
            $d['company_name'],
            $d['contact_person'] ?? '',
            $d['mobile']         ?? '',
            $d['email']          ?? '',
            $d['gst_number']     ?? '',
            $d['address']        ?? '',
            $userId,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    private function nextCustomerId(): string {
        $stmt = $this->pdo->query("SELECT customer_id FROM customers ORDER BY id DESC LIMIT 1");
        $last = $stmt->fetchColumn();
        $num  = $last ? ((int) substr($last, 4)) + 1 : 1;
        return 'FAB-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
