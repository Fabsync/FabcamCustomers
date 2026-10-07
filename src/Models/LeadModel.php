<?php

class LeadModel extends BaseModel {

    public const STATUSES = [
        'new'            => 'New',
        'contacted'      => 'Contacted',
        'follow_up'      => 'Follow-up',
        'demo'           => 'Demo',
        'quoted'         => 'Quoted',
        'negotiation'    => 'Negotiation',
        'won'            => 'Won',
        'lost'           => 'Lost',
        'not_interested' => 'Not Interested',
    ];

    /** Statuses that end the lead — these are highlighted and stamp closed_at. */
    public const CLOSED_STATUSES = ['won', 'lost', 'not_interested'];

    public const INTEREST_LEVELS = [
        'hot'  => 'Hot',
        'warm' => 'Warm',
        'cold' => 'Cold',
    ];

    public const SOURCES = [
        'phone'             => 'Phone Call',
        'email'             => 'Email',
        'website'           => 'Website',
        'referral'          => 'Referral',
        'walk_in'           => 'Walk-in',
        'exhibition'        => 'Exhibition / Event',
        'existing_customer' => 'Existing Customer',
        'other'             => 'Other',
    ];

    public const ACTIVITY_TYPES = [
        'call'    => 'Phone Call',
        'meeting' => 'Meeting',
        'email'   => 'Email',
        'demo'    => 'Demo',
        'visit'   => 'Site Visit',
        'note'    => 'Note',
    ];

