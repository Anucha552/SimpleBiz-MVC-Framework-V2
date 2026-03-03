<?php
declare(strict_types=1);

namespace Tests\Doubles;

final class Session
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

    public static function getAllFlash(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        return $_SESSION['_flash']['old'] ?? [];
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
