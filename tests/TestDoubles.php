<?php
declare(strict_types=1);

namespace App\Core;

// Lightweight Config test double
if (!class_exists('\App\Core\Config', false)) {
    class Config
    {
        private static array $values = [];

        public static function set(string $key, $value): void
        {
            self::$values[$key] = $value;
        }

        public static function get(string $key, $default = null)
        {
            if (array_key_exists($key, self::$values)) {
                return self::$values[$key];
            }
            $envKey = strtoupper(str_replace('.', '_', $key));
            return $_ENV[$envKey] ?? $_SERVER[$envKey] ?? getenv($envKey) ?: $default;
        }
    }
}

// Lightweight Session test double
if (!class_exists('\App\Core\Session', false)) {
    class Session
    {
        private static array $data = [];

        public static function start(): void {}

        public static function set(string $key, $value): void
        {
            self::$data[$key] = $value;
        }

        public static function get(string $key, $default = null)
        {
            return self::$data[$key] ?? $default;
        }

        public static function has(string $key): bool
        {
            return array_key_exists($key, self::$data);
        }

        public static function remove(string $key): void
        {
            unset(self::$data[$key]);
        }

        public static function flash(string $key, $value): void
        {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            if (!isset($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
                $_SESSION['_flash'] = ['new' => [], 'old' => []];
            }
            $_SESSION['_flash']['new'][$key] = $value;
        }

        public static function clear(): void
        {
            self::reset();
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $_SESSION = [];
        }

        public static function hasFlash(string $key): bool
        {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            return isset($_SESSION['_flash']['old'][$key]);
        }

        public static function getFlash(string $key, $default = null)
        {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            return $_SESSION['_flash']['old'][$key] ?? $default;
        }

        public static function flashInput(array $input): void
        {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            if (!isset($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
                $_SESSION['_flash'] = ['new' => [], 'old' => []];
            }
            $_SESSION['_flash']['new']['_old_input'] = $input;
        }

        public static function old(): array
        {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            return $_SESSION['_flash']['old']['_old_input'] ?? [];
        }

        public static function generateCsrfToken(): string
        {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $token = bin2hex(random_bytes(16));
            $_SESSION['_csrf_token'] = $token;
            return $token;
        }

        public static function getCsrfToken(): ?string
        {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            return $_SESSION['_csrf_token'] ?? null;
        }

        public static function regenerateWithContext(string $context, ?int $id = null): void {}

        public static function reset(): void
        {
            self::$data = [];
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $_SESSION = [];
        }
    }
}

// Lightweight Cache test double
if (!class_exists('\App\Core\Cache', false)) {
    class Cache
    {
        private static array $store = [];

        public static function get(string $key, $default = null)
        {
            $v = self::$store[$key] ?? null;
            if ($v === null) return $default;
            if (isset($v['expires']) && $v['expires'] < time()) {
                unset(self::$store[$key]);
                return $default;
            }
            return $v['value'];
        }

        public static function set(string $key, $value, int $ttl = 0): bool
        {
            $expires = $ttl > 0 ? time() + $ttl : null;
            self::$store[$key] = ['value' => $value, 'expires' => $expires];
            return true;
        }

        public static function forget(string $key): bool
        {
            if (isset(self::$store[$key])) {
                unset(self::$store[$key]);
            }
            return true;
        }

        public static function reset(): void
        {
            self::$store = [];
        }

        public static function setCacheDirectory(string $dir): void
        {
            // no-op for tests
        }

        public static function flush(): void
        {
            self::reset();
        }

        public static function has(string $key): bool
        {
            $v = self::$store[$key] ?? null;
            if ($v === null) return false;
            if (isset($v['expires']) && $v['expires'] < time()) {
                unset(self::$store[$key]);
                return false;
            }
            return true;
        }
    }
}

// Lightweight Logger test double
if (!class_exists('\App\Core\Logger', false)) {
    class Logger
    {
        public function info(string $msg, array $context = []) {}
        public function security(string $msg, array $context = []) {}
        public function error(string $msg, array $context = []) {}
        public function warning(string $msg, array $context = []) {}
    }
}

// Lightweight Database test double (PDO-backed in-memory DB for tests)
if (!class_exists('\App\Core\Database', false)) {
    class Database
    {
        private static ?self $instance = null;
        private ?\PDO $pdo = null;

        public static function getInstance(): self
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct()
        {
            $dbFile = dirname(__DIR__) . '/storage/test_sqlite.db';
            $this->pdo = new \PDO('sqlite:' . $dbFile);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            // create minimal users table expected by tests
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
            $dbFile = dirname(__DIR__) . '/storage/test_sqlite.db';
            $this->pdo = new \PDO('sqlite:' . $dbFile);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            // recreate minimal users table
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
            // ensure table is empty to avoid cross-test contamination
            try {
                $this->pdo->exec('DELETE FROM users');
            } catch (\Throwable $_) {
                // ignore
            }
        }
    }
}

// Helper to reset test doubles between tests
if (!function_exists('\\tests_reset_doubles')) {
    function tests_reset_doubles(): void
    {
        if (class_exists('\\App\\Core\\Session')) {
            \App\Core\Session::reset();
        }
        if (class_exists('\\App\\Core\\Cache')) {
            \App\Core\Cache::reset();
        }
        if (class_exists('\\App\\Core\\Database')) {
            try {
                $db = \App\Core\Database::getInstance();
                if (method_exists($db, 'reset')) {
                    $db->reset();
                }
            } catch (\Throwable $e) {
                // ignore reset errors in test bootstrap
            }
        }
    }
}