    private function buildWhere(array $filters): array {
        $where  = [];
        $params = [];

        if (($filters['status'] ?? '') === 'open') {
            $where[] = "l.status NOT IN ('won','lost','not_interested')";
        } elseif (($filters['status'] ?? '') === 'due') {
            $where[] = "l.status NOT IN ('won','lost','not_interested') AND l.next_follow_up <= CURDATE()";
        } elseif (!empty($filters['status']) && isset(self::STATUSES[$filters['status']])) {
            $where[]  = 'l.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['interest']) && isset(self::INTEREST_LEVELS[$filters['interest']])) {
            $where[]  = 'l.interest_level = ?';
            $params[] = $filters['interest'];
        }
        if (!empty($filters['customer_id'])) {
            $where[]  = 'l.customer_id = ?';
            $params[] = (int) $filters['customer_id'];
        }
        if (!empty($filters['q'])) {
            $like     = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $where[]  = '(l.company_name LIKE ? OR l.contact_person LIKE ? OR l.lead_number LIKE ? OR l.mobile LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }

    public function getCount(array $filters = []): int {
        [$whereSql, $params] = $this->buildWhere($filters);
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM leads l $whereSql");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getPaginated(array $filters, int $offset, int $limit): array {
        [$whereSql, $params] = $this->buildWhere($filters);
        // Open leads first (soonest follow-up on top), closed leads after, newest first
        $stmt = $this->pdo->prepare(
            "SELECT l.*, p.product_name, u.name AS assigned_to_name
             FROM leads l
             LEFT JOIN products p ON p.id = l.product_id
             LEFT JOIN users u    ON u.id = l.assigned_to
             $whereSql
             ORDER BY (l.status IN ('won','lost','not_interested')) ASC,
                      l.next_follow_up IS NULL, l.next_follow_up ASC,
                      l.updated_at DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Counts per status bucket for the summary cards. */
    public function getStatusSummary(): array {
        $row = $this->pdo->query(
            "SELECT
                SUM(status NOT IN ('won','lost','not_interested'))                                    AS open_count,
                SUM(status = 'won')                                                                    AS won_count,
                SUM(status = 'lost')                                                                   AS lost_count,
                SUM(status = 'not_interested')                                                         AS not_interested_count,
                SUM(status NOT IN ('won','lost','not_interested') AND next_follow_up <= CURDATE())     AS due_count,
                COALESCE(SUM(CASE WHEN status = 'won' THEN expected_value END), 0)                     AS won_value
             FROM leads"
        )->fetch();
        return array_map(fn($v) => $v ?? 0, $row ?: []);
    }

    public function findById(int $id): array|false {
        $stmt = $this->pdo->prepare(
            'SELECT l.*, p.product_name, c.customer_id AS cust_code,
                    ua.name AS assigned_to_name, uc.name AS created_by_name
             FROM leads l
             LEFT JOIN products  p  ON p.id  = l.product_id
             LEFT JOIN customers c  ON c.id  = l.customer_id
             LEFT JOIN users     ua ON ua.id = l.assigned_to
             LEFT JOIN users     uc ON uc.id = l.created_by
             WHERE l.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByCustomer(int $customerId): array {
        $stmt = $this->pdo->prepare(
            'SELECT l.*, p.product_name
             FROM leads l
             LEFT JOIN products p ON p.id = l.product_id
             WHERE l.customer_id = ?
             ORDER BY l.created_at DESC'
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public function insert(array $d, int $userId): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO leads (lead_number, customer_id, company_name, contact_person, mobile, email,
                                product_id, requirement, source, status, interest_level, expected_value,
                                next_follow_up, close_reason, closed_at, assigned_to, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $this->nextLeadNumber(),
            $d['customer_id'] ?: null,
            $d['company_name'],
            $d['contact_person'],
            $d['mobile'],
            $d['email'],
            $d['product_id'] ?: null,
            $d['requirement'],
            $d['source'],
            $d['status'],
            $d['interest_level'],
            $d['expected_value'],
            $d['next_follow_up'] ?: null,
            $d['close_reason'] ?: null,
            in_array($d['status'], self::CLOSED_STATUSES, true) ? date('Y-m-d H:i:s') : null,
            $d['assigned_to'] ?: null,
            $userId,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $d, array $existing): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE leads SET customer_id=?, company_name=?, contact_person=?, mobile=?, email=?,
                              product_id=?, requirement=?, source=?, status=?, interest_level=?,
                              expected_value=?, next_follow_up=?, close_reason=?, closed_at=?, assigned_to=?
             WHERE id=?'
        );
        return $stmt->execute([
            $d['customer_id'] ?: null,
            $d['company_name'],
            $d['contact_person'],
            $d['mobile'],
            $d['email'],
            $d['product_id'] ?: null,
            $d['requirement'],
            $d['source'],
            $d['status'],
            $d['interest_level'],
            $d['expected_value'],
            $d['next_follow_up'] ?: null,
            $d['close_reason'] ?: null,
            $this->closedAt($d['status'], $existing),
            $d['assigned_to'] ?: null,
            $id,
        ]);
    }

    public function delete(int $id): bool {
        return $this->pdo->prepare('DELETE FROM leads WHERE id = ?')->execute([$id]);
    }

    // ---- Discussion log ---------------------------------------------------

    public function getActivities(int $leadId): array {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, u.name AS created_by_name
             FROM lead_activities a
             LEFT JOIN users u ON u.id = a.created_by
             WHERE a.lead_id = ?
             ORDER BY a.activity_date DESC, a.id DESC'
        );
        $stmt->execute([$leadId]);
        return $stmt->fetchAll();
    }

    /** Logs a discussion and moves the lead to its new status / interest / follow-up in one transaction. */
    public function addActivity(array $lead, array $a, int $userId): void {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare(
                'INSERT INTO lead_activities (lead_id, activity_date, activity_type, notes, status_before,
                                              status_after, interest_level, next_follow_up, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                (int) $lead['id'],
                $a['activity_date'],
                $a['activity_type'],
                $a['notes'],
                $lead['status'],
                $a['status'],
                $a['interest_level'],
                $a['next_follow_up'] ?: null,
                $userId,
            ]);

            $isClosed = in_array($a['status'], self::CLOSED_STATUSES, true);
            $this->pdo->prepare(
                'UPDATE leads SET status=?, interest_level=?, next_follow_up=?, close_reason=?, closed_at=? WHERE id=?'
            )->execute([
                $a['status'],
                $a['interest_level'],
                $isClosed ? null : ($a['next_follow_up'] ?: null),
                $isClosed ? ($a['close_reason'] ?: $lead['close_reason']) : null,
                $this->closedAt($a['status'], $lead),
                (int) $lead['id'],
            ]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function deleteActivity(int $leadId, int $activityId): bool {
        return $this->pdo->prepare('DELETE FROM lead_activities WHERE id = ? AND lead_id = ?')
                         ->execute([$activityId, $leadId]);
    }

    /** Keeps the original close time when a closed lead stays closed; clears it if reopened. */
    private function closedAt(string $newStatus, array $existing): ?string {
        if (!in_array($newStatus, self::CLOSED_STATUSES, true)) return null;
        if (in_array($existing['status'] ?? '', self::CLOSED_STATUSES, true) && !empty($existing['closed_at'])) {
            return $existing['closed_at'];
        }
        return date('Y-m-d H:i:s');
    }

    private function nextLeadNumber(): string {
        $last = $this->pdo->query('SELECT lead_number FROM leads ORDER BY id DESC LIMIT 1')->fetchColumn();
        $num  = $last ? ((int) substr($last, 5)) + 1 : 1;
        return 'LEAD-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
