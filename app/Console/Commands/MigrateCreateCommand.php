<?php

declare(strict_types=1);

namespace App\Console\Commands;

class MigrateCreateCommand extends BaseCommand
{
    public function name(): string
    {
        return 'migrate:create';
    }

    public function aliases(): array
    {
        return ['m:c'];
    }

    protected function execute(array $args): void
    {
        if (empty($args)) {
            $this->error("กรุณาระบุชื่อ migration");
            $this->info("วิธีใช้: php console migrate:create <migration_name> [module_name]");
            return;
        }

        $name = $args[0];
        $module = $args[1] ?? null;
        $timestamp = date('Y_m_d_His');
        $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
        $filename = "{$timestamp}_{$name}_table.php";

        // ตรวจสอบ module
        if ($module) {
            $modulePath = $this->path('database/migrations/' . $module);
            
            // สร้างโฟลเดอร์สำหรับ module หากยังไม่มี
            if (!is_dir($modulePath)) {
                if (!mkdir($modulePath, 0777, true)) {
                    $this->error("ไม่สามารถสร้างโฟลเดอร์ migration ได้ สำหรับ module: {$module}");
                    return;
                }
            }

            // กำหนด path และ namespace สำหรับ module
            $path = $modulePath . '/' . $filename;
            $namespace = "Database\\Migrations";
        } else {
            $path = $this->path('database/migrations/' . $filename);
            $namespace = "Database\\Migrations";
        }

        $template = <<<PHP
<?php
/**
 * Migration: {$className}
 */

namespace {$namespace};

use App\Core\Migration;
use App\Core\Blueprint;

class {$className} extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        // ตัวอย่างการสร้างตารางแบบเขียน SQL ด้วยตนเอง
        // \$sql = "
        //     CREATE TABLE IF NOT EXISTS example_table (
        //         id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        //         name VARCHAR(255) NOT NULL,
        //         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        //     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        // ";
        // \$this->execute(\$sql);

        // ตัวอย่างการสร้างตารางแบบใช้ Schema Builder
        \$this->createTable('{$name}', function(Blueprint \$table) {
            \$table->increments('id')->comment('รหัส');
            \$table->string('name', 255)->comment('ชื่อ');
            \$table->timestamp('created_at')->comment('วันที่สร้าง');
        });

    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        // ตัวอย่างการลบตารางแบบเขียน SQL ด้วยตนเอง
        // \$this->execute("DROP TABLE IF EXISTS example_table");

        // ตัวอย่างการลบตารางแบบใช้ Schema Builder
        \$this->dropTable('{$name}');
    }
}
PHP;

        // สร้างไฟล์ migration
        if (file_put_contents($path, $template)) {
            $this->success("สร้าง migration สำเร็จ: {$filename}");
            if ($module) {
                $this->info("ที่อยู่: database/migrations/{$module}/{$filename}");
            } else {
                $this->info("ที่อยู่: database/migrations/{$filename}");
            }
        } else {
            $this->error("สร้างไฟล์ migration ไม่สำเร็จ");
        }
    }
}
