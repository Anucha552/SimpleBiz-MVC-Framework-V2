<?php

declare(strict_types=1);

namespace App\Console;

class ConsoleIO
{
    public function printBanner(): void
    {
        echo ConsoleColor::CYAN . ConsoleColor::BOLD;
        echo "\n╔════════════════════════════════════════╗\n";
        echo "║   SimpleBiz MVC Framework Console      ║\n";
        echo "╚════════════════════════════════════════╝\n";
        echo ConsoleColor::RESET . "\n";
    }

    public function success(string $message): void
    {
        echo ConsoleColor::GREEN . "[OK] {$message}" . ConsoleColor::RESET . "\n";
    }

    public function error(string $message): void
    {
        echo ConsoleColor::RED . "[X] {$message}" . ConsoleColor::RESET . "\n";
    }

    public function info(string $message): void
    {
        echo ConsoleColor::BLUE . "[i] {$message}" . ConsoleColor::RESET . "\n";
    }

    public function warning(string $message): void
    {
        echo ConsoleColor::YELLOW . "[!] {$message}" . ConsoleColor::RESET . "\n";
    }

    public function confirm(string $message, bool $default = false): bool
    {
        $defaultText = $default ? 'Y/n' : 'y/N';
        echo ConsoleColor::YELLOW . "[?] {$message} [{$defaultText}]: " . ConsoleColor::RESET;

        $input = strtolower(trim(fgets(STDIN)));

        if ($input === '') {
            return $default;
        }

        return in_array($input, ['y', 'yes', 'ใช่'], true);
    }
}
