<?php
/**
 * คราส ErrorHandler สำหรับจัดการข้อผิดพลาด
 * 
 * จุดประสงค์: จัดการข้อผิดพลาดทั้งหมดในแอปพลิเคชัน
 * ErrorHandler ควรใช้กับอะไร: เมื่อคุณต้องการสร้างการตอบกลับข้อผิดพลาดในรูปแบบ HTML หรือ JSON ขึ้นอยู่กับประเภทคำขอ
 * 
 * ฟีเจอร์หลัก:
 * - สร้างการตอบกลับข้อผิดพลาดในรูปแบบ HTML สำหรับเว็บ
 * - สร้างการตอบกลับข้อผิดพลาดในรูปแบบ JSON สำหรับ API
 * - รองรับรหัสข้อผิดพลาดทั่วไป: 404, 403, 405, 500, 503
 * - แสดงหน้าข้อผิดพลาดที่กำหนดเองหากมี
 * 
 * ตัวอย่างการใช้งานโดยรวม:
 * ```php
 * return ErrorHandler::response(404, 'ไม่พบหน้า');
 * ```
 */

namespace App\Core;

class ErrorHandler
{
    /**
     * สร้างการตอบกลับข้อผิดพลาด
     * จุดประสงค์: สร้างการตอบกลับข้อผิดพลาดในรูปแบบ HTML หรือ JSON ขึ้นอยู่กับประเภทคำขอ
     * response() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างการตอบกลับข้อผิดพลาดในตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return ErrorHandler::response(404, 'ไม่พบหน้า');
     * ```
     * 
     * @param int $code กำหนดรหัส HTTP error เช่น 404, 500
     * @param string $message กำหนดข้อความ error (optional)
     * @return Response คืนค่า Response ข้อผิดพลาดในรูปแบบ HTML หรือ JSON
     */
    public static function response(int $code, string $message = ''): Response
    {
        // ตรวจสอบว่าเป็น API request หรือไม่
        $isApiRequest = preg_match('#^/api(/|$)#', $_SERVER['REQUEST_URI'] ?? '');

        if ($isApiRequest) {
            return self::buildJsonError($code, $message);
        }

        return self::buildHtmlError($code, $message);
    }

    /**
     * แสดงหน้า error
     * จุดประสงค์: ส่งการตอบกลับข้อผิดพลาดไปยังเบราว์เซอร์และหยุดการทำงานของสคริปต์
     * show() ควรใช้กับอะไร: เมื่อคุณต้องการแสดงหน้าข้อผิดพลาดและหยุดการทำงานของสคริปต์
     * ตัวอย่างการใช้งาน:
     * ```php
     * ErrorHandler::show(404, 'ไม่พบหน้า');
     * ```
     * 
     * @param int $code กำหนดรหัส HTTP error เช่น 404, 500
     * @param string $message กำหนดข้อความ error (optional)
     * @return void ไม่คืนค่าอะไร
     */
    public static function show(int $code, string $message = ''): void
    {
        self::response($code, $message)->send();
    }

