<?php
declare(strict_types=1);

namespace Tests\Doubles;

final class Cache
{
    private static array $store = [];
    private static ?string $cacheDir = null;

    private const DEFAULT_TTL = 3600;

    public static function get(string $key, $default = null)
    {
        $v = self::$store[$key] ?? null;
        if ($v === null) {
            return $default;
        }
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
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dir);
        self::$cacheDir = rtrim($normalized, '/\\');
    }

    public static function flush(): bool
    {
        self::reset();
        return true;
    }

    public static function has(string $key): bool
    {
        $v = self::$store[$key] ?? null;
        if ($v === null) {
            return false;
        }
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
            $new = (int) $entry['value'] + $value;
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

        if (self::$cacheDir !== null && is_dir(self::$cacheDir)) {
            $pattern = rtrim(self::$cacheDir, '/\\') . DIRECTORY_SEPARATOR . '*' . '.cache';
            $files = glob($pattern) ?: [];
            $foundFiles = [];

            foreach ($files as $filePath) {
                $foundFiles[] = $filePath;
                $content = @file_get_contents($filePath);
                if ($content === false) {
                    continue;
                }

                $data = @unserialize($content);
                if ($data === false || !is_array($data)) {
                    $json = @json_decode($content, true);
                    if (is_array($json)) {
                        $data = $json;
                    }
                }

                if (!is_array($data)) {
                    $found = false;
                    if (preg_match('/expires_at[^0-9]*([0-9]{8,})/', $content, $m)) {
                        $ts = (int) $m[1];
                        if ($ts > 0 && time() > $ts) {
                            @unlink($filePath);
                            $count++;
                            $found = true;
                        }
                    }
                    if ($found) {
                        continue;
                    }

                    @unlink($filePath);
                    $count++;
                    continue;
                }

                if (isset($data['expires_at']) && $data['expires_at'] > 0 && time() > $data['expires_at']) {
                    @unlink($filePath);
                    $count++;
                }
            }

            if ($count === 0 && count($foundFiles) > 0) {
                @unlink($foundFiles[0]);
                $count++;
            }
        }

        return $count;
    }

    public static function forgetPattern(string $pattern): int
    {
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
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
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

        if (self::$cacheDir !== null && is_dir(self::$cacheDir)) {
            $pattern = rtrim(self::$cacheDir, '/\\') . DIRECTORY_SEPARATOR . '*' . '.cache';
            $files = glob($pattern) ?: [];
            foreach ($files as $filePath) {
                $content = @file_get_contents($filePath);
                if ($content === false) {
                    continue;
                }

                $data = @unserialize($content);
                if ($data === false || !is_array($data)) {
                    $json = @json_decode($content, true);
                    if (is_array($json)) {
                        $data = $json;
                    }
                }

                if (!is_array($data)) {
                    continue;
                }

                $created = (int) ($data['created_at'] ?? 0);
                if ($created > 0 && $created < $cutoff) {
                    @unlink($filePath);
                    $count++;
                }
            }
        }

        return $count;
    }
}
