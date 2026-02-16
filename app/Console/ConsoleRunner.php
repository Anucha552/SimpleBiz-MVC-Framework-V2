<?php

declare(strict_types=1);

namespace App\Console;

class ConsoleRunner
{
    private CommandRegistry $registry;
    private ConsoleContext $context;

    public function __construct(CommandRegistry $registry, ConsoleContext $context)
    {
        $this->registry = $registry;
        $this->context = $context;
    }

    /**
     * @param string[] $argv
     */
    public function run(array $argv): void
    {
        $this->context->io()->printBanner();

        if (count($argv) < 2) {
            $this->runHelp();
            return;
        }

        $command = $argv[1];
        $args = array_slice($argv, 2);

        $handler = $this->registry->find($command);
        if ($handler === null) {
            $this->context->io()->error("ไม่พบคำสั่ง '{$command}'");

            $suggestions = $this->findSimilarCommands($command);
            if (!empty($suggestions)) {
                echo "\n" . ConsoleColor::YELLOW . "คุณหมายถึง:" . ConsoleColor::RESET . "\n";
                foreach ($suggestions as $suggestion) {
                    echo "  " . ConsoleColor::CYAN . $suggestion . ConsoleColor::RESET . "\n";
                }
            }

            $this->context->io()->info("รันคำสั่ง 'php console help' เพื่อดูรายการคำสั่งที่มี \n");
            exit(1);
        }

        try {
            $handler->handle($args, $this->context);
        } catch (\Throwable $e) {
            $this->context->io()->error("Internal error dispatching command: " . $e->getMessage());
            exit(1);
        }
    }

    private function runHelp(): void
    {
        $handler = $this->registry->find('help');
        if ($handler === null) {
            $this->context->io()->error("ไม่พบคำสั่ง 'help'");
            return;
        }

        $handler->handle([], $this->context);
    }

    /**
     * @return string[]
     */
    private function findSimilarCommands(string $input): array
    {
        $allCommands = $this->registry->getCommandNames();
        $suggestions = [];

        foreach ($allCommands as $cmd) {
            if (strpos($cmd, $input) === 0) {
                $suggestions[] = $cmd;
            } elseif (levenshtein($input, $cmd) <= 2) {
                $suggestions[] = $cmd;
            }
        }

        return array_slice($suggestions, 0, 5);
    }
}
