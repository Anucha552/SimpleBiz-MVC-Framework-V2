<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Config;
use App\Core\Request;
use App\Middleware\Systems\SecurityHeadersMiddleware;
use Tests\TestCase;

final class SecurityHeadersProductionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        tests_reset_doubles();
    }

    public function testHstsHeaderIsSetOnHttpsInProduction(): void
    {
        Config::set('security.hsts', true);
        Config::set('security.hsts_max_age', 15552000);
        Config::set('security.hsts_include_subdomains', true);
        Config::set('security.hsts_preload', true);

        $_SERVER['HTTPS'] = 'on';

        $request = new Request();
        $mw = new SecurityHeadersMiddleware();
        $mw->handle($request);

        $headers = $request->getResponseHeaders();
        $this->assertArrayHasKey('Strict-Transport-Security', $headers);
        $this->assertSame('max-age=15552000; includeSubDomains; preload', $headers['Strict-Transport-Security']);
    }
}
