<?php

declare(strict_types=1);

namespace CreatorzHive\Services;

use CreatorzHive\Core\Database\Connection;

final class AuthService
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function generateVerificationToken(int $userId)
    {
        $token = generateToken(64);
            $this->db->insert('email_verifications', [
                'user_id' => $userId,
                'token' => $token,
                'expires_at' => date('Y-m-d H:i:s', time() + 86400),
            ]);
        
            return $token;
    }

    public function generatePasswordResetToken(int $userId)
    {
        $token = generateToken(64);
            $this->db->insert('password_resets', [
                'user_id' => $userId,
                'token' => $token,
                'expires_at' => date('Y-m-d H:i:s', time() + 3600),
            ]);
        
            return $token;
    }

    public function generatePasswordResetOtp(int $userId)
    {
        $otp = (string) random_int(100000, 999999);
            $token = $otp . ':' . generateToken(24);
        
            // Invalidate old reset codes for this account before issuing a new one.
            $this->db->query(
                'UPDATE password_resets SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL',
                ['user_id' => $userId]
            );
        
            $this->db->insert('password_resets', [
                'user_id' => $userId,
                'token' => $token,
                'expires_at' => date('Y-m-d H:i:s', time() + 600),
            ]);
        
            return $otp;
    }

    public function validateResetToken(string $token)
    {
        return $this->db->fetchOne(
                'SELECT * FROM password_resets WHERE token = :token AND used_at IS NULL AND expires_at > NOW() LIMIT 1',
                ['token' => $token]
            );
    }

    public function validateResetOtp(int $userId, string $otp)
    {
        return $this->db->fetchOne(
                'SELECT * FROM password_resets WHERE user_id = :user_id AND token LIKE :token_prefix AND used_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 1',
                [
                    'user_id' => $userId,
                    'token_prefix' => $otp . ':%',
                ]
            );
    }

    public function validateVerificationToken(string $token)
    {
        return $this->db->fetchOne(
                'SELECT * FROM email_verifications WHERE token = :token AND used_at IS NULL AND expires_at > NOW() LIMIT 1',
                ['token' => $token]
            );
    }

    public function hashPassword(string $password)
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public function checkPassword(string $plain, string $hash)
    {
        return password_verify($plain, $hash);
    }

    public function dummyPasswordCheck(string $plain)
    {
        // Keep timing close to a real password verification when user is missing.
            static $dummyHash = '$2y$12$GvB8jv3qvteYe6xYfEec8eQf7b2E0QxF4J2y2rWwF4vQ6q0Tl4QnG';
            password_verify($plain, $dummyHash);
    }

    public function hasRecentPasswordResetRequest(int $userId): bool
    {
        $row = $this->db->fetchOne(
            'SELECT id FROM password_resets WHERE user_id = :uid AND used_at IS NULL AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND) LIMIT 1',
            ['uid' => $userId]
        );

        return $row !== null;
    }

    public function markPasswordResetUsed(int $id): void
    {
        $this->db->update('password_resets', ['used_at' => now()], 'id = :id', ['id' => $id]);
    }

    public function markEmailVerificationUsed(int $id): void
    {
        $this->db->update('email_verifications', ['used_at' => now()], 'id = :id', ['id' => $id]);
    }
}

