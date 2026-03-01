<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Interface ServiceProviderInterface
 *
 * สัญญาผู้ให้บริการแบบง่ายสำหรับการลงทะเบียนบริการลงในคอนเทนเนอร์
 */
interface ServiceProviderInterface
{
    public function register(Container $container): void;
}
