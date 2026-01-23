<?php
/**
 * Error Handler
 * 
 * จุดประสงค์: จัดการข้อผิดพลาดทั้งหมดในแอปพลิเคชัน
 */

namespace App\Core;

class ErrorHandler
{
    /**
     * Build an error response (HTML for web, JSON for /api/*).
     */
    public static function response(int $code, string $message = ''): Response
    {
        // ตรวจสอบว่าเป็น API request หรือไม่
        $isApiRequest = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') === 0;

        if ($isApiRequest) {
            return self::buildJsonError($code, $message);
        }

        return self::buildHtmlError($code, $message);
    }

    /**
     * แสดงหน้า error
     * 
     * @param int $code รหัส HTTP error
     * @param string $message ข้อความ error (optional)
     */
    public static function show(int $code, string $message = ''): void
    {
        self::response($code, $message)->send();
    }

    /**
     * สร้าง error แบบ JSON
     */
    private static function buildJsonError(int $code, string $message): Response
    {
        $errors = [
            404 => 'ไม่พบข้อมูลที่ต้องการ',
            403 => 'ไม่มีสิทธิ์เข้าถึง',
            405 => 'เมธอดไม่ถูกต้องสำหรับเส้นทางนี้',
            500 => 'เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์',
            503 => 'ปิดปรับปรุงระบบชั่วคราว',
        ];

        $finalMessage = $message ?: ($errors[$code] ?? 'เกิดข้อผิดพลาด');

        return Response::apiError($finalMessage, [], $code);
    }

    /**
     * สร้าง error แบบ HTML
     */
    private static function buildHtmlError(int $code, string $message): Response
    {
        $viewPath = __DIR__ . "/../Views/errors/{$code}.php";
        
        // ถ้าไม่มี view สำหรับ error code นี้ ใช้ 500
        if (!file_exists($viewPath)) {
            $viewPath = __DIR__ . '/../Views/errors/500.php';
            $code = 500;
        }

        $error = $message;
        ob_start();
        require $viewPath;
        $html = ob_get_clean();

        return Response::html($html ?: '', $code);
    }

    /**
     * จัดการ 404 Not Found
     */
    public static function notFound(string $message = ''): void
    {
        self::show(404, $message);
    }

    /**
     * จัดการ 403 Forbidden
     */
    public static function forbidden(string $message = ''): void
    {
        self::show(403, $message);
    }

    /**
     * จัดการ 405 Method Not Allowed
     */
    public static function methodNotAllowed(string $message = ''): void
    {
        self::show(405, $message);
    }

    /**
     * จัดการ 500 Internal Server Error
     */
    public static function serverError(string $message = ''): void
    {
        self::show(500, $message);
    }

    /**
     * จัดการ 503 Service Unavailable
     */
    public static function maintenance(string $message = ''): void
    {
        self::show(503, $message);
    }
}
