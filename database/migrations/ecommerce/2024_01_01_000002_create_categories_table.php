<?php

use App\Core\Migration;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัสหมวดหมู่',
            parent_id INT UNSIGNED NULL COMMENT 'รหัสหมวดหมู่แม่ (สำหรับหมวดหมู่ย่อย)',
            name VARCHAR(255) NOT NULL COMMENT 'ชื่อหมวดหมู่',
            slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'URL-friendly name',
            description TEXT NULL COMMENT 'คำอธิบายหมวดหมู่',
            sort_order INT DEFAULT 0 COMMENT 'ลำดับการแสดงผล',
            status ENUM('active', 'inactive') DEFAULT 'active' COMMENT 'สถานะ: active=ใช้งาน, inactive=ปิดใช้งาน',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่แก้ไข',
            deleted_at TIMESTAMP NULL COMMENT 'วันที่ลบ (Soft Delete)',
            
            INDEX idx_parent_id (parent_id),
            INDEX idx_slug (slug),
            INDEX idx_status (status),
            INDEX idx_sort_order (sort_order),
            
            FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางหมวดหมู่สินค้า (รองรับ Hierarchical)'
        ";

        $this->execute($sql);

        // Insert sample data
        $sampleData = "
        INSERT INTO categories (name, slug, description, parent_id, sort_order, status) VALUES
        ('อิเล็กทรอนิกส์', 'electronics', 'สินค้าอิเล็กทรอนิกส์ทุกประเภท', NULL, 1, 'active'),
        ('เสื้อผ้า', 'clothing', 'เสื้อผ้าแฟชั่น', NULL, 2, 'active'),
        ('หนังสือ', 'books', 'หนังสือและนิตยสาร', NULL, 3, 'active'),
        ('มือถือ', 'mobile-phones', 'โทรศัพท์มือถือ', 1, 1, 'active'),
        ('คอมพิวเตอร์', 'computers', 'คอมพิวเตอร์และอุปกรณ์เสริม', 1, 2, 'active'),
        ('เสื้อผู้ชาย', 'mens-shirts', 'เสื้อสำหรับผู้ชาย', 2, 1, 'active'),
        ('เสื้อผู้หญิง', 'womens-shirts', 'เสื้อสำหรับผู้หญิง', 2, 2, 'active')
        ";

        $this->execute($sampleData);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS categories");
    }
}
