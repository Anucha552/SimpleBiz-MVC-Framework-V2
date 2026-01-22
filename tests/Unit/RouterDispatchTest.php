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
