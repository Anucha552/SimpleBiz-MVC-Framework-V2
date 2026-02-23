<?php
/**
 * config/mail.php
 *  
 * จุดประสงค์: จัดการการตั้งค่าที่เกี่ยวข้องกับระบบส่งอีเมล เช่น ที่อยู่อีเมลผู้ส่ง ชื่อผู้ส่ง โฮสต์พนักงานส่งอีเมล พอร์ต ชื่อผู้ใช้ รหัสผ่าน และประเภทการเข้ารหัสที่ใช้ในการส่งอีเมล
 * 
 * ตัวอย่างการใช้งาน:
 * ```php
 * $mailConfig = $config['mail'];
 */

require_once __DIR__ . '/env.php';

return [
    'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@simplebiz.local', 'string'),
    'from_name' => env('MAIL_FROM_NAME', 'SimpleBiz MVC', 'string'),
    'host' => env('MAIL_HOST', 'localhost', 'string'),
    'port' => env('MAIL_PORT', 587, 'int'),
    'username' => env('MAIL_USERNAME', '', 'string'),
    'password' => env('MAIL_PASSWORD', '', 'string'),
    'encryption' => env('MAIL_ENCRYPTION', 'tls', 'string'),
];
