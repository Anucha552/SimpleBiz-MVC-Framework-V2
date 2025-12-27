<?php

use App\Core\Migration;

class CreateActivityLogsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS activity_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัสล็อก',
            user_id INT UNSIGNED NULL COMMENT 'รหัสผู้ใช้',
            action VARCHAR(100) NOT NULL COMMENT 'การกระทำ เช่น created, updated, deleted, login',
            entity_type VARCHAR(50) NULL COMMENT 'ชนิด Entity เช่น Product, Order, User',
            entity_id INT UNSIGNED NULL COMMENT 'รหัส Entity',
            description TEXT NULL COMMENT 'คำอธิบายการกระทำ',
            ip_address VARCHAR(45) NULL COMMENT 'IP Address',
            user_agent TEXT NULL COMMENT 'User Agent (Browser)',
            metadata TEXT NULL COMMENT 'ข้อมูลเพิ่มเติม (JSON)',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่บันทึก',
            
            INDEX idx_user_id (user_id),
            INDEX idx_action (action),
            INDEX idx_entity (entity_type, entity_id),
            INDEX idx_created_at (created_at),
            INDEX idx_ip_address (ip_address),
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางบันทึกกิจกรรมผู้ใช้ (Audit Trail)'
        ";

        $this->execute($sql);

        // Insert sample data
        $sampleData = "
        INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address) VALUES
        (1, 'login', 'User', 1, 'ผู้ใช้เข้าสู่ระบบ', '127.0.0.1'),
        (1, 'created', 'Product', 1, 'สร้างสินค้าใหม่: iPhone 15 Pro Max', '127.0.0.1'),
        (2, 'created', 'Order', 1, 'สร้างคำสั่งซื้อใหม่ #ORD-20240001', '192.168.1.100')
        ";

        $this->execute($sampleData);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS activity_logs");
    }
}
