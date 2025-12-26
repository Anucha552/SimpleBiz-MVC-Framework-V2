<?php
/**
 * PRODUCT MODEL
 * 
 * Purpose: Manages product catalog and inventory
 * Security: Validates stock levels, prevents negative inventory
 * 
 * Business Rules:
 * - Price must be >= 0
 * - Stock must be >= 0
 * - Only 'active' products visible to customers
 * - Stock validation before order placement
 * - Price stored as DECIMAL for accuracy
 * 
 * IMPORTANT:
 * - Prices are read from database, NEVER from client input
 * - Stock is managed server-side only
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
     * Get all active products
     * 
     * Returns only products with status='active'
     * Ordered by creation date (newest first)
     * 
     * @return array Products array
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
     * Get product by ID
     * 
     * @param int $id Product ID
     * @return array|null Product data or null
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
     * Check if product is available
     * 
     * Validates:
     * - Product exists
     * - Product is active
     * - Sufficient stock available
     * 
     * @param int $productId Product ID
     * @param int $quantity Requested quantity
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
     * Decrease product stock
     * 
     * CRITICAL: Call this ONLY after successful order placement
     * Transaction-safe: Uses optimistic locking to prevent race conditions
     * 
     * @param int $productId Product ID
     * @param int $quantity Quantity to decrease
     * @return bool Success status
     */
    public function decreaseStock(int $productId, int $quantity): bool
    {
        // Use WHERE clause with stock check to prevent negative stock
        // This prevents race conditions between check and update
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
     * Increase product stock
     * 
     * Used for order cancellations or stock replenishment
     * 
     * @param int $productId Product ID
     * @param int $quantity Quantity to increase
     * @return bool Success status
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
     * Create new product
     * 
     * @param array $data Product data
     * @return array ['success' => bool, 'message' => string, 'product_id' => int|null]
     */
    public function create(array $data): array
    {
        // Validate required fields
        $required = ['name', 'price', 'stock'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return ['success' => false, 'message' => "Missing required field: {$field}"];
            }
        }

        // Validate price
        if ($data['price'] < 0) {
            return ['success' => false, 'message' => 'Price must be >= 0'];
        }

        // Validate stock
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
     * Search products by name
     * 
     * @param string $query Search query
     * @return array Products array
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
