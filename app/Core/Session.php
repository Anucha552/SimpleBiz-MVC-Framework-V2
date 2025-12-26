<?php
/**
 * คลาส Session
 * 
 * จุดประสงค์: จัดการ session และ flash messages
 * ฟีเจอร์: start/destroy session, flash messages, CSRF protection
 * 
 * ฟีเจอร์หลัก:
 * - จัดการ session lifecycle
 * - Flash messages (ข้อความที่แสดงครั้งเดียว)
 * - CSRF token สำหรับความปลอดภัย
 * - Old input สำหรับฟอร์ม
 * 
 * ตัวอย่างการใช้งาน:
 * ```php
 * // เริ่ม session
 * Session::start();
 * 
 * // ตั้งค่า
 * Session::set('user_id', 123);
 * Session::flash('success', 'บันทึกสำเร็จ');
 * 
 * // รับค่า
 * $userId = Session::get('user_id');
 * $message = Session::getFlash('success');
 * 
 * // ลบ session
 * Session::destroy();
 * ```
 */

namespace App\Core;

class Session
{
    /**
     * ตรวจสอบว่า session เริ่มแล้วหรือยัง
     */
    private static bool $started = false;

    /**
     * Flash data key prefix
     */
    private const FLASH_KEY = '_flash';

    /**
     * Old input key
     */
    private const OLD_INPUT_KEY = '_old_input';

    /**
     * CSRF token key
     */
    private const CSRF_TOKEN_KEY = '_csrf_token';

    /**
     * เริ่มต้น session
     * 
     * @param array $options ตัวเลือก session (ไม่บังคับ)
     */
    public static function start(array $options = []): void
    {
        if (self::$started) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            // ตั้งค่า session เริ่มต้น
            $defaultOptions = [
                'cookie_httponly' => true,
                'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
            ];

            $options = array_merge($defaultOptions, $options);
            session_start($options);
        }

        self::$started = true;

