<?php

declare(strict_types=1);

namespace Modules\Auth;

use App\Core\ModuleInterface;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;

final class AuthModule implements ModuleInterface
{
    public function register(Router $router): void
    {
        // Web auth
        $router->get('/login', Controllers\AuthController::class . '@showLogin', [GuestMiddleware::class]);
        $router->post('/login', Controllers\AuthController::class . '@login', [GuestMiddleware::class, CsrfMiddleware::class]);

        $router->get('/register', Controllers\AuthController::class . '@showRegister', [GuestMiddleware::class]);
        $router->post('/register', Controllers\AuthController::class . '@register', [GuestMiddleware::class, CsrfMiddleware::class]);

        $router->post('/logout', Controllers\AuthController::class . '@logout', [AuthMiddleware::class, CsrfMiddleware::class]);

        // Simple API example (session-based)
        $router->get('/api/me', Controllers\AuthApiController::class . '@me', [AuthMiddleware::class]);
    }
}
