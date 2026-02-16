<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\ConsoleColor;
use PDO;

class DbTableCommand extends BaseCommand
{
    public function name(): string
    {
        return 'db:table';
    }

    protected function execute(array $args): void
    {
        if (empty($args[0])) {
            $this->error("กรุณาระบุชื่อตาราง");
            $this->info("ตัวอย่าง: php console db:table users");
            return;
        }

        $tableName = $args[0];
        $this->info("กำลังแสดงโครงสร้างของตาราง '{$tableName}'...");

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

            $stmt = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
            if ($stmt->rowCount() === 0) {
                $this->error("ไม่พบตาราง '{$tableName}'");
                return;
            }

            $stmt = $pdo->query("DESCRIBE `{$tableName}`");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->query("SHOW INDEX FROM `{$tableName}`");
            $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->query("SELECT COUNT(*) FROM `{$tableName}`");
            $rowCount = $stmt->fetchColumn();

            echo "\n" . ConsoleColor::GREEN . "[TABLE] {$tableName}" . ConsoleColor::RESET . "\n";
            echo ConsoleColor::GRAY . "จำนวนแถว: {$rowCount}" . ConsoleColor::RESET . "\n\n";

            echo ConsoleColor::YELLOW . "คอลัมน์:" . ConsoleColor::RESET . "\n";
            echo str_repeat("─", 90) . "\n";
            echo str_pad("Column", 25) . str_pad("Type", 25) . str_pad("Null", 8) . str_pad("Key", 8) . "Default\n";
            echo str_repeat("─", 90) . "\n";

            foreach ($columns as $column) {
                $field = strlen($column['Field']) > 24 ? substr($column['Field'], 0, 21) . '...' : $column['Field'];
                $type = strlen($column['Type']) > 24 ? substr($column['Type'], 0, 21) . '...' : $column['Type'];
                $default = $column['Default'] ?? 'NULL';
                $default = strlen($default) > 20 ? substr($default, 0, 17) . '...' : $default;
                $key = $column['Key'] ?: '-';

                echo str_pad($field, 25) . str_pad($type, 25) . str_pad($column['Null'], 8) . str_pad($key, 8) . $default . "\n";
            }

            echo str_repeat("─", 90) . "\n";

            if (!empty($indexes)) {
                echo "\n" . ConsoleColor::YELLOW . "Indexes:" . ConsoleColor::RESET . "\n";
                echo str_repeat("─", 90) . "\n";

                $indexGroups = [];
                foreach ($indexes as $index) {
                    $indexGroups[$index['Key_name']][] = $index['Column_name'];
                }

                echo str_pad("Index Name", 40) . "Columns\n";
                echo str_repeat("─", 90) . "\n";

                foreach ($indexGroups as $indexName => $cols) {
                    $colsText = implode(', ', $cols);
                    $nameDisplay = strlen($indexName) > 39 ? substr($indexName, 0, 36) . '...' : $indexName;
                    $colsText = strlen($colsText) > 45 ? substr($colsText, 0, 42) . '...' : $colsText;
                    echo str_pad($nameDisplay, 40) . $colsText . "\n";
                }

                echo str_repeat("─", 90) . "\n";
            }

            echo "\n";
            $this->success("แสดงโครงสร้างตารางเรียบร้อยแล้ว \n");
        } catch (\Exception $e) {
            $this->error("เกิดข้อผิดพลาด: " . $e->getMessage() . "\n");
            exit(1);
        }
    }
}
