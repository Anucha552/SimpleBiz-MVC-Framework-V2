<?php

use App\Core\Migration;

class CreateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS settings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัสการตั้งค่า',
            `key` VARCHAR(100) NOT NULL UNIQUE COMMENT 'ชื่อการตั้งค่า',
            value TEXT NULL COMMENT 'ค่า',
            type ENUM('string', 'integer', 'float', 'boolean', 'array', 'json') DEFAULT 'string' COMMENT 'ชนิดข้อมูล',
            group_name VARCHAR(50) DEFAULT 'general' COMMENT 'จัดกลุ่ม เช่น general, email, payment, shop',
            description TEXT NULL COMMENT 'คำอธิบาย',
            is_public BOOLEAN DEFAULT FALSE COMMENT 'แสดงค่าให้ Frontend ได้หรือไม่',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่แก้ไข',
            
            INDEX idx_key (`key`),
            INDEX idx_group_name (group_name),
            INDEX idx_is_public (is_public)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางการตั้งค่าระบบ (Key-Value Store)'
        ";

        $this->execute($sql);

        // Insert sample settings
        $sampleData = "
        INSERT INTO settings (`key`, value, type, group_name, description, is_public) VALUES
        ('site_name', 'SimpleBiz Shop', 'string', 'general', 'ชื่อเว็บไซต์', 1),
        ('site_description', 'ร้านค้าออนไลน์ครบวงจร', 'string', 'general', 'คำอธิบายเว็บไซต์', 1),
        ('site_logo', '/images/logo.png', 'string', 'general', 'โลโก้เว็บไซต์', 1),
        ('timezone', 'Asia/Bangkok', 'string', 'general', 'เขตเวลา', 0),
        ('currency', 'THB', 'string', 'general', 'สกุลเงิน', 1),
        ('tax_rate', '7', 'float', 'shop', 'อัตราภาษี (%)', 1),
        ('free_shipping_threshold', '1000', 'float', 'shop', 'ยอดซื้อขั้นต่ำสำหรับจัดส่งฟรี', 1),
        ('enable_registration', '1', 'boolean', 'general', 'เปิดให้สมัครสมาชิก', 0),
        ('maintenance_mode', '0', 'boolean', 'general', 'โหมดปิดปรับปรุง', 0),
        ('items_per_page', '20', 'integer', 'general', 'จำนวนรายการต่อหน้า', 0)
        ";

        $this->execute($sampleData);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS settings");
    }
}
