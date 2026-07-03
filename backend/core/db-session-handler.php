<?php
declare(strict_types=1);

function db_session_open(string $path, string $name): bool
{
    return true;
}

function db_session_close(): bool
{
    return true;
}

function db_session_read(string $id): string
{
    try {
        $row = db_fetch('SELECT data FROM php_sessions WHERE id = ? AND expires_at > ?', [$id, time()]);
        return ($row !== null) ? (string) $row['data'] : '';
    } catch (Throwable $e) {
        return '';
    }
}

function db_session_write(string $id, string $data): bool
{
    try {
        $lifetime = (int) ini_get('session.gc_maxlifetime');
        if ($lifetime < 300) {
            $lifetime = max(7200, (int) env('SESSION_LIFETIME', 120) * 60);
        }
        $expires = time() + $lifetime;
        $pdo     = db_get_pdo();
        $stmt    = $pdo->prepare(
            'INSERT INTO php_sessions (id, data, expires_at) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE data = VALUES(data), expires_at = VALUES(expires_at)'
        );
        $stmt->execute([$id, $data, $expires]);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function db_session_destroy(string $id): bool
{
    try {
        db_get_pdo()->prepare('DELETE FROM php_sessions WHERE id = ?')->execute([$id]);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function db_session_gc(int $maxlifetime): int
{
    try {
        $stmt = db_get_pdo()->prepare('DELETE FROM php_sessions WHERE expires_at < ?');
        $stmt->execute([time()]);
        return $stmt->rowCount();
    } catch (Throwable $e) {
        return 0;
    }
}

function db_session_register(): void
{
    session_set_save_handler(
        'db_session_open',
        'db_session_close',
        'db_session_read',
        'db_session_write',
        'db_session_destroy',
        'db_session_gc'
    );
    register_shutdown_function('session_write_close');
}
