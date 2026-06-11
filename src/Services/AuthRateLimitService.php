<?php

declare(strict_types=1);

namespace CreatorzHive\Services;

use CreatorzHive\Core\Database\Connection;

final class AuthRateLimitService
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function row(string $key): ?array
    {
        return $this->db->fetchOne('SELECT * FROM rate_limits WHERE `key` = :k LIMIT 1', ['k' => $key]);
    }

    public function resetIfExpired(?array &$row, int $minutes): void
    {
        if ($row === null) {
            return;
        }

        $elapsed = (time() - strtotime((string) $row['last_refill'])) / 60;
        if ($elapsed > $minutes) {
            $this->db->update('rate_limits', ['tokens' => 0, 'last_refill' => now()], 'id = :id', ['id' => $row['id']]);
            $row['tokens'] = 0;
        }
    }

    public function reset(?array $row): void
    {
        if ($row === null) {
            return;
        }

        $this->db->update('rate_limits', ['tokens' => 0, 'last_refill' => now()], 'id = :id', ['id' => $row['id']]);
    }

    public function incrementLoginAttempts(string $key, ?array $rateRow): void
    {
        $this->increment($key, $rateRow);
    }

    public function incrementRegisterAttempts(string $key, ?array $rateRow): void
    {
        $this->increment($key, $rateRow);
    }

    public function incrementRateAttempts(string $key, ?array $rateRow): void
    {
        $this->increment($key, $rateRow);
    }

    private function increment(string $key, ?array $rateRow): void
    {
        if ($rateRow === null) {
            $this->db->insert('rate_limits', ['key' => $key, 'tokens' => 1, 'last_refill' => now()]);

            return;
        }

        $this->db->update(
            'rate_limits',
            ['tokens' => ((int) $rateRow['tokens']) + 1, 'last_refill' => now()],
            'id = :id',
            ['id' => $rateRow['id']]
        );
    }

    public function maybeSendLoginLockoutAlert(array $user, int $failedAttempts, string $ip): void
    {
        if ($failedAttempts < 5) {
            return;
        }

        $alertKey = 'login_alert:user:' . (int) ($user['id'] ?? 0);
        $alertRow = $this->row($alertKey);
        $this->resetIfExpired($alertRow, 30);
        if ((int) ($alertRow['tokens'] ?? 0) > 0) {
            return;
        }

        mailer_send_login_lockout_alert_email(
            (string) ($user['email'] ?? ''),
            (string) ($user['name'] ?? 'User'),
            $ip,
            request_user_agent()
        );

        if ($alertRow === null) {
            $this->db->insert('rate_limits', ['key' => $alertKey, 'tokens' => 1, 'last_refill' => now()]);
        } else {
            $this->db->update('rate_limits', ['tokens' => 1, 'last_refill' => now()], 'id = :id', ['id' => $alertRow['id']]);
        }
    }
}
