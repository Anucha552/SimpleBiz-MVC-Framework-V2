<?php
/**
 * Simple env() helper for config files.
 * Usage: env('KEY', $default, 'bool'|'int'|'array')
 */

if (!function_exists('env')) {
    function env(string $key, $default = null, ?string $type = null)
    {
        $value = getenv($key);

        if ($value === false) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }

        // If still null/false, use default
        if ($value === false || $value === null) {
            $value = $default;
        }

        // If default is null and value is empty string, keep empty string
        if ($value === '' && $default === null) {
            // keep empty string
        }

        if ($type === null) {
            return $value;
        }

        switch ($type) {
            case 'bool':
            case 'boolean':
                if (is_bool($value)) return $value;
                $v = strtolower((string)$value);
                return in_array($v, ['1', 'true', 'on', 'yes'], true);
            case 'int':
            case 'integer':
                return (int)$value;
            case 'array':
                if (is_array($value)) return $value;
                if ($value === null) return [];
                return array_map('trim', explode(',', (string)$value));
            case 'string':
            default:
                return (string)$value;
        }
    }
}
