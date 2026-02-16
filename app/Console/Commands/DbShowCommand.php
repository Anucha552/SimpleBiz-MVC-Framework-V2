<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\ConsoleColor;
use App\Core\Logger;
use PDO;

class DbShowCommand extends BaseCommand
{
    public function name(): string
    {
        return 'db:show';
    }

    protected function execute(array $args): void
    {
        $this->info("กำลังแสดงรายการตารางในฐานข้อมูล...");

        try {
            $config = require $this->path('config/database.php');

            if (isset($config['connections'])) {
                $dbConfig = $config['connections'][$config['default']];
            } else {
                $dbConfig = $config;
            }

            $pdo = new PDO(
                "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']}",
                $dbConfig['username'],
                $dbConfig['password']
            );

            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($tables)) {
                $this->warning("ไม่พบตารางในฐานข้อมูล");
                return;
            }

            echo "\n" . ConsoleColor::GREEN . "[DATABASE] ตารางทั้งหมด (" . count($tables) . " ตาราง):" . ConsoleColor::RESET . "\n";
            echo str_repeat("─", 50) . "\n";

            foreach ($tables as $index => $table) {
                $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
                $rowCount = $countStmt->fetchColumn();

                echo ConsoleColor::CYAN . sprintf("%2d. ", $index + 1) . ConsoleColor::WHITE . $table .
                     ConsoleColor::GRAY . " ({$rowCount} แถว)" . ConsoleColor::RESET . "\n";
            }

            echo str_repeat("─", 50) . "\n";
            $this->success("แสดงรายการตารางเรียบร้อยแล้ว \n");
        } catch (\Exception $e) {
            echo "\n";
            echo "┌─ Database Connection Error ─────────────────────────┐\n";
            $this->error("ไม่สามารถเชื่อมต่อฐานข้อมูล: " . $e->getMessage());
            echo "│\n";
            $this->warning("[TIP] คำแนะนำ:");
            $this->info("   1. ตรวจสอบว่าเปิด MySQL/MariaDB แล้วหรือไม่");
            $this->info("   2. ตรวจสอบการตั้งค่าใน .env ไฟล์");
            $this->info("   3. ตรวจสอบว่าสร้าง database แล้วหรือไม่");
            echo "└──────────────────────────────────────────────────────┘\n";
            echo "\n";

            try {
                (new Logger())->error('Database connection failed', [
                    'command' => 'db:show',
                    'error' => $e->getMessage(),
                ]);
            } catch (\Exception $logError) {
                // Ignore logging errors
            }

            exit(1);
        }
    }
}
