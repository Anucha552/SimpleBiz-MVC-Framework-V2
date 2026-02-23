<?php
/**
 * config/maintenance.php
 *
 * จุดประสงค์: จัดการการตั้งค่าที่เกี่ยวข้องกับโหมดบำรุงรักษา (Maintenance Mode) เช่น การเปิดหรือปิดโหมดบำรุงรักษา รายการที่อยู่ IP ที่อนุญาตให้เข้าถึงแอปพลิเคชันในขณะที่อยู่ในโหมดบำรุงรักษา และระยะเวลาที่แนะนำให้ผู้ใช้ลองเข้าถึงแอปพลิเคชันอีกครั้ง
 *
 * ตัวอย่างการใช้งาน:
 * ```php
 * $maintenanceEnabled = config('maintenance.enabled');
 * ```
 */

require_once __DIR__ . '/env.php';

return [
    'enabled' => env('MAINTENANCE_MODE', false, 'bool'),
    'allowed_ips' => array_values(array_filter(
        array_map('trim', env('MAINTENANCE_ALLOWED_IPS', [], 'array')),
        'strlen'
    )),
    'retry_after' => env('MAINTENANCE_RETRY_AFTER', null, 'string'),
];
