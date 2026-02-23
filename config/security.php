<?php
/**
 * config/security.php
 * 
 * จุดประสงค์: จัดการการตั้งค่าที่เกี่ยวข้องกับความปลอดภัยของแอปพลิเคชัน เช่น นโยบายความปลอดภัยของเนื้อหา (Content Security Policy - CSP) การใช้ HTTP Strict Transport Security (HSTS) และการตั้งค่าอื่นๆ ที่เกี่ยวข้องกับการรักษาความปลอดภัยของแอปพลิเคชัน
 *  
 * ตัวอย่างการใช้งาน:
 * ```php
 * $csp = config('security.csp');
 * ```
 */

require_once __DIR__ . '/env.php';

$env = env('APP_ENV', 'development', 'string');
$hstsDefault = ($env === 'production');

return [
    'csp' => env('SECURITY_CSP', '', 'string'),
    'hsts' => env('SECURITY_HSTS', $hstsDefault, 'bool'),
    'hsts_max_age' => env('SECURITY_HSTS_MAX_AGE', 15552000, 'int'),
    'hsts_include_subdomains' => env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', false, 'bool'),
    'hsts_preload' => env('SECURITY_HSTS_PRELOAD', false, 'bool'),
];
