<?php
/**
 * MigrateStatusCommand
 *
 * จุดประสงค์: เป็นคำสั่ง CLI ที่ใช้สำหรับแสดงสถานะของ migrations ในโปรเจกต์ โดยจะแสดงรายการ migrations ทั้งหมดพร้อมกับสถานะว่าแต่ละ migration ได้ถูกรันไปแล้วหรือยัง และถ้าถูกรันไปแล้วจะแสดงหมายเลข batch ที่เกี่ยวข้อง เพื่อให้ผู้ใช้สามารถตรวจสอบและจัดการกับ migrations ได้อย่างง่ายดายผ่านทางคอนโซล
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\ConsoleColor;
use App\Core\MigrationRunner;

class MigrateStatusCommand extends BaseCommand
{
    public function name(): string
    {
        return 'migrate:status';
    }

    public function aliases(): array
    {
        return ['m:s'];
    }

    protected function execute(array $args): void
    {
        try {
            $runner = new MigrationRunner();

            echo ConsoleColor::CYAN . ConsoleColor::BOLD . "สถานะ Migrations" . ConsoleColor::RESET . "\n";
            echo ConsoleColor::CYAN . "================" . ConsoleColor::RESET . "\n\n";

            $status = $runner->status();

            if (empty($status)) {
                $this->warning("ไม่พบ migrations");
                return;
            }

            foreach ($status as $migration) {
                $ran = $migration['ran'] ? ConsoleColor::GREEN . '[OK]' . ConsoleColor::RESET : ConsoleColor::RED . '[X]' . ConsoleColor::RESET;
                $batch = $migration['batch'] ? ConsoleColor::YELLOW . " (batch {$migration['batch']})" . ConsoleColor::RESET : '';
                echo "{$ran} {$migration['migration']}{$batch}\n";
            }
        } catch (\Exception $e) {
            $this->error("เกิดข้อผิดพลาด: " . $e->getMessage());
            exit(1);
        }
        
        echo "\n";
    }
}
