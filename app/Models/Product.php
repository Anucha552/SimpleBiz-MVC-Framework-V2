<?php
/**
 * โมเดลสินค้า
 * 
 * จุดประสงค์: จัดการแค็ตตาล็อกสินค้าและสินค้าคงคลัง
 * ความปลอดภัย: ตรวจสอบระดับสต็อก, ป้องกันสต็อกติดลบ
 * 
 * กฎทางธุรกิจ:
 * - ราคาต้อง >= 0
 * - สต็อกต้อง >= 0
 * - มีเฉพาะสินค้า 'active' เท่านั้นที่แสดงให้ลูกค้า
 * - ตรวจสอบสต็อกก่อนวางคำสั่งซื้อ
 * - ราคาเก็บแบบ DECIMAL เพื่อความแม่นยำ
 * 
 * สำคัญ:
 * - ราคาถูกอ่านจากฐานข้อมูล ห้ามเชื่อถือข้อมูลจากไคลเอนต์
 * - สต็อกจัดการฝั่งเซิร์ฟเวอร์เท่านั้น
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class Product
{
    private PDO $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }

    /**
     * ดึงสินค้าที่ใช้งานได้ทั้งหมด
     * 
     * คืนค่าเฉพาะสินค้าที่มีสถานะ='active' เท่านั้น
     * เรียงตามวันที่สร้าง (ใหม่สุดก่อน)
     * 
     * @return array อาร์เรย์สินค้า
     */
    public function getAll(): array
    {
        $stmt = $this->db->prepare("
            SELECT id, name, description, price, stock, status, created_at 
            FROM products 
            WHERE status = 'active'
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * ดึงข้อมูลสินค้าจาก ID
     * 
     * @param int $id ID สินค้า
     * @return array|null ข้อมูลสินค้าหรือ null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, name, description, price, stock, status, created_at 
            FROM products 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        return $product ?: null;
    }

    /**
     * ตรวจสอบว่าสินค้ามีจำหน่ายหรือไม่
     * 
     * ตรวจสอบ:
     * - สินค้ามีอยู่จริง
     * - สินค้าใช้งานได้
     * - มีสต็อกเพียงพอ
     * 
     * @param int $productId ID สินค้า
     * @param int $quantity จำนวนที่ต้องการ
     * @return array ['available' => bool, 'product' => array|null, 'reason' => string]
     */
    public function checkAvailability(int $productId, int $quantity): array
    {
        $product = $this->findById($productId);

        if (!$product) {
            return [
                'available' => false,
                'product' => null,
                'reason' => 'Product not found',
            ];
        }

        if ($product['status'] !== 'active') {
            return [
                'available' => false,
                'product' => $product,
                'reason' => 'Product not available',
            ];
        }

        if ($product['stock'] < $quantity) {
            return [
                'available' => false,
                'product' => $product,
                'reason' => 'Insufficient stock',
            ];
        }

        return [
            'available' => true,
            'product' => $product,
            'reason' => '',
        ];
    }

    /**
     * ลดสต็อกสินค้า
     * 
     * สำคัญ: เรียกใช้หลังจากวางคำสั่งซื้อสำเร็จเท่านั้น
     * ปลอดภัยในทรานแซกชัน: ใช้ optimistic locking เพื่อป้องกัน race conditions
     * 
     * @param int $productId ID สินค้า
     * @param int $quantity จำนวนที่จะลด
     * @return bool สถานะความสำเร็จ
     */
    public function decreaseStock(int $productId, int $quantity): bool
    {
        // ใช้ WHERE clause พร้อมการตรวจสอบสต็อกเพื่อป้องกันสต็อกติดลบ
        // สิ่งนี้ป้องกัน race conditions ระหว่างการตรวจสอบและการอัปเดต
        $stmt = $this->db->prepare("
            UPDATE products 
            SET stock = stock - ? 
            WHERE id = ? 
            AND stock >= ?
        ");
        $stmt->execute([$quantity, $productId, $quantity]);

        $success = $stmt->rowCount() > 0;

        if ($success) {
            $this->logger->info('product.stock_decreased', [
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        } else {
            $this->logger->error('product.stock_decrease_failed', [
                'product_id' => $productId,
                'quantity' => $quantity,
                'reason' => 'insufficient_stock_or_not_found',
            ]);
        }

        return $success;
    }

    /**
     * เพิ่มสต็อกสินค้า
     * 
     * ใช้สำหรับการยกเลิกคำสั่งซื้อหรือการเติมสต็อก
     * 
     * @param int $productId ID สินค้า
     * @param int $quantity จำนวนที่จะเพิ่ม
     * @return bool สถานะความสำเร็จ
     */
    public function increaseStock(int $productId, int $quantity): bool
    {
        $stmt = $this->db->prepare("
            UPDATE products 
            SET stock = stock + ? 
            WHERE id = ?
        ");
        $stmt->execute([$quantity, $productId]);

        $success = $stmt->rowCount() > 0;

        if ($success) {
            $this->logger->info('product.stock_increased', [
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        }

        return $success;
    }

    /**
     * สร้างสินค้าใหม่
     * 
     * @param array $data ข้อมูลสินค้า
     * @return array ['success' => bool, 'message' => string, 'product_id' => int|null]
     */
    public function create(array $data): array
    {
        // ตรวจสอบฟิลด์ที่จำเป็น
        $required = ['name', 'price', 'stock'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return ['success' => false, 'message' => "Missing required field: {$field}"];
            }
        }

        // ตรวจสอบราคา
        if ($data['price'] < 0) {
            return ['success' => false, 'message' => 'Price must be >= 0'];
        }

        // ตรวจสอบสต็อก
        if ($data['stock'] < 0) {
            return ['success' => false, 'message' => 'Stock must be >= 0'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO products (name, description, price, stock, status) 
            VALUES (?, ?, ?, ?, ?)
        ");

        try {
            $stmt->execute([
                $data['name'],
                $data['description'] ?? '',
                $data['price'],
                $data['stock'],
                $data['status'] ?? 'active',
            ]);

            $productId = $this->db->lastInsertId();

            $this->logger->info('product.created', [
                'product_id' => $productId,
                'name' => $data['name'],
            ]);

            return [
                'success' => true,
                'message' => 'Product created successfully',
                'product_id' => $productId,
            ];
        } catch (\PDOException $e) {
            $this->logger->error('product.create_failed', [
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to create product'];
        }
    }

    /**
     * ค้นหาสินค้าจากชื่อ
     * 
     * @param string $query คำค้นหา
     * @return array อาร์เรย์สินค้า
     */
    public function search(string $query): array
    {
        $stmt = $this->db->prepare("
            SELECT id, name, description, price, stock, status, created_at 
            FROM products 
            WHERE status = 'active'
            AND (name LIKE ? OR description LIKE ?)
            ORDER BY name
        ");
        $searchTerm = "%{$query}%";
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
}
