<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        tests_reset_doubles();
        // configure app debug and app key
        \App\Core\Config::set('app.debug', true);
        \App\Core\Config::set('auth.app_key', 'testing_app_key');
        \App\Core\Config::set('auth.cookie_domain', '');
        \App\Core\Config::set('auth.remember_samesite', 'Lax');

        // seed a user
        $password = password_hash('secret', PASSWORD_BCRYPT);
        \App\Core\Database::getInstance()->seedUsers([
            ['id' => 1, 'username' => 'johndoe', 'email' => 'john@example.com', 'password' => $password, 'status' => 'active', 'deleted_at' => null]
        ]);
    }

    public function testHashAndVerify(): void
    {
        $hash = \App\Core\Auth::hash('mysecret');
        $this->assertIsString($hash);
        $this->assertTrue(\App\Core\Auth::verifyPassword('mysecret', $hash));
        $this->assertTrue(\App\Core\Auth::verify('mysecret', $hash));
    }

    public function testAttemptFailsWithWrongCredentials(): void
    {
        $res = \App\Core\Auth::attempt(['username' => 'johndoe', 'password' => 'wrong'], false);
        $this->assertFalse($res);
    }

    public function testAttemptSucceedsAndSetsSession(): void
    {
        $res = \App\Core\Auth::attempt(['username' => 'johndoe', 'password' => 'secret'], false);
        $this->assertTrue($res);
        $this->assertTrue(\App\Core\Auth::check());
        $this->assertSame(1, \App\Core\Auth::id());
        $this->assertIsArray(\App\Core\Auth::user());
    }

    public function testLoginById(): void
    {
        \App\Core\Auth::logout();
        $ok = \App\Core\Auth::loginById(1, false);
        $this->assertTrue($ok);
        $this->assertSame(1, \App\Core\Auth::id());
    }

    public function testLoginTemporaryThrowsInProduction(): void
    {
        \App\Core\Config::set('app.debug', false);
        $this->expectException(RuntimeException::class);
        \App\Core\Auth::loginTemporary(['id' => 99]);
    }

    public function testRememberCookieAutoLogin(): void
    {
        // prepare token and store hashed token in DB
        $userId = 1;
        $rawToken = bin2hex(random_bytes(64));
        $hashed = hash('sha256', $rawToken);
        \App\Core\Database::getInstance()->execute("UPDATE users SET remember_token = :token WHERE id = :id", ['token' => $hashed, 'id' => $userId]);

        $payload = $userId . '|' . $rawToken;
        $signature = hash_hmac('sha256', $payload, \App\Core\Config::get('auth.app_key'));
        $_COOKIE['_auth_remember'] = $payload . '|' . $signature;

        // clear session
        \App\Core\Session::remove('_auth_user_id');

        $this->assertTrue(\App\Core\Auth::check());
        $this->assertSame(1, \App\Core\Auth::id());
    }

    public function testRememberFailsWhenAppKeyMissing(): void
    {
        \App\Core\Config::set('auth.app_key', '');
        $userId = 1;
        $rawToken = bin2hex(random_bytes(64));
        $payload = $userId . '|' . $rawToken;
        $signature = 'invalid';
        $_COOKIE['_auth_remember'] = $payload . '|' . $signature;

        $this->assertFalse(\App\Core\Auth::check());
    }

    public function testLogoutClearsSession(): void
    {
        \App\Core\Auth::loginById(1, false);
        $this->assertTrue(\App\Core\Auth::check());
        \App\Core\Auth::logout();
        $this->assertFalse(\App\Core\Auth::check());
    }

    public function testBruteForceLockingAndClearing(): void
    {
        $identifier = 'johndoe';
        $ip = '127.0.0.1';
        for ($i = 0; $i < 5; $i++) {
            \App\Core\Auth::attempt(['username' => $identifier, 'password' => 'wrong'], false);
        }
        // After threshold, further attempts should fail due to lock
        $this->assertFalse(\App\Core\Auth::attempt(['username' => $identifier, 'password' => 'secret'], false));

        // Clear cache directly (test double) to simulate expiry/clearing
        if (method_exists('\App\Core\Cache', 'reset')) {
            \App\Core\Cache::reset();
        }

        // Now attempt with correct password should succeed
        $this->assertTrue(\App\Core\Auth::attempt(['username' => $identifier, 'password' => 'secret'], false));
    }
}
