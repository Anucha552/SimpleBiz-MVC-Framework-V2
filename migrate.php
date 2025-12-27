#!/usr/bin/env php
<?php
/**
 * Migration CLI Tool
 * 
 * การใช้งาน:
 *   php migrate.php up                    - รัน migrations ทั้งหมด
 *   php migrate.php up --path=core        - รันเฉพาะ core module
 *   php migrate.php down                  - rollback batch ล่าสุด
 *   php migrate.php rollback 3            - rollback 3 batches
 *   php migrate.php status                - แสดงสถานะ migrations
 *   php migrate.php fresh                 - ลบทุกอย่างและรันใหม่
 *   php migrate.php create <name>         - สร้าง migration ใหม่
 *   php migrate.php modules               - แสดงรายการ modules
 */

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\MigrationRunner;

// ตรวจสอบ command
$command = $argv[1] ?? 'help';
$arg = $argv[2] ?? null;

// คำสั่งที่ไม่ต้องเชื่อม database
if ($command === 'modules' || $command === 'help') {
    if ($command === 'modules') {
        echo "Available Modules\n";
        echo "=================\n\n";
        showModules();
    } else {
        showHelp();
    }
    exit(0);
}

// แยก --path option
$modulePath = null;
foreach ($argv as $param) {
    if (strpos($param, '--path=') === 0) {
        $modulePath = substr($param, 7);
    }
}

// สร้าง MigrationRunner
try {
    $runner = new MigrationRunner();
    if ($modulePath) {
        $runner->setModule($modulePath);
        echo "Module: $modulePath\n";
    }
} catch (\Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}

// รันคำสั่ง
switch ($command) {
    case 'up':
    case 'migrate':
        echo "Running migrations...\n";
        echo "-------------------\n";
        $result = $runner->run();
        echo "\n" . $result['message'] . "\n";
        break;
        
    case 'down':
    case 'rollback':
        $steps = is_numeric($arg) ? (int) $arg : 1;
        echo "Rolling back $steps batch(es)...\n";
        echo "-------------------\n";
        $result = $runner->rollback($steps);
        echo "\n" . $result['message'] . "\n";
        break;
        
    case 'fresh':
        echo "Refreshing database...\n";
        echo "-------------------\n";
        $result = $runner->fresh();
        echo "\n" . $result['message'] . "\n";
        break;
        
    case 'status':
        echo "Migration Status\n";
        echo "================\n\n";
        $status = $runner->status();
        
        if (empty($status)) {
            echo "No migrations found.\n";
        } else {
            foreach ($status as $migration) {
                $ran = $migration['ran'] ? '✓' : '✗';
                $batch = $migration['batch'] ? " (batch {$migration['batch']})" : '';
                echo "$ran {$migration['migration']}$batch\n";
            }
        }
        break;
        
    case 'create':
        if (!$arg) {
            echo "Error: Migration name required.\n";
            echo "Usage: php migrate.php create <migration_name>\n";
            exit(1);
        }
        
        createMigration($arg);
        break;
        
    default:
        showHelp();
        break;
}

/**
 * สร้าง migration file ใหม่
 */
function createMigration(string $name): void
{
    $timestamp = date('Y_m_d_His');
    $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    $filename = "{$timestamp}_{$name}.php";
    $path = __DIR__ . '/database/migrations/' . $filename;
    
    $template = <<<PHP
<?php
/**
 * Migration: $className
 */

namespace Database\Migrations;

use App\Core\Migration;

class $className extends Migration
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
        echo "✓ Created migration: $filename\n";
        echo "  Path: $path\n";
    } else {
        echo "✗ Failed to create migration file.\n";
    }
}

/**
 * แสดงคำแนะนำการใช้งาน
 */
function showHelp(): void
{
    echo <<<HELP

SimpleBiz Migration Tool
========================

Usage:
  php migrate.php <command> [options]

Commands:
  up, migrate           Run all pending migrations
  down, rollback [n]    Rollback the last [n] batches (default: 1)
  fresh                 Drop all tables and re-run migrations
  status                Show migration status
  create <name>         Create a new migration
  modules               Show available modules

Options:
  --path=<module>       Run migrations for specific module only

Examples:
  php migrate.php up --path=core         Run core module migrations
  php migrate.php up --path=ecommerce    Run ecommerce module migrations
  php migrate.php status                 Show all migrations status
  php migrate.php modules                List all modules

HELP;
}

/**
 * แสดงรายการ modules ที่มี
 */
function showModules(): void
{
    $basePath = __DIR__ . '/database/migrations';
    $directories = glob($basePath . '/*', GLOB_ONLYDIR);
    
    if (empty($directories)) {
        echo "No modules found.\n";
        return;
    }
    
    foreach ($directories as $dir) {
        $moduleName = basename($dir);
        $files = glob($dir . '/*.php');
        $count = count($files);
        
        echo "- $moduleName ($count migration" . ($count != 1 ? 's' : '') . ")\n";
    }
    
    echo "\nUsage: php migrate.php up --path=<module>\n";
}
