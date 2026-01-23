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
use App\Core\Response;
use App\Core\Session;

class CsrfMiddleware extends Middleware
{
    private Logger $logger;
    private const TOKEN_LENGTH = 32;
    private const TOKEN_LIFETIME = 3600; // 1 ชั่วโมง

    public function __construct()
    {
        Session::start();

        $this->logger = new Logger();

        // สร้าง CSRF token ถ้ายังไม่มี
        $this->ensureTokenExists();
    }

    /**
     * จัดการการตรวจสอบ CSRF token
     * 
        * @return bool|Response True เพื่อดำเนินการต่อ, false เพื่อหยุด, หรือ Response เพื่อส่งกลับทันที
     */
        public function handle(?\App\Core\Request $request = null): bool|Response
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

            Session::flash('error', 'CSRF token missing');
            $referer = $_SERVER['HTTP_REFERER'] ?? '/';
            return Response::redirect($referer);
        }

        // ตรวจสอบ token
        if (!$this->validateToken($submittedToken)) {
            $this->logger->security('csrf.invalid_token', [
                'route' => $uri,
                'method' => $method,
            ]);

            Session::flash('error', 'Invalid CSRF token');
            $referer = $_SERVER['HTTP_REFERER'] ?? '/';
            return Response::redirect($referer);
        }

        // Token ถูกต้อง
        return true;
    }

    /**
     * ตรวจสอบว่า CSRF token มีอยู่ในเซสชัน
     */
    private function ensureTokenExists(): void
    {
        // Backward-compat: if legacy csrf_token exists, mirror into Session token.
        if (isset($_SESSION['csrf_token']) && !Session::getCsrfToken()) {
            $_SESSION['_csrf_token'] = $_SESSION['csrf_token'];
        }

        // Ensure Session token exists.
        if (!Session::getCsrfToken()) {
            Session::generateCsrfToken();
        }

        // Mirror Session token to legacy key so old templates still work.
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = Session::getCsrfToken();
            $_SESSION['csrf_token_time'] = time();
        }

        if (!isset($_SESSION['csrf_token_time'])) {
            $_SESSION['csrf_token_time'] = time();
        }
    }

    /**
     * สร้าง CSRF token ใหม่
     */
    private function regenerateToken(): void
    {
        // Generate via Session token, then mirror to legacy.
        $token = Session::generateCsrfToken();
        $_SESSION['csrf_token'] = $token;
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
        // ตรวจสอบจาก POST data (new + legacy)
        if (isset($_POST['_csrf_token'])) {
            return (string) $_POST['_csrf_token'];
        }
        if (isset($_POST['csrf_token'])) {
            return (string) $_POST['csrf_token'];
        }

        // ตรวจสอบจาก header (สำหรับ AJAX requests)
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return (string) $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        if (isset($_SERVER['HTTP_X_CSRF-TOKEN'])) {
            return (string) $_SERVER['HTTP_X_CSRF-TOKEN'];
        }
        if (isset($_SERVER['HTTP_X_XSRF_TOKEN'])) {
            return (string) $_SERVER['HTTP_X_XSRF_TOKEN'];
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
        $sessionToken = Session::getCsrfToken();
        if (is_string($sessionToken) && $sessionToken !== '' && hash_equals($sessionToken, $token)) {
            return true;
        }

        // Legacy fallback
        if (isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token'])) {
            return hash_equals($_SESSION['csrf_token'], $token);
        }

        return false;
    }

    /**
     * รับ CSRF token สำหรับใช้ในฟอร์ม (static method)
     * 
     * @return string
     */
    public static function getToken(): string
    {
        Session::start();
        $token = Session::getCsrfToken() ?? Session::generateCsrfToken();
        // Mirror to legacy
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = $_SESSION['csrf_token_time'] ?? time();
        return $token;
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
