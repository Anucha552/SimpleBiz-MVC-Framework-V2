<?php
/**
 * Command: Migrate Batch
 * จุดประสงค์: แสดงรายการ batch migrations ที่มีอยู่
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\MigrationRunner;

class MigrateBatchCommand extends BaseCommand
{
    public function name(): string
    {
        return 'migrate:batch';
    }

    public function aliases(): array
    {
        return ['m:batches', 'm:batch'];
    }

    protected function execute(array $args): void
    {
        // ตรวจสอบการเชื่อมต่อฐานข้อมูลก่อนดำเนินการ
        if (!$this->checkDatabaseConnection()) {
            exit(1);
        }

        $this->infoWhite("กำลังดึงข้อมูล batch migrations ที่มีอยู่...\n");
        try {
            $runner = new MigrationRunner();
            $batches = $runner->getRollbackableBatches();
            $countBatches = count($batches);
            $this->info("จำนวน batch migration ที่มีอยู่: " . $countBatches);
            for ($i = 0; $i < $countBatches; $i++) {
                $batch = $batches[$i];
                $this->info("  " . ($i + 1) . ". Batch: {$batch}");
            }
        } catch (\Exception $e) {
            $this->error("เกิดข้อผิดพลาด: " . $e->getMessage());
        }

        echo "\n";
    }
}