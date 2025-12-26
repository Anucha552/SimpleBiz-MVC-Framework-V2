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
     * @return bool True เพื่อดำเนินการต่อ, false เพื่อหยุด
     */
    abstract public function handle(): bool;

    /**
     * ตรวจสอบว่าผู้ใช้ยืนยันตัวตนหรือไม่
     * 
     * @return bool
     */
    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * ดึง ID ของผู้ใช้ปัจจุบัน
     * 
     * @return int|null
     */
    protected function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * ส่งการตอบกลับข้อผิดพลาด JSON และหยุดการทำงาน
     * 
     * @param string $message ข้อความข้อผิดพลาด
     * @param int $statusCode รหัสสถานะ HTTP
     */
    protected function jsonError(string $message, int $statusCode = 401): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message,
        ]);
        exit;
    }

    /**
     * เปลี่ยนเส้นทางไปยัง URL และหยุดการทำงาน
     * 
     * @param string $url URL สำหรับเปลี่ยนเส้นทาง
     */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
}
