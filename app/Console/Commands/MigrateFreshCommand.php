<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\ConsoleColor;
use App\Core\MigrationRunner;

class MigrateFreshCommand extends BaseCommand
{
    public function name(): string
    {
        return 'migrate:fresh';
    }

    public function aliases(): array
    {
        return ['m:f'];
    }

    protected function execute(array $args): void
    {
        try {
            if (!$this->checkDatabaseConnection()) {
                exit(1);
            }

            $runner = new MigrationRunner();

            echo ConsoleColor::RED . "[WARNING] จะลบข้อมูลทั้งหมดในฐานข้อมูล!" . ConsoleColor::RESET . "\n\n";

            if (!$this->hasForceFlag($args)) {
                if (!$this->confirm("คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลทั้งหมด?")) {
                    $this->warning("ยกเลิกการทำงาน");
                    return;
                }
                echo "\n";
            }

            $this->info("กำลัง refresh database...");
            echo ConsoleColor::WHITE . "──────────────────────────────────────────────────" . ConsoleColor::RESET . "\n";

            $result = $runner->fresh();
            echo "\n";
            $this->success($result['message']);
            echo "\n";
        } catch (\Exception $e) {
            $this->error("เกิดข้อผิดพลาด: " . $e->getMessage());
            echo "\n";
            exit(1);
        }
    }
}
