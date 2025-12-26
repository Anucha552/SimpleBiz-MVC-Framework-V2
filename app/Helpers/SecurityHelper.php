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
 */

namespace App\Helpers;

class SecurityHelper
{
    /**
     * Escape HTML เพื่อป้องกัน XSS
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
     * 
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
     * 
     * @param string $string
     * @return string
     */
    public static function base64Decode(string $string): string
    {
        return base64_decode($string);
    }

    /**
     * เข้ารหัสแบบ URL-safe base64
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
     * Encrypt string
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
     * Decrypt string
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
     * Clean filename
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
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file);
        finfo_close($finfo);
        
        return in_array($mimeType, $allowedMimeTypes);
    }

    /**
     * Escape JSON
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
     * 
     * @return void
     */
    public static function preventClickjacking(): void
    {
        header('X-Frame-Options: DENY');
    }

    /**
     * ตั้งค่า Content Security Policy
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
     * 
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
     * 
     * @return void
     */
    public static function preventMimeSniffing(): void
    {
        header('X-Content-Type-Options: nosniff');
    }

    /**
     * Rate limiting check
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
