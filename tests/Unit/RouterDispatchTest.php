<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Router;
use App\Core\Request;
use App\Core\Response;
use Tests\TestCase;

final class RouterDispatchTest extends TestCase
{
    public function testDispatchCallsControllerAndPassesParams(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/products/123';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $router = new Router();
        $router->get('/products/{id}', Fixtures\DummyController::class . '@show');

        Fixtures\DummyController::$lastCall = null;
        $router->dispatch();

        $this->assertSame(['show', ['123']], Fixtures\DummyController::$lastCall);
    }

    public function testMethodOverrideFromPostToPutWorks(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/products/9';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_POST['_method'] = 'PUT';

        $router = new Router();
        $router->put('/products/{id}', Fixtures\DummyController::class . '@update');

        Fixtures\DummyController::$lastCall = null;
        $router->dispatch();

        $this->assertSame(['update', ['9']], Fixtures\DummyController::$lastCall);

        unset($_POST['_method']);
    }

    public function testDispatchCanInjectRequestIntoController(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/req/55';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $_GET['q'] = 'x';

        $router = new Router();
        $router->get('/req/{id}', Fixtures\RequestController::class . '@show');

        Fixtures\RequestController::$lastCall = null;
        $router->dispatch();

        $this->assertIsArray(Fixtures\RequestController::$lastCall);
        $this->assertSame('show', Fixtures\RequestController::$lastCall[0]);
        $this->assertInstanceOf(Request::class, Fixtures\RequestController::$lastCall[1][0]);
        $this->assertSame('55', Fixtures\RequestController::$lastCall[1][1]);
    }

    public function testDispatchCanEmitResponseReturnedByController(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/resp';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $router = new Router();
        $router->get('/resp', Fixtures\ResponseController::class . '@index');

        ob_start();
        $router->dispatch();
        $output = ob_get_clean();
        $this->assertStringContainsString('{"ok":true}', $output);
    }

    public function testMiddlewareCanShortCircuitByReturningResponse(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/blocked';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $router = new Router();
        $router->get('/blocked', Fixtures\DummyController::class . '@show', [Fixtures\BlockingMiddleware::class]);

        Fixtures\DummyController::$lastCall = null;

        ob_start();
        $router->dispatch();
        $output = ob_get_clean();

        $this->assertNull(Fixtures\DummyController::$lastCall);
        $this->assertStringContainsString('"blocked":true', $output);
    }

    public function testCorsMiddlewareAggregatesHeadersAndRouterEmitsThem(): void
    {
        \App\Core\Response::clearLastSentHeaders();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/v1/cors-test';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['HTTP_ORIGIN'] = 'http://localhost:3000';

        $router = new Router();
        $router->get('/api/v1/cors-test', Fixtures\ResponseController::class . '@index', [\App\Middleware\Systems\CorsMiddleware::class]);

        ob_start();
        $router->dispatch(new Request());
        ob_end_clean();

        $headers = \App\Core\Response::getLastSentHeaders();
        $this->assertContains('Access-Control-Allow-Origin: http://localhost:3000', $headers);
    }

    public function testRateLimitMiddlewareAggregatesHeadersAndRouterEmitsThem(): void
    {
        \App\Core\Response::clearLastSentHeaders();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/v1/rl-test';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $router = new Router();
        $router->get('/api/v1/rl-test', Fixtures\ResponseController::class . '@index', [\App\Middleware\Systems\RateLimitMiddleware::class]);

        ob_start();
        $router->dispatch(new Request());
        ob_end_clean();

        $headers = \App\Core\Response::getLastSentHeaders();
        $this->assertTrue(
            count(array_filter($headers, fn($h) => str_starts_with($h, 'X-RateLimit-Limit: '))) === 1
        );
        $this->assertTrue(
            count(array_filter($headers, fn($h) => str_starts_with($h, 'X-RateLimit-Remaining: '))) === 1
        );
        $this->assertTrue(
            count(array_filter($headers, fn($h) => str_starts_with($h, 'X-RateLimit-Reset: '))) === 1
        );
    }

    public function testSecurityHeadersMiddlewareAggregatesHeadersAndRouterEmitsThem(): void
    {
        \App\Core\Response::clearLastSentHeaders();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/v1/sec-test';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $router = new Router();
        $router->get('/api/v1/sec-test', Fixtures\ResponseController::class . '@index', [\App\Middleware\Systems\SecurityHeadersMiddleware::class]);

        ob_start();
        $router->dispatch(new Request());
        ob_end_clean();

        $headers = \App\Core\Response::getLastSentHeaders();
        $this->assertContains('X-Content-Type-Options: nosniff', $headers);
        $this->assertContains('Referrer-Policy: strict-origin-when-cross-origin', $headers);
        $this->assertContains('X-Frame-Options: SAMEORIGIN', $headers);
        $this->assertContains('Permissions-Policy: geolocation=(), microphone=(), camera=()', $headers);
    }
}

namespace Tests\Unit\Fixtures;

final class DummyController
{
    /** @var array{0:string,1:array<int,string>}|null */
    public static ?array $lastCall = null;

    public function show(string $id): void
    {
        self::$lastCall = ['show', [$id]];
    }

    public function update(string $id): void
    {
        self::$lastCall = ['update', [$id]];
    }
}

final class RequestController
{
    /** @var array{0:string,1:array<int,mixed>}|null */
    public static ?array $lastCall = null;

    public function show(\App\Core\Request $request, string $id): void
    {
        self::$lastCall = ['show', [$request, $id]];
    }
}

final class ResponseController
{
    public function index(): \App\Core\Response
    {
        return \App\Core\Response::json(['ok' => true], 201);
    }
}

final class BlockingMiddleware extends \App\Core\Middleware
{
    public function handle(?\App\Core\Request $request = null): bool|\App\Core\Response
    {
        return \App\Core\Response::json(['blocked' => true], 401);
    }
}
