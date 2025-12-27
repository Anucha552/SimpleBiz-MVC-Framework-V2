<?php

use App\Core\Migration;

class CreateReviewsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS reviews (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัสรีวิว',
            product_id INT UNSIGNED NOT NULL COMMENT 'รหัสสินค้า',
            user_id INT UNSIGNED NOT NULL COMMENT 'รหัสผู้รีวิว',
            order_id INT UNSIGNED NULL COMMENT 'ซื้อจากคำสั่งซื้อไหน',
            rating TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5) COMMENT 'คะแนน 1-5 ดาว',
            title VARCHAR(255) NULL COMMENT 'หัวข้อรีวิว',
            comment TEXT NULL COMMENT 'ความคิดเห็น',
            images TEXT NULL COMMENT 'รูปภาพประกอบรีวิว (JSON array)',
            is_verified_purchase BOOLEAN DEFAULT FALSE COMMENT 'ยืนยันการซื้อ',
            is_approved BOOLEAN DEFAULT TRUE COMMENT 'อนุมัติแล้ว',
            helpful_count INT UNSIGNED DEFAULT 0 COMMENT 'จำนวนคนกด มีประโยชน์',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่รีวิว',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่แก้ไข',
            
            INDEX idx_product_id (product_id),
            INDEX idx_user_id (user_id),
            INDEX idx_order_id (order_id),
            INDEX idx_rating (rating),
            INDEX idx_is_verified_purchase (is_verified_purchase),
            INDEX idx_is_approved (is_approved),
            INDEX idx_created_at (created_at),
            FULLTEXT idx_search (title, comment),
            
            UNIQUE KEY unique_user_product (user_id, product_id),
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางรีวิวสินค้า'
        ";

        $this->execute($sql);

        // Create trigger to update product rating automatically
        $trigger = "
        CREATE TRIGGER after_review_insert
        AFTER INSERT ON reviews
        FOR EACH ROW
        BEGIN
            UPDATE products 
            SET rating = (
                SELECT AVG(rating) 
                FROM reviews 
                WHERE product_id = NEW.product_id AND is_approved = 1
            ),
            rating_count = (
                SELECT COUNT(*) 
                FROM reviews 
                WHERE product_id = NEW.product_id AND is_approved = 1
            )
            WHERE id = NEW.product_id;
        END
        ";

        $this->execute($trigger);

        $updateTrigger = "
        CREATE TRIGGER after_review_update
        AFTER UPDATE ON reviews
        FOR EACH ROW
        BEGIN
            UPDATE products 
            SET rating = (
                SELECT AVG(rating) 
                FROM reviews 
                WHERE product_id = NEW.product_id AND is_approved = 1
            ),
            rating_count = (
                SELECT COUNT(*) 
                FROM reviews 
                WHERE product_id = NEW.product_id AND is_approved = 1
            )
            WHERE id = NEW.product_id;
        END
        ";

        $this->execute($updateTrigger);

        // Insert sample reviews
        $sampleData = "
        INSERT INTO reviews (product_id, user_id, rating, title, comment, is_verified_purchase, is_approved) VALUES
        (1, 2, 5, 'เยี่ยมมาก!', 'iPhone 15 Pro Max ใช้งานลื่นไหล กล้องคมชัดมาก คุ้มค่าเงินทุกบาท', 1, 1),
        (1, 3, 4, 'ดีครับ', 'ของดี แต่ราคาค่อนข้างแพง', 1, 1),
        (2, 2, 5, 'Samsung ทำได้ดีมาก', 'จอสวย ปากกา S Pen ใช้งานได้ดีเยี่ยม', 0, 1)
        ";

        $this->execute($sampleData);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->execute("DROP TRIGGER IF EXISTS after_review_insert");
        $this->execute("DROP TRIGGER IF EXISTS after_review_update");
        $this->execute("DROP TABLE IF EXISTS reviews");
    }
}
