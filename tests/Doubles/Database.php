<?php
declare(strict_types=1);

namespace Tests\Doubles;

use PDO;

class Database
{
    private static ?self $instance = null;
    private ?PDO $pdo = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $dbFile = dirname(__DIR__, 2) . '/storage/simplebiz_test.sqlite';
        $this->pdo = new PDO('sqlite:' . $dbFile);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY,
                username TEXT,
                email TEXT,
                password TEXT,
                status TEXT,
                deleted_at TEXT,
                remember_token TEXT,
                last_login_at TEXT,
                last_login_ip TEXT
            )"
        );
    }

    public function seedUsers(array $users): void
    {
        foreach ($users as $u) {
            $cols = array_keys($u);
            $placeholders = array_map(fn($c) => ':' . $c, $cols);
            $sql = 'INSERT OR REPLACE INTO users (' . implode(',', $cols) . ') VALUES (' . implode(',', $placeholders) . ')';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($u);
        }
    }

    public function getDriverName(): string
    {
        return 'sqlite';
    }

    public function fetch(string $sql, array $params = [])
    {
        $sql = str_ireplace('NOW()', "datetime('now')", $sql);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? false : $row;
    }

    public function fetchAll(string $sql, array $params = [])
    {
        $sql = str_ireplace('NOW()', "datetime('now')", $sql);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function fetchColumn(string $sql, array $params = [])
    {
        $sql = str_ireplace('NOW()', "datetime('now')", $sql);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function query(string $sql, array $params = [])
    {
        $sql = str_ireplace('NOW()', "datetime('now')", $sql);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function execute(string $sql, array $params = []): int
    {
        $sql = str_ireplace('NOW()', "datetime('now')", $sql);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function lastInsertId(?string $name = null): string
    {
        return $this->pdo->lastInsertId($name);
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function execRaw(string $sql): int
    {
        $sql = str_ireplace('NOW()', "datetime('now')", $sql);
        return $this->pdo->exec($sql);
    }

    public function reset(): void
    {
        $dbFile = dirname(__DIR__, 2) . '/storage/simplebiz_test.sqlite';
        $this->pdo = new PDO('sqlite:' . $dbFile);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY,
                username TEXT,
                email TEXT,
                password TEXT,
                status TEXT,
                deleted_at TEXT,
                remember_token TEXT,
                last_login_at TEXT,
                last_login_ip TEXT
            )"
        );
        try {
            $this->pdo->exec('DELETE FROM users');
        } catch (\Throwable $_) {
        }
    }
}
