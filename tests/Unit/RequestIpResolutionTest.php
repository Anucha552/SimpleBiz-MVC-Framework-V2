<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\Doubles\Config as TestConfig;
use Tests\TestCase;
use function tests_reset_doubles;

final class RequestIpResolutionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        tests_reset_doubles();
        TestConfig::set('app.trusted_proxies', []);
    }

    public function testIpUsesForwardedHeaderOnlyFromTrustedProxy(): void
    {
        TestConfig::set('app.trusted_proxies', ['10.0.0.1']);

        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.24, 10.0.0.1';

        $request = new \App\Core\Request();

        $this->assertSame('198.51.100.24', $request->ip());
    }

    public function testIpIgnoresForwardedHeaderFromUntrustedProxy(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.24';
        $_SERVER['HTTP_X_REAL_IP'] = '198.51.100.25';
        $_SERVER['HTTP_CLIENT_IP'] = '198.51.100.26';

        $request = new \App\Core\Request();

        $this->assertSame('203.0.113.10', $request->ip());
    }
}