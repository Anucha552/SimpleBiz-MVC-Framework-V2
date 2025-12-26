<?php
/**
 * คลาสช่วยเหลือการตอบกลับ
 * 
 * จุดประสงค์: จัดรูปแบบการตอบกลับ JSON มาตรฐานสำหรับ API endpoints
 * 
 * ประโยชน์:
 * - โครงสร้างการตอบกลับ API ที่สม่ำเสมอ
 * - ง่ายต่อการดูแลรักษา
 * - การสร้างการตอบกลับที่ปลอดภัยเรื่องประเภทข้อมูล
 * - รหัสสถานะ HTTP ที่เหมาะสม
 * 
 * รูปแบบการตอบกลับมาตรฐาน:
 * {
 *   "success": true|false,
 *   "data": {...},
 *   "message": "...",
 *   "errors": [...],
 *   "meta": {...}
 * }
 * 
 * การใช้งาน:
 * ResponseHelper::success($data, 'Operation successful');
 * ResponseHelper::error('Validation failed', $errors, 400);
 */

namespace App\Helpers;

class ResponseHelper
{
    /**
     * ส่งการตอบกลับ JSON สำเร็จ
     * 
     * @param mixed $data ข้อมูลการตอบกลับ
     * @param string $message ข้อความสำเร็จ
     * @param array $meta ข้อมูลเมตา (ไม่บังคับ) เช่น pagination
     * @param int $statusCode รหัสสถานะ HTTP
     */
    public static function success($data = null, string $message = 'Success', array $meta = [], int $statusCode = 200): void
    {
        self::send(true, $data, $message, [], $meta, $statusCode);
    }

    /**
     * ส่งการตอบกลับ JSON ข้อผิดพลาด
     * 
     * @param string $message ข้อความข้อผิดพลาด
     * @param array $errors อาร์เรย์รายละเอียดข้อผิดพลาด
     * @param int $statusCode รหัสสถานะ HTTP
     */
    public static function error(string $message, array $errors = [], int $statusCode = 400): void
    {
        self::send(false, null, $message, $errors, [], $statusCode);
    }

    /**
     * ส่งการตอบกลับสร้างสำเร็จ (201)
     * 
     * @param mixed $data ข้อมูลทรัพยากรที่สร้างขึ้น
     * @param string $message ข้อความสำเร็จ
     */
    public static function created($data, string $message = 'Resource created'): void
    {
        self::success($data, $message, [], 201);
    }

    /**
     * ส่งการตอบกลับไม่มีเนื้อหา (204)
     */
    public static function noContent(): void
    {
        http_response_code(204);
        exit;
    }

    /**
     * ส่งการตอบกลับไม่พบทรัพยากร (404)
     * 
     * @param string $message ข้อความข้อผิดพลาด
     */
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error($message, [], 404);
    }

    /**
     * ส่งการตอบกลับไม่ได้รับอนุญาต (401)
     * 
     * @param string $message ข้อความข้อผิดพลาด
     */
    public static function unauthorized(string $message = 'Authentication required'): void
    {
        self::error($message, [], 401);
    }

    /**
     * ส่งการตอบกลับถูกห้าม (403)
     * 
     * @param string $message ข้อความข้อผิดพลาด
     */
    public static function forbidden(string $message = 'Access denied'): void
    {
        self::error($message, [], 403);
    }

    /**
     * ส่งการตอบกลับข้อผิดพลาดการตรวจสอบ (422)
     * 
     * @param array $errors ข้อผิดพลาดการตรวจสอบ
     * @param string $message ข้อความข้อผิดพลาด
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): void
    {
        self::error($message, $errors, 422);
    }

    /**
     * ส่งการตอบกลับข้อผิดพลาดเซิร์ฟเวอร์ภายใน (500)
     * 
     * @param string $message ข้อความข้อผิดพลาด
     */
    public static function serverError(string $message = 'Internal server error'): void
    {
        self::error($message, [], 500);
    }

    /**
     * ส่งการตอบกลับ JSON
     * 
     * @param bool $success สถานะความสำเร็จ
     * @param mixed $data ข้อมูลการตอบกลับ
     * @param string $message ข้อความ
     * @param array $errors รายละเอียดข้อผิดพลาด
     * @param array $meta ข้อมูลเมตา
     * @param int $statusCode รหัสสถานะ HTTP
     */
    private static function send(
        bool $success,
        $data,
        string $message,
        array $errors,
        array $meta,
        int $statusCode
    ): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        $response = [
            'success' => $success,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * ส่งการตอบกลับแบบแบ่งหน้า
     * 
     * @param array $data อาร์เรย์ข้อมูล
     * @param int $page หน้าปัจจุบัน
     * @param int $perPage จำนวนรายการต่อหน้า
     * @param int $total จำนวนรายการทั้งหมด
     * @param string $message ข้อความสำเร็จ
     */
    public static function paginated(
        array $data,
        int $page,
        int $perPage,
        int $total,
        string $message = 'Data retrieved'
    ): void {
        $totalPages = ceil($total / $perPage);

        $meta = [
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages,
            ],
        ];

        self::success($data, $message, $meta);
    }
}
