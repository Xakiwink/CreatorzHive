<?php

declare(strict_types=1);

namespace CreatorzHive\Core\Database;

use CreatorzHive\Config\AppConfig;
use PDO;
use PDOStatement;
use RuntimeException;

/**
 * PDO access with prepared statements only (SRP: persistence).
 */
final class Connection
{
    private ?PDO $pdo = null;

    /** @var AppConfig */
    private $config;

    public function __construct(AppConfig $config)
    {
        $this->config = $config;
    }

    public function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $this->config->dbHost(),
            $this->config->dbPort(),
            $this->config->dbDatabase()
        );

        $this->pdo = new PDO($dsn, $this->config->dbUsername(), $this->config->dbPassword(), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $this->pdo;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo()->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare SQL statement.');
        }
        $stmt->execute($params);

        return $stmt;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>|null
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return list<array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(string $table, array $data): string
    {
        $columns = array_keys($data);
        $quotedCols = array_map([$this, 'quoteColumn'], $columns);
        $placeholders = array_map(static fn (string $col): string => ':' . $col, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quoteTable($table),
            implode(', ', $quotedCols),
            implode(', ', $placeholders)
        );

        $this->query($sql, $data);

        return $this->pdo()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $params
     */
    public function update(string $table, array $data, string $where, array $params = []): int
    {
        $set = [];
        foreach ($data as $column => $value) {
            $set[] = $this->quoteColumn($column) . ' = :set_' . $column;
            $params['set_' . $column] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $this->quoteTable($table),
            implode(', ', $set),
            $where
        );

        return $this->query($sql, $params)->rowCount();
    }

    /**
     * @param array<string, mixed> $params
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = sprintf('DELETE FROM %s WHERE %s', $this->quoteTable($table), $where);

        return $this->query($sql, $params)->rowCount();
    }

    public function beginTransaction(): bool
    {
        return $this->pdo()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo()->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo()->rollBack();
    }

    public function quoteColumn(string $column): string
    {
        return '`' . str_replace('`', '``', $column) . '`';
    }

    public function quoteTable(string $table): string
    {
        $parts = explode('.', $table);
        $out = [];
        foreach ($parts as $part) {
            $out[] = '`' . str_replace('`', '``', $part) . '`';
        }

        return implode('.', $out);
    }

    /**
     * @return array{limit: int, offset: int}
     */
    public function pagination(int $limit, int $offset, int $maxLimit = 500): array
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
    public function bindLimit(array $params, int $limit, int $maxLimit = 500): array
    {
        $params['limit'] = $this->pagination($limit, 0, $maxLimit)['limit'];

        return $params;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    public function bindLimitOffset(array $params, int $limit, int $offset, int $maxLimit = 500): array
    {
        $page = $this->pagination($limit, $offset, $maxLimit);
        $params['limit'] = $page['limit'];
        $params['offset'] = $page['offset'];

        return $params;
    }

    /**
     * @param list<int> $ids
     *
     * @return array{sql: string, params: array<string, int>}
     */
    public function inIntPlaceholders(array $ids, string $prefix = 'in'): array
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

    public function sortDirection(string $dir): string
    {
        return strtoupper(trim($dir)) === 'ASC' ? 'ASC' : 'DESC';
    }
}
