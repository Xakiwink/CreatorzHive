<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

class DB
{
    private static ?PDO $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            Env::get('DB_HOST', '127.0.0.1'),
            Env::get('DB_PORT', '3306'),
            Env::get('DB_DATABASE')
        );

        self::$pdo = new PDO($dsn, Env::get('DB_USERNAME'), Env::get('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        return self::$pdo;
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql  = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(fn ($c) => "`$c`", $cols)),
            implode(', ', array_map(fn ($c) => ":$c", $cols))
        );
        self::run($sql, $data);
        return (int) self::connect()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $params = []): int
    {
        $set       = implode(', ', array_map(fn ($c) => "`$c` = :_$c", array_keys($data)));
        $setParams = [];
        foreach ($data as $k => $v) {
            $setParams["_$k"] = $v;
        }
        return self::run("UPDATE `$table` SET $set WHERE $where", array_merge($setParams, $params))->rowCount();
    }

    public static function scalar(string $sql, array $params = [])
    {
        return self::run($sql, $params)->fetchColumn();
    }
}
