<?php

class EstimateModel extends BaseModel {

    public function getAll(array $filters = []): array {
        $where  = [];
        $params = [];

        $allowed_status = ['draft', 'sent', 'accepted', 'cancelled'];
        if (!empty($filters['status']) && in_array($filters['status'], $allowed_status)) {
            $where[]  = 'e.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['customer_id'])) {
            $where[]  = 'e.customer_id = ?';
            $params[] = (int) $filters['customer_id'];
        }
        if (!empty($filters['q'])) {
            $like     = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $where[]  = '(c.company_name LIKE ? OR c.customer_id LIKE ? OR e.estimate_number LIKE ?)';
            array_push($params, $like, $like, $like);
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT e.*, c.company_name, c.customer_id AS cust_code
                FROM estimates e
                JOIN customers c ON c.id = e.customer_id
                $whereSql
                ORDER BY e.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): array|false {
        $stmt = $this->pdo->prepare(
            'SELECT e.*, c.company_name, c.customer_id AS cust_code,
                    c.contact_person, c.mobile, c.email AS customer_email,
                    c.gst_number, c.address AS customer_address,
                    u.name AS created_by_name
             FROM estimates e
             JOIN customers c ON c.id = e.customer_id
             LEFT JOIN users u ON u.id = e.created_by
             WHERE e.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findItems(int $estimateId): array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM estimate_items WHERE estimate_id = ? ORDER BY sl_no ASC'
        );
        $stmt->execute([$estimateId]);
        return $stmt->fetchAll();
    }

    public function insert(array $d, array $items, int $userId): int {
        $this->pdo->beginTransaction();
        try {
            $number = $this->nextEstimateNumber();
            $totals = $this->recalcTotals(
                $items,
                (float)($d['discount_pct'] ?? 0),
                $d['tax_type'] ?? 'cgst_sgst',
                (float)($d['tax_rate'] ?? 18)
            );

            $stmt = $this->pdo->prepare(
                'INSERT INTO estimates
                 (estimate_number, customer_id, estimate_date, valid_until,
                  subtotal, discount_pct, discount_amt, taxable_amount,
                  tax_type, tax_rate, cgst_amount, sgst_amount, igst_amount, grand_total,
                  notes, terms, status, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $number,
                (int) $d['customer_id'],
                $d['estimate_date'],
                $d['valid_until'] ?: null,
                $totals['subtotal'],
                $totals['discount_pct'],
                $totals['discount_amt'],
                $totals['taxable_amount'],
                $totals['tax_type'],
                $totals['tax_rate'],
                $totals['cgst_amount'],
                $totals['sgst_amount'],
                $totals['igst_amount'],
                $totals['grand_total'],
                trim($d['notes']  ?? ''),
                trim($d['terms']  ?? ''),
                $d['status'] ?? 'draft',
                $userId,
            ]);
            $id = (int) $this->pdo->lastInsertId();
            $this->insertItems($id, $items);
            $this->pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $d, array $items): bool {
        $this->pdo->beginTransaction();
        try {
            $totals = $this->recalcTotals(
                $items,
                (float)($d['discount_pct'] ?? 0),
                $d['tax_type'] ?? 'cgst_sgst',
                (float)($d['tax_rate'] ?? 18)
            );

            $stmt = $this->pdo->prepare(
                'UPDATE estimates SET
                 customer_id=?, estimate_date=?, valid_until=?,
                 subtotal=?, discount_pct=?, discount_amt=?, taxable_amount=?,
                 tax_type=?, tax_rate=?, cgst_amount=?, sgst_amount=?, igst_amount=?, grand_total=?,
                 notes=?, terms=?, status=?
                 WHERE id=?'
            );
            $stmt->execute([
                (int) $d['customer_id'],
                $d['estimate_date'],
                $d['valid_until'] ?: null,
                $totals['subtotal'],
                $totals['discount_pct'],
                $totals['discount_amt'],
                $totals['taxable_amount'],
                $totals['tax_type'],
                $totals['tax_rate'],
                $totals['cgst_amount'],
                $totals['sgst_amount'],
                $totals['igst_amount'],
                $totals['grand_total'],
                trim($d['notes']  ?? ''),
                trim($d['terms']  ?? ''),
                $d['status'] ?? 'draft',
                $id,
            ]);
            $this->pdo->prepare('DELETE FROM estimate_items WHERE estimate_id = ?')->execute([$id]);
            $this->insertItems($id, $items);
            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): bool {
        return $this->pdo->prepare('DELETE FROM estimates WHERE id = ?')->execute([$id]);
    }

    public function recalcTotals(array $items, float $discPct, string $taxType, float $taxRate): array {
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += (float)($item['quantity'] ?? 1) * (float)($item['unit_price'] ?? 0);
        }
        $discAmt  = round($subtotal * $discPct / 100, 2);
        $taxable  = round($subtotal - $discAmt, 2);
        $cgst = $sgst = $igst = 0.0;
        if ($taxType === 'cgst_sgst') {
            $cgst = $sgst = round($taxable * ($taxRate / 2) / 100, 2);
        } elseif ($taxType === 'igst') {
            $igst = round($taxable * $taxRate / 100, 2);
        }
        return [
            'subtotal'       => round($subtotal, 2),
            'discount_pct'   => $discPct,
            'discount_amt'   => $discAmt,
            'taxable_amount' => $taxable,
            'tax_type'       => $taxType,
            'tax_rate'       => $taxRate,
            'cgst_amount'    => $cgst,
            'sgst_amount'    => $sgst,
            'igst_amount'    => $igst,
            'grand_total'    => round($taxable + $cgst + $sgst + $igst, 2),
        ];
    }

    private function insertItems(int $estimateId, array $items): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO estimate_items (estimate_id, sl_no, description, hsn_sac, quantity, unit, unit_price, amount)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        foreach ($items as $i => $item) {
            $qty   = (float)($item['quantity']   ?? 1);
            $price = (float)($item['unit_price'] ?? 0);
            $stmt->execute([
                $estimateId,
                $i + 1,
                trim($item['description']),
                trim($item['hsn_sac']  ?? ''),
                $qty,
                trim($item['unit']     ?? 'Nos') ?: 'Nos',
                $price,
                round($qty * $price, 2),
            ]);
        }
    }

    private function nextEstimateNumber(): string {
        $last = $this->pdo->query(
            "SELECT estimate_number FROM estimates ORDER BY id DESC LIMIT 1"
        )->fetchColumn();
        $num = $last ? ((int) substr($last, 8)) + 1 : 1;
        return 'FAB-EST-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
