<?php

namespace App\Core;

interface ModuleInterface
{
    /**
     * Register routes/services for this module.
     */
    public function register(Router $router): void;
}
