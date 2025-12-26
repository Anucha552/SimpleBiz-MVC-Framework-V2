/*
 * USERS TABLE MIGRATION
 * 
 * Purpose: Stores user accounts for authentication
 * Security: Passwords must be hashed using password_hash()
 * 
 * Fields:
 * - id: Primary key
 * - username: Unique username for login
 * - email: Unique email address
 * - password: Hashed password (bcrypt/argon2)
 * - created_at: Account creation timestamp
 */

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
