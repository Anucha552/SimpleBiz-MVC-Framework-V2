<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\Session;
use Tests\TestCase;

final class AuthRememberIntegrationTest extends TestCase
{
    private array $envBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        tests_reset_doubles();

        $this->setEnv('APP_KEY', 'testing_app_key');

        Config::set('app.debug', false);
        Config::set('app.env', 'production');
        Config::set('auth.app_key', 'testing_app_key');
        Config::set('auth.cookie_domain', '');
        Config::set('auth.remember_samesite', 'Lax');

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_COOKIE = [];

        $password = password_hash('secret', PASSWORD_BCRYPT);
        Database::getInstance()->seedUsers([
            [
                'id' => 1,
                'username' => 'johndoe',
                'email' => 'john@example.com',
                'password' => $password,
                'status' => 'active',
                'deleted_at' => null,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        $this->restoreEnv();
        parent::tearDown();
    }

    public function testRememberCookieAutoLoginWorks(): void
    {
        $userId = 1;
        $rawToken = bin2hex(random_bytes(64));
        $hashed = hash('sha256', $rawToken);
        Database::getInstance()->execute(
            'UPDATE users SET remember_token = :token WHERE id = :id',
            ['token' => $hashed, 'id' => $userId]
        );

        $payload = $userId . '|' . $rawToken;
        $signature = hash_hmac('sha256', $payload, Config::get('auth.app_key'));
        $_COOKIE['_auth_remember'] = $payload . '|' . $signature;

        Session::remove('_auth_user_id');

        $this->assertTrue(Auth::check());
        $this->assertSame(1, Auth::id());
    }

    public function testRememberCookieInvalidSignatureFails(): void
    {
        $userId = 1;
        $rawToken = bin2hex(random_bytes(64));
        $payload = $userId . '|' . $rawToken;
        $_COOKIE['_auth_remember'] = $payload . '|invalid';

        Session::remove('_auth_user_id');

        $this->assertFalse(Auth::check());
    }

    private function setEnv(string $key, string $value): void
    {
        if (!array_key_exists($key, $this->envBackup)) {
            $current = getenv($key);
            $this->envBackup[$key] = $current === false ? null : $current;
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    private function restoreEnv(): void
    {
        foreach ($this->envBackup as $key => $value) {
            if ($value === null) {
                putenv($key . '=');
                unset($_ENV[$key], $_SERVER[$key]);
                continue;
            }

            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        $this->envBackup = [];
    }
}
