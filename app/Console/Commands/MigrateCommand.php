<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\ConsoleColor;
use App\Core\Logger;
use App\Core\MigrationRunner;

class MigrateCommand extends BaseCommand
{
    public function name(): string
    {
        return 'migrate';
    }

    public function aliases(): array
    {
        return ['m'];
    }

    protected function execute(array $args): void
    {
        try {
            $runner = new MigrationRunner();

            $modulePath = null;
            foreach ($args as $arg) {
                if (strpos($arg, '--path=') === 0) {
                    $modulePath = substr($arg, 7);
                }
            }

            if ($modulePath) {
                $runner->setModule($modulePath);
                $this->info("โมดูล: {$modulePath}");
            }

            $this->info("กำลังรัน migrations...");
            echo ConsoleColor::WHITE . "-------------------" . ConsoleColor::RESET . "\n";

            $result = $runner->run();
            echo "\n";
            $this->success($result['message'] . "\n");
        } catch (\Exception $e) {
            echo "\n";
            echo "┌─ Console Error ─────────────────────────────────────┐\n";
            $this->error("เกิดข้อผิดพลาด: " . $e->getMessage());
            $this->error("ไฟล์: " . $e->getFile());
            $this->error("บรรทัด: " . $e->getLine());
            echo "└──────────────────────────────────────────────────────┘\n";
            echo "\n";

            try {
                (new Logger())->error('Console command error', [
                    'command' => 'migrate',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            } catch (\Exception $logError) {
                // Ignore logging errors
            }

            exit(1);
        }
    }
}
