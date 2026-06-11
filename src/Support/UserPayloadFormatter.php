<?php

declare(strict_types=1);

namespace CreatorzHive\Support;

use function upload_url;

/**
 * Normalizes user rows for API / frontend session payloads.
 */
final class UserPayloadFormatter
{
    /**
     * @param array<string, mixed> $user
     *
     * @return array{id: int, name: string, username: string, email: string, role: string, avatar_url: string}
     */
    public function forApi(array $user): array
    {
        $av = (string) ($user['avatar_url'] ?? '');
        if ($av !== '' && preg_match('#^https?://#i', $av) !== 1) {
            $av = upload_url(ltrim($av, '/'));
        }

        return [
            'id' => (int) ($user['id'] ?? 0),
            'name' => (string) ($user['name'] ?? ''),
            'username' => (string) ($user['username'] ?? ''),
            'email' => (string) ($user['email'] ?? ''),
            'role' => (string) ($user['role'] ?? 'creator'),
            'avatar_url' => $av,
        ];
    }
}
