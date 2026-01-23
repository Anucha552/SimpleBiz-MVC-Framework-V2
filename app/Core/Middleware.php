<?php
/**
 * คลาส MIDDLEWARE พื้นฐาน
 * 
 * จุดประสงค์: จัดเตรียมฟังก์ชัน middleware สำหรับการกรองคำขอ
 * 
 * Middleware คืออะไร?
 * - โค้ดที่ทำงานก่อนที่ตัวควบคุมจะถูกเรียกใช้
 * - ใช้สำหรับ: การยืนยันตัวตน, การอนุญาต, การตรวจสอบ, การบันทึก
 * - สามารถหยุดการประมวลผลคำขอได้หากไม่ตรงตามเงื่อนไข
 * 
 * วิธีการทำงาน:
 * 1. Router เรียกใช้ middleware ก่อนตัวควบคุม
 * 2. เมธอด handle() ของ Middleware ทำงาน
 * 3. ถ้าคืนค่า false การประมวลผลคำขอจะหยุด
 * 4. ถ้าคืนค่า true คำขอจะดำเนินการต่อไปยังตัวควบคุม
 * 
 * กรณีการใช้งาน:
 * - ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่ (AuthMiddleware)
 * - ตรวจสอบ API keys (ApiKeyMiddleware)
 * - การจำกัดอัตรา
 * - การตรวจสอบ CSRF token
 * - การบันทึกคำขอ
 */

namespace App\Core;

abstract class Middleware
{
    /**
     * จัดการคำขอที่เข้ามา
     * 
     * คลาสลูกต้องสร้างเมธอดนี้
     * 
        * @return bool|Response True เพื่อดำเนินการต่อ, false เพื่อหยุด, หรือ Response เพื่อส่งกลับทันที
     */
        abstract public function handle(?Request $request = null): bool|Response;

    /**
     * ตรวจสอบว่าผู้ใช้ยืนยันตัวตนหรือไม่
     * 
     * @return bool
     */
    protected function isAuthenticated(): bool
    {
        Session::start();
        return Session::has('user_id');
    }

    /**
     * ดึง ID ของผู้ใช้ปัจจุบัน
     * 
     * @return int|null
     */
    protected function getUserId(): ?int
    {
        Session::start();
        $id = Session::get('user_id');
        if (is_int($id)) {
            return $id;
        }
        if (is_numeric($id)) {
            return (int) $id;
        }
        return null;
    }

    /**
     * สร้างการตอบกลับข้อผิดพลาด JSON (ไม่ส่ง/ไม่ exit)
     * 
     * @param string $message ข้อความข้อผิดพลาด
     * @param int $statusCode รหัสสถานะ HTTP
     */
    protected function jsonError(string $message, int $statusCode = 401): Response
    {
        return Response::apiError($message, [], $statusCode);
    }

    /**
     * สร้างการตอบกลับแบบ redirect (ไม่ส่ง/ไม่ exit)
     * 
     * @param string $url URL สำหรับเปลี่ยนเส้นทาง
     */
    protected function redirect(string $url): Response
    {
        return Response::redirect($url);
    }
}
