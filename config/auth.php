<?php
/**
 * config/auth.php
 *
 * จุดประสงค์: จัดการการตั้งค่าที่เกี่ยวข้องกับระบบการยืนยันตัวตน (Authentication) เช่น คีย์แอปพลิเคชันสำหรับการเข้ารหัสข้อมูลผู้ใช้ โดเมนคุกกี้ และเส้นทางสำหรับการเปลี่ยนเส้นทางผู้ใช้ที่ไม่ได้เข้าสู่ระบบหรือเข้าสู่ระบบแล้ว
 *
 * ตัวอย่างการใช้งาน:
 * ```php
 * $appKey = config('auth.app_key');
 * ```
 */

require_once __DIR__ . '/env.php';

return [
    'app_key' => env('APP_KEY', '', 'string'),
    'cookie_domain' => env('APP_COOKIE_DOMAIN', '', 'string'),
    'remember_samesite' => env('REMEMBER_SAMESITE', 'Lax', 'string'),
    'guest_redirect_to' => env('GUEST_REDIRECT_TO', '', 'string'),
    'auth_redirect_to' => env('AUTH_REDIRECT_TO', '', 'string'),
];
