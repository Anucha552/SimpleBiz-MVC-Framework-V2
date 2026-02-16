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
            $this->info("วิธีใช้: php console migrate:create <migration_name>");
            return;
        }

        $name = $args[0];
        $timestamp = date('Y_m_d_His');
        $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
        $filename = "{$timestamp}_{$name}.php";
        $path = $this->path('database/migrations/' . $filename);

        $template = <<<PHP
<?php
/**
 * Migration: {$className}
 */

namespace Database\Migrations;

use App\Core\Migration;

class {$className} extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        \$sql = "
            CREATE TABLE IF NOT EXISTS example_table (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";

        \$this->execute(\$sql);
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        \$this->execute("DROP TABLE IF EXISTS example_table");
    }
}
PHP;

        if (file_put_contents($path, $template)) {
            $this->success("สร้าง migration สำเร็จ: {$filename}");
            $this->info("ที่อยู่: database/migrations/{$filename}");
        } else {
            $this->error("สร้างไฟล์ migration ไม่สำเร็จ");
        }
    }
}