        // จัดการ flash messages
        self::ageFlashData();
    }

    /**
     * ตรวจสอบว่า session เริ่มแล้วหรือยัง
     * 
     * @return bool
     */
    public static function isStarted(): bool
    {
        return self::$started && session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * ตั้งค่าข้อมูลใน session
     * 
     * @param string $key
     * @param mixed $value
     */
    public static function set(string $key, $value): void
    {
        self::ensureStarted();
        $_SESSION[$key] = $value;
    }

    /**
     * รับค่าจาก session
     * 
     * @param string $key
     * @param mixed $default ค่าเริ่มต้น
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        self::ensureStarted();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * ตรวจสอบว่ามีคีย์อยู่ใน session หรือไม่
     * 
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        self::ensureStarted();
        return isset($_SESSION[$key]);
    }

    /**
     * ลบข้อมูลจาก session
     * 
     * @param string $key
     */
    public static function remove(string $key): void
    {
        self::ensureStarted();
        unset($_SESSION[$key]);
    }

    /**
     * รับค่าและลบออกจาก session
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function pull(string $key, $default = null)
    {
        $value = self::get($key, $default);
        self::remove($key);
        return $value;
    }

    /**
     * รับข้อมูลทั้งหมดใน session
     * 
     * @return array
     */
    public static function all(): array
    {
        self::ensureStarted();
        return $_SESSION ?? [];
    }

    /**
     * ล้างข้อมูลทั้งหมดใน session
     */
    public static function clear(): void
    {
        self::ensureStarted();
        $_SESSION = [];
    }

    /**
     * ทำลาย session
     */
    public static function destroy(): void
    {
        self::ensureStarted();

        // ล้างข้อมูล session
        $_SESSION = [];

        // ลบ session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // ทำลาย session
        session_destroy();
        self::$started = false;
    }

    /**
     * สร้าง session ID ใหม่
     * 
     * @param bool $deleteOldSession
     */
    public static function regenerate(bool $deleteOldSession = true): void
    {
        self::ensureStarted();
        session_regenerate_id($deleteOldSession);
    }

    // ========== Flash Messages ==========

    /**
     * ตั้งค่า flash message
     * 
     * @param string $key
     * @param mixed $value
     */
    public static function flash(string $key, $value): void
    {
        self::ensureStarted();
        $_SESSION[self::FLASH_KEY]['new'][$key] = $value;
    }

    /**
     * รับ flash message
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getFlash(string $key, $default = null)
    {
        self::ensureStarted();
        return $_SESSION[self::FLASH_KEY]['old'][$key] ?? $default;
    }

    /**
     * ตรวจสอบว่ามี flash message หรือไม่
     * 
     * @param string $key
     * @return bool
     */
    public static function hasFlash(string $key): bool
    {
        self::ensureStarted();
        return isset($_SESSION[self::FLASH_KEY]['old'][$key]);
    }

    /**
     * รับ flash messages ทั้งหมด
     * 
     * @return array
     */
    public static function getAllFlash(): array
    {
        self::ensureStarted();
        return $_SESSION[self::FLASH_KEY]['old'] ?? [];
    }

    /**
     * Keep flash data สำหรับคำขอถัดไป
     * 
     * @param array|string $keys
     */
    public static function keepFlash($keys = null): void
    {
        self::ensureStarted();

        if ($keys === null) {
            $keys = array_keys($_SESSION[self::FLASH_KEY]['old'] ?? []);
        } elseif (is_string($keys)) {
            $keys = [$keys];
        }

        foreach ($keys as $key) {
            if (isset($_SESSION[self::FLASH_KEY]['old'][$key])) {
                $_SESSION[self::FLASH_KEY]['new'][$key] = $_SESSION[self::FLASH_KEY]['old'][$key];
            }
        }
    }

    /**
     * จัดการ flash data (เรียกทุกครั้งที่ start session)
     */
    private static function ageFlashData(): void
    {
        // ลบ flash data เก่า
        if (isset($_SESSION[self::FLASH_KEY]['old'])) {
            unset($_SESSION[self::FLASH_KEY]['old']);
        }

        // ย้าย new เป็น old
        if (isset($_SESSION[self::FLASH_KEY]['new'])) {
            $_SESSION[self::FLASH_KEY]['old'] = $_SESSION[self::FLASH_KEY]['new'];
            unset($_SESSION[self::FLASH_KEY]['new']);
        }
    }

    // ========== Old Input (สำหรับฟอร์ม) ==========

    /**
     * บันทึก old input
     * 
     * @param array $input
     */
    public static function flashInput(array $input): void
    {
        self::flash(self::OLD_INPUT_KEY, $input);
    }

    /**
     * รับ old input
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public static function old(?string $key = null, $default = null)
    {
        $oldInput = self::getFlash(self::OLD_INPUT_KEY, []);

        if ($key === null) {
            return $oldInput;
        }

        return $oldInput[$key] ?? $default;
    }

    /**
     * ตรวจสอบว่ามี old input หรือไม่
     * 
     * @param string $key
     * @return bool
     */
    public static function hasOldInput(string $key): bool
    {
        $oldInput = self::getFlash(self::OLD_INPUT_KEY, []);
        return isset($oldInput[$key]);
    }

    // ========== CSRF Protection ==========

    /**
     * สร้าง CSRF token
     * 
     * @return string
     */
    public static function generateCsrfToken(): string
    {
        self::ensureStarted();

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::CSRF_TOKEN_KEY] = $token;

        return $token;
    }

    /**
     * รับ CSRF token ปัจจุบัน
     * 
     * @return string|null
     */
    public static function getCsrfToken(): ?string
    {
        self::ensureStarted();
        return $_SESSION[self::CSRF_TOKEN_KEY] ?? null;
    }

    /**
     * ตรวจสอบ CSRF token
     * 
     * @param string $token
     * @return bool
     */
    public static function verifyCsrfToken(string $token): bool
    {
        $sessionToken = self::getCsrfToken();

        if (!$sessionToken) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * สร้าง HTML input สำหรับ CSRF token
     * 
     * @return string
     */
    public static function csrfField(): string
    {
        $token = self::getCsrfToken() ?? self::generateCsrfToken();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * สร้าง meta tag สำหรับ CSRF token
     * 
     * @return string
     */
    public static function csrfMeta(): string
    {
        $token = self::getCsrfToken() ?? self::generateCsrfToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }

    // ========== Helper Methods ==========

    /**
     * ตรวจสอบว่า session เริ่มแล้ว ถ้าไม่ให้เริ่ม
     */
    private static function ensureStarted(): void
    {
        if (!self::isStarted()) {
            self::start();
        }
    }

    /**
     * รับ Session ID
     * 
     * @return string
     */
    public static function id(): string
    {
        self::ensureStarted();
        return session_id();
    }

    /**
     * ตั้งค่า Session ID
     * 
     * @param string $id
     */
    public static function setId(string $id): void
    {
        session_id($id);
    }

    /**
     * รับชื่อ session
     * 
     * @return string
     */
    public static function name(): string
    {
        return session_name();
    }

    /**
     * ตั้งค่าชื่อ session
     * 
     * @param string $name
     */
    public static function setName(string $name): void
    {
        session_name($name);
    }
}
