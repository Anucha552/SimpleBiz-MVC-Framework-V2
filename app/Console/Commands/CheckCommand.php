<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\ConsoleColor;

class CheckCommand extends BaseCommand
{
    public function name(): string
    {
        return 'check';
    }

    protected function execute(array $args): void
    {
        echo ConsoleColor::CYAN . ConsoleColor::BOLD . "\n[CHECK] SimpleBiz Framework - ตรวจสอบสภาพแวดล้อม\n" . ConsoleColor::RESET;
        echo ConsoleColor::WHITE . "กำลังตรวจสอบความพร้อมของระบบ...\n" . ConsoleColor::RESET;
        echo "\n";

        $passed = 0;
        $failed = 0;
        $warnings = 0;

        echo ConsoleColor::YELLOW . "━━━ PHP Environment ━━━\n" . ConsoleColor::RESET;
        $phpVersion = PHP_VERSION;
        $requiredVersion = '8.0.0';
        if (version_compare($phpVersion, $requiredVersion, '>=')) {
            $this->success("PHP Version: {$phpVersion} (ต้องการ >= {$requiredVersion})");
            $passed++;
        } else {
            $this->error("PHP Version: {$phpVersion} (ต้องการ >= {$requiredVersion})");
            $failed++;
        }

        $requiredExtensions = ['pdo', 'pdo_mysql', 'pdo_sqlite', 'mbstring', 'json', 'openssl', 'fileinfo'];
        $optionalExtensions = ['curl', 'gd', 'zip'];

        echo "\n";
        foreach ($requiredExtensions as $ext) {
            if (extension_loaded($ext)) {
                $this->success("Extension: {$ext}");
                $passed++;
            } else {
                $this->error("Extension: {$ext} (จำเป็น)");
                $failed++;
            }
        }

        foreach ($optionalExtensions as $ext) {
            if (extension_loaded($ext)) {
                $this->success("Extension: {$ext} (แนะนำ)");
                $passed++;
            } else {
                $this->warning("Extension: {$ext} (แนะนำ - ไม่บังคับ)");
                $warnings++;
            }
        }

        echo "\n" . ConsoleColor::YELLOW . "━━━ Dependencies ━━━\n" . ConsoleColor::RESET;
        if (file_exists($this->path('vendor/autoload.php'))) {
            $this->success("Composer dependencies: ติดตั้งแล้ว");
            $passed++;
        } else {
            $this->error("Composer dependencies: ยังไม่ติดตั้ง");
            $this->info("  กรุณารัน: composer install");
            $failed++;
        }

        if (file_exists($this->path('composer.json'))) {
            $this->success("composer.json: พบแล้ว");
            $passed++;
        } else {
            $this->error("composer.json: ไม่พบ");
            $failed++;
        }

        echo "\n" . ConsoleColor::YELLOW . "━━━ Files & Directories ━━━\n" . ConsoleColor::RESET;

        $requiredDirs = [
            'app' => 'โฟลเดอร์ Application',
            'config' => 'โฟลเดอร์ Configuration',
            'database' => 'โฟลเดอร์ Database',
            'public' => 'โฟลเดอร์ Public',
            'routes' => 'โฟลเดอร์ Routes',
            'storage' => 'โฟลเดอร์ Storage',
        ];

        foreach ($requiredDirs as $dir => $desc) {
            if (is_dir($this->path($dir))) {
                $this->success("{$desc} ({$dir}/)");
                $passed++;
            } else {
                $this->error("{$desc} ({$dir}/) - ไม่พบ");
                $failed++;
            }
        }

        echo "\n" . ConsoleColor::YELLOW . "━━━ Permissions ━━━\n" . ConsoleColor::RESET;

        $writableDirs = ['storage', 'storage/cache', 'storage/logs'];
        foreach ($writableDirs as $dir) {
            $path = $this->path($dir);
            if (is_dir($path) && is_writable($path)) {
                $this->success("{$dir}/ - เขียนได้");
                $passed++;
            } else {
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                    if (is_writable($path)) {
                        $this->success("{$dir}/ - สร้างและตั้งค่าสิทธิ์แล้ว");
                        $passed++;
                    } else {
                        $this->error("{$dir}/ - สร้างไม่ได้หรือเขียนไม่ได้");
                        $failed++;
                    }
                } else {
                    $this->error("{$dir}/ - เขียนไม่ได้");
                    $this->info("  chmod 755 {$dir}");
                    $failed++;
                }
            }
        }

        echo "\n" . ConsoleColor::YELLOW . "━━━ Configuration ━━━\n" . ConsoleColor::RESET;

