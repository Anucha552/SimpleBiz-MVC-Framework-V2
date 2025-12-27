<?php

use App\Core\Migration;

class CreateMediaTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create media table
        $media = "
        CREATE TABLE IF NOT EXISTS media (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัสไฟล์',
            filename VARCHAR(255) NOT NULL COMMENT 'ชื่อไฟล์ที่บันทึก',
            original_filename VARCHAR(255) NOT NULL COMMENT 'ชื่อไฟล์ต้นฉบับ',
            file_path VARCHAR(500) NOT NULL COMMENT 'เส้นทางไฟล์',
            file_size INT UNSIGNED NOT NULL COMMENT 'ขนาดไฟล์ (ไบต์)',
            mime_type VARCHAR(100) NOT NULL COMMENT 'ชนิดไฟล์ (MIME Type)',
            file_type ENUM('image', 'video', 'audio', 'document', 'other') NOT NULL COMMENT 'ประเภทไฟล์',
            width INT UNSIGNED NULL COMMENT 'ความกว้าง (สำหรับรูปภาพ/วิดีโอ)',
            height INT UNSIGNED NULL COMMENT 'ความสูง (สำหรับรูปภาพ/วิดีโอ)',
            duration INT UNSIGNED NULL COMMENT 'ความยาว (วินาที - สำหรับวิดีโอ/เสียง)',
            alt_text VARCHAR(255) NULL COMMENT 'ข้อความทางเลือก (Alt Text)',
            title VARCHAR(255) NULL COMMENT 'หัวข้อไฟล์',
            description TEXT NULL COMMENT 'คำอธิบายไฟล์',
            metadata TEXT NULL COMMENT 'ข้อมูลเพิ่มเติม (JSON)',
            uploaded_by INT UNSIGNED NULL COMMENT 'ผู้อัพโหลด',
            is_public BOOLEAN DEFAULT TRUE COMMENT 'เปิดเผยสาธารณะหรือไม่',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่อัพโหลด',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่แก้ไข',
            
            INDEX idx_filename (filename),
            INDEX idx_file_type (file_type),
            INDEX idx_mime_type (mime_type),
            INDEX idx_uploaded_by (uploaded_by),
            INDEX idx_created_at (created_at),
            FULLTEXT idx_search (filename, original_filename, title, description),
            
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางไฟล์มีเดีย (รูปภาพ, วิดีโอ, เอกสาร)'
        ";

        $this->execute($media);

        // Create media_relations table for polymorphic relationships
        $mediaRelations = "
        CREATE TABLE IF NOT EXISTS media_relations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัสความสัมพันธ์',
            media_id INT UNSIGNED NOT NULL COMMENT 'รหัสไฟล์มีเดีย',
            entity_type VARCHAR(50) NOT NULL COMMENT 'ชนิดของ Entity เช่น Product, User, Post',
            entity_id INT UNSIGNED NOT NULL COMMENT 'รหัสของ Entity',
            field_name VARCHAR(50) DEFAULT 'image' COMMENT 'ชื่อฟิลด์ เช่น avatar, gallery, thumbnail',
            sort_order INT DEFAULT 0 COMMENT 'ลำดับการเรียง',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่เชื่อมโยง',
            
            INDEX idx_media_id (media_id),
            INDEX idx_entity (entity_type, entity_id),
            INDEX idx_field_name (field_name),
            INDEX idx_sort_order (sort_order),
            
            FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางความสัมพันธ์ไฟล์กับ Entity (Polymorphic)'
        ";

        $this->execute($mediaRelations);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS media_relations");
        $this->execute("DROP TABLE IF EXISTS media");
    }
}
