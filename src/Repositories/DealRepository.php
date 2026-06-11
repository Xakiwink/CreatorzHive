<?php

declare(strict_types=1);

namespace CreatorzHive\Repositories;

use CreatorzHive\Core\Database\Connection;

final class DealRepository
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function statusesList()
    {
        return ['lead', 'negotiation', 'contract', 'active', 'completed', 'cancelled'];
    }

    public function typesList()
    {
        return ['sponsored_post', 'affiliate', 'ambassador', 'gifted', 'other'];
    }

    public function statuses()
    {
        return $this->statusesList();
    }

    public function types()
    {
        return $this->typesList();
    }

    public function getByUser(int $userId, array $filters = [])
    {
        $sql = 'SELECT * FROM deals WHERE user_id = :uid AND is_deleted = 0';
            $params = ['uid' => $userId];
            $status = isset($filters['status']) ? (string) $filters['status'] : '';
            if ($status !== '' && in_array($status, $this->statusesList(), true)) {
                $sql .= ' AND status = :st';
                $params['st'] = $status;
            }
            $dealType = isset($filters['deal_type']) ? (string) $filters['deal_type'] : '';
            if ($dealType !== '' && in_array($dealType, $this->typesList(), true)) {
                $sql .= ' AND deal_type = :dt';
                $params['dt'] = $dealType;
            }
            $search = isset($filters['search']) ? trim((string) $filters['search']) : '';
            if ($search !== '') {
                $sql .= ' AND (brand_name LIKE :q OR title LIKE :q2)';
                $params['q'] = '%' . $search . '%';
                $params['q2'] = '%' . $search . '%';
            }
            $sql .= ' ORDER BY updated_at DESC';
        
            return $this->db->fetchAll($sql, $params);
    }

    public function getKanban(int $userId)
    {
        $deals = $this->getByUser($userId);
            $out = [];
            foreach ($this->statusesList() as $st) {
                $out[$st] = [];
            }
            foreach ($deals as $d) {
                $st = (string) ($d['status'] ?? 'lead');
                if (!isset($out[$st])) {
                    $st = 'lead';
                }
                $out[$st][] = $this->normalizeDealRow($d);
            }
        
            return $out;
    }

    public function normalizeDealRow(array $row)
    {
        $row['id'] = (int) ($row['id'] ?? 0);
            $row['user_id'] = (int) ($row['user_id'] ?? 0);
            $row['amount'] = (string) $row['amount'];
            $row['is_deleted'] = (int) ($row['is_deleted'] ?? 0);
        
            return $row;
    }

    public function findById(int $id)
    {
        $row = $this->db->fetchOne('SELECT * FROM deals WHERE id = :id AND is_deleted = 0', ['id' => $id]);
        
            return $row === null ? null : $this->normalizeDealRow($row);
    }

    public function findForUser(int $id, int $userId)
    {
        $row = $this->db->fetchOne(
                'SELECT * FROM deals WHERE id = :id AND user_id = :uid AND is_deleted = 0',
                ['id' => $id, 'uid' => $userId]
            );
        
            return $row === null ? null : $this->normalizeDealRow($row);
    }

    public function prepareRow(array $data, bool $forInsert)
    {
        $status = (string) ($data['status'] ?? 'lead');
            if (!in_array($status, $this->statusesList(), true)) {
                $status = 'lead';
            }
            $dealType = (string) ($data['deal_type'] ?? 'sponsored_post');
            if (!in_array($dealType, $this->typesList(), true)) {
                $dealType = 'sponsored_post';
            }
            $currency = strtoupper(substr((string) ($data['currency'] ?? 'TZS'), 0, 3));
            if (strlen($currency) !== 3) {
                $currency = 'TZS';
            }
            $amountRaw = $data['amount'] ?? 0;
            $amountStr = is_numeric($amountRaw) ? (string) $amountRaw : '0';
        
            $row = [
                'user_id' => (int) ($data['user_id'] ?? 0),
                'brand_name' => (string) ($data['brand_name'] ?? ''),
                'brand_email' => isset($data['brand_email']) && $data['brand_email'] !== null && $data['brand_email'] !== ''
                    ? (string) $data['brand_email'] : null,
                'brand_logo_url' => isset($data['brand_logo_url']) && $data['brand_logo_url'] !== null && (string) $data['brand_logo_url'] !== ''
                    ? (string) $data['brand_logo_url'] : null,
                'title' => (string) ($data['title'] ?? ''),
                'description' => isset($data['description']) && $data['description'] !== null && (string) $data['description'] !== ''
                    ? (string) $data['description'] : null,
                'amount' => $amountStr,
                'currency' => $currency,
                'status' => $status,
                'deal_type' => $dealType,
                'deliverables' => isset($data['deliverables']) && $data['deliverables'] !== null && (string) $data['deliverables'] !== ''
                    ? (string) $data['deliverables'] : null,
                'deadline_at' => isset($data['deadline_at']) && $data['deadline_at'] !== null && (string) $data['deadline_at'] !== ''
                    ? (string) $data['deadline_at'] : null,
                'contracted_at' => isset($data['contracted_at']) && $data['contracted_at'] !== null && (string) $data['contracted_at'] !== ''
                    ? (string) $data['contracted_at'] : null,
                'completed_at' => isset($data['completed_at']) && $data['completed_at'] !== null && (string) $data['completed_at'] !== ''
                    ? (string) $data['completed_at'] : null,
                'notes' => isset($data['notes']) && $data['notes'] !== null && (string) $data['notes'] !== ''
                    ? (string) $data['notes'] : null,
            ];
            if (!$forInsert) {
                unset($row['user_id']);
            }
        
            return $row;
    }

    public function create(array $data)
    {
        $sql = 'INSERT INTO deals (
                    user_id, brand_name, brand_email, brand_logo_url, title, description,
                    amount, currency, status, deal_type, deliverables, deadline_at,
                    contracted_at, completed_at, notes
                ) VALUES (
                    :user_id, :brand_name, :brand_email, :brand_logo_url, :title, :description,
                    :amount, :currency, :status, :deal_type, :deliverables, :deadline_at,
                    :contracted_at, :completed_at, :notes
                )';
        
            $this->db->query($sql, $this->prepareRow($data, true));
        
            return (int) $this->db->pdo()->lastInsertId();
    }

    public function update(int $id, array $data)
    {
        $allowed = [
                'brand_name', 'brand_email', 'brand_logo_url', 'title', 'description',
                'amount', 'currency', 'status', 'deal_type', 'deliverables', 'deadline_at',
                'contracted_at', 'completed_at', 'notes',
            ];
            $sets = [];
            $params = ['id' => $id];
            foreach ($allowed as $col) {
                if (!array_key_exists($col, $data)) {
                    continue;
                }
                $sets[] = $this->db->quoteColumn($col) . ' = :' . $col;
                $params[$col] = $data[$col];
            }
            if ($sets === []) {
                return false;
            }
            $sql = 'UPDATE deals SET ' . implode(', ', $sets) . ' WHERE id = :id AND is_deleted = 0';
        
            $this->db->query($sql, $params);
        
            return true;
    }

    public function updateStatus(int $id, string $status, int $userId)
    {
        if (!in_array($status, $this->statusesList(), true)) {
                return false;
            }
            $extra = '';
            $params = ['id' => $id, 'uid' => $userId, 'st' => $status];
            if ($status === 'completed') {
                $extra = ', completed_at = COALESCE(completed_at, CURDATE())';
            }
            if ($status === 'contract') {
                $extra .= ', contracted_at = COALESCE(contracted_at, CURDATE())';
            }
            $this->db->query(
                'UPDATE deals SET status = :st' . $extra . ' WHERE id = :id AND user_id = :uid AND is_deleted = 0',
                $params
            );
        
            return true;
    }

    public function softDelete(int $id, int $userId)
    {
        $this->db->query(
                'UPDATE deals SET is_deleted = 1 WHERE id = :id AND user_id = :uid',
                ['id' => $id, 'uid' => $userId]
            );
        
            return true;
    }

    public function attachPost(int $dealId, int $postId)
    {
        $this->db->query(
                'INSERT IGNORE INTO deal_posts (deal_id, post_id) VALUES (:did, :pid)',
                ['did' => $dealId, 'pid' => $postId]
            );
    }

    public function getRevenueSummary(int $userId)
    {
        $currency = 'TZS';
            $row = $this->db->fetchOne(
                'SELECT
                        COUNT(*) AS total_deals,
                        SUM(CASE WHEN status = \'completed\' THEN 1 ELSE 0 END) AS completed_deals,
                        SUM(CASE WHEN status = \'active\' THEN 1 ELSE 0 END) AS active_deals,
                        COALESCE(SUM(CASE WHEN status = \'completed\' AND currency = :cur1 THEN amount ELSE 0 END), 0) AS earned_revenue,
                        COALESCE(SUM(CASE WHEN status = \'active\' AND currency = :cur2 THEN amount ELSE 0 END), 0) AS pipeline_revenue
                    FROM deals
                    WHERE user_id = :uid AND is_deleted = 0',
                ['uid' => $userId, 'cur1' => $currency, 'cur2' => $currency]
            );
        
            return [
                'earned_revenue' => (float) ($row['earned_revenue'] ?? 0),
                'pipeline_revenue' => (float) ($row['pipeline_revenue'] ?? 0),
                'total_deals' => (int) ($row['total_deals'] ?? 0),
                'completed_deals' => (int) ($row['completed_deals'] ?? 0),
                'active_deals' => (int) ($row['active_deals'] ?? 0),
                'currency' => $currency,
            ];
    }

    public function getRecentActivity(int $userId, int $limit = 10)
    {
        $limit = max(1, min(50, $limit));
        
            $params = $this->db->bindLimit(['uid' => $userId], $limit, 50);
        
            return $this->db->fetchAll(
                'SELECT id, action, entity_type, entity_id, old_values, new_values, created_at
                    FROM audit_logs
                    WHERE user_id = :uid
                    AND (entity_type = \'deal\' OR action LIKE \'deal.%\')
                    ORDER BY created_at DESC
                    LIMIT :limit',
                $params
            );
    }

    public function getActivityForDeal(int $dealId, int $userId, int $limit = 25)
    {
        $limit = max(1, min(50, $limit));
        
            $params = $this->db->bindLimit(['uid' => $userId, 'eid' => $dealId], $limit, 50);
        
            return $this->db->fetchAll(
                'SELECT id, action, entity_type, entity_id, old_values, new_values, created_at
                    FROM audit_logs
                    WHERE user_id = :uid AND entity_type = \'deal\' AND entity_id = :eid
                    ORDER BY created_at DESC
                    LIMIT :limit',
                $params
            );
    }

    public function getLinkedPosts(int $dealId, int $userId)
    {
        $sql = 'SELECT p.id, p.title, p.status, p.platforms, p.scheduled_at, p.published_at
                    FROM deal_posts dp
                    INNER JOIN posts p ON p.id = dp.post_id
                    WHERE dp.deal_id = :did AND p.user_id = :uid AND p.is_deleted = 0
                    ORDER BY p.updated_at DESC';
        
            return $this->db->fetchAll($sql, ['did' => $dealId, 'uid' => $userId]);
    }

}
