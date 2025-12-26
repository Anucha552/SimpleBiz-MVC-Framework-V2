/*
 * ไฟล์สร้างตาราง PRODUCTS
 * 
 * จุดประสงค์: เก็บแค็ตตาล็อกสินค้าสำหรับระบบอีคอมเมิร์ซ
 * ความปลอดภัย: ราคาเก็บเป็น DECIMAL เพื่อป้องกันข้อผิดพลาดจากทศนิยม
 * 
 * ฟิลด์:
 * - id: Primary key
 * - name: ชื่อสินค้า
 * - description: รายละเอียดสินค้า
 * - price: ราคาสินค้า (DECIMAL เพื่อความแม่นยำ)
 * - stock: สต็อกที่มีอยู่ (INT, ต้องไม่ติดลบ)
 * - status: สถานะการแสดงสินค้า (active/inactive)
 * - created_at: เวลาที่สร้างสินค้า
 * 
 * กฎทางธุรกิจ:
 * - stock ต้อง >= 0 (บังคับด้วย CHECK constraint)
 * - price ต้อง >= 0
 * - status กำหนดว่าสินค้าแสดงให้ลูกค้าเห็นหรือไม่
 */

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL CHECK (price >= 0),
    stock INT NOT NULL DEFAULT 0 CHECK (stock >= 0),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
