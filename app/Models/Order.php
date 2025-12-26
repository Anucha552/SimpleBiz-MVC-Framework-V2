<?php
/**
 * โมเดลคำสั่งซื้อ
 * 
 * จุดประสงค์: จัดการการสร้างคำสั่งซื้อและวงจรชีวิต
 * ความปลอดภัย: การคำนวณยอดรวมฝั่งเซิร์ฟเวอร์, การตรวจสอบสต็อก, การตรวจสอบราคา
 * 
 * กฎทางธุรกิจ:
 * - คำสั่งซื้อถูกสร้างจากรายการตะกร้า
 * - ราคาถูกคำนวณใหม่จากตาราง products (ห้ามเชื่อถือไคลเอนต์)
 * - สต็อกถูกตรวจสอบและลดลงแบบอะตอมิก
 * - สถานะคำสั่งซื้อ: pending → paid → shipped → cancelled
 * - คืนสต็อกเมื่อยกเลิก
 * 
 * กฎความปลอดภัยสำคัญ:
 * - คำนวณยอดรวมใหม่ทุกครั้งจากราคาสินค้าปัจจุบัน
 * - ตรวจสอบสต็อกที่มีก่อนสร้างคำสั่งซื้อ
 * - ใช้ทรานแซกชันของฐานข้อมูลเพื่อความสมบูรณ์
 * - บันทึกความแตกต่างของราคา (ความพยายามจัดการ)
 * - ลดสต็อกหลังจากสร้างคำสั่งซื้อสำเร็จเท่านั้น
 * 
 * ทำไมต้องแยก order และ order_items?
 * - order: ข้อมูลส่วนหัว (ผู้ใช้, ยอดรวม, สถานะ)
 * - order_items: รายการสินค้า (ภาพร่วม snapshot ณ เวลาซื้อ)
 * - ถ้าสินค้าถูกลบภายหลัง ประวัติคำสั่งซื้อยังคงอยู่
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class Order
{
    private PDO $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }

    /**
     * สร้างคำสั่งซื้อจากตะกร้า
     * 
     * กระบวนการ (ทรานแซกชันแบบอะตอมิก):
     * 1. ดึงรายการตะกร้า
     * 2. ตรวจสอบสต็อกสำหรับรายการทั้งหมด
     * 3. คำนวณยอดรวมใหม่จากราคาปัจจุบัน
     * 4. สร้างรีคอร์ดคำสั่งซื้อ
     * 5. สร้างรีคอร์ด order_items
     * 6. ลดสต็อกสินค้าทั้งหมด
     * 7. ล้างตะกร้า
     * 
     * ถ้าขั้นตอนใดล้มเหลว ทรานแซกชันทั้งหมดจะถูก rollback
     * 
     * @param int $userId ID ผู้ใช้
     * @return array ['success' => bool, 'message' => string, 'order_id' => int|null]
     */
    public function createFromCart(int $userId): array
    {
        $cartModel = new Cart();
        $productModel = new Product();

        // ดึงรายการตะกร้า
        $cartItems = $cartModel->getItems($userId);

        if (empty($cartItems)) {
            return ['success' => false, 'message' => 'Cart is empty'];
        }

        // เริ่มทรานแซกชัน
        $this->db->beginTransaction();

        try {
            // ขั้นที่ 1: ตรวจสอบสต็อกและคำนวณยอดรวมใหม่
            $total = 0;
            $orderItems = [];

            foreach ($cartItems as $item) {
                // ตรวจสอบความพร้อม (สต็อก + สถานะ)
                $availability = $productModel->checkAvailability(
                    $item['product_id'],
                    $item['qty']
                );

                if (!$availability['available']) {
                    throw new \Exception("Product '{$item['name']}': {$availability['reason']}");
                }

                $product = $availability['product'];
                $currentPrice = $product['price'];

                // ความปลอดภัย: ตรวจสอบการจัดการราคา
                // ถ้า added_price แตกต่างจาก current_price มาก ให้บันทึก
                $priceDiff = abs($item['added_price'] - $currentPrice);
                if ($priceDiff > 0.01) {
                    $this->logger->security('order.price_change_detected', [
                        'user_id' => $userId,
                        'product_id' => $item['product_id'],
                        'cart_price' => $item['added_price'],
                        'current_price' => $currentPrice,
                    ]);
                }

                // ใช้ราคาปัจจุบันสำหรับการคำนวณคำสั่งซื้อ
                $subtotal = $currentPrice * $item['qty'];
                $total += $subtotal;

                $orderItems[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'qty' => $item['qty'],
                    'price' => $currentPrice,
                    'subtotal' => $subtotal,
                ];
            }

            // ขั้นที่ 2: สร้างรีคอร์ดคำสั่งซื้อ
            $stmt = $this->db->prepare("
                INSERT INTO orders (user_id, total, status) 
                VALUES (?, ?, 'pending')
            ");
            $stmt->execute([$userId, $total]);
            $orderId = $this->db->lastInsertId();

            // ขั้นที่ 3: สร้างรีคอร์ด order_items
            $stmt = $this->db->prepare("
                INSERT INTO order_items 
                (order_id, product_id, product_name, qty, price, subtotal) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($orderItems as $item) {
                $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['product_name'],
                    $item['qty'],
                    $item['price'],
                    $item['subtotal'],
                ]);
            }

            // ขั้นที่ 4: ลดสต็อกสินค้าทั้งหมด
            foreach ($orderItems as $item) {
                $success = $productModel->decreaseStock(
                    $item['product_id'],
                    $item['qty']
                );

                if (!$success) {
                    throw new \Exception("Failed to update stock for product ID {$item['product_id']}");
                }
            }

            // ขั้นที่ 5: ล้างตะกร้า
            $cartModel->clear($userId);

            // Commit ทรานแซกชัน
            $this->db->commit();

            $this->logger->info('order.created', [
                'order_id' => $orderId,
                'user_id' => $userId,
                'total' => $total,
                'item_count' => count($orderItems),
            ]);

            return [
                'success' => true,
                'message' => 'Order created successfully',
                'order_id' => $orderId,
            ];
        } catch (\Exception $e) {
            // Rollback เมื่อเกิดข้อผิดพลาด
            $this->db->rollBack();

            $this->logger->error('order.create_failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * ดึงคำสั่งซื้อจาก ID
     * 
     * @param int $orderId ID คำสั่งซื้อ
     * @return array|null ข้อมูลคำสั่งซื้อหรือ null
     */
    public function findById(int $orderId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, user_id, total, status, created_at 
            FROM orders 
            WHERE id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        return $order ?: null;
    }

    /**
     * ดึงคำสั่งซื้อพร้อมรายการสินค้า
     * 
     * @param int $orderId ID คำสั่งซื้อ
     * @return array|null คำสั่งซื้อพร้อมรายการหรือ null
     */
    public function getWithItems(int $orderId): ?array
    {
        $order = $this->findById($orderId);

        if (!$order) {
            return null;
        }

        // ดึงรายการคำสั่งซื้อ
        $stmt = $this->db->prepare("
            SELECT 
                id, product_id, product_name, qty, price, subtotal 
            FROM order_items 
            WHERE order_id = ?
        ");
        $stmt->execute([$orderId]);
        $order['items'] = $stmt->fetchAll();

        return $order;
    }

    /**
     * ดึงคำสั่งซื้อของผู้ใช้
     * 
     * @param int $userId ID ผู้ใช้
     * @return array อาร์เรย์คำสั่งซื้อ
     */
    public function getUserOrders(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, total, status, created_at 
            FROM orders 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * อัปเดตสถานะคำสั่งซื้อ
     * 
     * การเปลี่ยนสถานะที่ถูกต้อง:
     * - pending → paid
     * - paid → shipped
     * - pending/paid → cancelled
     * 
     * @param int $orderId ID คำสั่งซื้อ
     * @param string $status สถานะใหม่
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateStatus(int $orderId, string $status): array
    {
        $validStatuses = ['pending', 'paid', 'shipped', 'cancelled'];

        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }

        $order = $this->findById($orderId);

        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        // ถ้ายกเลิก คืนสต็อก
        if ($status === 'cancelled' && $order['status'] !== 'cancelled') {
            $this->returnStock($orderId);
        }

        $stmt = $this->db->prepare("
            UPDATE orders 
            SET status = ? 
            WHERE id = ?
        ");
        $stmt->execute([$status, $orderId]);

        $this->logger->info('order.status_updated', [
            'order_id' => $orderId,
            'old_status' => $order['status'],
            'new_status' => $status,
        ]);

        return ['success' => true, 'message' => 'Order status updated'];
    }

    /**
     * คืนสต็อกเมื่อคำสั่งซื้อถูกยกเลิก
     * 
     * @param int $orderId ID คำสั่งซื้อ
     */
    private function returnStock(int $orderId): void
    {
        $productModel = new Product();

        $stmt = $this->db->prepare("
            SELECT product_id, qty 
            FROM order_items 
            WHERE order_id = ?
        ");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll();

        foreach ($items as $item) {
            $productModel->increaseStock($item['product_id'], $item['qty']);
        }

        $this->logger->info('order.stock_returned', [
            'order_id' => $orderId,
            'items_count' => count($items),
        ]);
    }

    /**
     * ดึงคำสั่งซื้อทั้งหมด (ฟังก์ชันผู้ดูแลระบบ)
     * 
     * @param int $limit จำนวนคำสั่งซื้อที่จะดึง
     * @param int $offset จุดเริ่มสำหรับการแบ่งหน้า
     * @return array อาร์เรย์คำสั่งซื้อ
     */
    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                o.id, o.user_id, o.total, o.status, o.created_at,
                u.username
            FROM orders o
            INNER JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
}
