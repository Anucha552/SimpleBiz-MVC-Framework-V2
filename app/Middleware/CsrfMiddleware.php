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
    /**
     * ตัวบันทึกเหตุการณ์ (Logger) สำหรับบันทึกความพยายาม CSRF ที่ไม่ถูกต้องและเหตุการณ์ที่เกี่ยวข้องกับ CSRF
     */
    private Logger $logger;

    /**
     * TOKEN_LENGTH ความยาวของ CSRF token ที่สร้างขึ้น
     */
    private const TOKEN_LENGTH = 32;

    /**
     * TOKEN_LIFETIME ระยะเวลาที่ CSRF token มีอายุ (วินาที) ก่อนที่จะหมดอายุและต้องสร้างใหม่
     */
    private const TOKEN_LIFETIME = 3600;

    /**
     * สร้างอินสแตนซ์ CsrfMiddleware ใหม่
     * จุดประสงค์: เตรียมตัวบันทึกเหตุการณ์และเริ่มต้นเซสชันเพื่อใช้ในการจัดการ CSRF token
     */
    public function __construct()
    {
        Session::start();

        $this->logger = new Logger();

        // สร้าง CSRF token ถ้ายังไม่มี
        $this->ensureTokenExists();
    }

    /**
     * จัดการการตรวจสอบ CSRF token
     * จุดประสงค์: ตรวจสอบว่า token ที่ส่งมาจากคำขอถูกต้องและยังไม่หมดอายุ เพื่อป้องกันการโจมตี CSRF
     * 
     * @param \App\Core\Request|null $request คำขอ HTTP ปัจจุบัน
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
        if (preg_match('#^/api(/|$)#', $uri)) {
            return true;
        }

        // ตรวจสอบว่า token หมดอายุหรือไม่
        if ($this->isTokenExpired()) {
            $this->regenerateToken();
        }

        // รับ token จากคำขอ
        $submittedToken = $this->getSubmittedToken();

        // ตรวจสอบว่า token ถูกส่งมาหรือไม่
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
     * จุดประสงค์: ให้แน่ใจว่า CSRF token ถูกสร้างและเก็บไว้ในเซสชันเพื่อใช้ในการตรวจสอบคำขอ
     * 
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะตรวจสอบและสร้าง token หากยังไม่มี
     */
    private function ensureTokenExists(): void
    {
        // รองรับ legacy token สำหรับเทมเพลตเก่า
        $legacy = Session::get('csrf_token');
        if ($legacy && !Session::getCsrfToken()) {
            Session::set('_csrf_token', $legacy);
        }

        // สร้าง token ใหม่ถ้ายังไม่มี
        if (!Session::getCsrfToken()) {
            Session::generateCsrfToken();
        }

        // เช็คและตั้งค่า token time ถ้ายังไม่มี
        if (!Session::has('csrf_token')) {
            Session::set('csrf_token', Session::getCsrfToken());
            Session::set('csrf_token_time', time());
        }

        // ตั้งค่า token time ถ้ายังไม่มี (สำหรับกรณีที่มี token แต่ไม่มีเวลา)
        if (!Session::has('csrf_token_time')) {
            Session::set('csrf_token_time', time());
        }
    }

    /**
     * สร้าง CSRF token ใหม่
     * จุดประสงค์: ให้สามารถสร้าง token ใหม่ได้เมื่อ token ปัจจุบันหมดอายุหรือเมื่อมีการ regenerate เซสชัน เพื่อรักษาความปลอดภัยของแอปพลิเคชัน
     * 
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะสร้าง token ใหม่และเก็บไว้ในเซสชัน
     */
    private function regenerateToken(): void
    {
        // สร้าง token ใหม่ผ่าน Session แล้วสะท้อนกลับไปยัง legacy
        $token = Session::generateCsrfToken();
        Session::set('csrf_token', $token);
        Session::set('csrf_token_time', time());
    }

    /**
     * ตรวจสอบว่า token หมดอายุหรือไม่
     * จุดประสงค์: ให้แน่ใจว่า token ที่ใช้ในการตรวจสอบคำขอยังไม่หมดอายุ เพื่อป้องกันการโจมตี CSRF ที่ใช้ token เก่า
     * 
     * @return bool คืนค่า true หาก token หมดอายุ, false หากยังไม่หมดอายุ
     */
    private function isTokenExpired(): bool
    {
        if (!Session::has('csrf_token_time')) {
            return true;
        }

        return (time() - (int) Session::get('csrf_token_time')) > self::TOKEN_LIFETIME;
    }

    /**
     * รับ token ที่ส่งมาจากคำขอ
     * จุดประสงค์: ให้สามารถดึง token ที่ผู้ใช้ส่งมาจากคำขอเพื่อใช้ในการตรวจสอบความถูกต้อง
     * 
     * @return string|null คืนค่า token หากมีการส่งมา, null หากไม่มี
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
     * จุดประสงค์: ให้สามารถตรวจสอบได้ว่า token ที่ผู้ใช้ส่งมานั้นตรงกับ token ที่เก็บไว้ในเซสชันหรือไม่ เพื่อป้องกันการโจมตี CSRF
     * 
     * @param string $token token ที่ส่งมาจากคำขอ
     * @return bool คืนค่า true หาก token ถูกต้อง, false หากไม่ถูกต้อง
     */
    private function validateToken(string $token): bool
    {
        // ตรวจสอบกับ token ในเซสชัน
        $sessionToken = Session::getCsrfToken();
        if (is_string($sessionToken) && $sessionToken !== '' && hash_equals($sessionToken, $token)) {
            return true;
        }

        // รองรับ legacy token สำหรับเทมเพลตเก่า
        if (isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token'])) {
            return hash_equals($_SESSION['csrf_token'], $token);
        }

        return false;
    }

    /**
     * รับ CSRF token สำหรับใช้ในฟอร์ม (static method)
     * จุดประสงค์: ให้สามารถดึง token สำหรับใช้ในฟอร์มได้อย่างง่ายดายผ่านฟังก์ชันนี้ โดยจะสร้าง token ใหม่ถ้ายังไม่มี และสะท้อนกลับไปยัง legacy เพื่อรองรับเทมเพลตเก่า
     * 
     * @return string คืนค่า token สำหรับใช้ในฟอร์ม
     */
    public static function getToken(): string
    {
        Session::start();
        $token = Session::getCsrfToken() ?? Session::generateCsrfToken();
        // สะท้อน token กลับไปยัง legacy เพื่อรองรับเทมเพลตเก่า
        Session::set('csrf_token', $token);
        if (!Session::has('csrf_token_time')) {
            Session::set('csrf_token_time', time());
        }
        return $token;
    }

    /**
     * สร้าง HTML input field สำหรับ CSRF token
     * จุดประสงค์: ให้สามารถสร้าง input field สำหรับ CSRF token ได้อย่างง่ายดาย เพื่อใช้ในฟอร์ม
     * 
     * @return string คืนค่า HTML string สำหรับ input field ของ CSRF token
     */
    public static function field(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
