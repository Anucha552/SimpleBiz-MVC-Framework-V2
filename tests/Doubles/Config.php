<?php
declare(strict_types=1);

namespace Tests\Doubles;

final class Config
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
