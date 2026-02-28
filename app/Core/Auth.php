<?php
declare(strict_types=1);
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

use App\Core\Session;
use App\Core\Config;
use App\Core\Logger;
use App\Core\Cache;
use App\Core\Database;
use App\Core\Authorization;

final class Auth
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
     * Dummy bcrypt hash used for timing-attack mitigation when user not found.
     * Generated with: password_hash('dummy', PASSWORD_BCRYPT)
     */
    private const DUMMY_PASSWORD_HASH = '$2y$12$2dWD7rDSfP1iwG1BS4lGqO3d1z5RN/U44ylkHX5fWdeI4haZmsEUK';

    /**
     * User data ที่ล็อกอินอยู่ สำหรับเก็บข้อมูลผู้ใช้ที่ล็อกอินอยู่ในหน่วยความจำ
     */
    private static ?array $user = null;

    /**
     * การป้องกันการโจมตีแบบ brute-force: กำหนดจำนวนครั้งสูงสุดที่อนุญาตให้พยายามเข้าสู่ระบบได้ และระยะเวลาที่จะบล็อกหลังจากเกินจำนวนครั้ง
     */
    private const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * ระยะเวลาที่จะบล็อกหลังจากเกินจำนวนครั้งที่อนุญาต (5 นาที)
     */
    private const ATTEMPT_WINDOW = 300; // seconds (5 minutes)


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

        // ตรวจสอบว่ามี username/email และ password หรือไม่
        if (!$identifier || !$password) {
            return false;
        }

        // ใช้ IP address ของผู้ใช้สำหรับการบล็อกการโจมตีแบบ brute-force
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $logger = new Logger();

        // ตรวจสอบการล็อกอินแบบ brute-force
        $blocked = self::isBlocked($identifier, $ip);
        if ($blocked['blocked']) {
            $logger->security('auth.login.locked', ['identifier' => substr($identifier, 0, 64), 'ip' => $ip, 'remaining_seconds' => $blocked['remaining']]);
            // don't reveal timing to clients, but add slight delay
            usleep(200000);
            return false;
        }

        // ค้นหาผู้ใช้
        $user = self::findUserByCredentials($identifier);

        // ถ้าไม่พบผู้ใช้, บันทึกความพยายามที่ล้มเหลวและทำการหน่วงเวลาเพื่อป้องกันการโจมตีแบบ brute-force
        if (!$user) {
            // record attempt by identifier and ip
            self::recordFailedAttemptFor($identifier, $ip);
            // timing-attack mitigation: run password_verify on a dummy bcrypt hash
            password_verify($password, self::DUMMY_PASSWORD_HASH);
            // small delay to slow brute force (200-300ms)
            usleep(250000);
            return false;
        }

        // ตรวจสอบสถานะผู้ใช้
        if (($user['status'] ?? null) !== 'active') {
            return false;
        }

        // ตรวจสอบว่าผู้ใช้ถูกลบหรือไม่
        if (!empty($user['deleted_at'])) {
            return false;
        }

        // ตรวจสอบรหัสผ่าน
        if (!self::verifyPassword($password, $user['password'])) {
            self::recordFailedAttemptFor($identifier, $ip);
            $logger->security('auth.login.failed', ['identifier' => substr($identifier, 0, 64), 'ip' => $ip]);
            // small delay to slow brute force (200-300ms)
            usleep(250000);
            return false;
        }

        // ล็อกอินผู้ใช้
        self::login($user, $remember);

        // On successful login clear failed attempts
        self::clearFailedAttemptsFor($identifier, $ip);
        $logger->info('auth.login.success', ['user_id' => $user['id'], 'ip' => $ip]);

        return true;
    }

    /**
     * เข้าสู่ระบบด้วยข้อมูลผู้ใช้
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
        
        // ตรวจสอบว่า $user เป็น object หรือ array และดึง user ID ออกมา
        if (is_object($user)) {
            // สมมติว่า object มี property 'id'
            $userId = $user->id;
        } else {
            // สมมติว่าเป็น array และมี key 'id'
            $userId = $user['id'];
        }

        // บันทึก user ID ใน session
        Session::set(self::SESSION_KEY, $userId);

        // สร้าง session ใหม่เพื่อป้องกัน session fixation (logged with context)
        Session::regenerateWithContext('login', is_int($userId) ? (int)$userId : null);

        // ถ้าต้องการจดจำการเข้าสู่ระบบ, ให้ตั้งค่า remember token และ cookie
        if ($remember) {
            $logger = new Logger();

            // ตรวจสอบว่า APP_KEY ถูกตั้งค่าเพื่อความปลอดภัยของ remember me functionality
            if (self::getAppKey() === '') {
                $logger->error('auth.app_key.missing', ['note' => 'APP_KEY missing, remember-me disabled']);
            } else {
                self::setRememberToken($userId);
                $logger->info('auth.remember.created', ['user_id' => $userId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            }
        }

        // โหลดข้อมูลผู้ใช้
        self::$user = self::getUserById($userId);

        // ถ้าไม่พบข้อมูลผู้ใช้ (อาจเกิดจากการลบหรือปัญหาฐานข้อมูล), ให้ทำการล็อกเอาต์เพื่อความปลอดภัย
        if (!self::$user) {
            self::logout();
            return;
        }
        // Cache ข้อมูลสิทธิ์ของผู้ใช้ใน session เพื่อลดการเรียกฐานข้อมูลในอนาคต
        // Ensure any previous permission cache is cleared to prevent permission leakage
        // when login occurs on top of an existing session (e.g., admin logs in over user)
        Session::remove('_auth_permissions');
        self::cacheUserPermissions(self::$user);
        // เรียกใช้ Logger เพื่อบันทึกเหตุการณ์การเข้าสู่ระบบ
        $logger = new Logger();
        $logger->info('login.created', ['user_id' => $userId]);

        $db = Database::getInstance();
        $db->execute(
            "UPDATE users SET last_login_at = NOW(), last_login_ip = :ip WHERE id = :id",
            [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'id' => $userId
            ]
        );
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

        // ลบ remember token (ensure we have a valid user id)
        if (self::check()) {
            $userId = Session::get(self::SESSION_KEY);
            if ($userId !== null) {
                self::removeRememberToken((int)$userId);
                $logger = new Logger();
                $logger->security('auth.logout.remember_cleared', ['user_id' => $userId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            }
        }

        // ลบข้อมูล session
        Session::remove(self::SESSION_KEY);
        self::$user = null;

        // ลบ cookie (ใช้ options เดียวกับการตั้งค่าเพื่อให้แน่ใจว่าถูกลบ)
        if (isset($_COOKIE[self::REMEMBER_COOKIE])) {
            $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            $domain = Config::get('auth.cookie_domain', null);
            if ($domain === '') {
                $domain = null;
            }
            $cookieOptions = [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => true,
                'samesite' => (string) Config::get('auth.remember_samesite', 'Lax'),
            ];

            if (PHP_SAPI === 'cli') {
                $_COOKIE[self::REMEMBER_COOKIE] = '';
                unset($_COOKIE[self::REMEMBER_COOKIE]);
            } else {
                setcookie(self::REMEMBER_COOKIE, '', $cookieOptions);
                unset($_COOKIE[self::REMEMBER_COOKIE]);
            }
        }

        // สร้าง session ใหม่ (logged with context 'logout')
        Session::regenerateWithContext('logout', isset($userId) ? (int)$userId : null);
        // log logout event
        $logger = new Logger();
        $logger->security('auth.logout', ['user_id' => $userId ?? null, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
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
        if (self::$user !== null) {
            return true;
        }

        Session::start();

        // ตรวจสอบ session
        if (Session::has(self::SESSION_KEY)) {
            $id = Session::get(self::SESSION_KEY);
            self::$user = self::getUserById($id);
            return self::$user !== null;
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
     * ห้ามใช้ใน request lifecycle จริง
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
        // การป้องกันการใช้งานในสภาพแวดล้อมการผลิต: ฟังก์ชันนี้ควรใช้สำหรับการทดสอบเท่านั้น และจะโยนข้อผิดพลาดถ้าพยายามใช้ในโหมด production
        if (!Config::get('app.debug')) {
            throw new \RuntimeException('loginTemporary() is for testing only and is disabled in production.');
        }

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
     * @return void ไม่มีผลลัพธ์ (void)
     */
    private static function setRememberToken(int $userId): void
    {
        $logger = new Logger();
        // enforce APP_KEY presence
        if (self::getAppKey() === '') {
            $logger->error('auth.app_key.missing', ['note' => 'APP_KEY missing, remember-me disabled']);
            return;
        }
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
        $domain = Config::get('auth.cookie_domain', null);
        if ($domain === '') {
            $domain = null;
        }
        $cookieOptions = [
            'expires' => time() + self::REMEMBER_DURATION,
            'path' => '/',
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => (string) Config::get('auth.remember_samesite', 'Lax'),
        ];

        $payload = $userId . '|' . $token;
        $signature = self::signRememberPayload($payload);

        if (PHP_SAPI === 'cli') {
            $_COOKIE[self::REMEMBER_COOKIE] = $payload . '|' . $signature;
        } else {
            setcookie(self::REMEMBER_COOKIE, $payload . '|' . $signature, $cookieOptions);
        }
        // do not log tokens or cookie values; log creation abstractly
        $logger->info('auth.remember.set', ['user_id' => $userId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
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

        $logger = new Logger();

        // Trim cookie value to avoid whitespace/tampering issues
        $cookieValue = trim((string) ($_COOKIE[self::REMEMBER_COOKIE] ?? ''));
        if ($cookieValue === '') {
            return false;
        }

        $parts = explode('|', $cookieValue, 3);
        if (count($parts) < 2) {
            return false;
        }

        [$userId, $token] = $parts;

        // Validate signature when present; reject legacy cookies if APP_KEY is set.
        if (count($parts) === 3) {
            $signature = $parts[2];
            if (!self::isRememberSignatureValid($userId . '|' . $token, $signature)) {
                // invalid signature -> possible tampering
                $logger->security('auth.remember.invalid_signature', ['user_id' => is_numeric($userId) ? (int)$userId : null, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                return false;
            }
        } elseif (self::getAppKey() !== '') {
            return false;
        }

        // If APP_KEY missing, remember functionality must not work
        if (self::getAppKey() === '') {
            $logger->error('auth.app_key.missing', ['note' => 'APP_KEY missing, remember-me disabled']);
            return false;
        }

        $hashedToken = hash('sha256', $token);

        // ตรวจสอบใน database (separate checks to provide specific logs)
        $db = Database::getInstance();
        $row = $db->fetch("SELECT remember_token FROM users WHERE id = :id LIMIT 1", ['id' => $userId]);

        if (!$row) {
            $logger->security('auth.remember.unknown_user', ['user_id' => is_numeric($userId) ? (int)$userId : null, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            return false;
        }

        $storedToken = $row['remember_token'] ?? null;

        if ($storedToken === null) {
            // token cleared server-side or expired
            $logger->security('auth.remember.expired', ['user_id' => (int)$userId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            return false;
        }

        // Compare stored hash with presented token hash without logging sensitive values
        if (!is_string($storedToken) || !hash_equals($storedToken, $hashedToken)) {
            $logger->security('auth.remember.mismatch', ['user_id' => (int)$userId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            return false;
        }

        // Valid remember token -> perform auto-login
        Session::start();
        Session::set(self::SESSION_KEY, $userId);

        // Regenerate session to prevent fixation (logged with context 'remember')
        Session::regenerateWithContext('remember', is_numeric($userId) ? (int)$userId : null);

        // Rotate remember token to prevent replay
        self::setRememberToken((int) $userId);

        // log token rotation (do not include token)
        $logger->security('auth.remember.rotated', ['user_id' => (int)$userId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);


        // Load user cache
        self::$user = self::getUserById((int) $userId);
        // Preload and cache permissions in session (same as normal login)
        self::cacheUserPermissions(self::$user);

        // security log for successful remember login
        $logger->security('auth.remember.login', ['user_id' => (int)$userId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);

        return true;
    }

    /**
     * ดึง APP_KEY สำหรับลงลายเซ็นข้อมูล
     * จุดประสงค์: ใช้เพื่อดึงค่า APP_KEY จากการตั้งค่าเพื่อใช้ในการลงลายเซ็นข้อมูลสำหรับ remember cookie
     * ตัวอย่างการใช้งาน:
     * ```php
     * $appKey = Auth::getAppKey();
     * ```
     * 
     * @return string คืนค่า APP_KEY ถ้ามีการตั้งค่า, คืนค่าเป็นสตริงว่างถ้าไม่มีการตั้งค่า
     */
    private static function getAppKey(): string
    {
        return (string) Config::get('auth.app_key', '');
    }

    /**
     * สร้างลายเซ็นสำหรับ remember cookie payload
     * จุดประสงค์: ใช้เพื่อสร้างลายเซ็น HMAC สำหรับข้อมูล payload ของ remember cookie เพื่อความปลอดภัย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $signature = Auth::signRememberPayload('1|randomtoken');
     * ```
     * 
     * @param string $payload กำหนดข้อมูล payload ที่ต้องการลงลายเซ็น
     * @return string คืนค่าลายเซ็น HMAC ของ payload
     */
    private static function signRememberPayload(string $payload): string
    {
        return hash_hmac('sha256', $payload, self::getAppKey());
    }

    /**
     * ตรวจสอบลายเซ็นของ remember cookie payload
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าลายเซ็น HMAC ของ remember cookie payload ถูกต้องหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isValid = Auth::isRememberSignatureValid('1|randomtoken', 'expected_signature');
     * ```
     * 
     * @param string $payload กำหนดข้อมูล payload ที่ต้องการตรวจสอบลายเซ็น
     * @param string $signature กำหนดลายเซ็นที่ต้องการตรวจสอบ
     * @return bool คืนค่า true ถ้าลายเซ็นถูกต้อง, false ถ้าไม่ถูกต้อง
     */
    private static function isRememberSignatureValid(string $payload, string $signature): bool
    {
        $key = self::getAppKey();
        if ($key === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $key);
        return hash_equals($expected, $signature);
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
            $sql = "SELECT * FROM users WHERE email = :identifier AND deleted_at IS NULL LIMIT 1";
        } else {
            $sql = "SELECT * FROM users WHERE username = :identifier AND deleted_at IS NULL LIMIT 1";
        }

        $user = $db->fetch($sql, ['identifier' => $identifier]);

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
        $sql = "SELECT * FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1";
        // ดึงข้อมูลผู้ใช้จากฐานข้อมูลของเรา
        $userData = $db->fetch($sql, ['id' => $userId]);

        return $userData ?: null;
    }

    // ========== Brute-force protection helpers ==========

    /**
     * สร้างคีย์สำหรับการติดตามความพยายามล็อกอินที่ล้มเหลวโดยใช้ identifier (username/email)
     * จุดประสงค์: ใช้เพื่อสร้างคีย์ที่ไม่ระบุข้อมูลส่วนตัวสำหรับการติดตามความพยายามล็อกอินที่ล้มเหลวโดยใช้ username หรือ email
     * ตัวอย่างการใช้งาน:
     * ```php
     * $key = Auth::attemptKeyIdentifier('johndoe');
     * ```
     * 
     * @param string $identifier กำหนด username หรือ email ที่ใช้เป็น identifier
     * @return string คืนค่าคีย์ที่ถูกแฮชสำหรับการติดตามความพยายามล็อกอินที่ล้มเหลว
     */
    private static function attemptKeyIdentifier(string $identifier): string
    {
        return 'auth:fail:identifier:' . hash('sha256', (string) $identifier);
    }

    /**
     * สร้างคีย์สำหรับการติดตามความพยายามล็อกอินที่ล้มเหลวโดยใช้ IP address
     * จุดประสงค์: ใช้เพื่อสร้างคีย์ที่ไม่ระบุข้อมูลส่วนตัวสำหรับการติดตามความพยายามล็อกอินที่ล้มเหลวโดยใช้ IP address
     * ตัวอย่างการใช้งาน:
     * ```php
     * $key = Auth::attemptKeyIp('127.0.0.1');
     * ```
     * 
     * @param string $ip กำหนด IP address ที่ใช้เป็น identifier
     * @return string คืนค่าคีย์ที่ถูกแฮชสำหรับการติดตามความพยายามล็อกอินที่ล้มเหลว
     */
    private static function attemptKeyIp(string $ip): string
    {
        return 'auth:fail:ip:' . (string) $ip;
    }

    /**
     * ตรวจสอบว่ามีการบล็อกการพยายามล็อกอินหรือไม่ โดยตรวจสอบทั้งจาก identifier และ IP address
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าผู้ใช้หรือ IP address มีการบล็อกการพยายามล็อกอินเนื่องจากความพยายามที่ล้มเหลวเกินขีดจำกัดหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $result = Auth::isBlocked('johndoe', '127.0.0.1');
     * ```
     * 
     * @param string $identifier กำหนด username หรือ email ที่ใช้เป็น identifier
     * @param string $ip กำหนด IP address ของผู้ใช้
     * @return array คืนค่า associative array ที่มีคีย์ 'blocked' (bool) และ 'remaining' (int) สำหรับเวลาที่เหลือในการบล็อก
     */
    private static function isBlocked(string $identifier, string $ip): array
    {
        $now = time();
        $idKey = self::attemptKeyIdentifier($identifier);
        $ipKey = self::attemptKeyIp($ip);

        $idEntry = Cache::get($idKey, null);
        $ipEntry = Cache::get($ipKey, null);

        $remaining = 0;
        $blocked = false;

        foreach ([$idEntry, $ipEntry] as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $count = (int) ($entry['count'] ?? 0);
            $first = (int) ($entry['first'] ?? 0);
            if ($count >= self::MAX_LOGIN_ATTEMPTS) {
                $expiresAt = $first + self::ATTEMPT_WINDOW;
                $r = $expiresAt - $now;
                if ($r > 0) {
                    $blocked = true;
                    if ($r > $remaining) {
                        $remaining = $r;
                    }
                }
            }
        }

        return ['blocked' => $blocked, 'remaining' => $remaining];
    }

    /**
     * บันทึกความพยายามล็อกอินที่ล้มเหลวและบันทึกลงใน log ใช้ structured cache entries เพื่อคำนวณเวลาที่เหลือ
     * จุดประสงค์: ใช้เพื่อบันทึกความพยายามล็อกอินที่ล้มเหลวสำหรับ identifier และ IP address และบันทึกเหตุการณ์ที่เกี่ยวข้องใน log โดยใช้โครงสร้างข้อมูลใน cache เพื่อคำนวณเวลาที่เหลือในการบล็อก
     * ตัวอย่างการใช้งาน:
     * ```php
     * Auth::recordFailedAttemptFor('johndoe', '127.0.0.1');
     * ```
     * 
     * @param string $identifier กำหนด username หรือ email ที่ใช้เป็น identifier
     * @param string $ip กำหนด IP address ของผู้ใช้
     * @return void ไม่มีผลลัพธ์ (void)
     */
    private static function recordFailedAttemptFor(string $identifier, string $ip): void
    {
        $logger = new Logger();

        $now = time();
        $idKey = self::attemptKeyIdentifier($identifier);
        $ipKey = self::attemptKeyIp($ip);

        $idEntry = Cache::get($idKey, null);
        if (!is_array($idEntry) || ($now - ($idEntry['first'] ?? 0)) > self::ATTEMPT_WINDOW) {
            $idEntry = ['count' => 1, 'first' => $now];
        } else {
            $idEntry['count'] = (int) ($idEntry['count'] ?? 0) + 1;
        }
        Cache::set($idKey, $idEntry, self::ATTEMPT_WINDOW);

        $ipEntry = Cache::get($ipKey, null);
        if (!is_array($ipEntry) || ($now - ($ipEntry['first'] ?? 0)) > self::ATTEMPT_WINDOW) {
            $ipEntry = ['count' => 1, 'first' => $now];
        } else {
            $ipEntry['count'] = (int) ($ipEntry['count'] ?? 0) + 1;
        }
        Cache::set($ipKey, $ipEntry, self::ATTEMPT_WINDOW);

        // Log every failed attempt as security-relevant
        $logger->security('auth.login.failed', ['identifier' => substr($identifier, 0, 64), 'ip' => $ip, 'id_attempts' => $idEntry['count'], 'ip_attempts' => $ipEntry['count']]);

        // If threshold is reached, log lockout event with remaining seconds
        if ($idEntry['count'] >= self::MAX_LOGIN_ATTEMPTS) {
            $remaining = ($idEntry['first'] + self::ATTEMPT_WINDOW) - $now;
            $logger->security('auth.login.locked', ['identifier' => substr($identifier, 0, 64), 'attempts' => $idEntry['count'], 'remaining_seconds' => $remaining > 0 ? $remaining : 0]);
        }

        if ($ipEntry['count'] >= self::MAX_LOGIN_ATTEMPTS) {
            $remaining = ($ipEntry['first'] + self::ATTEMPT_WINDOW) - $now;
            $logger->security('auth.login.locked', ['ip' => $ip, 'attempts' => $ipEntry['count'], 'remaining_seconds' => $remaining > 0 ? $remaining : 0]);
        }
    }

    /**
     * ล้างความพยายามล็อกอินที่ล้มเหลวสำหรับ identifier และ IP address
     * จุดประสงค์: ใช้เพื่อล้างข้อมูลความพยายามล็อกอินที่ล้มเหลวจาก cache สำหรับ identifier และ IP address เมื่อมีการล็อกอินที่สำเร็จ
     * ตัวอย่างการใช้งาน:
     * ```php
     * Auth::clearFailedAttemptsFor('johndoe', '127.0.0.1');
     * ```
     * 
     * @param string $identifier กำหนด username หรือ email ที่ใช้เป็น identifier
     * @param string $ip กำหนด IP address ของผู้ใช้
     * @return void ไม่มีผลลัพธ์ (void)
     */
    private static function clearFailedAttemptsFor(string $identifier, string $ip): void
    {
        $idKey = self::attemptKeyIdentifier($identifier);
        $ipKey = self::attemptKeyIp($ip);

        Cache::forget($idKey);
        Cache::forget($ipKey);
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
        return Authorization::can($permission);
    }

    /**
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
        return Authorization::hasRole($role);
    }

    /**
     * โหลดและแคชสิทธิ์ของผู้ใช้ใน session เพื่อประสิทธิภาพ
     * จุดประสงค์: ใช้เพื่อโหลดและแคชสิทธิ์ของผู้ใช้ที่ล็อกอินอยู่ใน session เพื่อปรับปรุงประสิทธิภาพการตรวจสอบสิทธิ์ในคำขอถัดไป
     * ตัวอย่างการใช้งาน:
     * ```php
     * Auth::cacheUserPermissions($user);
     * ```
     * 
     * @param array|null $user กำหนดข้อมูลผู้ใช้ที่ล็อกอินอยู่
     * @return void ไม่มีผลลัพธ์ (void)
     */
    private static function cacheUserPermissions(?array $user): void
    {
        // พยายามโหลดสิทธิ์ทั้งหมดจาก Authorization ถ้า method นั้นมีอยู่และทำงานได้
        $allPermissions = null;
        if (is_callable([Authorization::class, 'loadAllPermissions'])) {
            try {
                $allPermissions = Authorization::loadAllPermissions($user);
            } catch (\Throwable $e) {
                // ถ้าเกิดข้อผิดพลาดใดๆ ในการโหลดสิทธิ์จาก Authorization ให้ล้างค่าและใช้ fallback แบบเดิม
                $allPermissions = null;
            }
        }

        if (is_array($allPermissions)) {
            Session::set('_auth_permissions', $allPermissions);
            return;
        }

        // ถ้าไม่สามารถโหลดสิทธิ์ทั้งหมดได้ ให้พยายามดึงสิทธิ์จากข้อมูลผู้ใช้โดยตรง
        $perms = $user['permissions'] ?? null;
        $normalized = null;
        // ถ้า Authorization มี method สำหรับ normalize permissions ให้ใช้มัน
        if (is_callable([Authorization::class, 'normalizePermissions'])) {
            $normalized = Authorization::normalizePermissions($perms);
        }

        // ถ้าไม่ได้ผล ให้พยายามแปลงสิทธิ์จากรูปแบบต่างๆ (เช่น JSON หรือ comma-separated string) เป็น array
        if ($normalized === null) {
            if (is_string($perms)) {
                $decoded = json_decode($perms, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $normalized = $decoded;
                } else {
                    $normalized = array_filter(array_map('trim', explode(',', $perms)));
                }
            } elseif (is_array($perms)) {
                $normalized = $perms;
            }
        }

        Session::set('_auth_permissions', $normalized ?? []);
    }
}
