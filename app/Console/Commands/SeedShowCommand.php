<?php
/**
 * class SeedShowCommand
 * จุดประสงค์: คำสั่งนี้ถูกออกแบบมาเพื่อแสดงรายการ Seeder ที่มีอยู่ในโปรเจค โดยจะค้นหาไฟล์ที่อยู่ในโฟลเดอร์ database/seeders และแสดงชื่อของ Seeder แต่ละตัวที่พบ
 */

declare(strict_types=1);

namespace App\Console\Commands;

class SeedShowCommand extends BaseCommand
{
    public function name(): string
    {
        return 'seed:show';
    }

    protected function execute(array $args): void
    {
        // ตรวจสอบการเชื่อมต่อฐานข้อมูลก่อนดำเนินการ
        if (!$this->checkDatabaseConnection()) {
            exit(1);
        }

        $this->infoWhite("กำลังดึงข้อมูล seeders ที่มีอยู่...\n");
        
        $seederDir = $this->path('database/seeders');

        // ตรวจสอบว่าโฟลเดอร์ database/seeders มีอยู่หรือไม่
        if (!is_dir($seederDir)) {
            $this->warning("ไม่พบโฟลเดอร์ database/seeders");
            $this->info("สร้าง Seeder ใหม่ได้ด้วย: php console make:seeder UserSeeder");
            return;
        }

        // ค้นหาไฟล์ที่ลงท้ายด้วย "Seeder.php" ในโฟลเดอร์ database/seeders
        $files = glob($seederDir . '/*Seeder.php') ?: [];
        sort($files, SORT_STRING);

        $countSeeders = count($files);
        $this->info("จำนวน Seeder ที่มีอยู่: " . $countSeeders);

        for ($i = 0; $i < $countSeeders; $i++) {
            $file = $files[$i];
            $seederName = basename($file, '.php');
            $this->info("  " . ($i + 1) . ". Seeder: {$seederName}");
        }

        echo "\n";
    }
}