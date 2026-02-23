<?php
/**
 * คลาส Middleware พื้นฐาน สำหรับการกรองคำขอ
 * 
 * จุดประสงค์: จัดเตรียมฟังก์ชัน middleware สำหรับการกรองคำขอ
 * Middleware ควรใช้กับอะไร: เมื่อคุณต้องการสร้าง middleware เพื่อจัดการคำขอก่อนถึงตัวควบคุม
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
 * 
 * ตัวอย่างการใช้งานโดยรวม:
 * จะสร้างคลาส middleware ที่ขยายจากโฟลเดอร์ Middleware:
 * ```php
 * class AuthMiddleware extends Middleware {
 *     public function handle(?Request $request = null): bool {
 *         if (! $this->isAuthenticated()) {
 *             return false; // หยุดคำขอถ้าไม่ผ่านการยืนยันตัวตน
 *         }
 *         return true; // ดำเนินการต่อถ้าผ่าน
 *     }
 * }
 * ```
 */

namespace App\Core;

abstract class Middleware
{
    /**
     * จัดการคำขอที่เข้ามา
     * จุดประสงค์: เมธอดหลักที่ใช้ในการกรองคำขอ
     * handle() ควรใช้กับอะไร: เมื่อคุณต้องการกรองคำขอก่อนถึงตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * public function handle(?Request $request = null): bool|Response {
     *     if (! $this->isAuthenticated()) {
     *         return $this->jsonError('Unauthorized', 401); // ส่งกลับข้อผิดพลาดถ้าไม่ผ่านการยืนยันตัวตน
     *     }
     *     return true; // ดำเนินการต่อถ้าผ่าน
     * }
     * ```
     * 
     * @return bool|Response True เพื่อดำเนินการต่อ, false เพื่อหยุด, หรือ Response เพื่อส่งกลับทันที
     */
        abstract public function handle(?Request $request = null): bool|Response;

    /**
     * ตรวจสอบว่าผู้ใช้ยืนยันตัวตนหรือไม่
     * จุดประสงค์: ตรวจสอบสถานะการยืนยันตัวตนของผู้ใช้
     * isAuthenticated() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าผู้ใช้ได้ยืนยันตัวตนแล้วหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * if ($this->isAuthenticated()) {
     *     // ผู้ใช้ยืนยันตัวตนแล้ว
     * } else {
     *    // ผู้ใช้ยังไม่ได้ยืนยันตัวตน
     * }
     * ```
     * 
     * @return bool
     */
    protected function isAuthenticated(): bool
    {
        // Delegate to Auth to ensure consistent session key handling
        return Auth::check();
    }

    /**
     * ดึง ID ของผู้ใช้ปัจจุบัน
     * จุดประสงค์: รับ ID ของผู้ใช้ที่ยืนยันตัวตน
     * getUserId() ควรใช้กับอะไร: เมื่อคุณต้องการรับ ID ของผู้ใช้ที่ยืนยันตัวตนในระบบ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $userId = $this->getUserId();
     * ```
     * 
     * @return int|null คืนค่า ID ของผู้ใช้ที่ยืนยันตัวตน หรือ null ถ้าไม่ได้ยืนยันตัวตน
     */
    protected function getUserId(): ?int
    {
        // ใช้ Auth เพื่อดึง ID ของผู้ใช้ที่ยืนยันตัวตน
        return Auth::id();
    }

    /**
     * สร้างการตอบกลับข้อผิดพลาด JSON (ไม่ส่ง/ไม่ exit)
     * จุดประสงค์: สร้างการตอบกลับข้อผิดพลาดในรูปแบบ JSON สำหรับ API
     * jsonError() ควรใช้กับอะไร: เมื่อคุณต้องการส่งกลับข้อผิดพลาดในรูปแบบ JSON
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->jsonError('Unauthorized', 401);
     * ```
     * 
     * @param string $message กำหนดข้อความข้อผิดพลาด
     * @param int $statusCode กำหนดรหัสสถานะ HTTP เช่น 401, 403
     * @return Response คืนค่า Response ข้อผิดพลาดในรูปแบบ JSON
     */
    protected function jsonError(string $message, int $statusCode = 401): Response
    {
        return Response::apiError($message, [], $statusCode);
    }

    /**
     * สร้างการตอบกลับแบบ redirect (ไม่ส่ง/ไม่ exit)
     * จุดประสงค์: สร้างการตอบกลับ HTTP redirect
     * redirect() ควรใช้กับอะไร: เมื่อคุณต้องการเปลี่ยนเส้นทางผู้ใช้ไปยัง URL อื่น
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->redirect('/login');
     * ```
     * 
     * @param string $url กำหนด URL สำหรับเปลี่ยนเส้นทาง
     * @return Response คืนค่า Response การเปลี่ยนเส้นทาง
     */
    protected function redirect(string $url): Response
    {
        return Response::redirect($url);
    }

    /**
     * รับ IP ของ client โดยเชื่อ X-Forwarded-For เฉพาะ proxy ที่ไว้ใจได้
     * จุดประสงค์: รับ IP ของ client โดยเชื่อ X-Forwarded-For เฉพาะ proxy ที่ไว้ใจได้
     * getClientIp() ควรใช้กับอะไร: เมื่อคุณต้องการรับ IP ของ client โดยเชื่อ X-Forwarded-For เฉพาะ proxy ที่ไว้ใจได้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $clientIp = $this->getClientIp();
     * ```
     * 
     * @return string คืนค่า IP ของ client หรือ 'unknown' หากไม่สามารถระบุได้
     */
    protected function getClientIp(): string
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $trustedProxies = (array) Config::get('app.trusted_proxies', []);

        $isTrustedProxy = in_array($remoteAddr, $trustedProxies, true);

        if ($isTrustedProxy) {
            $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
            if (is_string($forwarded) && $forwarded !== '') {
                $parts = array_map('trim', explode(',', $forwarded));
                if (!empty($parts[0])) {
                    return $parts[0];
                }
            }

            $realIp = $_SERVER['HTTP_X_REAL_IP'] ?? '';
            if (is_string($realIp) && $realIp !== '') {
                return trim($realIp);
            }
        }

        return is_string($remoteAddr) && $remoteAddr !== '' ? $remoteAddr : 'unknown';
    }
}
