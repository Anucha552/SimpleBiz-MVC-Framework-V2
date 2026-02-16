<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\ConsoleColor;
use App\Core\MigrationRunner;

class MigrateResetCommand extends BaseCommand
{
    public function name(): string
    {
        return 'migrate:reset';
    }

    public function aliases(): array
    {
        return ['m:reset'];
    }

    protected function execute(array $args): void
    {
        if (!$this->checkDatabaseConnection()) {
            exit(1);
        }

        echo ConsoleColor::RED . "[WARNING] จะ rollback migrations ทั้งหมด!" . ConsoleColor::RESET . "\n\n";

        if (!$this->hasForceFlag($args)) {
            if (!$this->confirm("คุณแน่ใจหรือไม่?")) {
                $this->warning("ยกเลิกการทำงาน");
                return;
            }
            echo "\n";
        }

        $this->info("กำลัง rollback migrations ทั้งหมด...");

        $runner = new MigrationRunner();

        $maxLoops = 200;
        $loops = 0;
        $totalRolledBack = 0;

        while ($loops < $maxLoops) {
            $loops++;
            $result = $runner->rollback(1);
            $rolledBack = $result['rolled_back'] ?? [];
            $count = is_array($rolledBack) ? count($rolledBack) : 0;
            $totalRolledBack += $count;

            if ($count === 0) {
                break;
            }
        }

        if ($loops >= $maxLoops) {
            $this->warning("หยุดการ rollback เนื่องจากถึงขีดจำกัดความปลอดภัย ({$maxLoops} รอบ)");
        }

        $this->success("Reset migrations เรียบร้อยแล้ว (rollback {$totalRolledBack} migrations)");
    }
}
