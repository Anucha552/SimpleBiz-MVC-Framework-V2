<?php
/**
 * USER MODEL
 * 
 * Purpose: Handles user authentication and account management
 * Security: Password hashing with bcrypt, secure session management
 * 
 * Business Rules:
 * - Passwords must be hashed using password_hash()
 * - Username and email must be unique
 * - Validate email format
 * - Minimum password length (8 characters)
 * 
 * SECURITY CRITICAL:
 * - NEVER store plain text passwords
 * - Use password_hash() with PASSWORD_DEFAULT
 * - Use password_verify() for authentication
 * - Log all authentication attempts
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class User
{
    private PDO $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }

    /**
     * Register new user
     * 
     * Process:
     * 1. Validate input (username, email, password)
     * 2. Check for duplicates
     * 3. Hash password
     * 4. Insert into database
     * 
     * @param string $username Username
     * @param string $email Email address
     * @param string $password Plain text password
     * @return array ['success' => bool, 'message' => string, 'user_id' => int|null]
     */
    public function register(string $username, string $email, string $password): array
    {
        // Validate input
        if (strlen($username) < 3) {
            return ['success' => false, 'message' => 'Username must be at least 3 characters'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters'];
        }

        // Check for duplicate username
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username already exists'];
        }

        // Check for duplicate email
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        // Hash password (bcrypt with automatic salt)
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password) 
            VALUES (?, ?, ?)
        ");

        try {
            $stmt->execute([$username, $email, $hashedPassword]);
            $userId = $this->db->lastInsertId();

            $this->logger->info('user.registered', [
                'user_id' => $userId,
                'username' => $username,
            ]);

            return [
                'success' => true,
                'message' => 'Registration successful',
                'user_id' => $userId,
            ];
        } catch (\PDOException $e) {
            $this->logger->error('user.register_failed', [
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Registration failed'];
        }
    }

    /**
     * Authenticate user login
     * 
     * Process:
     * 1. Find user by username
     * 2. Verify password hash
     * 3. Create session
     * 
     * SECURITY: Log all login attempts (success and failure)
     * 
     * @param string $username Username
     * @param string $password Plain text password
     * @return array ['success' => bool, 'message' => string, 'user' => array|null]
     */
    public function login(string $username, string $password): array
    {
        $stmt = $this->db->prepare("
            SELECT id, username, email, password 
            FROM users 
            WHERE username = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // User not found
        if (!$user) {
            $this->logger->security('login.failed', [
                'username' => $username,
                'reason' => 'user_not_found',
            ]);

            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            $this->logger->security('login.failed', [
                'user_id' => $user['id'],
                'username' => $username,
                'reason' => 'invalid_password',
            ]);

            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        // Remove password from returned data
        unset($user['password']);

        // Create session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        $this->logger->info('login.success', [
            'user_id' => $user['id'],
            'username' => $username,
        ]);

        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => $user,
        ];
    }

    /**
     * Logout current user
     */
    public function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;

        if ($userId) {
            $this->logger->info('logout', ['user_id' => $userId]);
        }

        session_destroy();
    }

    /**
     * Get user by ID
     * 
     * @param int $userId User ID
     * @return array|null User data or null
     */
    public function findById(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, username, email, created_at 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Get user by username
     * 
     * @param string $username Username
     * @return array|null User data or null
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, username, email, created_at 
            FROM users 
            WHERE username = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        return $user ?: null;
    }
}
