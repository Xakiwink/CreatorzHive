<?php

declare(strict_types=1);

namespace CreatorzHive\Repositories;

use CreatorzHive\Core\Database\Connection;

final class UserRepository
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function findById(int $id)
    {
        return $this->db->fetchOne('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function findByEmail(string $email)
    {
        return $this->db->fetchOne('SELECT * FROM users WHERE email = :email LIMIT 1', ['email' => $email]);
    }

    public function findByUsername(string $username)
    {
        return $this->db->fetchOne('SELECT * FROM users WHERE username = :username LIMIT 1', ['username' => $username]);
    }

    public function findByGoogleId(string $googleId)
    {
        if ($googleId === '') {
            return null;
        }

        return $this->db->fetchOne(
            'SELECT * FROM users WHERE google_id = :gid LIMIT 1',
            ['gid' => $googleId]
        );
    }

    public function linkGoogleId(int $userId, string $googleId): void
    {
        if ($googleId === '') {
            return;
        }

        $this->db->update('users', ['google_id' => $googleId], 'id = :id', ['id' => $userId]);
    }

    public function suggestAvailableUsername(string $email, string $name): string
    {
        $local = strtolower((string) explode('@', $email)[0]);
        $base = preg_replace('/[^a-z0-9._-]/', '', $local) ?? '';
        if ($base === '' || strlen($base) < 3) {
            $base = preg_replace('/[^a-z0-9]/', '', strtolower($name)) ?? '';
        }
        if ($base === '' || strlen($base) < 3) {
            $base = 'user';
        }

        $base = substr($base, 0, 90);
        $candidate = $base;
        $n = 0;
        while ($this->findByUsername($candidate) !== null) {
            $n++;
            $candidate = substr($base, 0, 85) . (string) $n;
        }

        return $candidate;
    }

    public function create(array $data)
    {
        $id = $this->db->insert('users', [
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'] ?? 'creator',
                'email_verified' => 0,
                'is_active' => 1,
            ]);
        
            return (int) $id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createOAuthUser(array $data): int
    {
        $row = [
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'] ?? 'creator',
            'email_verified' => (int) ($data['email_verified'] ?? 0),
            'is_active' => 1,
        ];

        if (isset($data['google_id']) && (string) $data['google_id'] !== '') {
            $row['google_id'] = (string) $data['google_id'];
        }
        if (array_key_exists('avatar_url', $data) && $data['avatar_url'] !== null && (string) $data['avatar_url'] !== '') {
            $row['avatar_url'] = (string) $data['avatar_url'];
        }

        return (int) $this->db->insert('users', $row);
    }

    public function update(int $id, array $data)
    {
        return $this->db->update('users', $data, 'id = :id', ['id' => $id]) > 0;
    }

    public function updateLastLogin(int $id)
    {
        $this->db->update('users', ['last_login_at' => now()], 'id = :id', ['id' => $id]);
    }

    public function verifyEmail(int $id)
    {
        $this->db->update('users', ['email_verified' => 1], 'id = :id', ['id' => $id]);
    }

    public function updatePassword(int $id, string $hashedPassword)
    {
        $this->db->update('users', ['password' => $hashedPassword], 'id = :id', ['id' => $id]);
    }

    public function isEmailTaken(string $email)
    {
        return $this->findByEmail($email) !== null;
    }

    public function isUsernameTaken(string $username)
    {
        return $this->findByUsername($username) !== null;
    }

    public function listAll(int $limit = 200, int $offset = 0)
    {
        $params = $this->db->bindLimitOffset([], $limit, $offset, 500);
        
            return $this->db->fetchAll(
                'SELECT id, name, username, email, role, email_verified, is_active, created_at, updated_at
                 FROM users
                 ORDER BY id DESC
                 LIMIT :limit OFFSET :offset',
                $params
            );
    }

    public function countAll()
    {
        $row = $this->db->fetchOne('SELECT COUNT(*) AS c FROM users');
        
            return (int) ($row['c'] ?? 0);
    }

}
