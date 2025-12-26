<?php
/**
 * โมเดลผู้ใช้
 * 
 * จุดประสงค์: จัดการการยืนยันตัวตนและบัญชีผู้ใช้
 * ความปลอดภัย: การเข้ารหัสรหัสผ่านด้วย bcrypt, การจัดการเซสชันที่ปลอดภัย
 * 
 * กฎทางธุรกิจ:
 * - รหัสผ่านต้องเข้ารหัสด้วย password_hash()
 * - ชื่อผู้ใช้และอีเมลต้องไม่ซ้ำ
 * - ตรวจสอบรูปแบบอีเมล
 * - ความยาวรหัสผ่านขั้นต่ำ (8 ตัวอักษร)
 * 
 * ความปลอดภัยสำคัญมาก:
 * - ห้ามเก็บรหัสผ่านแบบข้อความธรรมดา
 * - ใช้ password_hash() กับ PASSWORD_DEFAULT
 * - ใช้ password_verify() สำหรับการยืนยันตัวตน
 * - บันทึกความพยายามยืนยันตัวตนทั้งหมด
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
     * ลงทะเบียนผู้ใช้ใหม่
     * 
     * กระบวนการ:
     * 1. ตรวจสอบข้อมูลนำเข้า (ชื่อผู้ใช้, อีเมล, รหัสผ่าน)
     * 2. ตรวจสอบข้อมูลซ้ำ
     * 3. เข้ารหัสรหัสผ่าน
     * 4. แทรกลงในฐานข้อมูล
     * 
     * @param string $username ชื่อผู้ใช้
     * @param string $email ที่อยู่อีเมล
     * @param string $password รหัสผ่านแบบข้อความธรรมดา
     * @return array ['success' => bool, 'message' => string, 'user_id' => int|null]
     */
    public function register(string $username, string $email, string $password): array
    {
        // ตรวจสอบข้อมูลนำเข้า
        if (strlen($username) < 3) {
            return ['success' => false, 'message' => 'Username must be at least 3 characters'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters'];
        }

        // ตรวจสอบชื่อผู้ใช้ซ้ำ
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username already exists'];
        }

        // ตรวจสอบอีเมลซ้ำ
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        // เข้ารหัสรหัสผ่าน (bcrypt พร้อม salt อัตโนมัติ)
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // แทรกผู้ใช้
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
     * ยืนยันตัวตนการเข้าสู่ระบบของผู้ใช้
     * 
     * กระบวนการ:
     * 1. ค้นหาผู้ใช้จากชื่อผู้ใช้
     * 2. ตรวจสอบแฮชรหัสผ่าน
     * 3. สร้างเซสชัน
     * 
     * ความปลอดภัย: บันทึกความพยายามเข้าสู่ระบบทั้งหมด (สำเร็จและล้มเหลว)
     * 
     * @param string $username ชื่อผู้ใช้
     * @param string $password รหัสผ่านแบบข้อความธรรมดา
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

        // ไม่พบผู้ใช้
        if (!$user) {
            $this->logger->security('login.failed', [
                'username' => $username,
                'reason' => 'user_not_found',
            ]);

            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        // ตรวจสอบรหัสผ่าน
        if (!password_verify($password, $user['password'])) {
            $this->logger->security('login.failed', [
                'user_id' => $user['id'],
                'username' => $username,
                'reason' => 'invalid_password',
            ]);

            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        // ลบรหัสผ่านออกจากข้อมูลที่คืนค่า
        unset($user['password']);

        // สร้างเซสชัน
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
     * ออกจากระบบผู้ใช้ปัจจุบัน
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
     * ดึงข้อมูลผู้ใช้จาก ID
     * 
     * @param int $userId ID ผู้ใช้
     * @return array|null ข้อมูลผู้ใช้หรือ null
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
     * ดึงข้อมูลผู้ใช้จากชื่อผู้ใช้
     * 
     * @param string $username ชื่อผู้ใช้
     * @return array|null ข้อมูลผู้ใช้หรือ null
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
