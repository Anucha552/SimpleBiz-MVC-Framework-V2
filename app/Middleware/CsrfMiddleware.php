<?php
/**
 * MIDDLEWARE CSRF TOKEN
 * 
 * จุดประสงค์: ป้องกัน Cross-Site Request Forgery (CSRF) attacks
 * 
 * การใช้งาน:
 * ใช้กับฟอร์มทั้งหมดที่มีการส่งข้อมูล (POST, PUT, DELETE):
 * - ฟอร์มการสมัครสมาชิก/เข้าสู่ระบบ
 * - ฟอร์มการเพิ่มสินค้าในตะกร้า
 * - ฟอร์มการสั่งซื้อ
 * - ฟอร์มการอัปเดตข้อมูล
 * 
 * วิธีการทำงาน:
 * 1. สร้าง CSRF token เฉพาะสำหรับแต่ละเซสชัน
 * 2. เก็บ token ในเซสชัน
 * 3. ตรวจสอบ token ที่ส่งมากับฟอร์มกับ token ในเซสชัน
 * 4. อนุญาตหรือปฏิเสธคำขอตาม token ที่ถูกต้อง
 * 
 * ความปลอดภัย:
 * - สร้าง token แบบสุ่มที่ไม่สามารถคาดเดาได้
 * - Token จะเปลี่ยนแปลงทุกครั้งที่มีการ regenerate เซสชัน
 * - บันทึกความพยายาม CSRF ที่ไม่ถูกต้อง
 * 
 * การใช้งานในฟอร์ม:
 * <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
 */

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Logger;

class CsrfMiddleware extends Middleware
{
    private Logger $logger;
    private const TOKEN_LENGTH = 32;
    private const TOKEN_LIFETIME = 3600; // 1 ชั่วโมง

    public function __construct()
    {
        // เริ่มเซสชันถ้ายังไม่ได้เริ่ม
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->logger = new Logger();

        // สร้าง CSRF token ถ้ายังไม่มี
        $this->ensureTokenExists();
    }

    /**
     * จัดการการตรวจสอบ CSRF token
     * 
     * @return bool True เพื่อดำเนินการต่อ, false เพื่อหยุด
     */
    public function handle(): bool
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // ตรวจสอบเฉพาะเมธอดที่เปลี่ยนแปลงข้อมูล
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return true; // ไม่ต้องตรวจสอบสำหรับ GET, HEAD, OPTIONS
        }

        // ข้าม API endpoints (ใช้ API key แทน)
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/api/') === 0) {
            return true;
        }

        // ตรวจสอบว่า token หมดอายุหรือไม่
        if ($this->isTokenExpired()) {
            $this->regenerateToken();
        }

        // รับ token จากคำขอ
        $submittedToken = $this->getSubmittedToken();

        if (!$submittedToken) {
            $this->logger->security('csrf.missing_token', [
                'route' => $uri,
                'method' => $method,
            ]);

            $this->jsonError('CSRF token missing', 403);
            return false;
        }

        // ตรวจสอบ token
        if (!$this->validateToken($submittedToken)) {
            $this->logger->security('csrf.invalid_token', [
                'route' => $uri,
                'method' => $method,
            ]);

            $this->jsonError('Invalid CSRF token', 403);
            return false;
        }

        // Token ถูกต้อง
        return true;
    }

    /**
     * ตรวจสอบว่า CSRF token มีอยู่ในเซสชัน
     */
    private function ensureTokenExists(): void
    {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            $this->regenerateToken();
        }
    }

    /**
     * สร้าง CSRF token ใหม่
     */
    private function regenerateToken(): void
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $_SESSION['csrf_token_time'] = time();
    }

    /**
     * ตรวจสอบว่า token หมดอายุหรือไม่
     * 
     * @return bool
     */
    private function isTokenExpired(): bool
    {
        if (!isset($_SESSION['csrf_token_time'])) {
            return true;
        }

        return (time() - $_SESSION['csrf_token_time']) > self::TOKEN_LIFETIME;
    }

    /**
     * รับ token ที่ส่งมาจากคำขอ
     * 
     * @return string|null
     */
    private function getSubmittedToken(): ?string
    {
        // ตรวจสอบจาก POST data
        if (isset($_POST['csrf_token'])) {
            return $_POST['csrf_token'];
        }

        // ตรวจสอบจาก header (สำหรับ AJAX requests)
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        return null;
    }

    /**
     * ตรวจสอบ token ที่ส่งมา
     * 
     * @param string $token
     * @return bool
     */
    private function validateToken(string $token): bool
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }

        // ใช้ hash_equals เพื่อป้องกัน timing attacks
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * รับ CSRF token สำหรับใช้ในฟอร์ม (static method)
     * 
     * @return string
     */
    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(self::TOKEN_LENGTH));
            $_SESSION['csrf_token_time'] = time();
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * สร้าง HTML input field สำหรับ CSRF token
     * 
     * @return string
     */
    public static function field(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
