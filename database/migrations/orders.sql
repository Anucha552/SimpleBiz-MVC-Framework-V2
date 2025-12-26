/*
 * ไฟล์สร้างตารางคำสั่งซื้อ
 * 
 * จุดประสงค์: จัดการคำสั่งซื้อและรายการสินค้าในคำสั่งซื้อ
 * ความปลอดภัย: ราคาถูกคำนวณใหม่ฝั่งเซิร์ฟเวอร์ตอนชำระเงิน
 * 
 * ตาราง ORDERS:
 * - เก็บข้อมูลส่วนหัวของคำสั่งซื้อ (ผู้ใช้, ยอดรวม, สถานะ, วันที่)
 * - สถานะติดตามวงจรชีวิตของคำสั่งซื้อ
 * 
 * ตาราง ORDER_ITEMS:
 * - เก็บภาพรวมของสินค้าที่ซื้อ
 * - ราคาถูกล็อค ณ เวลาที่ซื้อ (ไม่เชื่อมโยงกับตาราง products)
 * - subtotal = qty * price (คำนวณ)
 * 
 * ขั้นตอนสถานะคำสั่งซื้อ:
 * - pending: วางคำสั่งซื้อแล้ว รอชำระเงิน
 * - paid: ได้รับการชำระเงิน พร้อมจัดส่ง
 * - shipped: จัดส่งแล้ว
 * - cancelled: ยกเลิกแล้ว
 * 
 * กฎความปลอดภัย:
 * - ยอดรวมคำนวณฝั่งเซิร์ฟเวอร์จาก order_items
 * - สต็อกลดลงเฉพาะเมื่อสร้างคำสั่งซื้อสำเร็จเท่านั้น
 * - ตรวจสอบสต็อกที่มีอยู่ก่อนสร้างคำสั่งซื้อ
 * - บันทึกความพยายามจัดการราคาทุกครั้ง
 */

-- ตารางคำสั่งซื้อหลัก
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10, 2) NOT NULL CHECK (total >= 0),
    status ENUM('pending', 'paid', 'shipped', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fk_order_user) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตารางรายการคำสั่งซื้อ (ภาพรวมสินค้าที่ซื้อ)
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    qty INT NOT NULL CHECK (qty > 0),
    price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (fk_orderitem_order) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (fk_orderitem_product) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
