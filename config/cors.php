<?php
/**
 * config/cors.php
 * 
 * จุดประสงค์: จัดการการตั้งค่าที่เกี่ยวข้องกับ Cross-Origin Resource Sharing (CORS) เช่น รายการโดเมนที่อนุญาตให้เข้าถึงทรัพยากรของแอปพลิเคชันจากแหล่งที่มาที่แตกต่างกัน
 * 
 * ตัวอย่างการใช้งาน:
 * ```php
 * $allowedOrigins = config('cors.allowed_origins');
 * ```
 */
require_once __DIR__ . '/env.php';

$env = env('APP_ENV', 'development', 'string');
$origins = array_filter(env('CORS_ALLOWED_ORIGINS', [], 'array'), 'strlen');

if (empty($origins) && $env !== 'production') {
    $origins = ['http://localhost:3000'];
}

return [
    'allowed_origins' => $origins,
];
