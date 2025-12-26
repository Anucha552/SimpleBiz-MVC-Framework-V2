<?php
/**
 * MIDDLEWARE การยืนยันตัวตน
 * 
 * จุดประสงค์: ตรวจสอบว่าผู้ใช้เข้าสู่ระบบก่อนเข้าถึงเส้นทางที่ป้องกัน
 * 
 * การใช้งาน:
 * เส้นทางที่ต้องการการยืนยันตัวตนใช้ middleware นี้:
 * - /cart/* (การดำเนินการตะกร้าทั้งหมด)
 * - /orders/* (การดำเนินการคำสั่งซื้อทั้งหมด)
 * - /checkout (กระบวนการชำระเงิน)
 * 
 * วิธีการทำงาน:
 * 1. ตรวจสอบว่า user_id มีอยู่ในเซสชันหรือไม่
 * 2. ถ้าใช่ อนุญาตให้คำขอดำเนินการต่อ
 * 3. ถ้าไม่ เปลี่ยนเส้นทางไปหน้าเข้าสู่ระบบ (web) หรือคืนค่า 401 (API)
 * 
 * ความปลอดภัย:
 * - บันทึกความพยายามเข้าถึงที่ไม่ได้รับอนุญาต
 * - ป้องกันข้อมูลผู้ใช้ที่ละเอียดอ่อน
 */

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Logger;

class AuthMiddleware extends Middleware
{
    private Logger $logger;

    public function __construct()
    {
        // เริ่มเซสชันถ้ายังไม่ได้เริ่ม
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->logger = new Logger();
    }

    /**
     * จัดการการตรวจสอบการยืนยันตัวตน
     * 
     * @return bool True เพื่อดำเนินการต่อ, false เพื่อหยุด
     */
    public function handle(): bool
    {
        // ตรวจสอบว่าผู้ใช้ยืนยันตัวตนแล้วหรือไม่
        if ($this->isAuthenticated()) {
            return true; // ผู้ใช้เข้าสู่ระบบแล้ว ดำเนินการต่อ
        }

        // ผู้ใช้ไม่ได้ยืนยันตัวตน - บันทึกความพยายาม
        $this->logger->security('auth.unauthorized_access', [
            'route' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        ]);

        // กำหนดว่าเป็นคำขอ API หรือคำขอ web
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $isApiRequest = strpos($uri, '/api/') === 0;

        if ($isApiRequest) {
            // คำขอ API - คืนค่าข้อผิดพลาด JSON
            $this->jsonError('Authentication required', 401);
        } else {
            // คำขอ web - เปลี่ยนเส้นทางไปหน้าเข้าสู่ระบบ
            $this->redirect('/login');
        }

        return false; // หยุดการประมวลผลคำขอ
    }
}
