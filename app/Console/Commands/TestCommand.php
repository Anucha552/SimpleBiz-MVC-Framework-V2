<?php

declare(strict_types=1);

namespace App\Console\Commands;

class TestCommand extends BaseCommand
{
    public function name(): string
    {
        return 'test';
    }

    public function aliases(): array
    {
        return ['t'];
    }

    protected function execute(array $args): void
    {
        $this->info("กำลังรัน tests...");
        echo "\n";
        $phpunitPath = $this->path('vendor/bin/phpunit');

        if (PHP_OS_FAMILY === 'Windows') {
            $phpunitPath .= '.bat';
        }

        $command = $phpunitPath;
        if (!empty($args)) {
            $command .= ' ' . implode(' ', $args);
        }

        passthru($command);
    }
}
