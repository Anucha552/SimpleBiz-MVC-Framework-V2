<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Session;
use App\Middleware\ValidationMiddleware;
use Tests\TestCase;

final class ValidationMiddlewareTest extends TestCase
{
    public function testWebValidationFailureFlashesErrorsAndOldInputAndRedirects(): void
    {
        // Ensure session is started and clean for this test
        Session::start();
        $_SESSION['_flash'] = ['old' => [], 'new' => []];

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/register';
        $_SERVER['HTTP_REFERER'] = '/register';
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';

        $_POST = [
            'email' => '',
            'password' => 'secret',
        ];

        $middleware = new ValidationMiddleware([
            'email' => 'required|email',
        ]);

        $result = $middleware->handle();

        $this->assertInstanceOf(\App\Core\Response::class, $result);
        $this->assertSame(302, $result->getStatusCode());
        $this->assertSame('/register', $result->getHeaders()['Location']);

        $this->assertArrayHasKey('_flash', $_SESSION);
        $this->assertArrayHasKey('new', $_SESSION['_flash']);
        $this->assertArrayHasKey('validation_errors', $_SESSION['_flash']['new']);
        $this->assertArrayHasKey('_old_input', $_SESSION['_flash']['new']);

        $oldInput = $_SESSION['_flash']['new']['_old_input'];
        $this->assertIsArray($oldInput);
        $this->assertSame('', $oldInput['email']);
        $this->assertArrayNotHasKey('password', $oldInput);
    }
}
