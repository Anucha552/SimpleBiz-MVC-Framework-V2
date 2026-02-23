<?php
/**
 * MIDDLEWARE GUEST (แขก/ผู้ไม่ได้เข้าสู่ระบบ)
 * 
 * จุดประสงค์: ป้องกันผู้ที่เข้าสู่ระบบแล้วเข้าถึงหน้าที่เฉพาะแขก
 * 
 * การใช้งาน:
 * ใช้กับเส้นทางที่ผู้ใช้ที่เข้าสู่ระบบแล้วไม่ควรเข้าถึง:
 * - /login (หน้าเข้าสู่ระบบ)
 * - /register (หน้าสมัครสมาชิก)
 * - /forgot-password (หน้าลืมรหัสผ่าน)
 * 
 * วิธีการทำงาน:
 * 1. ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
 * 2. ถ้าเข้าสู่ระบบแล้ว เปลี่ยนเส้นทางไปหน้าหลัก/dashboard
 * 3. ถ้ายังไม่เข้าสู่ระบบ อนุญาตให้เข้าถึงหน้านั้น
 * 
 * ตัวอย่าง:
 * - ผู้ใช้ที่ล็อกอินแล้วพยายามเข้า /login จะถูกส่งไป /dashboard
 * - ผู้ใช้ที่ยังไม่ล็อกอินสามารถเข้า /login ได้ปกติ
 */

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Logger;
use App\Core\Response;
use App\Core\Session;
use App\Core\Config;

class GuestMiddleware extends Middleware
{
    /**
     * ตัวบันทึกเหตุการณ์ (Logger) สำหรับบันทึกความพยายามเข้าถึงที่ไม่ได้รับอนุญาตและเหตุการณ์ที่เกี่ยวข้องกับการตรวจสอบสถานะแขก
     */
    private Logger $logger;
    
    /**
     * หน้าที่จะเปลี่ยนเส้นทางไปเมื่อผู้ใช้เข้าสู่ระบบแล้ว
     */
    private string $redirectTo = '/';

    /**
     * สร้างอินสแตนซ์ GuestMiddleware ใหม่
     * จุดประสงค์: เตรียมตัวบันทึกเหตุการณ์และเริ่มต้นเซสชันเพื่อใช้ในการตรวจสอบสถานะแขก และกำหนดเส้นทางเปลี่ยนเส้นทางจาก config ถ้ามี
     * 
     * @param string|null $redirectTo เส้นทางสำหรับเปลี่ยนเส้นทางเมื่อผู้ใช้เข้าสู่ระบบแล้ว (ถ้าไม่กำหนดจะใช้ค่าเริ่มต้น)
     */
    public function __construct(?string $redirectTo = null)
    {
        Session::start();

        $this->logger = new Logger();

        // ใช้ redirect path ที่กำหนด
        if ($redirectTo !== null) {
            $this->redirectTo = $redirectTo;
        }

        // โหลด redirect path จาก config ถ้ามี
        $configRedirect = (string) Config::get('auth.guest_redirect_to', '');
        if ($configRedirect) {
            $this->redirectTo = $configRedirect;
        }
    }

    /**
     * จัดการการตรวจสอบสถานะแขก
     * จุดประสงค์: ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่ และดำเนินการตามนั้น (อนุญาตหรือปฏิเสธคำขอ)
     * 
     * @param \App\Core\Request|null $request คำขอ HTTP ปัจจุบัน (ไม่จำเป็นต้องใช้ในกรณีนี้ แต่สามารถรับได้ถ้าต้องการ)
     * @return bool|Response คืนค่า true เพื่ออนุญาตให้ดำเนินการต่อ, false เพื่อหยุดการดำเนินการ, หรือ Response เพื่อส่งกลับทันที
     */
        public function handle(?\App\Core\Request $request = null): bool|Response
    {
        // ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
        if ($this->isAuthenticated()) {
            // ผู้ใช้เข้าสู่ระบบแล้ว - บันทึกและเปลี่ยนเส้นทาง
            $this->logger->info('guest.authenticated_redirect', [
                'user_id' => $this->getUserId(),
                'from' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'to' => $this->redirectTo,
            ]);

            // กำหนดว่าเป็นคำขอ API หรือไม่
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $isApiRequest = preg_match('#^/api(/|$)#', $uri);

            if ($isApiRequest) {
                // คำขอ API - คืนค่าข้อความ
                return $this->jsonError('Already authenticated', 403);
            }

            // คำขอ web - เปลี่ยนเส้นทาง
            return $this->redirect($this->redirectTo);
        }

        // ผู้ใช้ยังไม่เข้าสู่ระบบ - อนุญาตให้เข้าถึง
        return true;
    }

    /**
     * ตั้งค่าเส้นทางสำหรับเปลี่ยนเส้นทาง
     * จุดประสงค์: ให้สามารถกำหนดเส้นทางที่ต้องการเปลี่ยนเส้นทางไปเมื่อผู้ใช้เข้าสู่ระบบแล้วได้อย่างง่ายดายผ่านฟังก์ชันนี้
     * 
     * @param string $path เส้นทางสำหรับเปลี่ยนเส้นทางเมื่อผู้ใช้เข้าสู่ระบบแล้ว
     */
    public function setRedirectTo(string $path): void
    {
        $this->redirectTo = $path;
    }

    /**
     * รับเส้นทางสำหรับเปลี่ยนเส้นทาง
     * จุดประสงค์: ให้สามารถดึงเส้นทางที่กำหนดไว้สำหรับการเปลี่ยนเส้นทางเมื่อผู้ใช้เข้าสู่ระบบแล้วได้อย่างง่ายดาย
     * 
     * @return string เส้นทางสำหรับเปลี่ยนเส้นทางเมื่อผู้ใช้เข้าสู่ระบบแล้ว
     */
    public function getRedirectTo(): string
    {
        return $this->redirectTo;
    }
}
