<?php

use App\Core\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS orders (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัสคำสั่งซื้อ',
            user_id INT UNSIGNED NOT NULL COMMENT 'รหัสผู้ซื้อ',
            order_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'เลขที่คำสั่งซื้อ',
            status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending' COMMENT 'สถานะ: รอดำเนินการ, กำลังดำเนินการ, จัดส่งแล้ว, จัดส่งสำเร็จ, ยกเลิก, คืนเงิน',
            payment_method ENUM('credit_card', 'bank_transfer', 'cash_on_delivery', 'paypal', 'promptpay') NOT NULL COMMENT 'วิธีชำระเงิน',
            payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending' COMMENT 'สถานะการชำระเงิน',
            subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'ราคาสินค้ารวม',
            tax DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'ภาษี',
            shipping_cost DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'ค่าจัดส่ง',
            discount DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'ส่วนลด',
            total DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'ยอดรวมทั้งหมด',
            currency VARCHAR(3) DEFAULT 'THB' COMMENT 'สกุลเงิน',
            shipping_address TEXT NULL COMMENT 'ที่อยู่จัดส่ง (JSON)',
            billing_address TEXT NULL COMMENT 'ที่อยู่ออกบิล (JSON)',
            notes TEXT NULL COMMENT 'หมายเหตุจากแอดมิน',
            customer_note TEXT NULL COMMENT 'ข้อความจากลูกค้า',
            tracking_number VARCHAR(100) NULL COMMENT 'เลขที่ติดตามพัสดุ',
            shipped_at TIMESTAMP NULL COMMENT 'วันที่จัดส่ง',
            delivered_at TIMESTAMP NULL COMMENT 'วันที่จัดส่งสำเร็จ',
            cancelled_at TIMESTAMP NULL COMMENT 'วันที่ยกเลิก',
            refunded_at TIMESTAMP NULL COMMENT 'วันที่คืนเงิน',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่แก้ไข',
            
            INDEX idx_user_id (user_id),
            INDEX idx_order_number (order_number),
            INDEX idx_status (status),
            INDEX idx_payment_status (payment_status),
            INDEX idx_created_at (created_at),
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางคำสั่งซื้อ'
        ";

        $this->execute($sql);

        // Create order items table
        $orderItems = "
        CREATE TABLE IF NOT EXISTS order_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัสรายการสินค้า',
            order_id INT UNSIGNED NOT NULL COMMENT 'รหัสคำสั่งซื้อ',
            product_id INT UNSIGNED NOT NULL COMMENT 'รหัสสินค้า',
            product_name VARCHAR(255) NOT NULL COMMENT 'ชื่อสินค้า ณ เวลาซื้อ',
            product_sku VARCHAR(100) NULL COMMENT 'SKU สินค้า ณ เวลาซื้อ',
            quantity INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'จำนวนที่ซื้อ',
            price DECIMAL(10, 2) NOT NULL COMMENT 'ราคาต่อหน่วย',
            subtotal DECIMAL(10, 2) NOT NULL COMMENT 'ราคารวมก่อนภาษี',
            tax DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'ภาษี',
            total DECIMAL(10, 2) NOT NULL COMMENT 'ราคารววมหลังภาษี',
            options TEXT NULL COMMENT 'ตัวเลือกสินค้า (JSON)',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
            
            INDEX idx_order_id (order_id),
            INDEX idx_product_id (product_id),
            
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางรายการสินค้าในคำสั่งซื้อ'
        ";

        $this->execute($orderItems);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS order_items");
        $this->execute("DROP TABLE IF EXISTS orders");
    }
}
