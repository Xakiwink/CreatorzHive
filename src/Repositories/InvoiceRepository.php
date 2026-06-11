<?php

declare(strict_types=1);

namespace CreatorzHive\Repositories;

use CreatorzHive\Core\Database\Connection;

final class InvoiceRepository
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function statusesList()
    {
        return ['draft', 'sent', 'paid', 'overdue', 'cancelled'];
    }

    public function statuses()
    {
        return $this->statusesList();
    }

    public function getByUser(int $userId, array $filters = [])
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
            $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 20)));
            $offset = ($page - 1) * $perPage;
            $status = isset($filters['status']) ? (string) $filters['status'] : '';
        
            $where = 'user_id = :uid';
            $params = ['uid' => $userId];
            if ($status !== '' && in_array($status, $this->statusesList(), true)) {
                $where .= ' AND status = :st';
                $params['st'] = $status;
            }
        
            $countRow = $this->db->fetchOne(
                'SELECT COUNT(*) AS c FROM invoices WHERE ' . $where,
                $params
            );
            $total = (int) ($countRow['c'] ?? 0);
        
            $sql = 'SELECT * FROM invoices WHERE ' . $where . ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
            $params = $this->db->bindLimitOffset($params, $perPage, $offset, 100);
            $items = $this->db->fetchAll($sql, $params);
            foreach ($items as &$row) {
                $row = $this->normalizeRow($row);
            }
        
            return [
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
            ];
    }

    public function findById(int $id)
    {
        $row = $this->db->fetchOne('SELECT * FROM invoices WHERE id = :id', ['id' => $id]);
        
            return $row === null ? null : $this->normalizeRow($row);
    }

    public function findForUser(int $id, int $userId)
    {
        $row = $this->db->fetchOne(
                'SELECT * FROM invoices WHERE id = :id AND user_id = :uid',
                ['id' => $id, 'uid' => $userId]
            );
        
            return $row === null ? null : $this->normalizeRow($row);
    }

    public function create(array $data)
    {
        $lineItems = $data['line_items'] ?? [];
            if (is_string($lineItems)) {
                $decoded = json_decode($lineItems, true);
                $lineItems = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($lineItems) || $lineItems === []) {
                $lineItems = [
                    ['description' => 'Services', 'qty' => 1, 'unit_price' => (float) ($data['total'] ?? 0)],
                ];
            }
            $userId = (int) ($data['user_id'] ?? 0);
            $number = (string) ($data['invoice_number'] ?? $this->generateNumber($userId));
            $subtotal = (float) ($data['subtotal'] ?? 0);
            $taxRate = (float) ($data['tax_rate'] ?? 0);
            $taxAmount = (float) ($data['tax_amount'] ?? 0);
            $total = (float) ($data['total'] ?? 0);
            if ($total <= 0 && $subtotal > 0) {
                $total = round($subtotal + $taxAmount, 2);
            }
            if ($subtotal <= 0) {
                $subtotal = $total - $taxAmount;
                if ($subtotal < 0) {
                    $subtotal = $total;
                }
            }
            $currency = strtoupper(substr((string) ($data['currency'] ?? 'TZS'), 0, 3));
            if (strlen($currency) !== 3) {
                $currency = 'TZS';
            }
            $status = (string) ($data['status'] ?? 'draft');
            if (!in_array($status, $this->statusesList(), true)) {
                $status = 'draft';
            }
        
            $sql = 'INSERT INTO invoices (
                    user_id, deal_id, invoice_number, recipient_name, recipient_email,
                    line_items, subtotal, tax_rate, tax_amount, total, currency, status, due_date, notes
                ) VALUES (
                    :user_id, :deal_id, :invoice_number, :recipient_name, :recipient_email,
                    :line_items, :subtotal, :tax_rate, :tax_amount, :total, :currency, :status, :due_date, :notes
                )';
        
            $this->db->query($sql, [
                'user_id' => $userId,
                'deal_id' => isset($data['deal_id']) && $data['deal_id'] !== '' && $data['deal_id'] !== null
                    ? (int) $data['deal_id'] : null,
                'invoice_number' => $number,
                'recipient_name' => (string) ($data['recipient_name'] ?? ''),
                'recipient_email' => (string) ($data['recipient_email'] ?? ''),
                'line_items' => json_encode($lineItems) ?: '[]',
                'subtotal' => round($subtotal, 2),
                'tax_rate' => round($taxRate, 2),
                'tax_amount' => round($taxAmount, 2),
                'total' => round($total, 2),
                'currency' => $currency,
                'status' => $status,
                'due_date' => isset($data['due_date']) && $data['due_date'] !== null && (string) $data['due_date'] !== ''
                    ? (string) $data['due_date'] : null,
                'notes' => isset($data['notes']) && $data['notes'] !== null && (string) $data['notes'] !== ''
                    ? (string) $data['notes'] : null,
            ]);
        
            return (int) $this->db->pdo()->lastInsertId();
    }

    public function update(int $id, array $data)
    {
        $allowed = [
                'recipient_name', 'recipient_email', 'due_date', 'status', 'notes',
                'tax_rate', 'tax_amount', 'subtotal', 'total',
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
            if (array_key_exists('line_items', $data)) {
                $li = $data['line_items'];
                if (is_array($li)) {
                    $sets[] = 'line_items = :line_items';
                    $params['line_items'] = json_encode($li) ?: '[]';
                }
            }
            if ($sets === []) {
                return false;
            }
            $this->db->query('UPDATE invoices SET ' . implode(', ', $sets) . ' WHERE id = :id', $params);
        
            return true;
    }

    public function generateNumber(int $userId)
    {
        $year = date('Y');
            $row = $this->db->fetchOne(
                'SELECT COUNT(*) AS c FROM invoices WHERE user_id = :uid AND YEAR(created_at) = :y',
                ['uid' => $userId, 'y' => (int) $year]
            );
            $next = (int) ($row['c'] ?? 0) + 1;
        
            return sprintf('INV-%s-%04d', $year, $next);
    }

    public function markPaid(int $id)
    {
        $this->db->query(
                'UPDATE invoices SET status = \'paid\', paid_at = COALESCE(paid_at, NOW()) WHERE id = :id',
                ['id' => $id]
            );
        
            return true;
    }

    public function getByDeal(int $dealId)
    {
        $rows = $this->db->fetchAll(
                'SELECT * FROM invoices WHERE deal_id = :did ORDER BY created_at DESC',
                ['did' => $dealId]
            );
            foreach ($rows as &$r) {
                $r = $this->normalizeRow($r);
            }
        
            return $rows;
    }

    public function normalizeRow(array $row)
    {
        $row['id'] = (int) ($row['id'] ?? 0);
            $row['user_id'] = (int) ($row['user_id'] ?? 0);
            $row['deal_id'] = isset($row['deal_id']) && $row['deal_id'] !== null ? (int) $row['deal_id'] : null;
            $li = $row['line_items'] ?? '[]';
            if (is_string($li)) {
                $decoded = json_decode($li, true);
                $row['line_items'] = is_array($decoded) ? $decoded : [];
            }
            $row['subtotal'] = (string) $row['subtotal'];
            $row['tax_rate'] = (string) $row['tax_rate'];
            $row['tax_amount'] = (string) $row['tax_amount'];
            $row['total'] = (string) $row['total'];
        
            $status = (string) ($row['status'] ?? 'draft');
            if ($status !== 'paid' && $status !== 'cancelled') {
                $due = $row['due_date'] ?? null;
                if ($due && strtotime((string) $due) < strtotime('today')) {
                    $row['display_status'] = 'overdue';
                } else {
                    $row['display_status'] = $status;
                }
            } else {
                $row['display_status'] = $status;
            }
        
            return $row;
    }

    public function sumPaidTotal(int $userId)
    {
        $row = $this->db->fetchOne(
                'SELECT COALESCE(SUM(total), 0) AS s FROM invoices WHERE user_id = :uid AND status = \'paid\'',
                ['uid' => $userId]
            );
        
            return (float) ($row['s'] ?? 0);
    }

}
