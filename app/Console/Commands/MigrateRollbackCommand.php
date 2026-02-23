<?php
/**
 * Command: Migrate Rollback
 * จุดประสงค์: ย้อนกลับการทำงานของ migration ล่าสุด
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\ConsoleColor;
use App\Core\Logger;
use App\Core\MigrationRunner;

class MigrateRollbackCommand extends BaseCommand
{
    public function name(): string
    {
        return 'migrate:rollback';
    }

    public function aliases(): array
    {
        return ['m:r'];
    }

    protected function execute(array $args): void
    {
        try {
            // ตรวจสอบการเชื่อมต่อฐานข้อมูลก่อนดำเนินการ
            if (!$this->checkDatabaseConnection()) {
                exit(1);
            }
            
            $runner = new MigrationRunner();
            $this->info("จำนวน batch migration ที่มีอยู่: {$runner->getBatchCount()}");
            $steps = (!empty($args) && is_numeric($args[0])) ? (int) $args[0] : 1;

            $this->info("กำลังย้อนกลับ {$steps} batch(es)...");
            echo ConsoleColor::WHITE . "──────────────────────────────────────────────────" . ConsoleColor::RESET . "\n";

            $result = $runner->rollback($steps);
            echo "\n";
            $this->success($result['message']);
            echo "\n";
        } catch (\Exception $e) {
            echo "\n";
            echo "┌─ Rollback Error ────────────────────────────────────┐\n";
            $this->error("เกิดข้อผิดพลาด: " . $e->getMessage());
            $this->error("ไฟล์: " . $e->getFile());
            $this->error("บรรทัด: " . $e->getLine());
            echo "└──────────────────────────────────────────────────────┘\n";
            echo "\n";

            try {
                (new Logger())->error('Rollback error', [
                    'command' => 'migrate:rollback',
                    'error' => $e->getMessage(),
                ]);
            } catch (\Exception $logError) {
                // Ignore logging errors
            }

            exit(1);
        }
    }
}
