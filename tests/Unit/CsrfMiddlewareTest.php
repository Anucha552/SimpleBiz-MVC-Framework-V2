<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Response;
use App\Core\Session;
use App\Middleware\CsrfMiddleware;
use Tests\TestCase;

final class CsrfMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clean superglobals used by middleware
        $_GET = [];
        $_POST = [];
        $_SERVER = [];

        Session::start();
        Session::clear();
    }

    public function testValidTokenFromUnderscoreFieldPasses(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/login';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $token = Session::generateCsrfToken();
        $_POST['_csrf_token'] = $token;

        $mw = new CsrfMiddleware();
        $result = $mw->handle();

        $this->assertTrue($result);
    }

    public function testValidLegacyTokenFromCsrfTokenFieldPasses(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/register';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $token = Session::generateCsrfToken();
        // mirror legacy session key expected by older forms
        $_SESSION['csrf_token'] = $token;
        $_POST['csrf_token'] = $token;

        $mw = new CsrfMiddleware();
        $result = $mw->handle();

        $this->assertTrue($result);
    }

    public function testMissingTokenRedirectsBackWithFlashError(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/login';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['HTTP_REFERER'] = '/login';

        $mw = new CsrfMiddleware();
        $result = $mw->handle();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(302, $result->getStatusCode());

        // Flash is queued for the next request under _flash.new
        $this->assertIsArray($_SESSION['_flash']['new'] ?? null);
        $this->assertArrayHasKey('error', $_SESSION['_flash']['new']);
    }

    public function testApiMissingTokenReturnsJson419(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['REQUEST_URI'] = '/api/employees/1';

        $result = (new CsrfMiddleware(true))->handle();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(419, $result->getStatusCode());
        $this->assertSame('application/json; charset=UTF-8', $result->getHeaders()['Content-Type']);
        $this->assertSame('CSRF token missing', json_decode($result->getBody(), true)['message']);
    }

    public function testApiInvalidTokenReturnsJson419(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/employees';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'invalid-token';
        Session::generateCsrfToken();

        $result = (new CsrfMiddleware(true))->handle();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(419, $result->getStatusCode());
        $this->assertSame('Invalid CSRF token', json_decode($result->getBody(), true)['message']);
    }

    public function testApiValidHeaderTokenPasses(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REQUEST_URI'] = '/api/employees/1';
        $token = Session::generateCsrfToken();
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;

        $this->assertTrue((new CsrfMiddleware(true))->handle());
    }

    public function testGetRequestDoesNotRequireCsrfToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/employees';

        $this->assertTrue((new CsrfMiddleware(true))->handle());
    }

    public function testValidTokenDoesNotRotateAfterOneHour(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/employees';
        $token = Session::generateCsrfToken();
        Session::set('csrf_token', $token);
        Session::set('csrf_token_time', time() - 7200);
        $_POST['_csrf_token'] = $token;

        $this->assertTrue((new CsrfMiddleware())->handle());
        $this->assertSame($token, Session::getCsrfToken());

        $this->assertTrue((new CsrfMiddleware())->handle());
        $this->assertSame($token, Session::getCsrfToken());
    }
}
