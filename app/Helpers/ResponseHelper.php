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

use App\Core\Response;

class ResponseHelper
{
    /**
     * ส่งการตอบกลับ JSON สำเร็จ
     * จุดประสงค์: ใช้เพื่อส่งการตอบกลับ JSON ที่แสดงถึงความสำเร็จของคำขอ API
     * ตัวอย่างการใช้งาน:
     * ```php
     * ResponseHelper::success($data, 'Data retrieved successfully', ['page' => 1]);
     * ```
     * 
     * ผลลัพธ์:
     * {
     *   "success": true,
     *   "data": {...},
     *   "message": "Data retrieved successfully",
     *   "meta": {
     *     "page": 1
     *   }
     * }
     * 
     * returns Response การตอบกลับ JSON สำเร็จ
     * 
     * @param mixed $data ข้อมูลการตอบกลับ
     * @param string $message ข้อความสำเร็จ
     * @param array $meta ข้อมูลเมตา (ไม่บังคับ) เช่น pagination
     * @param int $statusCode รหัสสถานะ HTTP
     */
    public static function success($data = null, string $message = 'Success', array $meta = [], int $statusCode = 200): Response
    {
        return Response::apiSuccess($data, $message, $meta, $statusCode);
    }

    /**
     * ส่งการตอบกลับ JSON ข้อผิดพลาด
     * จุดประสงค์: ใช้เพื่อส่งการตอบกลับ JSON ที่แสดงถึงความล้มเหลวของคำขอ API
     * ตัวอย่างการใช้งาน:
     * ```php
     * ResponseHelper::error('Validation failed', ['email' => 'Invalid email address'], 422);
     * ```
     * 
     * ผมลลัพธ์:
     * {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "email": "Invalid email address"
     *   }
     * }
     * 
     * returns Response การตอบกลับ JSON ข้อผิดพลาด
     * 
     * @param string $message ข้อความข้อผิดพลาด
     * @param array $errors อาร์เรย์รายละเอียดข้อผิดพลาด
     * @param int $statusCode รหัสสถานะ HTTP
     */
    public static function error(string $message, array $errors = [], int $statusCode = 400): Response
    {
        return Response::apiError($message, $errors, $statusCode);
    }

    /**
     * ส่งการตอบกลับสร้างสำเร็จ (201)
     * จุดประสงค์: ใช้เพื่อส่งการตอบกลับ JSON ที่แสดงถึงการสร้างทรัพยากรใหม่สำเร็จ
     * ตัวอย่างการใช้งาน:
     * ```php
     * ResponseHelper::created($newResourceData, 'Resource created successfully');
     * ```
     * 
     * ผลลัพธ์:
     * {
     *   "success": true,
     *   "data": {...},
     *   "message": "Resource created successfully"
     * }
     * 
     * returns Response การตอบกลับ JSON สร้างสำเร็จ
     * 
     * @param mixed $data ข้อมูลทรัพยากรที่สร้างขึ้น
     * @param string $message ข้อความสำเร็จ
     */
    public static function created($data, string $message = 'Resource created'): Response
    {
        return self::success($data, $message, [], 201);
    }

    /**
     * ส่งการตอบกลับไม่มีเนื้อหา (204)
     * จุดประสงค์: ใช้เพื่อส่งการตอบกลับที่ไม่มีเนื้อหาเมื่อคำขอสำเร็จแต่ไม่มีข้อมูลที่จะส่งกลับ
     * ตัวอย่างการใช้งาน:
     * ```php
     * ResponseHelper::noContent();
     * ```
     * 
     * ผลลัพธ์: การตอบกลับ HTTP 204 ไม่มีเนื้อหา
     * 
     * returns Response การตอบกลับไม่มีเนื้อหา
     */
    public static function noContent(): Response
    {
        return Response::noContent();
    }

    /**
     * ส่งการตอบกลับไม่พบทรัพยากร (404)
     * จุดประสงค์: ใช้เพื่อส่งการตอบกลับ JSON ที่แสดงถึงการไม่พบทรัพยากรที่ร้องขอ
     * ตัวอย่างการใช้งาน:
     * ```php
     * ResponseHelper::notFound('Resource not found');
     * ```
     * 
     * ผลลัพธ์:
     * {
     *   "success": false,
     *   "message": "Resource not found"
     * }
     * 
     * ผลลัพธ์: การตอบกลับ JSON ไม่พบทรัพยากร
     * 
     * returns Response การตอบกลับ JSON ไม่พบทรัพยากร
     * 
     * @param string $message ข้อความข้อผิดพลาด
     */
    public static function notFound(string $message = 'Resource not found'): Response
    {
        return self::error($message, [], 404);
    }

