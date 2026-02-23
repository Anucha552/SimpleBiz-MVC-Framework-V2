<?php
/**
 * config/database.php
 *  
 * จุดประสงค์: จัดการการตั้งค่าที่เกี่ยวข้องกับการเชื่อมต่อฐานข้อมูล เช่น ชนิดของฐานข้อมูล โฮสต์ พอร์ต ชื่อฐานข้อมูล ชื่อผู้ใช้ รหัสผ่าน และการเข้ารหัสตัวอักษร
 * 
 * ตัวอย่างการใช้งาน:
 * ```php
 * $dbConfig = $config['connection'];
 * ```
 */

require_once __DIR__ . '/env.php';

return [
    'connection' => env('DB_CONNECTION', 'mysql', 'string'),
    'host' => env('DB_HOST', '127.0.0.1', 'string'),
    'port' => env('DB_PORT', '3306', 'string'),
    'database' => env('DB_DATABASE', 'simplebiz_mvc', 'string'),
    'username' => env('DB_USERNAME', 'root', 'string'),
    'password' => env('DB_PASSWORD', '', 'string'),
    'charset' => env('DB_CHARSET', 'utf8mb4', 'string'),
];
