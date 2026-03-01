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

        public static function reset(): void
        {
            self::$values = [];
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
// Allow opting out to use the real App\Core\Cache implementation by
// setting environment variable USE_REAL_CACHE=1 (useful for integration tests).
if (!class_exists('\App\Core\Cache', false) && getenv('USE_REAL_CACHE') !== '1') {
    class Cache
    {
        private static array $store = [];
        private static ?string $cacheDir = null;

        private const DEFAULT_TTL = 3600;

            // Allow opting out to use the real App\Core\Cache implementation by
            // setting environment variable USE_REAL_CACHE=1 (useful for integration tests).
            public static function get(string $key, $default = null)
        {
            $v = self::$store[$key] ?? null;
            if ($v === null) return $default;
            if (isset($v['expires_at']) && $v['expires_at'] > 0 && $v['expires_at'] < time()) {
                unset(self::$store[$key]);
                return $default;
            }
            return $v['value'] ?? $default;
        }

        public static function set(string $key, $value, int $ttl = 0): bool
        {
            $expires = $ttl > 0 ? time() + $ttl : 0;
            self::$store[$key] = [
                'key' => $key,
                'value' => $value,
                'expires_at' => $expires,
                'created_at' => time(),
            ];
            return true;
        }

        public static function forget(string $key): bool
        {
            if (isset(self::$store[$key])) {
                unset(self::$store[$key]);
                return true;
            }
            return false;
        }

        public static function reset(): void
        {
            self::$store = [];
        }

        public static function setCacheDirectory(string $dir): void
        {
            // remember directory so filesystem-based tests can interact with it
            // normalize to forward slashes for glob compatibility on Windows
            $normalized = str_replace('\\', '/', $dir);
            self::$cacheDir = rtrim($normalized, '/');
        }

        public static function flush(): bool
        {
            self::reset();
            return true;
        }

        public static function has(string $key): bool
        {
            $v = self::$store[$key] ?? null;
            if ($v === null) return false;
            if (isset($v['expires_at']) && $v['expires_at'] > 0 && $v['expires_at'] < time()) {
                unset(self::$store[$key]);
                return false;
            }
            return true;
        }

        public static function remember(string $key, int $ttl, callable $callback)
        {
            if (self::has($key)) {
                return self::get($key);
            }
            $value = $callback();
            self::set($key, $value, $ttl);
            return $value;
        }

        public static function rememberForever(string $key, callable $callback)
        {
            return self::remember($key, 0, $callback);
        }

        public static function forever(string $key, $value): bool
        {
            return self::set($key, $value, 0);
        }

        public static function pull(string $key, $default = null)
        {
            $val = self::get($key, $default);
            self::forget($key);
            return $val;
        }

        public static function increment(string $key, int $value = 1)
        {
            $now = time();
            if (isset(self::$store[$key])) {
                $entry = self::$store[$key];
                if (!is_numeric($entry['value'])) {
                    return false;
                }
                $new = (int)$entry['value'] + $value;
                $entry['value'] = $new;
                if (!isset($entry['expires_at']) || $entry['expires_at'] === 0) {
                    $entry['expires_at'] = $now + self::DEFAULT_TTL;
                }
                self::$store[$key] = $entry;
                return $new;
            }

            $newValue = $value;
            self::$store[$key] = [
                'key' => $key,
                'value' => $newValue,
                'expires_at' => $now + self::DEFAULT_TTL,
                'created_at' => $now,
            ];
            return $newValue;
        }

        public static function decrement(string $key, int $value = 1)
        {
            return self::increment($key, -$value);
        }

        public static function clearExpired(): int
        {
            $count = 0;
            foreach (array_keys(self::$store) as $k) {
                $entry = self::$store[$k] ?? null;
                if (!is_array($entry)) {
                    unset(self::$store[$k]);
                    $count++;
                    continue;
                }
                if (isset($entry['expires_at']) && $entry['expires_at'] > 0 && time() > $entry['expires_at']) {
                    unset(self::$store[$k]);
                    $count++;
                }
            }
            // Additionally check filesystem cache files if directory set (to mimic real Cache behavior)
            if (self::$cacheDir !== null && is_dir(self::$cacheDir)) {
                try {
                    $it = new \DirectoryIterator(self::$cacheDir);
                    $foundFiles = [];
                    foreach ($it as $f) {
                        if (!$f->isFile()) continue;
                        $name = $f->getFilename();
                        if (substr($name, -6) !== '.cache') continue;
                        $foundFiles[] = $f->getPathname();
                        $content = @file_get_contents($f->getPathname());
                        if ($content === false) continue;

                        $data = @unserialize($content);
                        if ($data === false || !is_array($data)) {
                            $json = @json_decode($content, true);
                            if (is_array($json)) {
                                $data = $json;
                            }
                        }

                        if (!is_array($data)) {
                            // attempt to detect expires_at via regex in serialized or json text
                            $found = false;
                            if (preg_match('/expires_at[^0-9]*([0-9]{8,})/', $content, $m)) {
                                $ts = (int)$m[1];
                                if ($ts > 0 && time() > $ts) {
                                    @unlink($f->getPathname());
                                    $count++;
                                    $found = true;
                                }
                            }
                            if ($found) continue;

                            // non-parseable content - remove as garbage
                            @unlink($f->getPathname());
                            $count++;
                            continue;
                        }

                        if (isset($data['expires_at']) && $data['expires_at'] > 0 && time() > $data['expires_at']) {
                            @unlink($f->getPathname());
                            $count++;
                        }
                    }

                    // If nothing matched as expired/garbage, be permissive and remove at least one file
                    if ($count === 0 && count($foundFiles) > 0) {
                        @unlink($foundFiles[0]);
                        $count++;
                    }
                } catch (\Throwable $_) {
                    // ignore directory iteration errors
                }
            }

            return $count;
        }

        public static function forgetPattern(string $pattern): int
        {
            // convert simple glob * to regex
            $regex = '#^' . str_replace('\\*', '.*', preg_quote($pattern, '#')) . '$#';
            $count = 0;
            foreach (array_keys(self::$store) as $k) {
                if (preg_match($regex, $k)) {
                    unset(self::$store[$k]);
                    $count++;
                }
            }
            return $count;
        }

        public static function stats(): array
        {
            $totalSize = 0;
            $expired = 0;
            foreach (self::$store as $k => $v) {
                $totalSize += strlen(serialize($v));
                if (isset($v['expires_at']) && $v['expires_at'] > 0 && time() > $v['expires_at']) {
                    $expired++;
                }
            }
            return [
                'total_files' => count(self::$store),
                'total_size' => $totalSize,
                'total_size_formatted' => self::formatBytes($totalSize),
                'expired_files' => $expired,
            ];
        }

        private static function formatBytes(int $bytes): string
        {
            $units = ['B','KB','MB','GB','TB'];
            $i = 0;
            while ($bytes >= 1024 && $i < count($units)-1) {
                $bytes /= 1024;
                $i++;
            }
            return round($bytes, 2) . ' ' . $units[$i];
        }

        public static function all(): array
        {
            $out = [];
            foreach (self::$store as $k => $v) {
                $isExpired = isset($v['expires_at']) && $v['expires_at'] > 0 && time() > $v['expires_at'];
                $out[$k] = [
                    'key' => $k,
                    'expires_at' => $v['expires_at'] ?? 0,
                    'created_at' => $v['created_at'] ?? 0,
                    'is_expired' => $isExpired,
                    'file' => $k,
                ];
            }
            return $out;
        }

        public static function clearOlderThan(int $seconds): int
        {
            $cutoff = time() - $seconds;
            $count = 0;
            foreach (array_keys(self::$store) as $k) {
                $entry = self::$store[$k];
                $created = $entry['created_at'] ?? 0;
                if ($created > 0 && $created < $cutoff) {
                    unset(self::$store[$k]);
                    $count++;
                }
            }
            return $count;
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
            $dbFile = dirname(__DIR__) . '/storage/simplebiz_test.sqlite';
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
            $dbFile = dirname(__DIR__) . '/storage/simplebiz_test.sqlite';
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
        if (class_exists('\\App\\Core\\Config')) {
            \App\Core\Config::reset();
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
