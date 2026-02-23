<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\ConsoleColor;
use App\Core\Database;

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
            $db = Database::getInstance();
            $driver = $db->getDriverName();
            if ($driver === 'sqlite') {
                $exists = $db->fetchColumn(
                    "SELECT name FROM sqlite_master WHERE type='table' AND name = :name",
                    ['name' => $tableName]
                );
                if ($exists === false) {
                    $this->error("ไม่พบตาราง '{$tableName}'");
                    return;
                }

                $columns = $db->fetchAll("PRAGMA table_info(`{$tableName}`)");
                $indexList = $db->fetchAll("PRAGMA index_list(`{$tableName}`)");

                $indexes = [];
                foreach ($indexList as $index) {
                    $indexName = $index['name'] ?? '';
                    if ($indexName === '') {
                        continue;
                    }
                    $idxCols = $db->fetchAll("PRAGMA index_info(`{$indexName}`)");
                    foreach ($idxCols as $col) {
                        $indexes[] = [
                            'Key_name' => $indexName,
                            'Column_name' => $col['name'] ?? '',
                        ];
                    }
                }

                $rowCount = $db->fetchColumn("SELECT COUNT(*) FROM `{$tableName}`");
            } else {
                $exists = $db->fetchColumn("SHOW TABLES LIKE :name", ['name' => $tableName]);
                if ($exists === false) {
                    $this->error("ไม่พบตาราง '{$tableName}'");
                    return;
                }

                $columns = $db->fetchAll("DESCRIBE `{$tableName}`");
                $indexes = $db->fetchAll("SHOW INDEX FROM `{$tableName}`");
                $rowCount = $db->fetchColumn("SELECT COUNT(*) FROM `{$tableName}`");
            }

            echo "\n" . ConsoleColor::GREEN . "[TABLE] {$tableName}" . ConsoleColor::RESET . "\n";
            echo ConsoleColor::GRAY . "จำนวนแถว: {$rowCount}" . ConsoleColor::RESET . "\n\n";

            echo ConsoleColor::YELLOW . "คอลัมน์:" . ConsoleColor::RESET . "\n";
            echo str_repeat("─", 90) . "\n";
            echo str_pad("Column", 25) . str_pad("Type", 25) . str_pad("Null", 8) . str_pad("Key", 8) . "Default\n";
            echo str_repeat("─", 90) . "\n";

            foreach ($columns as $column) {
                $fieldName = $column['Field'] ?? $column['name'] ?? '';
                $field = strlen($fieldName) > 24 ? substr($fieldName, 0, 21) . '...' : $fieldName;
                $typeName = $column['Type'] ?? $column['type'] ?? '';
                $type = strlen($typeName) > 24 ? substr($typeName, 0, 21) . '...' : $typeName;
                $defaultValue = $column['Default'] ?? $column['dflt_value'] ?? 'NULL';
                $default = $defaultValue ?? 'NULL';
                $default = strlen($default) > 20 ? substr($default, 0, 17) . '...' : $default;
                $nullValue = $column['Null'] ?? (($column['notnull'] ?? 0) ? 'NO' : 'YES');
                $key = $column['Key'] ?? '-';

                echo str_pad($field, 25) . str_pad($type, 25) . str_pad($nullValue, 8) . str_pad($key, 8) . $default . "\n";
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
