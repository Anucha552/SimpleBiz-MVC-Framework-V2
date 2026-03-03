<?php
declare(strict_types=1);

// Global helpers for tests
if (!function_exists('tests_reset_doubles')) {
    function tests_reset_doubles(): void
    {
        $testingEnv = [
            'APP_ENV' => 'testing',
            'APP_DEBUG' => 'true',
            'DB_DATABASE' => 'storage/simplebiz_test.sqlite',
            'CORS_ALLOWED_ORIGINS' => 'http://localhost:3000',
        ];

        foreach ($testingEnv as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        if (class_exists('\App\Core\Session')) {
            \App\Core\Session::reset();
        }
        if (class_exists('\App\Core\Config')) {
            \App\Core\Config::reset();
        }
        if (class_exists('\App\Core\Cache')) {
            \App\Core\Cache::reset();
        }
        if (class_exists('\App\Core\Database')) {
            try {
                $rc = new \ReflectionClass('\App\Core\Database');
                if ($rc->hasProperty('instance')) {
                    $prop = $rc->getProperty('instance');
                    $prop->setAccessible(true);
                    $prop->setValue(null, null);
                }
            } catch (\Throwable $e) {
                // ignore reflection errors
            }
            $db = \App\Core\Database::getInstance();
            $reset = [$db, 'reset'];
            if (is_callable($reset)) {
                $reset();
            }
        }
        // ensure auth state cleared
        if (class_exists('\App\Core\Auth')) {
            try {
                \App\Core\Auth::logout();
            } catch (\Throwable $e) {
                // ignore
            }
        }
        // Clear PHP superglobals
        $_COOKIE = [];
    }
}
