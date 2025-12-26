<?php
/**
 * โมเดลตะกร้าสินค้า
 * 
 * จุดประสงค์: จัดการฟังก์ชันตะกร้าสินค้า
 * ความปลอดภัย: การตรวจสอบราคาฝั่งเซิร์ฟเวอร์, การตรวจสอบสต็อก
 * 
 * กฎทางธุรกิจ:
 * - แต่ละผู้ใช้มีตะกร้าที่ใช้งานได้หนึ่งตะกร้า
 * - จำนวนต้อง > 0
 * - ไม่สามารถเกินสต็อกที่มีอยู่
 * - ราคาถูกเก็บไว้เพื่อแสดงผลแต่จะคำนวณใหม่ตอนชำระเงิน
 * 
 * กฎความปลอดภัยสำคัญ:
 * - ห้ามเชื่อถือราคาที่ไคลเอนต์ส่งมา
 * - ดึงราคาปัจจุบันจากตาราง products เสมอ
 * - cart_items.price เป็นเพื่อแสดงผลเท่านั้น ไม่ใช่สำหรับคำนวณยอดรวมคำสั่งซื้อ
 * - เซิร์ฟเวอร์คำนวณทุกอย่างใหม่ตอนชำระเงิน
 * 
 * ทำไมต้องเก็บราคาใน cart_items?
 * - แสดงให้ผู้ใช้เห็นราคาตอนที่เพิ่มสินค้า
 * - ถ้าราคาสินค้าเปลี่ยน ผู้ใช้จะเห็นทั้งสองราคา
 * - แต่: การชำระเงินใช้ราคาปัจจุบันจากตาราง products
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class Cart
{
    private PDO $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }

    /**
     * ดึงหรือสร้างตะกร้าสำหรับผู้ใช้
     * 
     * แต่ละผู้ใช้มีตะกร้าที่ใช้งานหนึ่งตะกร้า
     * สร้างตะกร้าอัตโนมัติถ้ายังไม่มี
     * 
     * @param int $userId ID ผู้ใช้
     * @return int ID ตะกร้า
     */
    public function getOrCreateCart(int $userId): int
    {
        // ตรวจสอบว่าผู้ใช้มีตะกร้าอยู่แล้วหรือไม่
        $stmt = $this->db->prepare("SELECT id FROM carts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $cart = $stmt->fetch();

        if ($cart) {
            return $cart['id'];
        }

        // สร้างตะกร้าใหม่
        $stmt = $this->db->prepare("INSERT INTO carts (user_id) VALUES (?)");
        $stmt->execute([$userId]);

        $cartId = $this->db->lastInsertId();

        $this->logger->info('cart.created', [
            'cart_id' => $cartId,
            'user_id' => $userId,
        ]);

        return $cartId;
    }

    /**
     * เพิ่มสินค้าลงตะกร้า
     * 
     * กระบวนการ:
     * 1. ตรวจสอบว่าสินค้ามีอยู่และมีสต็อก
     * 2. ดึงราคาปัจจุบันจากตาราง products (ความปลอดภัย!)
     * 3. ถ้าสินค้าอยู่ในตะกร้าแล้ว อัปเดตจำนวน
     * 4. ถ้าไม่มี แทรกรายการตะกร้าใหม่
     * 
     * @param int $userId ID ผู้ใช้
     * @param int $productId ID สินค้า
     * @param int $quantity จำนวนที่จะเพิ่ม
     * @return array ['success' => bool, 'message' => string]
     */
    public function addItem(int $userId, int $productId, int $quantity): array
    {
        // ตรวจสอบจำนวน
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Quantity must be greater than 0'];
        }

        // ดึงข้อมูลสินค้าและตรวจสอบความพร้อม
        $productModel = new Product();
        $availability = $productModel->checkAvailability($productId, $quantity);

        if (!$availability['available']) {
            $this->logger->security('cart.add_failed', [
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'reason' => $availability['reason'],
            ]);

            return ['success' => false, 'message' => $availability['reason']];
        }

        $product = $availability['product'];
        $currentPrice = $product['price']; // ใช้ราคาจากฐานข้อมูลเสมอ!

        // ดึงหรือสร้างตะกร้า
        $cartId = $this->getOrCreateCart($userId);

        // ตรวจสอบว่าสินค้าอยู่ในตะกร้าแล้วหรือไม่
        $stmt = $this->db->prepare("
            SELECT id, qty 
            FROM cart_items 
            WHERE cart_id = ? AND product_id = ?
        ");
        $stmt->execute([$cartId, $productId]);
        $existingItem = $stmt->fetch();

        try {
            if ($existingItem) {
                // อัปเดตจำนวน
                $newQuantity = $existingItem['qty'] + $quantity;

                // ตรวจสอบว่าจำนวนใหม่เกินสต็อกหรือไม่
                if ($newQuantity > $product['stock']) {
                    return [
                        'success' => false,
                        'message' => "Cannot add {$quantity} more. Only {$product['stock']} available.",
                    ];
                }

                $stmt = $this->db->prepare("
                    UPDATE cart_items 
                    SET qty = ?, price = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$newQuantity, $currentPrice, $existingItem['id']]);
            } else {
                // แทรกรายการตะกร้าใหม่
                $stmt = $this->db->prepare("
                    INSERT INTO cart_items (cart_id, product_id, qty, price) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$cartId, $productId, $quantity, $currentPrice]);
            }

            $this->logger->info('cart.item_added', [
                'user_id' => $userId,
                'cart_id' => $cartId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $currentPrice,
            ]);

            return ['success' => true, 'message' => 'Product added to cart'];
        } catch (\PDOException $e) {
            $this->logger->error('cart.add_failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to add product to cart'];
        }
    }

    /**
     * อัปเดตจำนวนรายการในตะกร้า
     * 
     * @param int $userId ID ผู้ใช้
     * @param int $productId ID สินค้า
     * @param int $quantity จำนวนใหม่
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateQuantity(int $userId, int $productId, int $quantity): array
    {
        if ($quantity <= 0) {
            return $this->removeItem($userId, $productId);
        }

        // ตรวจสอบสต็อกที่มี
        $productModel = new Product();
        $availability = $productModel->checkAvailability($productId, $quantity);

        if (!$availability['available']) {
            return ['success' => false, 'message' => $availability['reason']];
        }

        $cartId = $this->getOrCreateCart($userId);

        $stmt = $this->db->prepare("
            UPDATE cart_items 
            SET qty = ? 
            WHERE cart_id = ? AND product_id = ?
        ");
        $stmt->execute([$quantity, $cartId, $productId]);

        if ($stmt->rowCount() > 0) {
            $this->logger->info('cart.quantity_updated', [
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);

            return ['success' => true, 'message' => 'Quantity updated'];
        }

        return ['success' => false, 'message' => 'Product not found in cart'];
    }

    /**
     * ลบรายการออกจากตะกร้า
     * 
     * @param int $userId ID ผู้ใช้
     * @param int $productId ID สินค้า
     * @return array ['success' => bool, 'message' => string]
     */
    public function removeItem(int $userId, int $productId): array
    {
        $cartId = $this->getOrCreateCart($userId);

        $stmt = $this->db->prepare("
            DELETE FROM cart_items 
            WHERE cart_id = ? AND product_id = ?
        ");
        $stmt->execute([$cartId, $productId]);

        if ($stmt->rowCount() > 0) {
            $this->logger->info('cart.item_removed', [
                'user_id' => $userId,
                'product_id' => $productId,
            ]);

            return ['success' => true, 'message' => 'Item removed from cart'];
        }

        return ['success' => false, 'message' => 'Item not found in cart'];
    }

    /**
     * ดึงเนื้อหาตะกร้าพร้อมรายละเอียดสินค้าปัจจุบัน
     * 
     * สำคัญ: เชื่อมกับตาราง products เพื่อดึงราคาปัจจุบัน
     * แสดงทั้งราคาที่เก็บไว้และราคาปัจจุบันเพื่อเปรียบเทียบ
     * 
     * @param int $userId ID ผู้ใช้
     * @return array รายการตะกร้าพร้อมรายละเอียดสินค้า
     */
    public function getItems(int $userId): array
    {
        $cartId = $this->getOrCreateCart($userId);

        $stmt = $this->db->prepare("
            SELECT 
                ci.id,
                ci.product_id,
                ci.qty,
                ci.price as added_price,
                p.name,
                p.description,
                p.price as current_price,
                p.stock,
                p.status,
                (ci.qty * p.price) as subtotal
            FROM cart_items ci
            INNER JOIN products p ON ci.product_id = p.id
            WHERE ci.cart_id = ?
            ORDER BY ci.created_at DESC
        ");
        $stmt->execute([$cartId]);
        return $stmt->fetchAll();
    }

    /**
     * คำนวณยอดรวมตะกร้าโดยใช้ราคาปัจจุบัน
     * 
     * ความปลอดภัย: ใช้ราคาปัจจุบันจากตาราง products
     * ห้ามเชื่อถือยอดรวมที่ไคลเอนต์ส่งมา
     * 
     * @param int $userId ID ผู้ใช้
     * @return array ['total' => float, 'item_count' => int]
     */
    public function calculateTotal(int $userId): array
    {
        $items = $this->getItems($userId);

        $total = 0;
        $itemCount = 0;

        foreach ($items as $item) {
            // ใช้ current_price ไม่ใช่ added_price!
            $total += $item['current_price'] * $item['qty'];
            $itemCount += $item['qty'];
        }

        return [
            'total' => $total,
            'item_count' => $itemCount,
        ];
    }

    /**
     * ล้างตะกร้าหลังจากวางคำสั่งซื้อ
     * 
     * @param int $userId ID ผู้ใช้
     */
    public function clear(int $userId): void
    {
        $cartId = $this->getOrCreateCart($userId);

        $stmt = $this->db->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        $stmt->execute([$cartId]);

        $this->logger->info('cart.cleared', [
            'user_id' => $userId,
            'cart_id' => $cartId,
        ]);
    }

    /**
     * ดึงจำนวนรายการในตะกร้า
     * 
     * @param int $userId ID ผู้ใช้
     * @return int จำนวนรายการทั้งหมดในตะกร้า
     */
    public function getItemCount(int $userId): int
    {
        $cartId = $this->getOrCreateCart($userId);

        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(qty), 0) as total 
            FROM cart_items 
            WHERE cart_id = ?
        ");
        $stmt->execute([$cartId]);
        $result = $stmt->fetch();

        return (int) $result['total'];
    }
}
