<?php

use App\Core\Migration;

class CreateAddressesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS addresses (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัสที่อยู่',
            user_id INT UNSIGNED NOT NULL COMMENT 'รหัสผู้ใช้',
            type ENUM('shipping', 'billing', 'both') DEFAULT 'shipping' COMMENT 'ชนิดที่อยู่: จัดส่ง, ออกบิล, ทั้งสองอย่าง',
            first_name VARCHAR(100) NOT NULL COMMENT 'ชื่อจริง',
            last_name VARCHAR(100) NOT NULL COMMENT 'นามสกุล',
            company VARCHAR(100) NULL COMMENT 'ชื่อบริษัท',
            phone VARCHAR(20) NOT NULL COMMENT 'เบอร์โทรศัพท์',
            email VARCHAR(100) NULL COMMENT 'อีเมล',
            address_line1 VARCHAR(255) NOT NULL COMMENT 'ที่อยู่บรรทัดที่ 1',
            address_line2 VARCHAR(255) NULL COMMENT 'ที่อยู่บรรทัดที่ 2',
            city VARCHAR(100) NOT NULL COMMENT 'เมือง/อำเภอ',
            state_province VARCHAR(100) NULL COMMENT 'จังหวัด',
            postal_code VARCHAR(20) NOT NULL COMMENT 'รหัสไปรษณีย์',
            country VARCHAR(2) DEFAULT 'TH' COMMENT 'รหัสประเทศ (ISO Code)',
            is_default BOOLEAN DEFAULT FALSE COMMENT 'ที่อยู่เริ่มต้น',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่แก้ไข',
            
            INDEX idx_user_id (user_id),
            INDEX idx_type (type),
            INDEX idx_is_default (is_default),
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางที่อยู่จัดส่ง/ออกบิล'
        ";

        $this->execute($sql);

        // Insert sample addresses
        $sampleData = "
        INSERT INTO addresses (user_id, type, first_name, last_name, phone, address_line1, city, state_province, postal_code, country, is_default) VALUES
        (2, 'both', 'John', 'Doe', '0823456789', '123 ถนนสุขุมวิท', 'กรุงเทพมหานคร', 'กรุงเทพมหานคร', '10110', 'TH', 1),
        (2, 'shipping', 'John', 'Doe', '0823456789', '456 ถนนพระราม 4', 'กรุงเทพมหานคร', 'กรุงเทพมหานคร', '10120', 'TH', 0),
        (3, 'both', 'Jane', 'Smith', '0834567890', '789 ถนนรัชดาภิเษก', 'กรุงเทพมหานคร', 'กรุงเทพมหานคร', '10400', 'TH', 1)
        ";

        $this->execute($sampleData);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS addresses");
    }
}
