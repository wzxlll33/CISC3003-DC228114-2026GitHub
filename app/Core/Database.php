<?php

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    protected static ?self $instance = null;

    protected PDO $pdo;

    public function __construct(array $config)
    {
        $databasePath = $config['database'] ?? '';

        if ($databasePath === '') {
            throw new PDOException('SQLite database path is not configured.');
        }

        $directory = dirname($databasePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $this->pdo = new PDO('sqlite:' . $databasePath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->pdo->setAttribute(PDO::ATTR_TIMEOUT, (int) ($config['timeout'] ?? 5));

        $this->pdo->exec('PRAGMA journal_mode = WAL;');
        $this->pdo->exec('PRAGMA synchronous = NORMAL;');
        $this->pdo->exec('PRAGMA busy_timeout = ' . (int) (($config['busy_timeout_ms'] ?? 5000)) . ';');

        if (!empty($config['foreign_keys'])) {
            $this->pdo->exec('PRAGMA foreign_keys = ON;');
        }

        self::$instance = $this;
    }

    public static function instance(): ?self
    {
        return self::$instance;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    public function fetch(string $sql, array $params = []): array|false
    {
        return $this->query($sql, $params)->fetch();
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert(string $table, array $data): string
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->query($sql, $data);

        return $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $params = []): int
    {
        $assignments = [];
        $values = [];

        foreach ($data as $column => $value) {
            $key = 'set_' . $column;
            $assignments[] = $column . ' = :' . $key;
            $values[$key] = $value;
        }

        $statement = $this->query(
            sprintf('UPDATE %s SET %s WHERE %s', $table, implode(', ', $assignments), $where),
            array_merge($values, $params)
        );

        return $statement->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        $statement = $this->query(sprintf('DELETE FROM %s WHERE %s', $table, $where), $params);

        return $statement->rowCount();
    }
}
