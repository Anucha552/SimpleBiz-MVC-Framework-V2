/*
 * ไฟล์สร้างตารางตะกร้าสินค้า
 * 
 * จุดประสงค์: จัดการตะกร้าสินค้าและรายการในตะกร้าของผู้ใช้
 * ความปลอดภัย: Foreign keys รับประกันความสมบูรณ์ของข้อมูล
 * 
 * ตาราง CARTS:
 * - หนึ่งตะกร้าต่อหนึ่ง session/บัญชีผู้ใช้
 * - cart_id เชื่อมโยงไปยัง cart_items
 * 
 * ตาราง CART_ITEMS:
 * - เก็บสินค้าแต่ละรายการในตะกร้า
 * - price snapshot: เก็บราคา ณ เวลาที่เพิ่ม (เพื่ออ้างอิง)
 * - qty: จำนวนสินค้า
 * 
 * กฎความปลอดภัยสำคัญ:
 * - ห้ามเชื่อถือราคาที่มาจากฝั่งไคลเอนต์สำหรับการชำระเงิน
 * - เซิร์ฟเวอร์ต้องคำนวณใหม่จากตาราง products เสมอ
 * - cart_items.price ใช้สำหรับแสดงผลเท่านั้น ไม่ใช่สำหรับคำนวณ
 * - ต้องตรวจสอบ qty > 0 และ qty <= สต็อกที่มีอยู่
 */

-- ตารางตะกร้าสินค้าหลัก (หนึ่งตะกร้าต่อหนึ่งผู้ใช้)
CREATE TABLE IF NOT EXISTS carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fk_cart_user) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตารางรายการในตะกร้า (สินค้าในตะกร้า)
CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    qty INT NOT NULL CHECK (qty > 0),
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fk_cartitem_cart) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (fk_cartitem_product) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_product (cart_id, product_id),
    INDEX idx_cart (cart_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
