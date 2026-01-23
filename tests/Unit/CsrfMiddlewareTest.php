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
}
