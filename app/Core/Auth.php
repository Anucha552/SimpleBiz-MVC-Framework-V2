<?php
/**
 * คลาส Auth
 * 
 * จุดประสงค์: จัดการ Authentication และ Authorization
 * ฟีเจอร์: login, logout, ตรวจสอบสิทธิ์, remember me
 * 
 * ฟีเจอร์หลัก:
 * - Login/Logout
 * - ตรวจสอบการเข้าสู่ระบบ
 * - รับข้อมูลผู้ใช้ที่ล็อกอิน
 * - Remember Me functionality
 * - Password hashing
 * 
 * ตัวอย่างการใช้งาน:
 * ```php
 * // Login
 * if (Auth::attempt(['username' => $username, 'password' => $password])) {
 *     // ล็อกอินสำเร็จ
 * }
 * 
 * // ตรวจสอบ
 * if (Auth::check()) {
 *     $user = Auth::user();
 * }
 * 
 * // Logout
 * Auth::logout();
 * ```
 */

namespace App\Core;

class Auth
{
    /**
     * Session key สำหรับ user ID
     */
    private const SESSION_KEY = '_auth_user_id';

    /**
     * Remember me cookie name
     */
    private const REMEMBER_COOKIE = '_auth_remember';

    /**
     * Remember me token length
     */
    private const REMEMBER_TOKEN_LENGTH = 64;

    /**
     * Remember me duration (30 วัน)
     */
    private const REMEMBER_DURATION = 60 * 60 * 24 * 30;

    /**
     * User data ที่ล็อกอินอยู่
     */
    private static ?array $user = null;

    /**
     * พยายามเข้าสู่ระบบ
     * 
     * @param array $credentials ข้อมูลการเข้าสู่ระบบ ['username' => '...', 'password' => '...']
     * @param bool $remember จดจำการเข้าสู่ระบบหรือไม่
     * @return bool
     */
    public static function attempt(array $credentials, bool $remember = false): bool
    {
        // ต้องมี username/email และ password
        $identifier = $credentials['username'] ?? $credentials['email'] ?? null;
        $password = $credentials['password'] ?? null;

        if (!$identifier || !$password) {
            return false;
        }

        // ค้นหาผู้ใช้
        $user = self::findUserByCredentials($identifier);

        if (!$user) {
            return false;
        }

        // ตรวจสอบรหัสผ่าน
        if (!self::verifyPassword($password, $user['password'])) {
            return false;
        }

        // ล็อกอินผู้ใช้
        self::login($user, $remember);

        return true;
    }

    /**
     * เข้าสู่ระบบด้วย User object
     * 
     * @param array|object $user
     * @param bool $remember
     */
    public static function login($user, bool $remember = false): void
    {
        Session::start();

        // แปลงเป็น array ถ้าเป็น object
        if (is_object($user)) {
            /** @var object{id: int} $user */
            $userId = $user->id;
        } else {
            $userId = $user['id'];
        }

        // บันทึก user ID ใน session
        Session::set(self::SESSION_KEY, $userId);

        // สร้าง session ใหม่เพื่อป้องกัน session fixation
        Session::regenerate();

        // Remember me
        if ($remember) {
            self::setRememberToken($userId);
        }

        // โหลดข้อมูลผู้ใช้
        self::$user = self::getUserById($userId);
    }

    /**
     * ออกจากระบบ
     */
    public static function logout(): void
    {
        Session::start();

        // ลบ remember token
        if (self::check()) {
            $userId = Session::get(self::SESSION_KEY);
            self::removeRememberToken($userId);
        }

        // ลบข้อมูล session
        Session::remove(self::SESSION_KEY);
        self::$user = null;

        // ลบ cookie
        if (isset($_COOKIE[self::REMEMBER_COOKIE])) {
            setcookie(self::REMEMBER_COOKIE, '', time() - 3600, '/');
            unset($_COOKIE[self::REMEMBER_COOKIE]);
        }

        // สร้าง session ใหม่
        Session::regenerate();
    }

    /**
     * ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
     * 
     * @return bool
     */
    public static function check(): bool
    {
        Session::start();

        // ตรวจสอบ session
        if (Session::has(self::SESSION_KEY)) {
            return true;
        }

        // ตรวจสอบ remember me cookie
        if (self::checkRememberToken()) {
            return true;
        }

        return false;
    }

    /**
     * ตรวจสอบว่าเป็นแขก (ไม่ได้ล็อกอิน)
     * 
     * @return bool
     */
    public static function guest(): bool
    {
        return !self::check();
    }

