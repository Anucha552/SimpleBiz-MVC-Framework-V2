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

return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'simplebiz_mvc',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
];
