/*
 * PRODUCTS TABLE MIGRATION
 * 
 * Purpose: Stores product catalog for e-commerce
 * Security: Price stored as DECIMAL to prevent floating-point errors
 * 
 * Fields:
 * - id: Primary key
 * - name: Product name
 * - description: Product description
 * - price: Product price (DECIMAL for accuracy)
 * - stock: Available inventory (INT, cannot be negative)
 * - status: Product visibility (active/inactive)
 * - created_at: Product creation timestamp
 * 
 * Business Rules:
 * - stock must be >= 0 (enforced by CHECK constraint)
 * - price must be >= 0
 * - status determines if product is visible to customers
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
