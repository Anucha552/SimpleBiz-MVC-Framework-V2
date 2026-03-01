<?php
/**
 * คลาส Session สำหรับจัดการ session และ flash messages
 * 
 * จุดประสงค์: จัดการ session และ flash messages
 * Session() ควรใช้กับอะไร: การจัดการ session lifecycle, flash messages, CSRF token, และ old input
 * 
 * ฟีเจอร์หลัก:
 * - จัดการ session lifecycle
 * - Flash messages (ข้อความที่แสดงครั้งเดียว)
 * - CSRF token สำหรับความปลอดภัย
 * - Old input สำหรับฟอร์ม
 * 
 * ตัวอย่างการใช้งานโดยรวม:
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

use App\Core\Config;
use App\Core\Logger;
use Throwable;

class Session
{
    /**
     * ตรวจสอบว่า session เริ่มแล้วหรือยัง
     */
    private static bool $started = false;

    /**
     * Flash key สำหรับเก็บ flash messages
     */
    private const FLASH_KEY = '_flash';

    /**
     * Old input key สำหรับเก็บข้อมูล input เก่า
     */
    private const OLD_INPUT_KEY = '_old_input';

    /**
     * CSRF token key สำหรับเก็บ CSRF token
     */
    private const CSRF_TOKEN_KEY = '_csrf_token';

    /**
     * เริ่มต้น session
     * จุดประสงค์: เริ่ม session ถ้ายังไม่เริ่ม
     * start() ควรใช้กับอะไร: เมื่อคุณต้องการเริ่ม session เพื่อจัดการข้อมูลผู้ใช้
     * ตัวอย่างการใช้งาน:
     * ```php
     * Session::start();
     * ```
     * 
     * @param array $options กำหนดตัวเลือก session (ไม่บังคับ) เช่น cookie parameters
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public static function start(array $options = []): void
    {
        // ตรวจสอบว่า session เริ่มแล้วหรือยัง
        if (self::$started) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            // รองรับ CLI และ testing environment
            // สำหรับ CLI หรือ testing environment เราจะไม่ใช้ session ของ PHP
            $isTesting = (Config::get('app.env', 'development') === 'testing');
            if (PHP_SAPI === 'cli' || $isTesting) {
                if (!isset($_SESSION) || !is_array($_SESSION)) {
                    $_SESSION = [];
                }
                self::$started = true;
                self::ageFlashData();
                return;
            }

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
     * จุดประสงค์: ตรวจสอบสถานะการเริ่ม session
     * isStarted() ควรใช้กับอะไร: เมื่อต้องการตรวจสอบว่ามีการเริ่ม session หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * if (Session::isStarted()) {
     *     // session เริ่มแล้ว
     * }
     * ```
     * 
     * @return bool true ถ้า session เริ่มแล้ว, false ถ้ายังไม่เริ่ม
     */
    public static function isStarted(): bool
    {
        return self::$started && session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * ตั้งค่าข้อมูลใน session
     * จุดประสงค์: ตั้งค่าข้อมูลใน session
     * set() ควรใช้กับอะไร: เมื่อต้องการเก็บข้อมูลใน session
     * ตัวอย่างการใช้งาน:
     * ```php
     * Session::set('user_id', 123);
     * ```
     * 
     * @param string $key กำหนดชื่อคีย์
     * @param mixed $value กำหนดค่าที่จะตั้งค่า
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public static function set(string $key, $value): void
    {
        self::ensureStarted();
        $_SESSION[$key] = $value;
    }

    /**
     * รับค่าจาก session
     * จุดประสงค์: รับค่าจาก session
     * get() ควรใช้กับอะไร: เมื่อต้องการดึงข้อมูลจาก session
     * ตัวอย่างการใช้งาน:
     * ```php
     * $userId = Session::get('user_id', null);
     * ```
     * 
     * @param string $key กำหนดชื่อคีย์
     * @param mixed $default กำหนดค่าที่จะคืนถ้าไม่มีคีย์
     * @return mixed ค่าที่เก็บใน session หรือค่าดีฟอลต์ถ้าไม่มีคีย์
     */
    public static function get(string $key, $default = null)
    {
        self::ensureStarted();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * ตรวจสอบว่ามีคีย์อยู่ใน session หรือไม่
     * จุดประสงค์: ตรวจสอบการมีอยู่ของคีย์ใน session
     * has() ควรใช้กับอะไร: เมื่อต้องการตรวจสอบว่ามีคีย์ใน session หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * if (Session::has('user_id')) {
     *    // มีคีย์ 'user_id' ใน session
     * }
     * ```
     * 
     * @param string $key กำหนดชื่อคีย์
     * @return bool true ถ้ามีคีย์ใน session, false ถ้าไม่มี
     */
    public static function has(string $key): bool
    {
        self::ensureStarted();
        return isset($_SESSION[$key]);
    }

    /**
     * ลบข้อมูลจาก session
     * จุดประสงค์: ลบข้อมูลจาก session
     * remove() ควรใช้กับอะไร: เมื่อต้องการลบคีย์จาก session
     * ตัวอย่างการใช้งาน:
     * ```php
     * Session::remove('user_id');
     * ```
     * 
     * @param string $key กำหนดชื่อคีย์
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public static function remove(string $key): void
    {
        self::ensureStarted();
        unset($_SESSION[$key]);
    }

    /**
     * รับค่าและลบออกจาก session
     * จุดประสงค์: รับค่าจาก session แล้วลบคีย์นั้นออก
     * pull() ควรใช้กับอะไร: เมื่อต้องการดึงค่าจาก session และลบคีย์นั้นในครั้งเดียว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $value = Session::pull('flash_message', 'default value');
     * ```
     * 
     * @param string $key กำหนดชื่อคีย์
     * @param mixed $default กำหนดค่าที่จะคืนถ้าไม่มีคีย์
     * @return mixed ค่าที่เก็บใน session หรือค่าดีฟอลต์ถ้าไม่มีคีย์
     */
    public static function pull(string $key, $default = null)
    {
        $value = self::get($key, $default);
        self::remove($key);
        return $value;
    }

    /**
     * รับข้อมูลทั้งหมดใน session
     * จุดประสงค์: รับข้อมูลทั้งหมดใน session
     * ฃall() ควรใช้กับอะไร: เมื่อต้องการดึงข้อมูล session ทั้งหมด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $allData = Session::all();
     * ```
     * @return array ข้อมูลทั้งหมดใน session ในรูปแบบอาร์เรย์
     */
    public static function all(): array
    {
        self::ensureStarted();
        return $_SESSION ?? [];
    }

    /**
     * ล้างข้อมูลทั้งหมดใน session
     * จุดประสงค์: ล้างข้อมูลทั้งหมดใน session
     * clear() ควรใช้กับอะไร: เมื่อต้องการล้างข้อมูล session ทั้งหมด
     * ตัวอย่างการใช้งาน:
     * ```php
     * Session::clear();
     * ```
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public static function clear(): void
    {
        self::ensureStarted();
        $_SESSION = [];
    }

    /**
     * ทำลาย session
     * จุดประสงค์: ทำลาย session และลบข้อมูลทั้งหมด
     * destroy() ควรใช้กับอะไร: เมื่อต้องการทำลาย session ทั้งหมด
     * ตัวอย่างการใช้งาน:
     * ```php
     * Session::destroy();
     * ```
     * 
     * @return void ไม่มีค่าที่ส่งกลับ
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
     * จุดประสงค์: สร้าง session ID ใหม่เพื่อป้องกัน session fixation
     * regenerate() ควรใช้กับอะไร: เมื่อต้องการสร้าง session ID ใหม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * Session::regenerate();
     * ```
     * 
     * @param bool $deleteOldSession
     */
    public static function regenerate(bool $deleteOldSession = true): void
    {
        self::ensureStarted();
        session_regenerate_id($deleteOldSession);
    }

    /**
     * Regenerate session and log the event with context (login|remember|logout).
     * This helper preserves the original `regenerate()` behavior and adds a
     * contextual logging option without exposing session identifiers.
     *
     * @param string $context One of 'login','remember','logout' or custom
     * @param int|null $userId User id to include in log (nullable)
     * @param bool $deleteOldSession Passed to session_regenerate_id()
     * @return void
     */
    public static function regenerateWithContext(string $context, ?int $userId = null, bool $deleteOldSession = true): void
    {
        self::ensureStarted();
        session_regenerate_id($deleteOldSession);

        // Structured logging: do not log session id
        try {
            $logger = new Logger();
            $logger->info('auth.session.regenerated', [
                'context' => $context,
                'user_id' => $userId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
        } catch (Throwable $e) {
            // Logging should not interrupt execution
        }
    }

    // ========== Flash Messages ==========

    /**
     * ตั้งค่า flash message
     * จุดประสงค์: ตั้งค่า flash message ที่จะแสดงครั้งเดียว
     * flash() ควรใช้กับอะไร: เมื่อต้องการตั้งค่า flash message
     * ตัวอย่างการใช้งาน:
     * ```php
     * Session::flash('success', 'บันทึกสำเร็จ');
     * ```
     * 
     * @param string $key กำหนดชื่อคีย์
     * @param mixed $value กำหนดค่าที่จะตั้งค่า เช่น ข้อความ
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public static function flash(string $key, $value): void
    {
        self::ensureStarted();
        $_SESSION[self::FLASH_KEY]['new'][$key] = $value;
    }

    /**
     * รับ flash message
     * จุดประสงค์: รับ flash message ที่ตั้งค่าไว้
     * getFlash() ควรใช้กับอะไร: เมื่อต้องการรับ flash message
     * ตัวอย่างการใช้งาน:
     * ```php
     * $message = Session::getFlash('success');
     * ```
     * @param string $key กำหนดชื่อคีย์
     * @param mixed $default กำหนดค่าดีฟอลต์ถ้าไม่มีคีย์
     * @return mixed ค่าที่เก็บใน flash message หรือค่าดีฟอลต์ถ้าไม่มีคีย์
     */
    public static function getFlash(string $key, $default = null)
    {
        self::ensureStarted();
        return $_SESSION[self::FLASH_KEY]['old'][$key] ?? $default;
    }

    /**
     * ตรวจสอบว่ามี flash message หรือไม่
     * จุดประสงค์: ตรวจสอบว่ามี flash message หรือไม่
     * hasFlash() ควรใช้กับอะไร: เมื่อต้องการตรวจสอบ flash message
     * ตัวอย่างการใช้งาน:
     * ```php
     * if (Session::hasFlash('success')) {
     *     // มี flash message
     * }
     * ```
     * @param string $key กำหนดชื่อคีย์
     * @return bool คืนค่าเป็นจริงถ้ามี flash message ที่กำหนด
     */
    public static function hasFlash(string $key): bool
    {
        self::ensureStarted();
        return isset($_SESSION[self::FLASH_KEY]['old'][$key]);
    }

    /**
     * รับ flash messages ทั้งหมด
     * จุดประสงค์: รับ flash messages ทั้งหมด
     * allFlash() ควรใช้กับอะไร: เมื่อต้องการรับ flash messages ทั้งหมด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $allMessages = Session::allFlash();
     * ```
     * 
     * @return array คืนค่าอาร์เรย์ของ flash messages ทั้งหมด
     */
    public static function getAllFlash(): array
    {
        self::ensureStarted();
        return $_SESSION[self::FLASH_KEY]['old'] ?? [];
    }

    /**
     * Keep flash data สำหรับคำขอถัดไป
     * จุดประสงค์: รักษา flash data สำหรับคำขอถัดไป
     * keepFlash() ควรใช้กับอะไร: เมื่อต้องการเก็บ flash data สำหรับคำขอถัดไป
     * ตัวอย่างการใช้งาน:
     * ```php
     * Session::keepFlash(); // เก็บทั้งหมด
     * Session::keepFlash('success'); // เก็บเฉพาะคีย์ 'success'
     * ```
     * 
     * @param array|string $keys กำหนดชื่อคีย์หรืออาร์เรย์ของคีย์
     * @return void ไม่มีค่าที่ส่งกลับ
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
     * จุดประสงค์: จัดการอายุของ flash data
     * ageFlashData() ควรใช้กับอะไร: เรียกโดยอัตโนมัติเมื่อเริ่ม session
     * ตัวอย่างการใช้งาน:
     * ```php
     * Session::start(); // จะเรียก ageFlashData() อัตโนมัติ
     * ```
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
     * จุดประสงค์: บันทึกข้อมูล input เก่าที่ผู้ใช้ป้อน
     * flashInput() ควรใช้กับอะไร: เมื่อต้องการบันทึกข้อมูล input เก่า
     * ตัวอย่างการใช้งาน:
     * ```php
     * $inputData = $_POST;
     * Session::flashInput($inputData);
     * ```
     * 
     * @param array $input กำหนดอาร์เรย์ของข้อมูล input
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public static function flashInput(array $input): void
    {
        self::flash(self::OLD_INPUT_KEY, $input);
    }

    /**
     * รับ old input
     * จุดประสงค์: รับข้อมูล input เก่าที่ผู้ใช้ป้อน
     * old() ควรใช้กับอะไร: เมื่อต้องการรับข้อมูล input เก่า
     * ตัวอย่างการใช้งาน:
     * ```php
     * $oldInput = Session::old();
     * $oldValue = Session::old('username', 'default');
     * ```
     * 
     * @param string|null $key กำหนดชื่อคีย์ของ old input หรือ null เพื่อรับทั้งหมด
     * @param mixed $default ค่าที่จะคืนถ้าไม่มี old input ที่กำหนด
     * @return mixed คืนค่า old input หรือค่าดีฟอลต์ถ้าไม่มีคีย์
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
     * จุดประสงค์: ตรวจสอบว่ามี old input สำหรับคีย์ที่ระบุหรือไม่
     * hasOldInput() ควรใช้กับอะไร: เมื่อต้องการตรวจสอบ old input
     * ตัวอย่างการใช้งาน:
     * ```php
     * if (Session::hasOldInput('username')) {
     *     // มีข้อมูลเก่าสำหรับ 'username'
     * }
     * ```
     * 
     * @param string $key กำหนดชื่อคีย์ของ old input
     * @return bool คืนค่าเป็นจริงถ้ามี old input สำหรับคีย์ที่ระบุ
     */
    public static function hasOldInput(string $key): bool
    {
        $oldInput = self::getFlash(self::OLD_INPUT_KEY, []);
        return isset($oldInput[$key]);
    }

    // ========== CSRF Protection ==========

    /**
     * สร้าง CSRF token
     * จุดประสงค์: สร้าง CSRF token ใหม่และเก็บใน session
     * generateCsrfToken() ควรใช้กับอะไร: เมื่อต้องการสร้าง CSRF token ใหม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $token = Session::generateCsrfToken();
     * ```
     * 
     * @return string คืนค่า CSRF token ที่สร้างขึ้นใหม่
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
     * จุดประสงค์: รับ CSRF token ที่เก็บใน session
     * getCsrfToken() ควรใช้กับอะไร: เมื่อต้องการรับ CSRF token
     * ตัวอย่างการใช้งาน:
     * ```php
     * $token = Session::getCsrfToken();
     * ```
     * 
     * @return string|null คืนค่า CSRF token หรือ null ถ้าไม่มี
     */
    public static function getCsrfToken(): ?string
    {
        self::ensureStarted();
        return $_SESSION[self::CSRF_TOKEN_KEY] ?? null;
    }

    /**
     * ตรวจสอบ CSRF token
     * จุดประสงค์: ตรวจสอบว่า CSRF token ที่ส่งมาตรงกับที่เก็บใน session หรือไม่
     * verifyCsrfToken() ควรใช้กับอะไร: เมื่อต้องการตรวจสอบ CSRF token
     * ตัวอย่างการใช้งาน:
     * ```php
     * if (Session::verifyCsrfToken($tokenFromRequest)) {
     *    // CSRF token ถูกต้อง
     * }
     * ```
     * @param string $token กำหนด CSRF token ที่จะตรวจสอบ
     * @return bool คืนค่าจริงถ้า CSRF token ถูกต้อง, เท็จถ้าไม่ถูกต้อง
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
     * จุดประสงค์: สร้าง input type="hidden" สำหรับ CSRF token เพื่อใช้ในฟอร์ม
     * csrfField() ควรใช้กับอะไร: เมื่อต้องการเพิ่ม CSRF token ในฟอร์ม HTML
     * ตัวอย่างการใช้งาน:
     * ```php
     * echo Session::csrfField();
     * ```
     * 
     * @return string คืนค่า HTML input สำหรับ CSRF token
     */
    public static function csrfField(): string
    {
        $token = self::getCsrfToken() ?? self::generateCsrfToken();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * สร้าง meta tag สำหรับ CSRF token
     * จุดประสงค์: สร้าง meta tag สำหรับ CSRF token เพื่อใช้ใน HTML
     * csrfMeta() ควรใช้กับอะไร: เมื่อต้องการเพิ่ม CSRF token ในส่วนหัวของ HTML
     * ตัวอย่างการใช้งาน:
     * ```php
     * echo Session::csrfMeta();
     * ```
     * @return string คืนค่า meta tag สำหรับ CSRF token
     */
    public static function csrfMeta(): string
    {
        $token = self::getCsrfToken() ?? self::generateCsrfToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }

    // ========== Helper Methods ==========

    /**
     * ตรวจสอบว่า session เริ่มแล้ว ถ้าไม่ให้เริ่ม
     * จุดประสงค์: ตรวจสอบว่า session เริ่มแล้วหรือไม่ ถ้ายังไม่เริ่มให้เริ่ม session
     * ensureStarted() ควรใช้กับอะไร: เมื่อต้องการให้แน่ใจว่า session เริ่มแล้วก่อนใช้งาน
     * ตัวอย่างการใช้งาน:
     * ```php
     * Session::ensureStarted();
     * ```
     * 
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    private static function ensureStarted(): void
    {
        if (!self::isStarted()) {
            self::start();
        }
    }

    /**
     * รับ Session ID
     * จุดประสงค์: รับ Session ID ปัจจุบัน
     * id() ควรใช้กับอะไร: เมื่อต้องการดึง Session ID ปัจจุบัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $sessionId = Session::id();
     * ```
     * 
     * @return string คืนค่า Session ID ปัจจุบัน
     */
    public static function id(): string
    {
        self::ensureStarted();
        return session_id();
    }

    /**
     * ตั้งค่า Session ID
     * จุดประสงค์: ตั้งค่า Session ID ใหม่
     * setId() ควรใช้กับอะไร: เมื่อต้องการตั้งค่า Session ID ใหม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * Session::setId('new_session_id');
     * ```
     * 
     * @param string $id กำหนด Session ID ใหม่
     */
    public static function setId(string $id): void
    {
        session_id($id);
    }

    /**
     * รับชื่อ session
     * จุดประสงค์: รับชื่อ session ปัจจุบัน
     * name() ควรใช้กับอะไร: เมื่อต้องการดึงชื่อ session ปัจจุบัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $sessionName = Session::name();
     * ```
     * 
     * @return string คืนค่าชื่อ session ปัจจุบัน
     */
    public static function name(): string
    {
        return session_name();
    }

    /**
     * ตั้งค่าชื่อ session
     * จุดประสงค์: ตั้งค่าชื่อ session ใหม่
     * setName() ควรใช้กับอะไร: เมื่อต้องการตั้งค่าชื่อ session ใหม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * Session::setName('MY_SESSION');
     * ```
     * @param string $name กำหนดชื่อ session ใหม่
     */
    public static function setName(string $name): void
    {
        session_name($name);
    }
}
