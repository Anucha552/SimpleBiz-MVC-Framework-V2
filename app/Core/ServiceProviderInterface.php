<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Interface ServiceProviderInterface
 *
 * Simple service provider contract for registering services into the Container.
 */
interface ServiceProviderInterface
{
    public function register(Container $container): void;
}
