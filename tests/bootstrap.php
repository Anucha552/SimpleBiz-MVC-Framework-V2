<?php
/**
 * PHPUnit bootstrap for project tests
 * - Loads Composer autoload
 * - Loads .env.testing into environment (phpdotenv if available, else fallback)
 */

// Load composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Ensure env() helper is available for tests
if (file_exists(__DIR__ . '/../config/env.php')) {
    require_once __DIR__ . '/../config/env.php';
}

$envFile = dirname(__DIR__) . '/.env.testing';
if (file_exists($envFile)) {
    if (class_exists(\Dotenv\Dotenv::class)) {
        // Load specifically .env.testing
        try {
            \Dotenv\Dotenv::createImmutable(dirname(__DIR__), '.env.testing')->safeLoad();
        } catch (Throwable $e) {
            // ignore - safeLoad should be safe
        }
    } else {
        // Fallback: simple parser
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if (!getenv($key)) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }
}
