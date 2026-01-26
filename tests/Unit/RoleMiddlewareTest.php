<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Middleware\RoleMiddleware;
use App\Core\Auth;
use App\Core\Response;
use Tests\TestCase;

final class RoleMiddlewareTest extends TestCase
{
    public function testUnauthenticatedIsRedirected(): void
    {
        $_SERVER['REQUEST_URI'] = '/admin';

        $mw = new RoleMiddleware('admin');
        $result = $mw->handle();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(302, $result->getStatusCode());
    }

    public function testAuthenticatedButInsufficientRoleIsForbidden(): void
    {
        // Create an in-memory user with id 999
        Auth::loginTemporary(['id' => 999, 'role' => 'user']);
        // Ensure Database will not find a real user; middleware should fallback
        $_SERVER['REQUEST_URI'] = '/admin';
        $mw = new RoleMiddleware('admin');
        $result = $mw->handle();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(302, $result->getStatusCode());
    }
}
