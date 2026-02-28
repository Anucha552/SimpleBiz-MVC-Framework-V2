<?php
declare(strict_types=1);

// Global helpers for tests
if (!function_exists('tests_reset_doubles')) {
    function tests_reset_doubles(): void
    {
        if (class_exists('\App\Core\Session')) {
            \App\Core\Session::reset();
        }
        if (class_exists('\App\Core\Cache')) {
            \App\Core\Cache::reset();
        }
        if (class_exists('\App\Core\Database')) {
            \App\Core\Database::getInstance()->reset();
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
