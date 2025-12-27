<?php

use App\Core\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัสผู้ใช้',
            username VARCHAR(50) NOT NULL UNIQUE COMMENT 'ชื่อผู้ใช้',
            email VARCHAR(100) NOT NULL UNIQUE COMMENT 'อีเมล',
            password VARCHAR(255) NOT NULL COMMENT 'รหัสผ่าน (Hashed)',
            first_name VARCHAR(100) NULL COMMENT 'ชื่อจริง',
            last_name VARCHAR(100) NULL COMMENT 'นามสกุล',
            phone VARCHAR(20) NULL COMMENT 'เบอร์โทรศัพท์',
            status ENUM('active', 'inactive', 'banned') DEFAULT 'active' COMMENT 'สถานะ: active=ใช้งาน, inactive=ปิดใช้งาน, banned=ระงับ',
            email_verified_at TIMESTAMP NULL COMMENT 'วันที่ยืนยันอีเมล',
            remember_token VARCHAR(100) NULL COMMENT 'Token จดจำการเข้าสู่ระบบ',
            last_login_at TIMESTAMP NULL COMMENT 'วันที่เข้าสู่ระบบล่าสุด',
            last_login_ip VARCHAR(45) NULL COMMENT 'IP Address ล่าสุด',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่แก้ไข',
            deleted_at TIMESTAMP NULL COMMENT 'วันที่ลบ (Soft Delete)',
            
            INDEX idx_username (username),
            INDEX idx_email (email),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางผู้ใช้งานระบบ'
        ";

        $this->execute($sql);

        // Insert sample data
        $sampleData = "
        INSERT INTO users (username, email, password, first_name, last_name, phone, status) VALUES
        ('admin', 'admin@example.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', '0812345678', 'active'),
        ('john', 'john@example.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', '0823456789', 'active'),
        ('jane', 'jane@example.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Smith', '0834567890', 'active')
        ";

        $this->execute($sampleData);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS users");
    }
}
