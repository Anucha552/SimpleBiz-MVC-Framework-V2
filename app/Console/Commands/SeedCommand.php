<?php
/**
 * class SeedCommand
 * จุดประสงค์: คำสั่งนี้ถูกออกแบบมาเพื่อรัน Seeder ที่มีอยู่ในโปรเจค โดยจะค้นหาไฟล์ที่อยู่ในโฟลเดอร์ database/seeders และรัน Seeder แต่ละตัวที่พบ หรือรัน Seeder ที่ระบุผ่านพารามิเตอร์
 */

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
        // ตรวจสอบการเชื่อมต่อฐานข้อมูลก่อนดำเนินการ
        if (!$this->checkDatabaseConnection()) {
            exit(1);
        }

        $this->info("กำลังรัน database seeders...");
        echo "\n";

        $seeders = []; // ตัวแปรสำหรับเก็บรายชื่อ Seeder ที่จะรัน
        $requestedSeeder = null; // ตัวแปรสำหรับเก็บชื่อ Seeder ที่ถูกระบุผ่านพารามิเตอร์ (ถ้ามี)

        // ตรวจสอบอาร์กิวเมนต์เพื่อหาชื่อ Seeder ที่ถูกระบุผ่านพารามิเตอร์
        foreach ($args as $arg) {
            // ตรวจสอบรูปแบบ --class=SeederName
            if (is_string($arg) && str_starts_with($arg, '--class=')) {
                $requestedSeeder = trim(substr($arg, strlen('--class=')));
                break;
            }
        }
        
        // ถ้าไม่พบรูปแบบ --class=SeederName ให้ตรวจสอบอาร์กิวเมนต์แรกที่ไม่ใช่ option ว่าเป็นชื่อ Seeder หรือไม่
        if ($requestedSeeder === null && !empty($args[0]) && is_string($args[0]) && !str_starts_with($args[0], '--')) {
            $requestedSeeder = trim($args[0]);
        }

        // ถ้ามีการระบุชื่อ Seeder ผ่านพารามิเตอร์ ให้รัน Seeder นั้นเท่านั้น
        if ($requestedSeeder !== null && $requestedSeeder !== '') {
            // ตรวจสอบว่า Seeder ที่ระบุมีรูปแบบที่ถูกต้องหรือไม่ (ควรลงท้ายด้วย "Seeder")
            if (!str_ends_with($requestedSeeder, 'Seeder')) {
                $requestedSeeder .= 'Seeder';
            }

            // สร้างชื่อ class เต็มรูปแบบของ Seeder
            $seeders = ['Database\\Seeders\\' . $requestedSeeder];
        } else {

            // ถ้าไม่มีการระบุชื่อ Seeder ให้ค้นหา Seeder ทั้งหมดในโฟลเดอร์ database/seeders
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

            // สร้างรายชื่อ class ของ Seeder จากชื่อไฟล์ที่พบ
            foreach ($files as $file) {
                // ดึงชื่อ class จากชื่อไฟล์ (สมมติว่าไฟล์ชื่อ UserSeeder.php จะได้ class UserSeeder)
                $classBase = basename($file, '.php');

                // ข้ามไฟล์ที่ไม่มีชื่อ class หรือชื่อ class เป็น "Seeder" เท่านั้น
                if ($classBase === '' || $classBase === 'Seeder') {
                    continue;
                }

                // สร้างชื่อ class เต็มรูปแบบของ Seeder
                $seeders[] = 'Database\\Seeders\\' . $classBase;
            }
        }

        // ถ้าไม่พบ Seeder ที่จะรัน ให้แสดงข้อความเตือนและคำแนะนำ
        if (empty($seeders)) {
            $this->warning("ไม่พบ Seeder ที่จะรัน");
            $this->info("สร้าง Seeder ใหม่ได้ด้วย: php console make:seeder UserSeeder");
            return;
        }

        try {
            $ran = 0;
            $skipped = 0;

            // รัน Seeder แต่ละตัวที่พบ
            foreach ($seeders as $seederClass) {
                // ตรวจสอบว่า class ของ Seeder มีอยู่จริงหรือไม่
                if (!class_exists($seederClass)) {
                    $this->warning("ไม่พบ Seeder class {$seederClass} ข้าม...");
                    $skipped++;
                    continue;
                }

                // รัน Seeder
                $seeder = new $seederClass();
                $seeder->run();
                $ran++;
                echo "\n";
            }

            // แสดงผลลัพธ์การรัน Seeder
            if ($ran === 0) {
                $this->warning("ไม่มี Seeder ที่ถูกรัน (ข้ามทั้งหมด {$skipped})");
                return;
            }

            // แสดงข้อความสรุปผลการรัน Seeder
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
