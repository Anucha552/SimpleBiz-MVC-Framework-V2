<?php
/**
 * Database Seeder Runner
 * 
 * จุดประสงค์: รัน seeders ทั้งหมด
 */

require_once __DIR__ . '/../vendor/autoload.php';

// โหลดตัวแปรสภาพแวดล้อม
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

echo "\n";
echo "╔════════════════════════════════════════╗\n";
echo "║   SimpleBiz MVC - Database Seeder    ║\n";
echo "╚════════════════════════════════════════╝\n";
echo "\n";

// รายการ Seeders ที่จะรัน (เรียงตามลำดับที่ต้องการ)
$seeders = [
    'Database\Seeders\CategorySeeder',
    'Database\Seeders\UserSeeder',
    'Database\Seeders\ProductSeeder',
];

try {
    foreach ($seeders as $seederClass) {
        if (!class_exists($seederClass)) {
            echo "⚠️  Warning: Seeder class {$seederClass} not found. Skipping...\n";
            continue;
        }
        
        $seeder = new $seederClass();
        $seeder->run();
        echo "\n";
    }
    
    echo "✅ All seeders completed successfully!\n\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n\n";
    exit(1);
}
