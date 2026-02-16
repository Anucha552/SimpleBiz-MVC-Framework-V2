<?php

declare(strict_types=1);

namespace App\Console\Commands;

class SeedCommand extends BaseCommand
{
    public function name(): string
    {
        return 'seed';
    }

    protected function execute(array $args): void
    {
        if (!$this->checkDatabaseConnection()) {
            exit(1);
        }

        $this->info("กำลังรัน database seeders...");
        echo "\n";

        $seeders = [];
        $requestedSeeder = null;
        foreach ($args as $arg) {
            if (is_string($arg) && str_starts_with($arg, '--class=')) {
                $requestedSeeder = trim(substr($arg, strlen('--class=')));
                break;
            }
        }
        if ($requestedSeeder === null && !empty($args[0]) && is_string($args[0]) && !str_starts_with($args[0], '--')) {
            $requestedSeeder = trim($args[0]);
        }

        if ($requestedSeeder !== null && $requestedSeeder !== '') {
            if (!str_ends_with($requestedSeeder, 'Seeder')) {
                $requestedSeeder .= 'Seeder';
            }
            $seeders = ['Database\\Seeders\\' . $requestedSeeder];
        } else {
            $seederDir = $this->path('database/seeders');
            if (!is_dir($seederDir)) {
                $this->warning("ไม่พบโฟลเดอร์ database/seeders");
                $this->info("สร้าง Seeder ใหม่ได้ด้วย: php console make:seeder UserSeeder");
                return;
            }

            $files = glob($seederDir . '/*Seeder.php') ?: [];
            sort($files, SORT_STRING);

            foreach ($files as $file) {
                $classBase = basename($file, '.php');
                if ($classBase === '' || $classBase === 'Seeder') {
                    continue;
                }
                $seeders[] = 'Database\\Seeders\\' . $classBase;
            }
        }

        if (empty($seeders)) {
            $this->warning("ไม่พบ Seeder ที่จะรัน");
            $this->info("สร้าง Seeder ใหม่ได้ด้วย: php console make:seeder UserSeeder");
            return;
        }

        try {
            $ran = 0;
            $skipped = 0;
            foreach ($seeders as $seederClass) {
                if (!class_exists($seederClass)) {
                    $this->warning("ไม่พบ Seeder class {$seederClass} ข้าม...");
                    $skipped++;
                    continue;
                }

                $seeder = new $seederClass();
                $seeder->run();
                $ran++;
                echo "\n";
            }

            if ($ran === 0) {
                $this->warning("ไม่มี Seeder ที่ถูกรัน (ข้ามทั้งหมด {$skipped})");
                return;
            }

            if ($skipped > 0) {
                $this->success("รัน seeders เสร็จสมบูรณ์! (รัน {$ran}, ข้าม {$skipped})");
            } else {
                $this->success("รัน seeders เสร็จสมบูรณ์! (รัน {$ran})");
            }
            echo "\n";
        } catch (\Exception $e) {
            $this->error("เกิดข้อผิดพลาด: " . $e->getMessage());
            $this->error("ไฟล์: " . $e->getFile());
            $this->error("บรรทัด: " . $e->getLine());
            exit(1);
        }
    }
}
