<?php
/**
 * ค่าคอนฟิกฐานข้อมูล
 * 
 * จุดประสงค์: การตั้งค่าการเชื่อมต่อฐานข้อมูล
 * 
 * การตั้งค่า:
 * - host: ชื่อโฮสต์ของเซิร์ฟเวอร์ฐานข้อมูล
 * - port: พอร์ตของเซิร์ฟเวอร์ฐานข้อมูล
 * - database: ชื่อฐานข้อมูล
 * - username: ชื่อผู้ใช้ฐานข้อมูล
 * - password: รหัสผ่านฐานข้อมูล
 * - charset: การเข้ารหัสอักขระ
 * 
 * ความปลอดภัย:
 * - โหลดข้อมูลการเข้าถึงจากตัวแปรสภาพแวดล้อม
 * - ห้าม commit ข้อมูลการเข้าถึงไปยัง version control
 * - ใช้ไฟล์ .env สำหรับการพัฒนาในเครื่อง
 * - ใช้ตัวแปรสภาพแวดล้อมในสภาพแวดล้อมจริง
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
