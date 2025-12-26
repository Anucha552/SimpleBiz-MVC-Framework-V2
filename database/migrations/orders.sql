/*
 * ORDERS TABLES MIGRATION
 * 
 * Purpose: Manages customer orders and order items
 * Security: Prices are recalculated server-side at checkout
 * 
 * ORDERS TABLE:
 * - Stores order header (user, total, status, date)
 * - Status tracks order lifecycle
 * 
 * ORDER_ITEMS TABLE:
 * - Stores snapshot of purchased items
 * - price is locked at purchase time (not linked to products table)
 * - subtotal = qty * price (calculated)
 * 
 * ORDER STATUS WORKFLOW:
 * - pending: Order placed, awaiting payment
 * - paid: Payment received, ready to ship
 * - shipped: Order dispatched
 * - cancelled: Order cancelled
 * 
 * SECURITY RULES:
 * - Total is calculated server-side from order_items
 * - Stock is decremented ONLY after successful order
 * - Validate stock availability before order creation
 * - Log any price manipulation attempts
 */

-- Main orders table
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

-- Order items table (snapshot of purchased products)
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
