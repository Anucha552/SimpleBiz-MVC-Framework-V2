<?php
/**
 * ค่าคอนฟิกแอปพลิเคชัน
 * 
 * จุดประสงค์: การตั้งค่าหลักของแอปพลิเคชัน
 * 
 * ตัวเลือกการตั้งค่า:
 * - APP_NAME: ชื่อแอปพลิเคชัน
 * - APP_ENV: สภาพแวดล้อม (development|production)
 * - APP_DEBUG: โหมดดีบัก (true|false)
 * - APP_URL: URL หลักของแอปพลิเคชัน
 * 
 * สำคัญ:
 * - ตั้ง APP_ENV=production ในสภาพแวดล้อมจริง
 * - ตั้ง APP_DEBUG=false ในสภาพแวดล้อมจริง
 * - โหลดค่าคอนฟิกที่ละเอียดอ่อนจากไฟล์ .env
 */

return [
    'name' => getenv('APP_NAME') ?: 'SimpleBiz MVC Framework V2',
    'env' => getenv('APP_ENV') ?: 'development',
    'debug' => getenv('APP_DEBUG') !== 'false',
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'timezone' => 'UTC',
];
