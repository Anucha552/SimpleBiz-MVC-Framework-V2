<?php

use App\Core\Migration;

class CreatePagesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS pages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัสหน้า',
            title VARCHAR(255) NOT NULL COMMENT 'หัวข้อหน้า',
            slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'URL-friendly name',
            content LONGTEXT NULL COMMENT 'เนื้อหาหน้า',
            excerpt TEXT NULL COMMENT 'คำอธิบายสั้น',
            featured_image VARCHAR(255) NULL COMMENT 'รูปภาพหลัก',
            template VARCHAR(50) DEFAULT 'default' COMMENT 'Template ที่ใช้ เช่น default, about, contact',
            status ENUM('draft', 'published', 'archived') DEFAULT 'draft' COMMENT 'สถานะ: แบบร่าง, เผยแพร่, เก็บเข้าคลัง',
            author_id INT UNSIGNED NULL COMMENT 'ผู้เขียน',
            parent_id INT UNSIGNED NULL COMMENT 'รหัสหน้าแม่ (สำหรับหน้าย่อย)',
            sort_order INT DEFAULT 0 COMMENT 'ลำดับการแสดง',
            view_count INT UNSIGNED DEFAULT 0 COMMENT 'จำนวนครั้งที่เข้าชม',
            meta_title VARCHAR(255) NULL COMMENT 'SEO Title',
            meta_description TEXT NULL COMMENT 'SEO Description',
            meta_keywords VARCHAR(255) NULL COMMENT 'SEO Keywords',
            published_at TIMESTAMP NULL COMMENT 'วันที่เผยแพร่',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่แก้ไข',
            deleted_at TIMESTAMP NULL COMMENT 'วันที่ลบ (Soft Delete)',
            
            INDEX idx_slug (slug),
            INDEX idx_status (status),
            INDEX idx_author_id (author_id),
            INDEX idx_parent_id (parent_id),
            INDEX idx_sort_order (sort_order),
            INDEX idx_published_at (published_at),
            FULLTEXT idx_search (title, content),
            
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (parent_id) REFERENCES pages(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางหน้าเนื้อหา (CMS)'
        ";

        $this->execute($sql);

        // Insert sample pages
        $sampleData = "
        INSERT INTO pages (title, slug, content, excerpt, template, status, author_id, published_at) VALUES
        ('เกี่ยวกับเรา', 'about-us', '<h1>เกี่ยวกับเรา</h1><p>SimpleBiz Shop เป็นร้านค้าออนไลน์ที่มอบประสบการณ์การช้อปปิ้งที่ดีที่สุด</p>', 'ข้อมูลเกี่ยวกับบริษัท', 'about', 'published', 1, NOW()),
        ('ติดต่อเรา', 'contact-us', '<h1>ติดต่อเรา</h1><p>ที่อยู่: 123 ถนนสุขุมวิท กรุงเทพฯ</p><p>โทร: 02-xxx-xxxx</p>', 'ช่องทางติดต่อ', 'contact', 'published', 1, NOW()),
        ('นโยบายความเป็นส่วนตัว', 'privacy-policy', '<h1>นโยบายความเป็นส่วนตัว</h1><p>เรามุ่งมั่นในการปกป้องข้อมูลส่วนบุคคลของคุณ</p>', 'นโยบายความเป็นส่วนตัว', 'default', 'published', 1, NOW()),
        ('เงื่อนไขการใช้งาน', 'terms-of-service', '<h1>เงื่อนไขการใช้งาน</h1><p>โปรดอ่านเงื่อนไขการใช้งานอย่างละเอียด</p>', 'เงื่อนไขการใช้งานเว็บไซต์', 'default', 'published', 1, NOW())
        ";

        $this->execute($sampleData);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS pages");
    }
}
