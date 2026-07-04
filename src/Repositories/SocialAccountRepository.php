<?php

declare(strict_types=1);

namespace CreatorzHive\Repositories;

use CreatorzHive\Core\Database\Connection;
use CreatorzHive\Core\Security\TokenCrypto;
use CreatorzHive\Helpers\PlatformHelper;

final class SocialAccountRepository
{
    /** @var Connection */
    private $db;

    /** @var TokenCrypto */
    private $crypto;

    public function __construct(Connection $db, TokenCrypto $crypto)
    {
        $this->db = $db;
        $this->crypto = $crypto;
    }

    public function accountDecryptRow(array $row)
    {
        if (array_key_exists('access_token', $row)) {
            $row['access_token'] = $this->crypto->decryptDb((string) ($row['access_token'] ?? ''));
        }
        if (array_key_exists('refresh_token', $row)) {
            $refresh = (string) ($row['refresh_token'] ?? '');
            $row['refresh_token'] = $refresh !== '' ? $this->crypto->decryptDb($refresh) : '';
        }

        return $row;
    }

    public function accountEncryptToken(string $plaintext)
    {
        return $this->crypto->encryptDb($plaintext);
    }

    public function accountFetch(int $userId, string $platform, bool $activeOnly = true)
    {
        $platform = PlatformHelper::normalize($platform) ?? '';
        if ($platform === '') {
            return null;
        }
        $sql = 'SELECT * FROM social_accounts WHERE user_id = :uid AND platform = :p';
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        $sql .= ' LIMIT 1';

        $row = $this->db->fetchOne($sql, ['uid' => $userId, 'p' => $platform]);

        return $row === null ? null : $this->accountDecryptRow($row);
    }

    public function accountFetchById(int $accountId, int $userId)
    {
        $row = $this->db->fetchOne(
            'SELECT * FROM social_accounts WHERE id = :id AND user_id = :uid LIMIT 1',
            ['id' => $accountId, 'uid' => $userId]
        );

        return $row === null ? null : $this->accountDecryptRow($row);
    }

    public function accountUpsert(int $userId, array $data)
    {
        $accessToken = $this->accountEncryptToken((string) ($data['access_token'] ?? ''));
        $refreshPlain = (string) ($data['refresh_token'] ?? '');
        $refreshToken = $refreshPlain !== '' ? $this->accountEncryptToken($refreshPlain) : '';

        $this->db->query(
            'INSERT INTO social_accounts (
                user_id, platform, platform_user_id, username, display_name,
                avatar_url, access_token, refresh_token, token_expires_at,
                follower_count, is_active, connected_at, updated_at
            ) VALUES (
                :uid, :platform, :platform_user_id, :username, :display_name,
                :avatar_url, :access_token, :refresh_token, :expires_at,
                :follower_count, 1, NOW(), NOW()
            )
            ON DUPLICATE KEY UPDATE
                platform_user_id = VALUES(platform_user_id),
                username = VALUES(username),
                display_name = VALUES(display_name),
                avatar_url = VALUES(avatar_url),
                access_token = VALUES(access_token),
                refresh_token = VALUES(refresh_token),
                token_expires_at = VALUES(token_expires_at),
                follower_count = VALUES(follower_count),
                is_active = 1,
                updated_at = NOW()',
            [
                'uid' => $userId,
                'platform' => (string) $data['platform'],
                'platform_user_id' => (string) $data['platform_user_id'],
                'username' => (string) $data['username'],
                'display_name' => (string) $data['display_name'],
                'avatar_url' => $data['avatar_url'] ?? null,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_at' => (string) $data['token_expires_at'],
                'follower_count' => (int) ($data['follower_count'] ?? 0),
            ]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listSummaryForUser(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT id, platform, username, display_name, follower_count, token_expires_at, is_active
             FROM social_accounts WHERE user_id = :uid ORDER BY platform ASC',
            ['uid' => $userId]
        );
    }

    public function findSummaryForUserPlatform(int $userId, string $platform): ?array
    {
        $row = $this->db->fetchOne(
            'SELECT id, platform, username, display_name, follower_count, token_expires_at, is_active
             FROM social_accounts WHERE user_id = :uid AND platform = :platform LIMIT 1',
            ['uid' => $userId, 'platform' => $platform]
        );

        return $row === null ? null : $row;
    }

    public function deactivate(int $userId, string $platform): void
    {
        $normalized = PlatformHelper::normalize($platform) ?? $platform;
        $this->db->query(
            'UPDATE social_accounts SET is_active = 0 WHERE user_id = :uid AND platform = :p',
            ['uid' => $userId, 'p' => $normalized]
        );
    }

    public function accountUpdateTokens(int $accountId, string $accessToken, string $refreshToken, string $expiresAt): void
    {
        $updates = ['access_token' => $this->accountEncryptToken($accessToken)];
        if ($refreshToken !== '') {
            $updates['refresh_token'] = $this->accountEncryptToken($refreshToken);
        }
        if ($expiresAt !== '') {
            $updates['token_expires_at'] = $expiresAt;
        }
        $this->db->update('social_accounts', $updates, 'id = :id', ['id' => $accountId]);
    }

    public function accountMigratePlaintextTokens()
    {
        $rows = $this->db->fetchAll('SELECT id, access_token, refresh_token FROM social_accounts');
        $encrypted = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id < 1) {
                continue;
            }

            $access = (string) ($row['access_token'] ?? '');
            $refresh = (string) ($row['refresh_token'] ?? '');
            $updates = [];

            if ($access !== '' && !$this->crypto->isEncryptedDb($access)) {
                $updates['access_token'] = $this->accountEncryptToken($access);
            }
            if ($refresh !== '' && !$this->crypto->isEncryptedDb($refresh)) {
                $updates['refresh_token'] = $this->accountEncryptToken($refresh);
            }

            if ($updates === []) {
                $skipped++;

                continue;
            }

            $this->db->update('social_accounts', $updates, 'id = :id', ['id' => $id]);
            $encrypted++;
        }

        return [
            'scanned' => count($rows),
            'encrypted' => $encrypted,
            'skipped' => $skipped,
        ];
    }
}
