<?php
declare(strict_types=1);

namespace Tests\Doubles;

final class Logger
{
    public function info(string $msg, array $context = []): void {}

    public function security(string $msg, array $context = []): void {}

    public function error(string $msg, array $context = []): void {}

    public function warning(string $msg, array $context = []): void {}
}
