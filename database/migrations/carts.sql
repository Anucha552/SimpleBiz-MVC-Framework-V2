/*
 * SHOPPING CART TABLES MIGRATION
 * 
 * Purpose: Manages user shopping carts and cart items
 * Security: Foreign keys ensure referential integrity
 * 
 * CARTS TABLE:
 * - One cart per user session/account
 * - cart_id links to cart_items
 * 
 * CART_ITEMS TABLE:
 * - Stores individual products in cart
 * - price snapshot: stores price at time of adding (for reference)
 * - qty: quantity of product
 * 
 * IMPORTANT SECURITY RULES:
 * - NEVER trust client-side prices for checkout
 * - Server MUST recalculate from products table
 * - cart_items.price is for display only, not calculation
 * - Validate qty > 0 and qty <= available stock
 */

-- Main cart table (one per user)
CREATE TABLE IF NOT EXISTS carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fk_cart_user) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cart items table (products in cart)
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