    /**
     * ส่งการตอบกลับไม่ได้รับอนุญาต (401)
     * จุดประสงค์: ใช้เพื่อส่งการตอบกลับ JSON ที่แสดงถึงการไม่ได้รับอนุญาต
     * ตัวอย่างการใช้งาน:
     * ```php
     * ResponseHelper::unauthorized('Authentication required');
     * ```
     * 
     * ผลลัพธ์:
     * {
     *   "success": false,
     *   "message": "Authentication required"
     * }
     * 
     * ผลลัพธ์: การตอบกลับ JSON ไม่ได้รับอนุญาต
     * 
     * returns Response การตอบกลับ JSON ไม่ได้รับอนุญาต
     * 
     * @param string $message ข้อความข้อผิดพลาด
     */
    public static function unauthorized(string $message = 'Authentication required'): Response
    {
        return self::error($message, [], 401);
    }

    /**
     * ส่งการตอบกลับถูกห้าม (403)
     * จุดประสงค์: ใช้เพื่อส่งการตอบกลับ JSON ที่แสดงถึงการถูกห้ามเข้าถึงทรัพยากร
     * ตัวอย่างการใช้งาน:
     * ```php
     * ResponseHelper::forbidden('Access denied');
     * ```
     * ผลลัพธ์:
     * {
     *   "success": false,
     *   "message": "Access denied"
     * }
     * 
     * ผลลัพธ์: การตอบกลับ JSON ถูกห้าม
     * 
     * @param string $message ข้อความข้อผิดพลาด
     */
    public static function forbidden(string $message = 'Access denied'): Response
    {
        return self::error($message, [], 403);
    }

    /**
     * ส่งการตอบกลับข้อผิดพลาดการตรวจสอบ (422)
     * จุดประสงค์: ใช้เพื่อส่งการตอบกลับ JSON ที่แสดงถึงข้อผิดพลาดการตรวจสอบข้อมูลที่ผู้ใช้ป้อน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $errors = ['email' => 'Invalid email address'];
     * ResponseHelper::validationError($errors, 'Validation failed');
     * ```
     * 
     * ผลลัพธ์:
     * {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "email": "Invalid email address"
     *   }
     * }
     * 
     * returns Response การตอบกลับ JSON ข้อผิดพลาดการตรวจสอบ
     * 
     * @param array $errors ข้อผิดพลาดการตรวจสอบ
     * @param string $message ข้อความข้อผิดพลาด
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): Response
    {
        return self::error($message, $errors, 422);
    }

    /**
     * ส่งการตอบกลับข้อผิดพลาดเซิร์ฟเวอร์ภายใน (500)
     * จุดประสงค์: ใช้เพื่อส่งการตอบกลับ JSON ที่แสดงถึงข้อผิดพลาดภายในเซิร์ฟเวอร์
     * ตัวอย่างการใช้งาน:
     * ```php
     * ResponseHelper::serverError('Internal server error');
     * ```
     * 
     * ผลลัพธ์:
     * {
     *   "success": false,
     *   "message": "Internal server error"
     * }
     * 
     * returns Response การตอบกลับ JSON ข้อผิดพลาดเซิร์ฟเวอร์ภายใน
     * 
     * @param string $message ข้อความข้อผิดพลาด
     */
    public static function serverError(string $message = 'Internal server error'): Response
    {
        return self::error($message, [], 500);
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
    // send() ถูกแทนที่ด้วย Response::apiSuccess/apiError เพื่อให้มาตรฐานเดียวกันทั้งระบบ

    /**
     * ส่งการตอบกลับแบบแบ่งหน้า
     * จุดประสงค์: ใช้เพื่อส่งการตอบกลับ JSON ที่มีข้อมูลแบบแบ่งหน้า (pagination)
     * ตัวอย่างการใช้งาน:
     * ```php
     * $data = [...]; // ข้อมูลรายการ
     * ResponseHelper::paginated($data, $page, $perPage, $total, 'Data retrieved');
     * ```
     * 
     * ผลลัพธ์:
     * {
     *   "success": true,
     *   "data": [...],
     *   "message": "Data retrieved",
     *   "meta": {
     *     "pagination": {
     *       "current_page": 1,
     *       "per_page": 10,
     *       "total": 50,
     *      "total_pages": 5,
     *      "has_more": true
     *    }
     *  }
     * }
     * 
     * returns Response การตอบกลับ JSON แบบแบ่งหน้า
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
    ): Response {
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

        return self::success($data, $message, $meta);
    }
}
