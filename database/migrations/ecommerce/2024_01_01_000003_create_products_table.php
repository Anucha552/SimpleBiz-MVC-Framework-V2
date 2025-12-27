<?php

use App\Core\Migration;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS products (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัสสินค้า',
            category_id INT UNSIGNED NULL COMMENT 'รหัสหมวดหมู่',
            name VARCHAR(255) NOT NULL COMMENT 'ชื่อสินค้า',
            slug VARCHAR(255) NOT NULL UNIQUE COMMENT 'URL-friendly name',
            description TEXT NULL COMMENT 'รายละเอียดสินค้า',
            price DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'ราคาขาย',
            compare_price DECIMAL(10, 2) NULL COMMENT 'ราคาเปรียบเทียบ (ราคาก่อนลด)',
            cost_price DECIMAL(10, 2) NULL COMMENT 'ราคาทุน',
            sku VARCHAR(100) UNIQUE NULL COMMENT 'รหัสสินค้า (Stock Keeping Unit)',
            barcode VARCHAR(100) NULL COMMENT 'บาร์โค้ด',
            stock_quantity INT UNSIGNED DEFAULT 0 COMMENT 'จำนวนคงเหลือ',
            low_stock_threshold INT UNSIGNED DEFAULT 10 COMMENT 'แจ้งเตือนเมื่อสต็อกต่ำกว่า',
            weight DECIMAL(8, 2) NULL COMMENT 'น้ำหนัก (กรัม)',
            length DECIMAL(8, 2) NULL COMMENT 'ความยาว (ซม.)',
            width DECIMAL(8, 2) NULL COMMENT 'ความกว้าง (ซม.)',
            height DECIMAL(8, 2) NULL COMMENT 'ความสูง (ซม.)',
            image VARCHAR(255) NULL COMMENT 'รูปภาพหลัก',
            images TEXT NULL COMMENT 'รูปภาพเพิ่มเติม (JSON array)',
            is_featured BOOLEAN DEFAULT FALSE COMMENT 'สินค้าแนะนำ',
            is_active BOOLEAN DEFAULT TRUE COMMENT 'เปิดใช้งาน',
            meta_title VARCHAR(255) NULL COMMENT 'SEO Title',
            meta_description TEXT NULL COMMENT 'SEO Description',
            meta_keywords VARCHAR(255) NULL COMMENT 'SEO Keywords',
            view_count INT UNSIGNED DEFAULT 0 COMMENT 'จำนวนครั้งที่เข้าชม',
            rating DECIMAL(3, 2) DEFAULT 0.00 COMMENT 'คะแนนเฉลี่ย (0-5)',
            rating_count INT UNSIGNED DEFAULT 0 COMMENT 'จำนวนรีวิว',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่แก้ไข',
            deleted_at TIMESTAMP NULL COMMENT 'วันที่ลบ (Soft Delete)',
            
            INDEX idx_category_id (category_id),
            INDEX idx_slug (slug),
            INDEX idx_sku (sku),
            INDEX idx_is_featured (is_featured),
            INDEX idx_is_active (is_active),
            INDEX idx_price (price),
            INDEX idx_created_at (created_at),
            FULLTEXT idx_search (name, description),
            
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางสินค้า'
        ";

        $this->execute($sql);

        // Insert sample data
        $sampleData = "
        INSERT INTO products (category_id, name, slug, description, price, compare_price, sku, stock_quantity, image, is_featured, is_active) VALUES
        (4, 'iPhone 15 Pro Max', 'iphone-15-pro-max', 'มือถือสุดพรีเมียมจาก Apple', 49900.00, 52900.00, 'IPH15PM-256', 50, '/images/iphone15.jpg', 1, 1),
        (4, 'Samsung Galaxy S24 Ultra', 'samsung-s24-ultra', 'มือถือ Android flagship', 42900.00, 45900.00, 'SAM-S24U-512', 30, '/images/samsung-s24.jpg', 1, 1),
        (5, 'MacBook Pro 14', 'macbook-pro-14', 'โน้ตบุ๊คสำหรับมืออาชีพ', 79900.00, 84900.00, 'MBP14-M3-512', 20, '/images/macbook.jpg', 1, 1),
        (3, 'Clean Code', 'clean-code-book', 'หนังสือเขียนโปรแกรมที่ดี', 890.00, NULL, 'BOOK-CC-001', 100, '/images/clean-code.jpg', 0, 1),
        (6, 'เสื้อโปโล', 'polo-shirt-men', 'เสื้อโปโลผู้ชาย สไตล์คลาสสิก', 590.00, 790.00, 'POLO-M-001', 200, '/images/polo-men.jpg', 0, 1)
        ";

        $this->execute($sampleData);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS products");
    }
}
