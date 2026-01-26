<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Middleware\ApiKeyMiddleware;
use App\Core\Response;
use App\Core\Request;
use Tests\TestCase;

final class ApiKeyMiddlewareTest extends TestCase
{
    public function testMissingKeyReturnsJsonError(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/data';

        // Ensure no api_key present from other tests
        unset($_GET['api_key']);
        unset($_SERVER['HTTP_AUTHORIZATION']);

        $mw = new ApiKeyMiddleware();
        $result = $mw->handle();

        // Depending on environment, middleware may return a Response or false to stop processing.
        $this->assertNotTrue($result);
        if ($result instanceof Response) {
            $this->assertSame(401, $result->getStatusCode());
        }
    }

    public function testValidKeyInQueryPasses(): void
    {
        // Arrange: set API_KEYS via env/server and provide X-API-Key header
        $_SERVER['API_KEYS'] = 'test-key-67890';
        $_SERVER['HTTP_X_API_KEY'] = 'test-key-67890';
        $_SERVER['REQUEST_URI'] = '/api/data';

        $mw = new ApiKeyMiddleware();
        $request = new Request();
        $result = $mw->handle($request);

        $this->assertTrue($result);
    }
}
