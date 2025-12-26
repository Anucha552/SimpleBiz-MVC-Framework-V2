<?php
/**
 * CART MODEL
 * 
 * Purpose: Manages shopping cart functionality
 * Security: Server-side price validation, stock checking
 * 
 * Business Rules:
 * - Each user has ONE active cart
 * - Quantities must be > 0
 * - Cannot exceed available stock
 * - Prices stored for display but RECALCULATED at checkout
 * 
 * CRITICAL SECURITY RULE:
 * - NEVER trust client-submitted prices
 * - ALWAYS fetch current price from products table
 * - cart_items.price is for display only, not for order total calculation
 * - Server recalculates everything at checkout
 * 
 * Why store price in cart_items?
 * - Shows user the price when they added item
 * - If product price changes, user sees both prices
 * - BUT: checkout uses CURRENT price from products table
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
     * Get or create cart for user
     * 
     * Each user has one active cart
     * Creates cart automatically if doesn't exist
     * 
     * @param int $userId User ID
     * @return int Cart ID
     */
    public function getOrCreateCart(int $userId): int
    {
        // Check if user has existing cart
        $stmt = $this->db->prepare("SELECT id FROM carts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $cart = $stmt->fetch();

        if ($cart) {
            return $cart['id'];
        }

        // Create new cart
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
     * Add product to cart
     * 
     * Process:
     * 1. Validate product exists and has stock
     * 2. Get current price from products table (SECURITY!)
     * 3. If product already in cart, update quantity
     * 4. Otherwise, insert new cart item
     * 
     * @param int $userId User ID
     * @param int $productId Product ID
     * @param int $quantity Quantity to add
     * @return array ['success' => bool, 'message' => string]
     */
    public function addItem(int $userId, int $productId, int $quantity): array
    {
        // Validate quantity
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Quantity must be greater than 0'];
        }

        // Get product and validate availability
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
        $currentPrice = $product['price']; // ALWAYS use price from database!

        // Get or create cart
        $cartId = $this->getOrCreateCart($userId);

        // Check if product already in cart
        $stmt = $this->db->prepare("
            SELECT id, qty 
            FROM cart_items 
            WHERE cart_id = ? AND product_id = ?
        ");
        $stmt->execute([$cartId, $productId]);
        $existingItem = $stmt->fetch();

        try {
            if ($existingItem) {
                // Update quantity
                $newQuantity = $existingItem['qty'] + $quantity;

                // Check if new quantity exceeds stock
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
                // Insert new cart item
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
     * Update cart item quantity
     * 
     * @param int $userId User ID
     * @param int $productId Product ID
     * @param int $quantity New quantity
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateQuantity(int $userId, int $productId, int $quantity): array
    {
        if ($quantity <= 0) {
            return $this->removeItem($userId, $productId);
        }

        // Validate stock availability
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
     * Remove item from cart
     * 
     * @param int $userId User ID
     * @param int $productId Product ID
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
     * Get cart contents with current product details
     * 
     * IMPORTANT: Joins with products table to get CURRENT prices
     * Shows both stored price and current price for comparison
     * 
     * @param int $userId User ID
     * @return array Cart items with product details
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
     * Calculate cart total using CURRENT prices
     * 
     * SECURITY: This uses current prices from products table
     * NEVER trust client-submitted totals
     * 
     * @param int $userId User ID
     * @return array ['total' => float, 'item_count' => int]
     */
    public function calculateTotal(int $userId): array
    {
        $items = $this->getItems($userId);

        $total = 0;
        $itemCount = 0;

        foreach ($items as $item) {
            // Use current_price, not added_price!
            $total += $item['current_price'] * $item['qty'];
            $itemCount += $item['qty'];
        }

        return [
            'total' => $total,
            'item_count' => $itemCount,
        ];
    }

    /**
     * Clear cart after order placement
     * 
     * @param int $userId User ID
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
     * Get cart item count
     * 
     * @param int $userId User ID
     * @return int Total items in cart
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
