<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Config;
use App\Core\Request;
use App\Middleware\Systems\CorsMiddleware;
use Tests\TestCase;

final class CorsProductionTest extends TestCase
{
    private ?string $appEnvBackup = null;

    protected function setUp(): void
    {
        parent::setUp();
        tests_reset_doubles();

        $current = getenv('APP_ENV');
        $this->appEnvBackup = $current === false ? null : $current;
        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = 'production';
        $_SERVER['APP_ENV'] = 'production';
    }

    protected function tearDown(): void
    {
        if ($this->appEnvBackup === null) {
            putenv('APP_ENV=');
            unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);
        } else {
            putenv('APP_ENV=' . $this->appEnvBackup);
            $_ENV['APP_ENV'] = $this->appEnvBackup;
            $_SERVER['APP_ENV'] = $this->appEnvBackup;
        }

        parent::tearDown();
    }

    public function testCorsIsNotAddedWhenOriginNotAllowedInProduction(): void
    {
        Config::set('app.debug', false);
        Config::set('cors.allowed_origins', []);

        $_SERVER['HTTP_ORIGIN'] = 'https://evil.example';

        $request = new Request();
        $mw = new CorsMiddleware();
        $mw->handle($request);

        $headers = $request->getResponseHeaders();
        $this->assertArrayNotHasKey('Access-Control-Allow-Origin', $headers);
    }

    public function testCorsIsAddedWhenOriginAllowedInProduction(): void
    {
        Config::set('app.debug', false);
        Config::set('cors.allowed_origins', ['https://app.example']);

        $_SERVER['HTTP_ORIGIN'] = 'https://app.example';

        $request = new Request();
        $mw = new CorsMiddleware();
        $mw->handle($request);

        $headers = $request->getResponseHeaders();
        $this->assertArrayHasKey('Access-Control-Allow-Origin', $headers);
        $this->assertSame('https://app.example', $headers['Access-Control-Allow-Origin']);
    }
}
