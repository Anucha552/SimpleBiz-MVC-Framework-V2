<?php
/**
 * config/api.php
 * 
 * จุดประสงค์: จัดการการตั้งค่าที่เกี่ยวข้องกับ API เช่น คีย์ API ที่อนุญาตให้เข้าถึง API ของแอปพลิเคชัน
 * 
 * ตัวอย่างการใช้งาน:
 * ```php
 * $apiKeys = config('api.keys');
 * ```
 */

require_once __DIR__ . '/env.php';

$keys = env('API_KEYS', [], 'array');
$key = env('API_KEY', '', 'string');
if ($key !== '') {
    $keys[] = $key;
}

return [
    'keys' => array_values(array_filter(array_unique($keys), 'strlen')),
];
