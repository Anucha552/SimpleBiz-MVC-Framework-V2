<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Router;
use Tests\TestCase;

final class RouterMiddlewareIntegrationTest extends TestCase
{
    public function testNamedParamIsPassedToController(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/items/42';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $router = new Router();
        $router->get('/items/{id}', TestDummyController::class . '@show');

        // Capture output produced by Response::send()
        ob_start();
        $router->dispatch();
        $output = ob_get_clean();

        $this->assertStringContainsString('ITEM:42', $output);
    }
}
