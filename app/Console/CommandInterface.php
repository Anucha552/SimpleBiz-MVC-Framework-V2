<?php

declare(strict_types=1);

namespace App\Console;

interface CommandInterface
{
    public function name(): string;

    /**
     * @return string[]
     */
    public function aliases(): array;

    /**
     * @param string[] $args
     */
    public function handle(array $args, ConsoleContext $context): void;
}
