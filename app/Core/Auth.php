<?php
/**
 * คลาส Auth สำหรับการจัดการการพิสูจน์ตัวตน (Authentication) และการอนุญาต (Authorization)
 * 
 * จุดประสงค์: จัดการ Authentication และ Authorization ในแอปพลิเคชัน
 * Auth ควรใช้กับอะไร: ฟอร์มล็อกอิน, การตรวจสอบสิทธิ์ก่อนเข้าถึงหน้าที่ต้องล็อกอิน, การจัดการผู้ใช้
 * 
 * ฟีเจอร์หลัก:
 * - Login/Logout
 * - ตรวจสอบการเข้าสู่ระบบ
 * - รับข้อมูลผู้ใช้ที่ล็อกอิน
 * - Remember Me functionality
 * - Password hashing
 * - Authorization checks (can, hasRole)
 * 
 * ตัวอย่างการใช้งานโดยรวม:
 * ```php
 * // พยายามเข้าสู่ระบบ
 * $credentials = ['username' => 'johndoe', 'password' => 'secret'];
 * if (Auth::attempt($credentials, true)) {
 *    echo 'ล็อกอินสำเร็จ';
 * } else {
 *   echo 'ล็อกอินล้มเหลว';
 * }
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
     * Remember me cookie name สำหรับการจดจำผู้ใช้
     */
    private const REMEMBER_COOKIE = '_auth_remember';

    /**
     * Remember me token length สำหรับความยาวของโทเค็นที่ใช้ในการจดจำผู้ใช้
     */
    private const REMEMBER_TOKEN_LENGTH = 64;

    /**
     * Remember me duration (30 วัน) ระยะเวลาที่โทเค็นจะยังคงใช้งานได้
     */
    private const REMEMBER_DURATION = 60 * 60 * 24 * 30;

    /**
     * User data ที่ล็อกอินอยู่ สำหรับเก็บข้อมูลผู้ใช้ที่ล็อกอินอยู่ในหน่วยความจำ
     */
    private static ?array $user = null;

    /**
     * พยายามเข้าสู่ระบบด้วยข้อมูลรับรอง
     * จุดประสงค์: ใช้เพื่อพยายามเข้าสู่ระบบด้วยข้อมูลรับรองที่ให้มา
     * attempt() ควรใช้กับอะไร: ฟอร์มล็อกอิน, API authentication, etc.
     * ตัวอย่างการใช้งาน:
     * ```php
     * $credentials = ['username' => 'johndoe', 'password' => 'secret'];
     * $success = Auth::attempt($credentials, true);
     * ```
     * 
     * @param array $credentials กำหนดข้อมูลการเข้าสู่ระบบ ['username' => '...', 'password' => '...']
     * @param bool $remember กำหนดค่า true เพื่อจดจำการเข้าสู่ระบบ
     * @return bool true ถ้าการเข้าสู่ระบบสำเร็จ, false ถ้าล้มเหลว
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
     * จุดประสงค์: ใช้เพื่อเข้าสู่ระบบด้วยข้อมูลผู้ใช้ที่ให้มา
     * login() ควรใช้กับอะไร: หลังจากการลงทะเบียน, การเข้าสู่ระบบแบบโซเชียล, etc.
     * ตัวอย่างการใช้งาน:
     * ```php
     * $user = ['id' => 1, 'username' => 'johndoe', 'email' => 'johndoe@example.com'];
     * Auth::login($user, true);
     * ```
     * 
     * @param array|object $user กำหนดข้อมูลผู้ใช้ที่ใช้เข้าสู่ระบบ
     * @param bool $remember กำหนดค่า true เพื่อจดจำการเข้าสู่ระบบ
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
     * จุดประสงค์: ใช้เพื่อล็อกเอาต์ผู้ใช้ที่ล็อกอินอยู่
     * logout() ควรใช้กับอะไร: เมื่อผู้ใช้ต้องการออกจากระบบ
     * ตัวอย่างการใช้งาน:
     * ```php
     * Auth::logout();
     * ```
     * 
     * @return void ไม่มีผลลัพธ์ (void)
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

        // ลบ cookie (ใช้ options เดียวกับการตั้งค่าเพื่อให้แน่ใจว่าถูกลบ)
        if (isset($_COOKIE[self::REMEMBER_COOKIE])) {
            $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            $cookieOptions = [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => \env('APP_COOKIE_DOMAIN', '', 'string'),
                'secure' => $secure,
                'httponly' => true,
                'samesite' => \env('REMEMBER_SAMESITE', 'Lax', 'string'),
            ];

            setcookie(self::REMEMBER_COOKIE, '', $cookieOptions);
            unset($_COOKIE[self::REMEMBER_COOKIE]);
        }

        // สร้าง session ใหม่
        Session::regenerate();
    }

    /**
     * ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าผู้ใช้ได้เข้าสู่ระบบหรือไม่
     * check() ควรใช้กับอะไร: การตรวจสอบสิทธิ์ก่อนเข้าถึงหน้าที่ต้องล็อกอิน
     * ตัวอย่างการใช้งาน:
     * ```php
     * if (Auth::check()) {
     *     // ผู้ใช้ล็อกอินอยู่
     * } else {
     *    // ผู้ใช้เป็นแขก
     * }
     * ```
     * 
     * @return bool คืนค่า true ถ้าผู้ใช้ล็อกอินอยู่, false ถ้าเป็นแขก
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
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าผู้ใช้เป็นแขก (ไม่ได้ล็อกอิน)
     * guest() ควรใช้กับอะไร: การตรวจสอบสิทธิ์ก่อนเข้าถึงหน้าที่ต้องล็อกอิน
     * ตัวอย่างการใช้งาน:
     * ```php
     * if (Auth::guest()) {
     *    // ผู้ใช้เป็นแขก
     * } else {
     *   // ผู้ใช้ล็อกอินอยู่
     * }
     * ```
     * 
     * @return bool คืนค่า true ถ้าผู้ใช้เป็นแขก, false ถ้าล็อกอินอยู่
     */
    public static function guest(): bool
    {
        return !self::check();
    }

    /**
     * รับข้อมูลผู้ใช้ที่ล็อกอินอยู่
     * จุดประสงค์: ใช้เพื่อรับข้อมูลผู้ใช้ที่ล็อกอินอยู่เป็น associative array
     * user() ควรใช้กับอะไร: การแสดงข้อมูลผู้ใช้ในส่วนต่างๆ ของแอปพลิเคชัน เช่น แถบเมนู, โปรไฟล์ผู้ใช้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $user = Auth::user();
     * if ($user) {
     *    echo 'Hello, ' . $user['username'];
     * } else {
     *   echo 'Guest';
     * }
     * ```
     * 
     * @return array|null คืนค่า associative array ของข้อมูลผู้ใช้ถ้าล็อกอินอยู่, null ถ้าเป็นแขก
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
     * จุดประสงค์: ใช้เพื่อรับ ID ของผู้ใช้ที่ล็อกอินอยู่
     * id() ควรใช้กับอะไร: การแสดงข้อมูลผู้ใช้ในส่วนต่างๆ ของแอปพลิเคชัน เช่น แถบเมนู, โปรไฟล์ผู้ใช้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $userId = Auth::id();
     * if ($userId) {
     *   echo 'User ID: ' . $userId;
     * } else {
     *  echo 'Guest';
     * }
     * ```
     * 
     * @return int|null คืนค่า ID ของผู้ใช้ถ้าล็อกอินอยู่, null ถ้าเป็นแขก
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
     * จุดประสงค์: ใช้เพื่อเข้าสู่ระบบด้วย ID ของผู้ใช้
     * loginById() ควรใช้กับอะไร: การเข้าสู่ระบบแบบอัตโนมัติ, การทดสอบ, หรือการจัดการผู้ใช้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $success = Auth::loginById(1, true);
     * ```
     * 
     * @param int $userId กำหนด ID ของผู้ใช้ที่จะเข้าสู่ระบบ
     * @param bool $remember กำหนดค่า true เพื่อจดจำการเข้าสู่ระบบ
     * @return bool คืนค่า true ถ้าการเข้าสู่ระบบสำเร็จ, false ถ้าล้มเหลว
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
     * จุดประสงค์: ใช้เพื่อเข้าสู่ระบบแบบชั่วคราวด้วยข้อมูลผู้ใช้ที่ให้มา (สำหรับการทดสอบ)
     * loginTemporary() ควรใช้กับอะไร: การทดสอบ, การดีบัก, หรือสถานการณ์ที่ไม่ต้องการการตรวจสอบสิทธิ์จริง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $user = ['id' => 1, 'username' => 'johndoe', 'email' => 'johndoe@example.com'];
     * Auth::loginTemporary($user);
     * ```
     * 
     * @param array $user กำหนดข้อมูลผู้ใช้ในรูปแบบ associative array
     */
    public static function loginTemporary(array $user): void
    {
        self::$user = $user;
    }

    // ========== Password Methods ==========

    /**
     * Hash password
     * จุดประสงค์: ใช้เพื่อแฮชรหัสผ่านโดยใช้ BCRYPT
     * hash() ควรใช้กับอะไร: การเก็บรหัสผ่านในฐานข้อมูลอย่างปลอดภัย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $hashedPassword = Auth::hash('mysecretpassword');
     * ```
     * 
     * @param string $password กำหนดรหัสผ่านที่ต้องการแฮช
     * @return string คืนค่ารหัสผ่านที่ถูกแฮช
     */
    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * ตรวจสอบ password
     * จุดประสงค์: ใช้เพื่อตรวจสอบรหัสผ่านกับแฮชที่เก็บไว้
     * verifyPassword() ควรใช้กับอะไร: การตรวจสอบรหัสผ่านในฟอร์มล็อกอิน, การยืนยันตัวตน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isValid = Auth::verifyPassword('mysecretpassword', $hashedPassword);
     * ```
     * 
     * @param string $password กำหนดรหัสผ่านที่ต้องการตรวจสอบ
     * @param string $hash กำหนดแฮชของรหัสผ่านที่ใช้เปรียบเทียบ
     * @return bool คืนค่า true ถ้ารหัสผ่านถูกต้อง, false ถ้าไม่ถูกต้อง
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * ตรวจสอบ password (alias ของ verifyPassword)
     * จุดประสงค์: ใช้เพื่อตรวจสอบรหัสผ่านกับแฮชที่เก็บไว้ (alias ของ verifyPassword)
     * verify() ควรใช้กับอะไร: การตรวจสอบรหัสผ่านในฟอร์มล็อกอิน, การยืนยันตัวตน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isValid = Auth::verify('mysecretpassword', $hashedPassword);
     * ```
     * 
     * @param string $password กำหนดรหัสผ่านที่ต้องการตรวจสอบ
     * @param string $hash กำหนดแฮชของรหัสผ่านที่ใช้เปรียบเทียบ
     * @return bool คืนค่า true ถ้ารหัสผ่านถูกต้อง, false ถ้าไม่ถูกต้อง
     */
    public static function verify(string $password, string $hash): bool
    {
        return self::verifyPassword($password, $hash);
    }

    /**
     * ตรวจสอบว่า hash ต้องทำใหม่หรือไม่
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าแฮชรหัสผ่านต้องทำการแฮชใหม่หรือไม่
     * needsRehash() ควรใช้กับอะไร: การอัปเกรดความปลอดภัยของรหัสผ่านเมื่อมีการเปลี่ยนแปลงนโยบายแฮช
     * ตัวอย่างการใช้งาน:
     * ```php
     * $needsRehash = Auth::needsRehash($hashedPassword);
     * ```
     * 
     * @param string $hash กำหนดแฮชของรหัสผ่านที่ต้องตรวจสอบ
     * @return bool คืนค่า true ถ้าต้องทำการแฮชใหม่, false ถ้าไม่ต้อง
     */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT);
    }

    // ========== Remember Me Methods ==========

    /**
     * ตั้งค่า remember token
     * จุดประสงค์: ใช้เพื่อสร้างและตั้งค่า remember token สำหรับผู้ใช้
     * setRememberToken() ควรใช้กับอะไร: การจดจำผู้ใช้ระหว่างการเยี่ยมชมเว็บไซต์
     * ตัวอย่างการใช้งาน:
     * ```php
     * Auth::setRememberToken(1);
     * ```
     * 
     * @param int $userId กำหนดรหัสผู้ใช้ที่ต้องการตั้งค่า remember token
     */
    private static function setRememberToken(int $userId): void
    {
        // สร้าง token
        $token = bin2hex(random_bytes(self::REMEMBER_TOKEN_LENGTH));
        $hashedToken = hash('sha256', $token);

        // บันทึกใน database
        $db = Database::getInstance();
        $sql = "UPDATE users SET remember_token = :token WHERE id = :id";
        $db->execute($sql, [
            'token' => $hashedToken,
            'id' => $userId
        ]);

        // ตั้งค่า cookie โดยใช้ options array (รองรับ PHP 7.3+)
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $cookieOptions = [
            'expires' => time() + self::REMEMBER_DURATION,
            'path' => '/',
            'domain' => \env('APP_COOKIE_DOMAIN', '', 'string'),
            'secure' => $secure,
            'httponly' => true,
            'samesite' => \env('REMEMBER_SAMESITE', 'Lax', 'string'),
        ];

        setcookie(self::REMEMBER_COOKIE, $userId . '|' . $token, $cookieOptions);
    }

    /**
     * ตรวจสอบ remember token
     * จุดประสงค์: ใช้เพื่อตรวจสอบและยืนยัน remember token จาก cookie
     * checkRememberToken() ควรใช้กับอะไร: การตรวจสอบสิทธิ์ก่อนเข้าถึงหน้าที่ต้องล็อกอิน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isValid = Auth::checkRememberToken();
     * ```
     * 
     * @return bool คืนค่า true ถ้า token ถูกต้อง, false ถ้าไม่ถูกต้อง
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
        $db = Database::getInstance();
        $sql = "SELECT id FROM users WHERE id = :id AND remember_token = :token";
        $stmt = $db->query($sql, [
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
     * จุดประสงค์: ใช้เพื่อลบ remember token ของผู้ใช้ จาก database
     * removeRememberToken() ควรใช้กับอะไร: เมื่อผู้ใช้ทำการล็อกเอาต์
     * ตัวอย่างการใช้งาน:
     * ```php
     * Auth::removeRememberToken(1);
     * ```
     * 
     * @param int $userId กำหนด ID ของผู้ใช้ที่ต้องการลบ remember token
     * @return void ไม่มีผลลัพธ์ (void)
     */
    private static function removeRememberToken(int $userId): void
    {
        // ลบจาก database
        $db = Database::getInstance();
        $sql = "UPDATE users SET remember_token = NULL WHERE id = :id";
        $db->execute($sql, ['id' => $userId]);
    }

    // ========== Helper Methods ==========

    /**
     * ค้นหาผู้ใช้จาก credentials
     * จุดประสงค์: ใช้เพื่อค้นหาผู้ใช้ใน database โดยใช้ username หรือ email
     * findUserByCredentials() ควรใช้กับอะไร: ฟอร์มล็อกอิน, API authentication, etc.
     * ตัวอย่างการใช้งาน:
     * ```php
     * $user = Auth::findUserByCredentials('johndoe');
     * ```
     * 
     * @param string $identifier username หรือ email
     * @return array|null คืนค่า associative array ของข้อมูลผู้ใช้ถ้าพบ, null ถ้าไม่พบ    
     */
    private static function findUserByCredentials(string $identifier): ?array
    {
        $db = Database::getInstance();

        // ตรวจสอบว่าเป็น email หรือ username
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $sql = "SELECT * FROM users WHERE email = :identifier LIMIT 1";
        } else {
            $sql = "SELECT * FROM users WHERE username = :identifier LIMIT 1";
        }

        $stmt = $db->query($sql, ['identifier' => $identifier]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * รับข้อมูลผู้ใช้จาก ID
     * จุดประสงค์: ใช้เพื่อรับข้อมูลผู้ใช้จาก database โดยใช้ user ID
     * getUserById() ควรใช้กับอะไร: การโหลดข้อมูลผู้ใช้ที่ล็อกอินอยู่, การจัดการผู้ใช้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $userData = Auth::getUserById(1);
     * ```
     * 
     * @param int $userId กำหนด ID ของผู้ใช้ที่ต้องการรับข้อมูล
     * @return array|null คืนค่า associative array ของข้อมูลผู้ใช้ถ้าพบ, null ถ้าไม่พบ
     */
    private static function getUserById(int $userId): ?array
    {
        $db = Database::getInstance();
        $sql = "SELECT * FROM users WHERE id = :id LIMIT 1";
        $stmt = $db->query($sql, ['id' => $userId]);
        // ดึงข้อมูลผู้ใช้จากฐานข้อมูลของเรา
        $userData = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $userData ?: null;
    }

    /**
     * 
     * ========= ตรวจสอบสิทธิ์ อาจจะต้องปรับปรุงในอนาคต ==========
     * 
     * ตรวจสอบว่าผู้ใช้มีสิทธิ์หรือไม่
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าผู้ใช้ที่ล็อกอินอยู่มีสิทธิ์ที่ระบุหรือไม่
     * can() ควรใช้กับอะไร: การตรวจสอบสิทธิ์ก่อนเข้าถึงหน้าที่ต้องล็อกอิน
     * ตัวอย่างการใช้งาน:
     * ```php
     * if (Auth::can('edit_posts')) {
     *    // ผู้ใช้มีสิทธิ์แก้ไขโพสต์
     * } else {
     *   // ผู้ใช้ไม่มีสิทธิ์
     * }
     * ```
     * 
     * @param string $permission กำหนดชื่อสิทธิ์ที่ต้องการตรวจสอบ
     * @return bool คืนค่า true ถ้าผู้ใช้มีสิทธิ์, false ถ้าไม่มี
     */
    public static function can(string $permission): bool
    {
        $user = self::user();

        if (!$user) {
            return false;
        }

        // ตรวจสอบสิทธิ์ตามโครงสร้างของคุณ
        // ตัวอย่าง: ตรวจสอบใน database หรือ user property
        // Admin bypass: ถ้ามีฟิลด์ is_admin หรือ role เป็น admin
        if (!empty($user['is_admin']) || (!empty($user['role']) && $user['role'] === 'admin')) {
            return true;
        }

        // ลองอ่าน permissions จากข้อมูลผู้ใช้ (รองรับ array, JSON string, หรือ comma-separated string)
        $perms = $user['permissions'] ?? null;

        if (is_string($perms)) {
            // พยายาม decode เป็น JSON ก่อน
            $decoded = json_decode($perms, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $perms = $decoded;
            } else {
                // แยกรายการด้วย comma
                $perms = array_filter(array_map('trim', explode(',', $perms)));
            }
        }

        if (is_array($perms)) {
            return in_array($permission, $perms, true);
        }

        // หากไม่มีข้อมูลสิทธิ์จาก user property ให้ลองตรวจสอบจากฐานข้อมูล (fallback)
        try {
            $db = Database::getInstance();
            $userId = $user['id'] ?? null;

            if ($userId) {
                // ตาราง user_permissions (user_id, permission)
                $sql = "SELECT 1 FROM user_permissions WHERE user_id = :uid AND permission = :perm LIMIT 1";
                $stmt = $db->query($sql, ['uid' => $userId, 'perm' => $permission]);
                if ($stmt->fetchColumn()) {
                    return true;
                }
            }

            // ตรวจสอบ role permissions (มี role_id หรือ role name)
            $roleId = $user['role_id'] ?? null;
            $roleName = $user['role'] ?? null;

            if ($roleId) {
                $sql = "SELECT 1 FROM role_permissions WHERE role_id = :rid AND permission = :perm LIMIT 1";
                $stmt = $db->query($sql, ['rid' => $roleId, 'perm' => $permission]);
                if ($stmt->fetchColumn()) {
                    return true;
                }
            } elseif ($roleName) {
                // หา role id จากชื่่อ role แล้วเช็ค
                $sql = "SELECT id FROM roles WHERE name = :rname LIMIT 1";
                $stmt = $db->query($sql, ['rname' => $roleName]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row && !empty($row['id'])) {
                    $rid = $row['id'];
                    $sql = "SELECT 1 FROM role_permissions WHERE role_id = :rid AND permission = :perm LIMIT 1";
                    $stmt = $db->query($sql, ['rid' => $rid, 'perm' => $permission]);
                    if ($stmt->fetchColumn()) {
                        return true;
                    }
                }
            }
        } catch (\Throwable $e) {
            // ถ้า DB หรือตารางไม่ถูกต้อง ให้ fallback เงียบ ๆ กลับไปยังการคืนค่า false
        }

        // หากไม่พบสิทธิ์ ให้คืนค่า false
        return false;
    }

    /**
     * 
     * ========= ตรวจสอบ Role อาจจะต้องปรับปรุงในอนาคต ==========
     * 
     * ตรวจสอบว่าผู้ใช้มี role หรือไม่ (สำหรับขยายในอนาคต)
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าผู้ใช้ที่ล็อกอินอยู่มี role ที่ระบุหรือไม่
     * hasRole() ควรใช้กับอะไร: การตรวจสอบสิทธิ์ก่อนเข้าถึงหน้าที่ต้องล็อกอิน
     * ตัวอย่างการใช้งาน:
     * ```php
     * if (Auth::hasRole('editor')) {
     *   // ผู้ใช้มี role editor
     * } else {
     *  // ผู้ใช้ไม่มี role นี้
     * }
     * ```
     * 
     * @param string $role กำหนดชื่อ role ที่ต้องการตรวจสอบ
     * @return bool คืนค่า true ถ้าผู้ใช้มี role, false ถ้าไม่มี
     */
    public static function hasRole(string $role): bool
    {
        $user = self::user();

        if (!$user) {
            return false;
        }

        // ตรวจสอบ role ตามโครงสร้างของคุณ
        // ตัวอย่าง: $user->role === $role
        // Admin bypass
        if (!empty($user['is_admin']) || (!empty($user['role']) && $user['role'] === 'admin')) {
            return true;
        }

        // รองรับหลายรูปแบบของข้อมูล role: `roles` (array), `roles` JSON, comma-separated string, หรือ `role` เป็น string
        $roles = $user['roles'] ?? ($user['role'] ?? null);

        if (is_string($roles)) {
            $decoded = json_decode($roles, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $roles = $decoded;
            } else {
                if (strpos($roles, ',') !== false) {
                    $roles = array_filter(array_map('trim', explode(',', $roles)));
                } else {
                    $roles = [$roles];
                }
            }
        }

        if (is_array($roles)) {
            return in_array($role, $roles, true);
        }

        // Fallback: ตรวจสอบจากฐานข้อมูล (user_roles / roles)
        try {
            $db = Database::getInstance();
            $userId = $user['id'] ?? null;

            if ($userId) {
                $sql = "SELECT 1 FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = :uid AND (r.name = :rname OR r.slug = :rname) LIMIT 1";
                $stmt = $db->query($sql, ['uid' => $userId, 'rname' => $role]);
                if ($stmt->fetchColumn()) {
                    return true;
                }
            }

            // ถ้ามี role field เดี่ยว ๆ ให้เช็คเทียบชื่ออีกครั้ง
            $roleName = $user['role'] ?? null;
            if ($roleName && $roleName === $role) {
                return true;
            }
        } catch (\Throwable $e) {
            // เงียบๆ fallback เป็น false
        }

        return false;
    }
}
