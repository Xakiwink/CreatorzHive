<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class User
{
    public static function findById(int $id): ?array
    {
        return DB::fetch('SELECT * FROM users WHERE id = :id AND is_active = 1 LIMIT 1', ['id' => $id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return DB::fetch('SELECT * FROM users WHERE email = :e AND is_active = 1 LIMIT 1', ['e' => $email]);
    }

    public static function create(string $name, string $email, string $password, string $role = 'creator'): int
    {
        $username = strtolower(preg_replace('/[^a-z0-9]/i', '', $name))
            . '_' . substr(bin2hex(random_bytes(3)), 0, 6);

        return DB::insert('users', [
            'name'      => $name,
            'username'  => $username,
            'email'     => $email,
            'password'  => password_hash($password, PASSWORD_BCRYPT),
            'role'      => $role,
            'is_active' => 1,
        ]);
    }

    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function safe(array $user): array
    {
        unset($user['password']);
        return $user;
    }
}