        if (file_exists($this->path('.env'))) {
            $this->success(".env: พบแล้ว");
            $passed++;

            $envContent = file_get_contents($this->path('.env'));
            if (strpos($envContent, 'APP_KEY=') !== false && preg_match('/APP_KEY=.{16,}/', $envContent)) {
                $this->success("APP_KEY: ตั้งค่าแล้ว");
                $passed++;
            } else {
                $this->warning("APP_KEY: ยังไม่ตั้งค่าหรือสั้นเกินไป");
                $this->info("  รัน: php console key:generate");
                $warnings++;
            }
        } else {
            $this->warning(".env: ไม่พบ");
            $this->info("  รัน: php console setup หรือ copy .env.example");
            $warnings++;
        }

        if (file_exists($this->path('.env.example'))) {
            $this->success(".env.example: พบแล้ว");
            $passed++;
        } else {
            $this->warning(".env.example: ไม่พบ");
            $warnings++;
        }

        if (file_exists($this->path('.env'))) {
            echo "\n" . ConsoleColor::YELLOW . "━━━ Database ━━━\n" . ConsoleColor::RESET;
            try {
                $config = [];
                $lines = file($this->path('.env'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) {
                        continue;
                    }
                    if (strpos($line, '=') !== false) {
                        [$key, $value] = explode('=', $line, 2);
                        $config[trim($key)] = trim($value);
                    }
                }

                if (isset($config['DB_CONNECTION'], $config['DB_DATABASE'])) {
                    $this->info("Database Config: พบการตั้งค่า");
                    $this->info("  Driver: " . ($config['DB_CONNECTION'] ?? 'ไม่ระบุ'));
                    $this->info("  Host: " . ($config['DB_HOST'] ?? 'ไม่ระบุ'));
                    $this->info("  Database/Path: " . ($config['DB_DATABASE'] ?? 'ไม่ระบุ'));
                    $passed++;
                } else {
                    $this->warning("Database Config: ยังไม่ครบถ้วน");
                    $warnings++;
                }
            } catch (\Exception $e) {
                $this->warning("Database Config: ไม่สามารถอ่านได้");
                $warnings++;
            }
        }

        echo "\n" . ConsoleColor::YELLOW . "━━━ Routes ━━━\n" . ConsoleColor::RESET;
        if (file_exists($this->path('routes/web.php'))) {
            $this->success("routes/web.php: พบแล้ว");
            $passed++;
        } else {
            $this->error("routes/web.php: ไม่พบ");
            $failed++;
        }

        if (file_exists($this->path('routes/api.php'))) {
            $this->success("routes/api.php: พบแล้ว");
            $passed++;
        } else {
            $this->warning("routes/api.php: ไม่พบ (ไม่บังคับ)");
            $warnings++;
        }

        echo "\n" . ConsoleColor::CYAN . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" . ConsoleColor::RESET;
        echo ConsoleColor::BOLD . "สรุปผลการตรวจสอบ:\n" . ConsoleColor::RESET;
        echo ConsoleColor::GREEN . "  [OK] ผ่าน: {$passed} รายการ\n" . ConsoleColor::RESET;
        if ($warnings > 0) {
            echo ConsoleColor::YELLOW . "  [!] คำเตือน: {$warnings} รายการ\n" . ConsoleColor::RESET;
        }
        if ($failed > 0) {
            echo ConsoleColor::RED . "  [X] ล้มเหลว: {$failed} รายการ\n" . ConsoleColor::RESET;
        }
        echo "\n";

        if ($failed > 0) {
            echo ConsoleColor::RED . "[ERROR] ระบบยังไม่พร้อมใช้งาน\n" . ConsoleColor::RESET;
            echo ConsoleColor::WHITE . "กรุณาแก้ไขปัญหาที่พบก่อนเริ่มใช้งาน\n" . ConsoleColor::RESET;
            exit(1);
        }

        if ($warnings > 0) {
            echo ConsoleColor::YELLOW . "[WARNING] ระบบพร้อมใช้งาน แต่มีข้อแนะนำบางอย่าง\n" . ConsoleColor::RESET;
            echo ConsoleColor::WHITE . "แนะนำให้แก้ไขตามคำเตือนเพื่อประสิทธิภาพสูงสุด\n" . ConsoleColor::RESET;
            return;
        }

        echo ConsoleColor::GREEN . "[READY] ระบบพร้อมใช้งานทั้งหมด!\n" . ConsoleColor::RESET;
        echo "\n";
    }
}
