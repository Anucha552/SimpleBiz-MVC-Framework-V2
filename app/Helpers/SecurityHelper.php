<?php
/**
 * Security Helper
 * 
 * จุดประสงค์: ฟังก์ชันช่วยเหลือด้านความปลอดภัย
 * 
 * ฟีเจอร์:
 * - ป้องกัน XSS, SQL Injection
 * - Sanitize และ Escape
 * - การเข้ารหัส
 * - การตรวจสอบความถูกต้องของข้อมูล
 * - การจัดการ CSRF
 * - การตั้งค่า security headers
 * - การจำกัดอัตราการเข้าถึง (Rate Limiting)
 * - การตรวจสอบความแข็งแรงของรหัสผ่าน
 * 
 */

namespace App\Helpers;

use finfo;

class SecurityHelper
{
    /**
     * Escape HTML เพื่อป้องกัน XSS
     * จุดประสงค์: ใช้เพื่อแปลงอักขระพิเศษในสตริงเป็นรูปแบบที่ปลอดภัยสำหรับการแสดงผลใน HTML
     * ตัวอย่างการใช้งาน:
     * ```php
     * $safeString = SecurityHelper::escape('<script>alert("XSS")</script>');
     * ```
     * 
     * ผลลัพธ์: &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;
     * 
     * returns string สตริงที่ถูก escape แล้ว
     * 
     * @param string $string
     * @return string
     */
    public static function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Escape HTML แบบเต็มรูปแบบ
     * จุดประสงค์: ใช้เพื่อแปลงอักขระพิเศษทั้งหมดในสตริงเป็นรูปแบบ HTML entities
     * ตัวอย่างการใช้งาน:
     * ```php
     * $safeString = SecurityHelper::escapeHtml('<div class="test">Hello & Welcome</div>');
     * ```
     * 
     * ผลลัพธ์: &amp;lt;div class=&amp;quot;test&amp;quot;&amp;gt;Hello &amp;amp; Welcome&amp;lt;/div&amp;gt;
     * 
     * returns string สตริงที่ถูก escape แล้ว
     * 
     * @param string $string
     * @return string
     */
    public static function escapeHtml(string $string): string
    {
        return htmlentities($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * ทำความสะอาด string
     * จุดประสงค์: ใช้เพื่อลบ HTML tags, ช่องว่างส่วนเกิน และ whitespace ซ้ำในสตริง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $cleanString = SecurityHelper::sanitize('  <b>Hello</b>   World!  ');
     * ```
     * 
     * ผลลัพธ์: Hello World!
     * 
     * returns string สตริงที่ถูกทำความสะอาดแล้ว
     * @param string $string
     * @return string
     */
    public static function sanitize(string $string): string
    {
        // ลบ HTML tags
        $string = strip_tags($string);
        
        // ลบช่องว่างส่วนเกิน
        $string = trim($string);
        
        // ลบ whitespace ซ้ำ
        $string = preg_replace('/\s+/', ' ', $string);
        
        return $string;
    }

    /**
     * ทำความสะอาด email
     * จุดประสงค์: ใช้เพื่อลบอักขระที่ไม่เหมาะสมออกจากอีเมล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $cleanEmail = SecurityHelper::sanitizeEmail(' user@example.com ');
     * ```
     * 
     * ผลลัพธ์: user@example.com
     * 
     * returns string อีเมลที่ถูกทำความสะอาดแล้ว
     * 
     * @param string $email
     * @return string
     */
    public static function sanitizeEmail(string $email): string
    {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    /**
     * ทำความสะอาด URL
     * จุดประสงค์: ใช้เพื่อลบอักขระที่ไม่เหมาะสมออกจาก URL
     * ตัวอย่างการใช้งาน:
     * ```php
     * $cleanUrl = SecurityHelper::sanitizeUrl(' https://example.com ');
     * ```
     * 
     * ผลลัพธ์: https://example.com
     * 
     * returns string URL ที่ถูกทำความสะอาดแล้ว
     * 
     * @param string $url
     * @return string
     */
    public static function sanitizeUrl(string $url): string
    {
        return filter_var($url, FILTER_SANITIZE_URL);
    }

    /**
     * ทำความสะอาดตัวเลข
     * จุดประสงค์: ใช้เพื่อลบอักขระที่ไม่เหมาะสมออกจากตัวเลข
     * ตัวอย่างการใช้งาน:
     * ```php
     * $cleanNumber = SecurityHelper::sanitizeInt(' 123abc ');
     * ```
     * 
     * ผลลัพธ์: 123
     * 
     * returns int ตัวเลขที่ถูกทำความสะอาดแล้ว
     * 
     * @param string $number
     * @return int
     */
    public static function sanitizeInt(string $number): int
    {
        return filter_var($number, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * ทำความสะอาดทศนิยม
     * จุดประสงค์: ใช้เพื่อลบอักขระที่ไม่เหมาะสมออกจากทศนิยม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $cleanFloat = SecurityHelper::sanitizeFloat(' 123.45abc ');
     * ```
     * 
     * ผลลัพธ์: 123.45
     * 
     * returns float ทศนิยมที่ถูกทำความสะอาดแล้ว
     * 
     * @param string $float
     * @return float
     */
    public static function sanitizeFloat(string $float): float
    {
        return filter_var($float, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * ลบ HTML tags
     * จุดประสงค์: ใช้เพื่อลบ HTML tags ออกจากสตริง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $cleanString = SecurityHelper::stripTags('<b>Hello</b> World!');
     * ```
     * 
     * ผลลัพธ์: Hello World!
     * 
     * returns string สตริงที่ถูกลบ HTML tags แล้ว
     * 
     * @param string $string
     * @param string|null $allowedTags
     * @return string
     */
    public static function stripTags(string $string, ?string $allowedTags = null): string
    {
        return strip_tags($string, $allowedTags);
    }

    /**
     * ลบ JavaScript
     * จุดประสงค์: ใช้เพื่อลบโค้ด JavaScript ออกจากสตริง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $cleanString = SecurityHelper::stripJavaScript('<script>alert("XSS")</script>Hello World!');
     * ```
     * 
     * ผลลัพธ์: Hello World!
     * 
     * returns string สตริงที่ถูกลบ JavaScript แล้ว
     * 
     * @param string $string
     * @return string
     */
    public static function stripJavaScript(string $string): string
    {
        return preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $string);
    }

    /**
     * ลบ SQL keywords
     * จุดประสงค์: ใช้เพื่อลบคำสำคัญของ SQL ออกจากสตริง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $cleanString = SecurityHelper::stripSqlKeywords('SELECT * FROM users');
     * ```
     * 
     * ผลลัพธ์: * FROM users
     * 
     * returns string สตริงที่ถูกลบคำสำคัญของ SQL แล้ว
     * 
     * @param string $string
     * @return string
     */
    public static function stripSqlKeywords(string $string): string
    {
        $keywords = [
            'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE',
            'ALTER', 'TRUNCATE', 'EXEC', 'EXECUTE', 'UNION', 'DECLARE'
        ];
        
        return str_ireplace($keywords, '', $string);
    }

    /**
     * Validate email
     * จุดประสงค์: ใช้เพื่อตรวจสอบความถูกต้องของอีเมล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isValid = SecurityHelper::isValidEmail('example@example.com');
     * ```
     * 
     * ผลลัพธ์: true
     * 
     * returns bool ผลลัพธ์การตรวจสอบอีเมล
     * 
     * @param string $email
     * @return bool
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL
     * จุดประสงค์: ใช้เพื่อตรวจสอบความถูกต้องของ URL
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isValid = SecurityHelper::isValidUrl('https://example.com');
     * ```
     * 
     * ผลลัพธ์: true
     * 
     * returns bool ผลลัพธ์การตรวจสอบ URL
     * 
     * @param string $url
     * @return bool
     */
    public static function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate IP
     * จุดประสงค์: ใช้เพื่อตรวจสอบความถูกต้องของ IP address
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isValid = SecurityHelper::isValidIp('192.168.1.1');
     * ```
     * 
     * ผลลัพธ์: true
     * 
     * returns bool ผลลัพธ์การตรวจสอบ IP address
     * 
     * @param string $ip
     * @return bool
     */
    public static function isValidIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * เข้ารหัส string ด้วย base64
     * จุดประสงค์: ใช้เพื่อเข้ารหัสสตริงเป็นรูปแบบ base64
     * ตัวอย่างการใช้งาน:
     * ```php
     * $encoded = SecurityHelper::base64Encode('Hello World');
     * ```
     * 
     * ผลลัพธ์: SGVsbG8gV29ybGQ=
     * 
     * returns string สตริงที่ถูกเข้ารหัสด้วย base64
     * 
     * @param string $string
     * @return string
     */
    public static function base64Encode(string $string): string
    {
        return base64_encode($string);
    }

    /**
     * ถอดรหัส base64
     * จุดประสงค์: ใช้เพื่อถอดรหัสสตริงที่ถูกเข้ารหัสด้วย base64
     * ตัวอย่างการใช้งาน:
     * ```php
     * $decoded = SecurityHelper::base64Decode('SGVsbG8gV29ybGQ=');
     * ```
     * 
     * ผลลัพธ์: Hello World
     * 
     * returns string สตริงที่ถูกถอดรหัส base64 แล้ว
     * @param string $string
     * @return string
     */
    public static function base64Decode(string $string): string
    {
        return base64_decode($string);
    }

    /**
     * เข้ารหัสแบบ URL-safe base64
     * จุดประสงค์: ใช้เพื่อเข้ารหัสสตริงเป็นรูปแบบ base64 ที่ปลอดภัยสำหรับ URL
     * ตัวอย่างการใช้งาน:
     * ```php
     * $encoded = SecurityHelper::base64UrlEncode('Hello World');
     * ```
     * 
     * ผลลัพธ์: SGVsbG8gV29ybGQ
     * 
     * returns string สตริงที่ถูกเข้ารหัสด้วย URL-safe base64
     * 
     * @param string $string
     * @return string
     */
    public static function base64UrlEncode(string $string): string
    {
        return rtrim(strtr(base64_encode($string), '+/', '-_'), '=');
    }

    /**
     * ถอดรหัส URL-safe base64
     * จุดประสงค์: ใช้เพื่อถอดรหัสสตริงที่ถูกเข้ารหัสด้วย URL-safe base64
     * ตัวอย่างการใช้งาน:
     * ```php
     * $decoded = SecurityHelper::base64UrlDecode('SGVsbG8gV29ybGQ');
     * ```
     * 
     * ผลลัพธ์: Hello World
     * 
     * returns string สตริงที่ถูกถอดรหัส URL-safe base64 แล้ว
     * 
     * @param string $string
     * @return string
     */
    public static function base64UrlDecode(string $string): string
    {
        return base64_decode(strtr($string, '-_', '+/'));
    }

    /**
     * Hash password
     * จุดประสงค์: ใช้เพื่อสร้าง hash ของรหัสผ่านโดยใช้ฟังก์ชัน password_hash
     * ตัวอย่างการใช้งาน:
     * ```php
     * $hash = SecurityHelper::hashPassword('my_secure_password');
     * ```
     * 
     * ผลลัพธ์: (string) hash ของรหัสผ่าน
     * 
     * returns string hash ของรหัสผ่าน
     * 
     * @param string $password
     * @return string
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify password
     * จุดประสงค์: ใช้เพื่อตรวจสอบรหัสผ่านกับ hash ที่เก็บไว้โดยใช้ฟังก์ชัน password_verify
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isValid = SecurityHelper::verifyPassword('my_secure_password', $hash);
     * ```
     * 
     * ผลลัพธ์: true
     * 
     * returns bool ผลลัพธ์การตรวจสอบรหัสผ่าน
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
     * สร้าง token สุ่ม
     * จุดประสงค์: ใช้เพื่อสร้าง token สุ่มที่มีความยาวตามที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $token = SecurityHelper::generateToken(32);
     * ```
     * 
     * ผลลัพธ์: (string) token สุ่ม
     * 
     * returns string token สุ่ม
     * 
     * @param int $length
     * @return string
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * สร้าง UUID v4
     * จุดประสงค์: ใช้เพื่อสร้าง UUID เวอร์ชัน 4 ที่ไม่ซ้ำกัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $uuid = SecurityHelper::uuid();
     * ```
     * 
     * ผลลัพธ์: (string) UUID v4
     * 
     * returns string UUID v4
     * 
     * @return string
     */
    public static function uuid(): string
    {
        $data = random_bytes(16);
        
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * สร้าง CSRF token
     * จุดประสงค์: ใช้เพื่อสร้าง token สำหรับป้องกัน CSRF
     * ตัวอย่างการใช้งาน:
     * ```php
     * $csrfToken = SecurityHelper::generateCsrfToken();
     * ```
     * 
     * ผลลัพธ์: (string) CSRF token
     * 
     * returns string CSRF token
     * 
     * @return string
     */
    public static function generateCsrfToken(): string
    {
        $token = self::generateToken(32);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['csrf_token'] = $token;
        
        return $token;
    }

    /**
     * ตรวจสอบ CSRF token
     * จุดประสงค์: ใช้เพื่อตรวจสอบ token สำหรับป้องกัน CSRF
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isValid = SecurityHelper::verifyCsrfToken($token);
     * ```
     * 
     * ผลลัพธ์: true
     * 
     * returns bool ผลลัพธ์การตรวจสอบ CSRF token
     * 
     * @param string $token
     * @return bool
     */
    public static function verifyCsrfToken(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * เข้ารหัสข้อความ
     * จุดประสงค์: ใช้เพื่อเข้ารหัสข้อความด้วย AES-256-CBC
     * ตัวอย่างการใช้งาน:
     * ```php
     * $encrypted = SecurityHelper::encrypt('Hello World', 'my_secret_key');
     * ```
     * ผลลัพธ์: (string) ข้อความที่ถูกเข้ารหัส
     * 
     * returns string ข้อความที่ถูกเข้ารหัส
     * 
     * @param string $data
     * @param string $key
     * @return string
     */
    public static function encrypt(string $data, string $key): string
    {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        
        return self::base64UrlEncode($iv . $encrypted);
    }

    /**
     * ถอดรหัสข้อความ
     * จุดประสงค์: ใช้เพื่อถอดรหัสข้อความที่ถูกเข้ารหัสด้วย AES-256-CBC
     * ตัวอย่างการใช้งาน:
     * ```php
     * $decrypted = SecurityHelper::decrypt($encryptedData, 'my_secret_key');
     * ```
     * ผลลัพธ์: (string|false) ข้อความที่ถูกถอดรหัสหรือ false ถ้าถอดรหัสไม่สำเร็จ
     * 
     * returns string|false ข้อความที่ถูกถอดรหัสหรือ false
     * 
     * @param string $data
     * @param string $key
     * @return string|false
     */
    public static function decrypt(string $data, string $key)
    {
        $data = self::base64UrlDecode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }

    /**
     * สร้าง hash
     * จุดประสงค์: ใช้เพื่อสร้าง hash ของข้อมูลด้วยอัลกอริทึมที่กำหนด
     * อัลกอริทึมที่รองรับ: md5, sha1, sha256, sha512 เป็นต้น
     * ตัวอย่างการใช้งาน:
     * ```php
     * $hash = SecurityHelper::hash('my_data', 'sha256');
     * ```
     * 
     * ผลลัพธ์: (string) hash ของข้อมูล
     * 
     * returns string hash ของข้อมูล
     * 
     * @param string $data
     * @param string $algo
     * @return string
     */
    public static function hash(string $data, string $algo = 'sha256'): string
    {
        return hash($algo, $data);
    }

    /**
     * สร้าง HMAC
     * จุดประสงค์: ใช้เพื่อสร้าง HMAC ของข้อมูลด้วยอัลกอริทึมที่กำหนด
     * อัลกอริทึมที่รองรับ: md5, sha1, sha256, sha512 เป็นต้น
     * ตัวอย่างการใช้งาน:
     * ```php
     * $hmac = SecurityHelper::hmac('my_data', 'my_secret_key', 'sha256');
     * ```
     * 
     * ผลลัพธ์: (string) HMAC ของข้อมูล
     * 
     * returns string HMAC ของข้อมูล
     * 
     * @param string $data
     * @param string $key
     * @param string $algo
     * @return string
     */
    public static function hmac(string $data, string $key, string $algo = 'sha256'): string
    {
        return hash_hmac($algo, $data, $key);
    }

    /**
     * ตรวจสอบ hash แบบปลอดภัย
     * จุดประสงค์: ใช้เพื่อตรวจสอบความเท่ากันของ hash สองค่าอย่างปลอดภัย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isEqual = SecurityHelper::hashEquals($knownHash, $userHash);
     * ```
     * 
     * ผลลัพธ์: true
     * 
     * returns bool ผลลัพธ์การตรวจสอบความเท่ากันของ hash
     * 
     * @param string $known
     * @param string $user
     * @return bool
     */
    public static function hashEquals(string $known, string $user): bool
    {
        return hash_equals($known, $user);
    }

    /**
     * ซ่อนข้อมูลบางส่วน (เช่น อีเมล, เบอร์โทร)
     * จุดประสงค์: ใช้เพื่อซ่อนข้อมูลบางส่วนของสตริงด้วยอักขระมาสก์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $maskedString = SecurityHelper::mask('1234567890', 3, 4);
     * ```
     * 
     * ผลลัพธ์: 123****890
     * 
     * returns string สตริงที่ถูกซ่อนข้อมูลบางส่วนแล้ว
     * 
     * @param string $string
     * @param int $start
     * @param int $length
     * @param string $mask
     * @return string
     */
    public static function mask(string $string, int $start = 0, ?int $length = null, string $mask = '*'): string
    {
        if ($length === null) {
            $length = strlen($string) - $start;
        }
        
        $segment = mb_substr($string, $start, $length);
        $maskString = str_repeat($mask, mb_strlen($segment));
        
        return mb_substr($string, 0, $start) . $maskString . mb_substr($string, $start + $length);
    }

    /**
     * ซ่อนอีเมล
     * จุดประสงค์: ใช้เพื่อซ่อนส่วนหนึ่งของอีเมลเพื่อความเป็นส่วนตัว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $maskedEmail = SecurityHelper::maskEmail('example@example.com');
     * ```
     * 
     * ผลลัพธ์: ex******@example.com
     * 
     * returns string อีเมลที่ถูกซ่อนบางส่วนแล้ว
     * 
     * @param string $email
     * @return string
     */
    public static function maskEmail(string $email): string
    {
        if (strpos($email, '@') === false) {
            return $email;
        }
        
        [$name, $domain] = explode('@', $email);
        
        $nameLength = strlen($name);
        $visibleLength = min(2, $nameLength);
        $maskedLength = $nameLength - $visibleLength;
        
        return substr($name, 0, $visibleLength) . str_repeat('*', $maskedLength) . '@' . $domain;
    }

    /**
     * ซ่อนเบอร์โทร
     * จุดประสงค์: ใช้เพื่อซ่อนส่วนหนึ่งของเบอร์โทรเพื่อความเป็นส่วนตัว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $maskedPhone = SecurityHelper::maskPhone('0123456789');
     * ```
     * 
     * ผลลัพธ์: 012****789
     * 
     * returns string เบอร์โทรที่ถูกซ่อนบางส่วนแล้ว
     * 
     * @param string $phone
     * @return string
     */
    public static function maskPhone(string $phone): string
    {
        $length = strlen($phone);
        
        if ($length < 6) {
            return $phone;
        }
        
        return substr($phone, 0, 3) . str_repeat('*', $length - 6) . substr($phone, -3);
    }

    /**
     * ทำความสะอาดชื่อไฟล์
     * จุดประสงค์: ใช้เพื่อลบอักขระที่ไม่ปลอดภัยออกจากชื่อไฟล์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $cleanFilename = SecurityHelper::cleanFilename('../etc/passwd');
     * 
     * ผลลัพธ์: etc_passwd
     * 
     * returns string ชื่อไฟล์ที่ถูกทำความสะอาดแล้ว
     * 
     * @param string $filename
     * @return string
     */
    public static function cleanFilename(string $filename): string
    {
        // ลบ path traversal
        $filename = str_replace(['../', '..\\'], '', $filename);
        
        // ลบอักขระพิเศษ
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        return $filename;
    }

    /**
     * ตรวจสอบ file extension
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าไฟล์มีนามสกุลที่อนุญาตหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isAllowed = SecurityHelper::isAllowedExtension('image.jpg', ['jpg', 'png', 'gif']);
     * ```
     * ผลลัพธ์: true
     * 
     * returns bool ผลลัพธ์การตรวจสอบนามสกุลไฟล์
     * 
     * @param string $filename
     * @param array $allowedExtensions
     * @return bool
     */
    public static function isAllowedExtension(string $filename, array $allowedExtensions): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, array_map('strtolower', $allowedExtensions));
    }

    /**
     * ตรวจสอบ MIME type
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าไฟล์มี MIME type ที่อนุญาตหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isAllowed = SecurityHelper::isAllowedMimeType('path/to/file.jpg', ['image/jpeg', 'image/png']);
     * ```
     * ผลลัพธ์: true
     * 
     * returns bool ผลลัพธ์การตรวจสอบ MIME type
     * 
     * @param string $file
     * @param array $allowedMimeTypes
     * @return bool
     */
    public static function isAllowedMimeType(string $file, array $allowedMimeTypes): bool
    {
        if (!file_exists($file)) {
            return false;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file);

        return in_array($mimeType, $allowedMimeTypes, true);
    }


    /**
     * Escape JSON
     * จุดประสงค์: ใช้เพื่อป้องกันการโจมตีแบบ JSON Injection
     * ตัวอย่างการใช้งาน:
     * ```php
     * $escapedJson = SecurityHelper::escapeJson('{"key": "value"}');
     * ```
     * ผลลัพธ์: "{\"key\": \"value\"}"
     * 
     * returns string ข้อความ JSON ที่ถูก escape แล้ว
     * 
     * @param string $string
     * @return string
     */
    public static function escapeJson(string $string): string
    {
        return json_encode($string, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    /**
     * Escape JavaScript
     * จุดประสงค์: ใช้เพื่อป้องกันการโจมตีแบบ JavaScript Injection
     * ตัวอย่างการใช้งาน:
     * ```php
     * $escapedJs = SecurityHelper::escapeJs("alert('XSS');");
     * ```
     * 
     * ผลลัพธ์: alert(\'XSS\');
     * 
     * returns string ข้อความ JavaScript ที่ถูก escape แล้ว
     * 
     * @param string $string
     * @return string
     */
    public static function escapeJs(string $string): string
    {
        return str_replace(
            ['\\', "'", '"', "\n", "\r", '<', '>', '&'],
            ['\\\\', "\\'", '\\"', '\\n', '\\r', '\\x3C', '\\x3E', '\\x26'],
            $string
        );
    }

    /**
     * Escape attribute
     * จุดประสงค์: ใช้เพื่อป้องกันการโจมตีแบบ Attribute Injection
     * ตัวอย่างการใช้งาน:
     * ```php
     * $escapedAttr = SecurityHelper::escapeAttr('onmouseover="alert(\'XSS\')"');
     * ```
     * 
     * ผลลัพธ์: onmouseover=&quot;alert(&#039;XSS&#039;)&quot;
     * 
     * returns string ข้อความ Attribute ที่ถูก escape แล้ว
     * 
     * @param string $string
     * @return string
     */
    public static function escapeAttr(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * ป้องกัน clickjacking
     * จุดประสงค์: ใช้เพื่อป้องกันการโจมตีแบบ clickjacking โดยการตั้งค่า header X-Frame-Options
     * ตัวอย่างการใช้งาน:
     * ```php
     * SecurityHelper::preventClickjacking();
     * ```
     * 
     * ผลลัพธ์: ตั้งค่า header X-Frame-Options เป็น DENY
     * 
     * returns void
     * 
     * @return void
     */
    public static function preventClickjacking(): void
    {
        header('X-Frame-Options: DENY');
    }

    /**
     * ตั้งค่า Content Security Policy
     * จุดประสงค์: ใช้เพื่อป้องกันการโจมตีแบบ Content Security Policy
     * ตัวอย่างการใช้งาน:
     * ```php
     * SecurityHelper::setCSP("default-src 'self'; script-src 'self' https://trustedscripts.example.com");
     * ```
     * 
     * ผลลัพธ์: ตั้งค่า header Content-Security-Policy ตามนโยบายที่กำหนด
     * 
     * returns void
     * 
     * @param string $policy
     * @return void
     */
    public static function setCSP(string $policy): void
    {
        header("Content-Security-Policy: $policy");
    }

    /**
     * ตั้งค่า security headers
     * จุดประสงค์: ใช้เพื่อป้องกันการโจมตีแบบ security headers
     * ตัวอย่างการใช้งาน:
     * ```php
     * SecurityHelper::setSecurityHeaders();
     * ```
     * 
     * ผลลัพธ์: ตั้งค่า header X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy
     * 
     * returns void
     * @return void
     */
    public static function setSecurityHeaders(): void
    {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }

    /**
     * Force HTTPS
     * จุดประสงค์: ใช้เพื่อบังคับให้เชื่อมต่อผ่าน HTTPS
     * ตัวอย่างการใช้งาน:
     * ```php
     * SecurityHelper::forceHttps();
     * ```
     * 
     * ผลลัพธ์: ถ้าไม่ใช่ HTTPS จะทำการ redirect ไปยัง URL ที่ใช้ HTTPS
     * 
     * returns void
     * 
     * @return void
     */
    public static function forceHttps(): void
    {
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('Location: ' . $redirect, true, 301);
            exit;
        }
    }

    /**
     * ป้องกัน MIME type sniffing
     * จุดประสงค์: ใช้เพื่อป้องกันการโจมตีแบบ MIME type sniffing
     * ตัวอย่างการใช้งาน:
     * ```php
     * SecurityHelper::preventMimeSniffing();
     * ```
     * 
     * ผลลัพธ์: ตั้งค่า header X-Content-Type-Options เป็น nosniff
     * 
     * returns void
     * 
     * @return void
     */
    public static function preventMimeSniffing(): void
    {
        header('X-Content-Type-Options: nosniff');
    }

    /**
     * Rate limiting check
     * จุดประสงค์: ใช้เพื่อตรวจสอบการจำกัดอัตราการเข้าถึงตามคีย์ที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isAllowed = SecurityHelper::rateLimitCheck('login_attempts', 5, 1);
     * ```
     * 
     * ผลลัพธ์: true ถ้ายังไม่เกินจำนวนครั้งที่กำหนด, false ถ้าเกิน
     * 
     * returns bool ผลลัพธ์การตรวจสอบ rate limit
     * 
     * @param string $key
     * @param int $maxAttempts
     * @param int $decayMinutes
     * @return bool
     */
    public static function rateLimitCheck(string $key, int $maxAttempts = 5, int $decayMinutes = 1): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $rateKey = 'rate_limit_' . $key;
        $now = time();
        
        if (!isset($_SESSION[$rateKey])) {
            $_SESSION[$rateKey] = [
                'attempts' => 1,
                'reset_at' => $now + ($decayMinutes * 60)
            ];
            return true;
        }
        
        $data = $_SESSION[$rateKey];
        
        if ($now > $data['reset_at']) {
            $_SESSION[$rateKey] = [
                'attempts' => 1,
                'reset_at' => $now + ($decayMinutes * 60)
            ];
            return true;
        }
        
        if ($data['attempts'] >= $maxAttempts) {
            return false;
        }
        
        $_SESSION[$rateKey]['attempts']++;
        
        return true;
    }

    /**
     * Clear rate limit
     * จุดประสงค์: ใช้เพื่อล้างข้อมูล rate limit สำหรับคีย์ที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * SecurityHelper::clearRateLimit('login_attempts');
     * ```
     * 
     * ผลลัพธ์: ล้างข้อมูล rate limit สำหรับคีย์ที่กำหนด
     * 
     * returns void
     * 
     * @param string $key
     * @return void
     */
    public static function clearRateLimit(string $key): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $rateKey = 'rate_limit_' . $key;
        unset($_SESSION[$rateKey]);
    }

    /**
     * ตรวจสอบความแข็งแรงของรหัสผ่าน
     * จุดประสงค์: ใช้เพื่อตรวจสอบความแข็งแรงของรหัสผ่าน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $strength = SecurityHelper::checkPasswordStrength('MyP@ssw0rd');
     * ```
     * 
     * ผลลัพธ์:
     * [
     *   'score' => 4,
     *   'label' => 'แข็งแรงมาก',
     *   'feedback' => []
     * ]
     * 
     * returns array ผลลัพธ์การตรวจสอบความแข็งแรงของรหัสผ่าน
     * 
     * @param string $password
     * @return array
     */
    public static function checkPasswordStrength(string $password): array
    {
        $strength = 0;
        $feedback = [];
        
        // ความยาว
        if (strlen($password) >= 8) {
            $strength++;
        } else {
            $feedback[] = 'ควรมีอย่างน้อย 8 ตัวอักษร';
        }
        
        // ตัวพิมพ์เล็ก
        if (preg_match('/[a-z]/', $password)) {
            $strength++;
        } else {
            $feedback[] = 'ควรมีตัวพิมพ์เล็ก';
        }
        
        // ตัวพิมพ์ใหญ่
        if (preg_match('/[A-Z]/', $password)) {
            $strength++;
        } else {
            $feedback[] = 'ควรมีตัวพิมพ์ใหญ่';
        }
        
        // ตัวเลข
        if (preg_match('/[0-9]/', $password)) {
            $strength++;
        } else {
            $feedback[] = 'ควรมีตัวเลข';
        }
        
        // อักขระพิเศษ
        if (preg_match('/[^a-zA-Z0-9]/', $password)) {
            $strength++;
        } else {
            $feedback[] = 'ควรมีอักขระพิเศษ';
        }
        
        $strengthLabel = ['อ่อนมาก', 'อ่อน', 'ปานกลาง', 'แข็งแรง', 'แข็งแรงมาก'];
        
        return [
            'score' => $strength,
            'label' => $strengthLabel[$strength] ?? 'อ่อนมาก',
            'feedback' => $feedback
        ];
    }
}