    /**
     * รับข้อมูลผู้ใช้ที่ล็อกอินอยู่
     * 
     * @return array|null User data as associative array
     */
    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }

        if (!self::check()) {
            return null;
        }

        Session::start();
        $userId = Session::get(self::SESSION_KEY);

        if ($userId) {
            self::$user = self::getUserById($userId);
        }

        return self::$user;
    }

    /**
     * รับ ID ของผู้ใช้ที่ล็อกอินอยู่
     * 
     * @return int|null
     */
    public static function id(): ?int
    {
        $user = self::user();
        if ($user && is_array($user)) {
            return $user['id'] ?? null;
        }
        return null;
    }

    /**
     * เข้าสู่ระบบด้วย ID
     * 
     * @param int $userId
     * @param bool $remember
     * @return bool
     */
    public static function loginById(int $userId, bool $remember = false): bool
    {
        $user = self::getUserById($userId);

        if (!$user) {
            return false;
        }

        self::login($user, $remember);
        return true;
    }

    /**
     * เข้าสู่ระบบแบบชั่วคราว (สำหรับการทดสอบ)
     * 
     * @param array $user User data as associative array
     */
    public static function loginTemporary(array $user): void
    {
        self::$user = $user;
    }

    // ========== Password Methods ==========

    /**
     * Hash password
     * 
     * @param string $password
     * @return string
     */
    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * ตรวจสอบ password
     * 
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * ตรวจสอบ password (alias ของ verifyPassword)
     * 
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verify(string $password, string $hash): bool
    {
        return self::verifyPassword($password, $hash);
    }

    /**
     * ตรวจสอบว่า hash ต้องทำใหม่หรือไม่
     * 
     * @param string $hash
     * @return bool
     */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT);
    }

    // ========== Remember Me Methods ==========

    /**
     * ตั้งค่า remember token
     * 
     * @param int $userId
     */
    private static function setRememberToken(int $userId): void
    {
        // สร้าง token
        $token = bin2hex(random_bytes(self::REMEMBER_TOKEN_LENGTH));
        $hashedToken = hash('sha256', $token);

        // บันทึกใน database
        $db = Database::getInstance()->getConnection();
        $sql = "UPDATE users SET remember_token = :token WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'token' => $hashedToken,
            'id' => $userId
        ]);

        // ตั้งค่า cookie
        setcookie(
            self::REMEMBER_COOKIE,
            $userId . '|' . $token,
            time() + self::REMEMBER_DURATION,
            '/',
            '',
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            true
        );
    }

    /**
     * ตรวจสอบ remember token
     * 
     * @return bool
     */
    private static function checkRememberToken(): bool
    {
        if (!isset($_COOKIE[self::REMEMBER_COOKIE])) {
            return false;
        }

        $cookieValue = $_COOKIE[self::REMEMBER_COOKIE];
        $parts = explode('|', $cookieValue, 2);

        if (count($parts) !== 2) {
            return false;
        }

        [$userId, $token] = $parts;
        $hashedToken = hash('sha256', $token);

        // ตรวจสอบใน database
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT id FROM users WHERE id = :id AND remember_token = :token";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'id' => $userId,
            'token' => $hashedToken
        ]);

        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user) {
            // ล็อกอินอัตโนมัติ
            Session::set(self::SESSION_KEY, $userId);
            return true;
        }

        return false;
    }

    /**
     * ลบ remember token
     * 
     * @param int $userId
     */
    private static function removeRememberToken(int $userId): void
    {
        // ลบจาก database
        $db = Database::getInstance()->getConnection();
        $sql = "UPDATE users SET remember_token = NULL WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $userId]);
    }

    // ========== Helper Methods ==========

    /**
     * ค้นหาผู้ใช้จาก credentials
     * 
     * @param string $identifier username หรือ email
     * @return array|null
     */
    private static function findUserByCredentials(string $identifier): ?array
    {
        $db = Database::getInstance()->getConnection();

        // ตรวจสอบว่าเป็น email หรือ username
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $sql = "SELECT * FROM users WHERE email = :identifier LIMIT 1";
        } else {
            $sql = "SELECT * FROM users WHERE username = :identifier LIMIT 1";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute(['identifier' => $identifier]);

        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * รับข้อมูลผู้ใช้จาก ID
     * 
     * @param int $userId
     * @return array|null User data as associative array
     */
    private static function getUserById(int $userId): ?array
    {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM users WHERE id = :id LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $userId]);

        $userData = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $userData ?: null;
    }

    /**
     * ตรวจสอบว่าผู้ใช้มีสิทธิ์หรือไม่ (สำหรับขยายในอนาคต)
     * 
     * @param string $permission
     * @return bool
     */
    public static function can(string $permission): bool
    {
        $user = self::user();

        if (!$user) {
            return false;
        }

        // ตรวจสอบสิทธิ์ตามโครงสร้างของคุณ
        // ตัวอย่าง: ตรวจสอบใน database หรือ user property
        
        return false; // ให้คุณเพิ่มเติมตามระบบของคุณ
    }

    /**
     * ตรวจสอบว่าผู้ใช้มี role หรือไม่ (สำหรับขยายในอนาคต)
     * 
     * @param string $role
     * @return bool
     */
    public static function hasRole(string $role): bool
    {
        $user = self::user();

        if (!$user) {
            return false;
        }

        // ตรวจสอบ role ตามโครงสร้างของคุณ
        // ตัวอย่าง: $user->role === $role
        
        return false; // ให้คุณเพิ่มเติมตามระบบของคุณ
    }
}
