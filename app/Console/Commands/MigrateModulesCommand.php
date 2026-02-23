<?php
/**
 * MigrateModulesCommand
 *
 * จุดประสงค์: เป็นคำสั่ง CLI ที่ใช้สำหรับแสดงรายการ modules ที่มีอยู่ในโปรเจกต์ โดยจะแสดงชื่อ module และจำนวนไฟล์ migration ที่อยู่ในแต่ละ module เพื่อให้ผู้ใช้สามารถตรวจสอบและจัดการกับ modules ได้อย่างง่ายดายผ่านทางคอนโซล
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\ConsoleColor;

class MigrateModulesCommand extends BaseCommand
{
    public function name(): string
    {
        return 'migrate:modules';
    }

    protected function execute(array $args): void
    {
        echo ConsoleColor::CYAN . ConsoleColor::BOLD . "Modules ที่มี" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::CYAN . "=================" . ConsoleColor::RESET . "\n\n";

        $basePath = $this->path('database/migrations');
        $directories = glob($basePath . '/*', GLOB_ONLYDIR);

        if (empty($directories)) {
            $this->warning("ไม่พบ modules");
            return;
        }

        foreach ($directories as $dir) {
            $moduleName = basename($dir);
            $files = glob($dir . '/*.php');
            $count = count($files);

            echo ConsoleColor::GREEN . "- " . ConsoleColor::WHITE . "{$moduleName} " . ConsoleColor::YELLOW . "({$count} migration" . ($count !== 1 ? 's' : '') . ")" . ConsoleColor::RESET . "\n";
        }

        echo "\n";
        $this->info("วิธีใช้: php console migrate --path=<module>");
        echo "\n";
    }
}
