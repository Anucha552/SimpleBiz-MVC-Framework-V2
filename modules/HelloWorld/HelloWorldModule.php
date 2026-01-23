<?php

namespace Modules\HelloWorld;

use App\Core\ModuleInterface;
use App\Core\Router;

final class HelloWorldModule implements ModuleInterface
{
    public function register(Router $router): void
    {
        $router->get('/hello', Controllers\HelloController::class . '@index');
        $router->get('/api/hello', Controllers\HelloController::class . '@api');
    }
}
