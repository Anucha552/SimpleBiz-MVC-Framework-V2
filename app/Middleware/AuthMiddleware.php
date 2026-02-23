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
use App\Core\Response;
use App\Core\Session;
use App\Core\Config;

class AuthMiddleware extends Middleware
{
    /**
     * ตัวบันทึกเหตุการณ์ (Logger) สำหรับบันทึกความพยายามเข้าถึงที่ไม่ได้รับอนุญาตและเหตุการณ์ที่เกี่ยวข้องกับการยืนยันตัวตน
     */
    private Logger $logger;

    /**
     * สร้างอินสแตนซ์ AuthMiddleware ใหม่
     * จุดประสงค์: เตรียมตัวบันทึกเหตุการณ์และเริ่มต้นเซสชันเพื่อใช้ในการตรวจสอบการยืนยันตัวตน
     * 
     */
    public function __construct()
    {
        Session::start();

        $this->logger = new Logger();
    }

    /**
     * จัดการการตรวจสอบการยืนยันตัวตน
     * จุดประสงค์: ตรวจสอบว่า user_id มีอยู่ในเซสชันหรือไม่ และดำเนินการตามนั้น (อนุญาตหรือปฏิเสธคำขอ)
     * 
     * การทำงาน:
     * 1. ตรวจสอบว่าผู้ใช้ยืนยันตัวตนแล้วหรือไม่
     * 2. ถ้าใช่ อนุญาตให้คำขอดำเนินการต่อ
     * 3. ถ้าไม่ เปลี่ยนเส้นทางไปหน้าเข้าสู่ระบบ (web) หรือคืนค่า 401 (API)
     * 
     * ความปลอดภัย:
     * - บันทึกความพยายามเข้าถึงที่ไม่ได้รับอนุญาต
     * - ป้องกันข้อมูลผู้ใช้ที่ละเอียดอ่อน
     * 
     * @return bool|Response True เพื่อดำเนินการต่อ, false เพื่อหยุด, หรือ Response เพื่อส่งกลับทันที
     */
        public function handle(?\App\Core\Request $request = null): bool|Response
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
        $isApiRequest = preg_match('#^/api(/|$)#', $uri);

        if ($isApiRequest) {
            // คำขอ API - คืนค่าข้อผิดพลาด JSON
            return $this->jsonError('Authentication required', 401);
        }

        // คำขอ web - บันทึกเส้นทางที่ผู้ใช้พยายามเข้าถึงเพื่อเปลี่ยนเส้นทางกลับหลังจากเข้าสู่ระบบสำเร็จ
        $path = $uri;
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }
        // บันทึกเส้นทางที่ผู้ใช้พยายามเข้าถึง (ยกเว้นหน้าเข้าสู่ระบบเอง) เพื่อเปลี่ยนเส้นทางกลับหลังจากเข้าสู่ระบบสำเร็จ
        if (is_string($path) && $path !== '' && $path !== '/login') {
            Session::set('auth.intended', $path);
        }

        // คำขอ web - เปลี่ยนเส้นทางไปหน้าเข้าสู่ระบบ
        $redirectTo = (string) Config::get('auth.auth_redirect_to', '/login');
        if ($redirectTo === '') {
            $redirectTo = '/login';
        }

        return $this->redirect($redirectTo);
    }
}
