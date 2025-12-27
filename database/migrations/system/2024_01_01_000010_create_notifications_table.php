<?php

use App\Core\Migration;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS notifications (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัสการแจ้งเตือน',
            user_id INT UNSIGNED NOT NULL COMMENT 'รหัสผู้รับ',
            type VARCHAR(50) NOT NULL COMMENT 'ชนิด เช่น order, product, system, payment',
            title VARCHAR(255) NOT NULL COMMENT 'หัวข้อ',
            message TEXT NOT NULL COMMENT 'ข้อความ',
            action_url VARCHAR(500) NULL COMMENT 'URL สำหรับคลิกดูรายละเอียด',
            icon VARCHAR(100) NULL COMMENT 'ไอคอน',
            priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal' COMMENT 'ระดับความสำคัญ',
            is_read BOOLEAN DEFAULT FALSE COMMENT 'อ่านแล้วหรือไม่',
            read_at TIMESTAMP NULL COMMENT 'วันที่อ่าน',
            data TEXT NULL COMMENT 'ข้อมูลเพิ่มเติม (JSON)',
            expires_at TIMESTAMP NULL COMMENT 'วันหมดอายุ',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
            
            INDEX idx_user_id (user_id),
            INDEX idx_type (type),
            INDEX idx_is_read (is_read),
            INDEX idx_priority (priority),
            INDEX idx_created_at (created_at),
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางการแจ้งเตือน'
        ";

        $this->execute($sql);

        // Insert sample notifications
        $sampleData = "
        INSERT INTO notifications (user_id, type, title, message, action_url, priority, is_read) VALUES
        (2, 'order', 'คำสั่งซื้อสำเร็จ', 'คำสั่งซื้อ #ORD-20240001 ของคุณได้รับการยืนยันแล้ว', '/orders/1', 'high', 0),
        (2, 'product', 'สินค้าลดราคา', 'iPhone 15 Pro Max ลดราคาพิเศษ 10%', '/products/iphone-15-pro-max', 'normal', 0),
        (1, 'system', 'อัพเดทระบบ', 'ระบบจะปิดปรับปรุงในวันที่ 1 ม.ค. 2567', NULL, 'urgent', 0)
        ";

        $this->execute($sampleData);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS notifications");
    }
}
