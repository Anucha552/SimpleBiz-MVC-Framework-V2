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
     * แสดงหน้า error
     * 
     * @param int $code รหัส HTTP error
     * @param string $message ข้อความ error (optional)
     */
    public static function show(int $code, string $message = ''): void
    {
        http_response_code($code);
        
        // ตรวจสอบว่าเป็น API request หรือไม่
        $isApiRequest = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') === 0;
        
        if ($isApiRequest) {
            self::showJsonError($code, $message);
        } else {
            self::showHtmlError($code, $message);
        }
        
        exit;
    }

    /**
     * แสดง error แบบ JSON
     */
    private static function showJsonError(int $code, string $message): void
    {
        header('Content-Type: application/json');
        
        $errors = [
            404 => 'ไม่พบข้อมูลที่ต้องการ',
            403 => 'ไม่มีสิทธิ์เข้าถึง',
            500 => 'เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์',
            503 => 'ปิดปรับปรุงระบบชั่วคราว',
        ];
        
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message ?: ($errors[$code] ?? 'เกิดข้อผิดพลาด'),
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * แสดง error แบบ HTML
     */
    private static function showHtmlError(int $code, string $message): void
    {
        $viewPath = __DIR__ . "/../Views/errors/{$code}.php";
        
        // ถ้าไม่มี view สำหรับ error code นี้ ใช้ 500
        if (!file_exists($viewPath)) {
            $viewPath = __DIR__ . '/../Views/errors/500.php';
            $code = 500;
        }
        
        $error = $message;
        require $viewPath;
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
