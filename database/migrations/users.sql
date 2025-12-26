/*
 * ไฟล์สร้างตาราง USERS
 * 
 * จุดประสงค์: เก็บบัญชีผู้ใช้สำหรับการยืนยันตัวตน
 * ความปลอดภัย: รหัสผ่านต้อง hash ด้วย password_hash()
 * 
 * ฟิลด์:
 * - id: Primary key
 * - username: ชื่อผู้ใช้ที่ไม่ซ้ำกันสำหรับเข้าสู่ระบบ
 * - email: อีเมลที่ไม่ซ้ำกัน
 * - password: รหัสผ่านที่ hash แล้ว (bcrypt/argon2)
 * - created_at: เวลาที่สร้างบัญชี
 */

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
