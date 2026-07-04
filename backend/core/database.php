<?php

declare(strict_types=1);

/**
 * @return PDO
 */
function db_get_pdo()
{
    if (!empty($GLOBALS['_cz_pdo']) && $GLOBALS['_cz_pdo'] instanceof PDO) {
        return $GLOBALS['_cz_pdo'];
    }

    if (!empty($GLOBALS['cz_container']) && $GLOBALS['cz_container'] instanceof \CreatorzHive\Core\Container) {
        /** @var \CreatorzHive\Core\Database\Connection $connection */
        $connection = $GLOBALS['cz_container']->get(\CreatorzHive\Core\Database\Connection::class);
        $GLOBALS['_cz_pdo'] = $connection->pdo();

        return $GLOBALS['_cz_pdo'];
    }

    $host = (string) env('DB_HOST', '127.0.0.1');
    $port = (string) env('DB_PORT', '3306');
    $database = (string) env('DB_DATABASE', 'creatorz_hive');
    $username = (string) env('DB_USERNAME', 'root');
    $password = (string) env('DB_PASSWORD', '');

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

    $GLOBALS['_cz_pdo'] = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00'",
    ]);

    return $GLOBALS['_cz_pdo'];
}

function db_quote_column(string $column): string
{
    return '`' . str_replace('`', '``', $column) . '`';
}

function db_quote_table(string $table): string
{
    $parts = explode('.', $table);
    $out = [];
    foreach ($parts as $p) {
        $out[] = '`' . str_replace('`', '``', $p) . '`';
    }

    return implode('.', $out);
}

/**
 * @return array{limit: int, offset: int}
 */
function db_pagination(int $limit, int $offset, int $maxLimit = 500): array
{
    return [
        'limit' => max(1, min($maxLimit, $limit)),
        'offset' => max(0, $offset),
    ];
}

/**
 * @param array<string, mixed> $params
 *
 * @return array<string, mixed>
 */
function db_bind_limit(array $params, int $limit, int $maxLimit = 500): array
{
    $params['limit'] = db_pagination($limit, 0, $maxLimit)['limit'];

    return $params;
}

/**
 * @param array<string, mixed> $params
 *
 * @return array<string, mixed>
 */
function db_bind_limit_offset(array $params, int $limit, int $offset, int $maxLimit = 500): array
{
    $page = db_pagination($limit, $offset, $maxLimit);
    $params['limit'] = $page['limit'];
    $params['offset'] = $page['offset'];

    return $params;
}

/**
 * Placeholders for integer IN (...) lists (all values bound).
 *
 * @param list<int> $ids
 *
 * @return array{sql: string, params: array<string, int>}
 */
function db_in_int_placeholders(array $ids, string $prefix = 'in'): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
    if ($ids === []) {
        return ['sql' => 'NULL', 'params' => []];
    }

    $placeholders = [];
    $params = [];
    foreach ($ids as $i => $id) {
        $key = $prefix . '_' . $i;
        $placeholders[] = ':' . $key;
        $params[$key] = $id;
    }

    return ['sql' => implode(', ', $placeholders), 'params' => $params];
}

function db_query(string $sql, array $params = []): PDOStatement
{
    $conn = app_connection();
    if ($conn !== null) {
        return $conn->query($sql, $params);
    }

    $pdo = db_get_pdo();
    $stmt = $pdo->prepare($sql);
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare SQL statement.');
    }
    $stmt->execute($params);

    return $stmt;
}

/** ASC/DESC only — for ORDER BY direction (identifiers cannot be bound). */
function db_sql_sort_direction(string $dir): string
{
    return strtoupper(trim($dir)) === 'ASC' ? 'ASC' : 'DESC';
}

function db_fetch(string $sql, array $params = []): ?array
{
    $conn = app_connection();
    if ($conn !== null) {
        return $conn->fetchOne($sql, $params);
    }

    $row = db_query($sql, $params)->fetch();

    return $row === false ? null : $row;
}

function db_fetch_all(string $sql, array $params = []): array
{
    $conn = app_connection();
    if ($conn !== null) {
        return $conn->fetchAll($sql, $params);
    }

    return db_query($sql, $params)->fetchAll();
}

/** @deprecated use db_fetch_all */
function db_fetchAll(string $sql, array $params = []): array
{
    return db_fetch_all($sql, $params);
}

function db_insert(string $table, array $data): string
{
    $conn = app_connection();
    if ($conn !== null) {
        return $conn->insert($table, $data);
    }

    $columns = array_keys($data);
    $quotedCols = array_map('db_quote_column', $columns);
    $placeholders = array_map(static function (string $col): string {
        return ':' . $col;
    }, $columns);

    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        db_quote_table($table),
        implode(', ', $quotedCols),
        implode(', ', $placeholders)
    );

    db_query($sql, $data);

    return db_last_insert_id();
}

function db_update(string $table, array $data, string $where, array $params = []): int
{
    $conn = app_connection();
    if ($conn !== null) {
        return $conn->update($table, $data, $where, $params);
    }

    $set = [];
    foreach ($data as $column => $value) {
        $set[] = db_quote_column($column) . ' = :set_' . $column;
        $params['set_' . $column] = $value;
    }

    $sql = sprintf('UPDATE %s SET %s WHERE %s', db_quote_table($table), implode(', ', $set), $where);

    return db_query($sql, $params)->rowCount();
}

function db_delete(string $table, string $where, array $params = []): int
{
    $conn = app_connection();
    if ($conn !== null) {
        return $conn->delete($table, $where, $params);
    }

    $sql = sprintf('DELETE FROM %s WHERE %s', db_quote_table($table), $where);

    return db_query($sql, $params)->rowCount();
}

function db_begin_transaction(): bool
{
    return db_get_pdo()->beginTransaction();
}

function db_commit(): bool
{
    return db_get_pdo()->commit();
}

function db_rollback(): bool
{
    return db_get_pdo()->rollBack();
}

function db_last_insert_id(): string
{
    return db_get_pdo()->lastInsertId();
}

/** @deprecated use db_get_pdo — kept for scripts/migrate.php */
function db_connection(): PDO
{
    return db_get_pdo();
}
