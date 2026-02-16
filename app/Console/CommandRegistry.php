<?php

declare(strict_types=1);

namespace App\Console;

class CommandRegistry
{
    /** @var array<string, CommandInterface> */
    private array $commands = [];

    /** @var array<string, string> */
    private array $aliases = [];

    public function register(CommandInterface $command): void
    {
        $name = $command->name();
        $this->commands[$name] = $command;

        foreach ($command->aliases() as $alias) {
            $this->aliases[$alias] = $name;
        }
    }

    public function find(string $name): ?CommandInterface
    {
        if (isset($this->commands[$name])) {
            return $this->commands[$name];
        }

        if (isset($this->aliases[$name])) {
            $target = $this->aliases[$name];
            return $this->commands[$target] ?? null;
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function getCommandNames(bool $includeAliases = true): array
    {
        $names = array_keys($this->commands);

        if ($includeAliases) {
            $names = array_merge($names, array_keys($this->aliases));
        }

        sort($names);
        return $names;
    }

    public function discover(string $directory, string $baseNamespace): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $pattern = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php';
        $files = glob($pattern) ?: [];

        foreach ($files as $file) {
            $class = $baseNamespace . '\\' . basename($file, '.php');
            if (!class_exists($class)) {
                continue;
            }

            try {
                $ref = new \ReflectionClass($class);
            } catch (\ReflectionException $e) {
                continue;
            }

            if ($ref->isAbstract()) {
                continue;
            }

            $instance = $ref->newInstance();
            if (!$instance instanceof CommandInterface) {
                continue;
            }

            $this->register($instance);
        }
    }
}
