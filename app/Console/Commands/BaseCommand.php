<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Console\ConsoleContext;
use App\Console\ConsoleIO;

abstract class BaseCommand implements CommandInterface
{
    protected ConsoleContext $context;
    protected ConsoleIO $io;

    /**
     * @return string[]
     */
    public function aliases(): array
    {
        return [];
    }

    /**
     * @param string[] $args
     */
    final public function handle(array $args, ConsoleContext $context): void
    {
        $this->context = $context;
        $this->io = $context->io();
        $this->execute($args);
    }

    /**
     * @param string[] $args
     */
    abstract protected function execute(array $args): void;

    protected function path(string $relative): string
    {
        return $this->context->path($relative);
    }

    protected function success(string $message): void
    {
        $this->io->success($message);
    }

    protected function error(string $message): void
    {
        $this->io->error($message);
    }

    protected function info(string $message): void
    {
        $this->io->info($message);
    }

    protected function warning(string $message): void
    {
        $this->io->warning($message);
    }

    protected function confirm(string $message, bool $default = false): bool
    {
        return $this->io->confirm($message, $default);
    }

    /**
     * @param string[] $args
     */
    protected function hasForceFlag(array $args): bool
    {
        return $this->context->hasForceFlag($args);
    }

    protected function checkDatabaseConnection(): bool
    {
        return $this->context->checkDatabaseConnection();
    }

    protected function removeDirectory(string $dir): void
    {
        $this->context->removeDirectory($dir);
    }

    protected function humanFilesize(int $bytes, int $decimals = 2): string
    {
        return $this->context->humanFilesize($bytes, $decimals);
    }
}
