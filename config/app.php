<?php
/**
 * config/app.php
 *
 * จุดประสงค์: จัดการการตั้งค่าทั่วไปของแอปพลิเคชัน เช่น ชื่อแอปพลิเคชัน สภาพแวดล้อม การดีบัก URL และเขตเวลา
 *
 * ตัวอย่างการใช้งาน:
 * ```php
 * $appName = config('app.key');
 * ```
 */

require_once __DIR__ . '/env.php';

$env = env('APP_ENV', 'development', 'string');
$debug = env('APP_DEBUG', true, 'bool');
if ($env === 'production') {
    $debug = false;
}

return [
    'name' => env('APP_NAME', 'SimpleBiz MVC Framework V2', 'string'),
    'env' => $env,
    'debug' => $debug,
    'url' => env('APP_URL', 'http://localhost', 'string'),
    'timezone' => env('APP_TIMEZONE', 'UTC', 'string'),
    'trusted_proxies' => array_values(array_filter(
        array_map('trim', env('APP_TRUSTED_PROXIES', [], 'array')),
        'strlen'
    )),
];
