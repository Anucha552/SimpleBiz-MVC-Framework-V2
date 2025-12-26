<?php
/**
 * ORDER MODEL
 * 
 * Purpose: Manages order creation and lifecycle
 * Security: Server-side total calculation, stock validation, price verification
 * 
 * Business Rules:
 * - Orders created from cart items
 * - Prices RECALCULATED from products table (NEVER trust client)
 * - Stock validated and decremented atomically
 * - Order status: pending → paid → shipped → cancelled
 * - Stock returned on cancellation
 * 
 * CRITICAL SECURITY RULES:
 * - ALWAYS recalculate total from current product prices
 * - Validate stock availability before order creation
 * - Use database transactions for atomicity
 * - Log any price discrepancies (tamper attempts)
 * - Decrease stock ONLY after successful order creation
 * 
 * Why separate order and order_items?
 * - order: Header info (user, total, status)
 * - order_items: Line items (snapshot at purchase time)
 * - If product deleted later, order history preserved
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
     * Create order from cart
     * 
     * Process (ATOMIC TRANSACTION):
     * 1. Get cart items
     * 2. Validate stock for all items
     * 3. Recalculate total from current prices
     * 4. Create order record
     * 5. Create order_items records
     * 6. Decrease stock for all products
     * 7. Clear cart
     * 
     * If ANY step fails, entire transaction is rolled back
     * 
     * @param int $userId User ID
     * @return array ['success' => bool, 'message' => string, 'order_id' => int|null]
     */
    public function createFromCart(int $userId): array
    {
        $cartModel = new Cart();
        $productModel = new Product();

        // Get cart items
        $cartItems = $cartModel->getItems($userId);

        if (empty($cartItems)) {
            return ['success' => false, 'message' => 'Cart is empty'];
        }

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Step 1: Validate stock and recalculate total
            $total = 0;
            $orderItems = [];

            foreach ($cartItems as $item) {
                // Check availability (stock + status)
                $availability = $productModel->checkAvailability(
                    $item['product_id'],
                    $item['qty']
                );

                if (!$availability['available']) {
                    throw new \Exception("Product '{$item['name']}': {$availability['reason']}");
                }

                $product = $availability['product'];
                $currentPrice = $product['price'];

                // SECURITY: Check for price manipulation
                // If added_price differs significantly from current_price, log it
                $priceDiff = abs($item['added_price'] - $currentPrice);
                if ($priceDiff > 0.01) {
                    $this->logger->security('order.price_change_detected', [
                        'user_id' => $userId,
                        'product_id' => $item['product_id'],
                        'cart_price' => $item['added_price'],
                        'current_price' => $currentPrice,
                    ]);
                }

                // Use CURRENT price for order calculation
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

            // Step 2: Create order record
            $stmt = $this->db->prepare("
                INSERT INTO orders (user_id, total, status) 
                VALUES (?, ?, 'pending')
            ");
            $stmt->execute([$userId, $total]);
            $orderId = $this->db->lastInsertId();

            // Step 3: Create order_items records
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

            // Step 4: Decrease stock for all products
            foreach ($orderItems as $item) {
                $success = $productModel->decreaseStock(
                    $item['product_id'],
                    $item['qty']
                );

                if (!$success) {
                    throw new \Exception("Failed to update stock for product ID {$item['product_id']}");
                }
            }

            // Step 5: Clear cart
            $cartModel->clear($userId);

            // Commit transaction
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
            // Rollback on any error
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
     * Get order by ID
     * 
     * @param int $orderId Order ID
     * @return array|null Order data or null
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
     * Get order with items
     * 
     * @param int $orderId Order ID
     * @return array|null Order with items or null
     */
    public function getWithItems(int $orderId): ?array
    {
        $order = $this->findById($orderId);

        if (!$order) {
            return null;
        }

        // Get order items
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
     * Get user orders
     * 
     * @param int $userId User ID
     * @return array Orders array
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
     * Update order status
     * 
     * Valid transitions:
     * - pending → paid
     * - paid → shipped
     * - pending/paid → cancelled
     * 
     * @param int $orderId Order ID
     * @param string $status New status
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

        // If cancelling, return stock
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
     * Return stock when order is cancelled
     * 
     * @param int $orderId Order ID
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
     * Get all orders (admin function)
     * 
     * @param int $limit Number of orders to retrieve
     * @param int $offset Offset for pagination
     * @return array Orders array
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
