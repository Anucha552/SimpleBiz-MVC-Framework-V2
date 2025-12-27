<?php

use App\Core\Migration;

class CreateCartsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS carts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัสตะกร้า',
            user_id INT UNSIGNED NULL COMMENT 'รหัสผู้ใช้ (NULL สำหรับ Guest)',
            session_id VARCHAR(100) NULL COMMENT 'Session ID สำหรับ Guest',
            product_id INT UNSIGNED NOT NULL COMMENT 'รหัสสินค้า',
            quantity INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'จำนวน',
            price DECIMAL(10, 2) NOT NULL COMMENT 'ราคา ณ เวลาที่เพิ่มลงตะกร้า',
            options TEXT NULL COMMENT 'ตัวเลือกสินค้า เช่น สี, ขนาด (JSON)',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่เพิ่มลงตะกร้า',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่แก้ไข',
            
            INDEX idx_user_id (user_id),
            INDEX idx_session_id (session_id),
            INDEX idx_product_id (product_id),
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางตะกร้าสินค้า'
        ";

        $this->execute($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS carts");
    }
}
