<?php

declare(strict_types=1);

namespace App\Console;

final class ConsoleColor
{
    public const RESET = "\033[0m";
    public const RED = "\033[31m";
    public const GREEN = "\033[32m";
    public const YELLOW = "\033[33m";
    public const BLUE = "\033[34m";
    public const CYAN = "\033[36m";
    public const WHITE = "\033[37m";
    public const GRAY = "\033[90m";
    public const BOLD = "\033[1m";
}