    /**
     * สร้าง error แบบ JSON
     * จุดประสงค์: สร้างการตอบกลับข้อผิดพลาดในรูปแบบ JSON สำหรับ API
     * สามารถสร้างเพิ่มเองได้ตามรหัสข้อผิดพลาดที่ต้องการ
     * buildJsonError() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างการตอบกลับข้อผิดพลาดในรูปแบบ JSON
     * ตัวอย่างการใช้งาน:
     * ```php
     * return ErrorHandler::buildJsonError(404, 'ไม่พบข้อมูล');
     * ```
     * 
     * @param int $code กำหนดรหัส HTTP error เช่น 404, 500
     * @param string $message กำหนดข้อความ error (optional)
     * @return Response คืนค่า Response ข้อผิดพลาดในรูปแบบ JSON
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
     * จุดประสงค์: สร้างการตอบกลับข้อผิดพลาดในรูปแบบ HTML สำหรับเว็บ
     * buildHtmlError() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างการตอบกลับข้อผิดพลาดในรูปแบบ HTML
     * ตัวอย่างการใช้งาน:
     * ```php
     * return ErrorHandler::buildHtmlError(404, 'ไม่พบหน้า');
     * ```
     * 
     * @param int $code กำหนดรหัส HTTP error เช่น 404, 500
     * @param string $message กำหนดข้อความ error (optional)
     * @return Response คืนค่า Response ข้อผิดพลาดในรูปแบบ HTML
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
     * จุดประสงค์: แสดงหน้าข้อผิดพลาด 404 Not Found
     * notFound() ควรใช้กับอะไร: เมื่อคุณต้องการแสดงหน้าข้อผิดพลาด 404 Not Found
     * ตัวอย่างการใช้งาน:
     * ```php
     * ErrorHandler::notFound('ไม่พบหน้า');
     * ```
     * @param string $message กำหนดข้อความ error (optional)
     * @return void ไม่คืนค่าอะไร
     */
    public static function notFound(string $message = ''): void
    {
        self::show(404, $message);
    }

    /**
     * จัดการ 403 Forbidden
     * จุดประสงค์: แสดงหน้าข้อผิดพลาด 403 Forbidden
     * forbidden() ควรใช้กับอะไร: เมื่อคุณต้องการแสดงหน้าข้อผิดพลาด 403 Forbidden
     * ตัวอย่างการใช้งาน:
     * ```php
     * ErrorHandler::forbidden('ไม่มีสิทธิ์เข้าถึง');
     * ```
     * 
     * @param string $message กำหนดข้อความ error (optional)
     * @return void ไม่คืนค่าอะไร
     */
    public static function forbidden(string $message = ''): void
    {
        self::show(403, $message);
    }

    /**
     * จัดการ 405 Method Not Allowed
     * จุดประสงค์: แสดงหน้าข้อผิดพลาด 405 Method Not Allowed
     * methodNotAllowed() ควรใช้กับอะไร: เมื่อคุณต้องการแสดงหน้าข้อผิดพลาด 405 Method Not Allowed
     * ตัวอย่างการใช้งาน:
     * ```php
     * ErrorHandler::methodNotAllowed('เมธอดไม่ถูกต้อง');
     * ```
     * 
     * @param string $message กำหนดข้อความ error (optional)
     * @return void ไม่คืนค่าอะไร
     */
    public static function methodNotAllowed(string $message = ''): void
    {
        self::show(405, $message);
    }

    /**
     * จัดการ 500 Internal Server Error
     * จุดประสงค์: แสดงหน้าข้อผิดพลาด 500 Internal Server Error
     * serverError() ควรใช้กับอะไร: เมื่อคุณต้องการแสดงหน้าข้อผิดพลาด 500 Internal Server Error
     * ตัวอย่างการใช้งาน:
     * ```php
     * ErrorHandler::serverError('เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์');
     * ```
     * 
     * @param string $message กำหนดข้อความ error (optional)
     * @return void ไม่คืนค่าอะไร
     */
    public static function serverError(string $message = ''): void
    {
        self::show(500, $message);
    }

    /**
     * จัดการ 503 Service Unavailable
     * จุดประสงค์: แสดงหน้าข้อผิดพลาด 503 Service Unavailable
     * maintenance() ควรใช้กับอะไร: เมื่อคุณต้องการแสดงหน้าข้อผิดพลาด 503 Service Unavailable
     * ตัวอย่างการใช้งาน:
     * ```php
     * ErrorHandler::maintenance('ปิดปรับปรุงระบบชั่วคราว');
     * ```
     * 
     * @param string $message กำหนดข้อความ error (optional)
     * @return void ไม่คืนค่าอะไร
     */
    public static function maintenance(string $message = ''): void
    {
        self::show(503, $message);
    }
}
