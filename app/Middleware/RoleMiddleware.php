<?php
/**
 * MIDDLEWARE ROLE (บทบาท/สิทธิ์)
 * 
 * จุดประสงค์: ตรวจสอบบทบาทและสิทธิ์ของผู้ใช้
 * 
 * การใช้งาน:
 * ใช้กับเส้นทางที่ต้องการบทบาทเฉพาะ:
 * - /admin/* (เฉพาะ admin)
 * - /dashboard (เฉพาะผู้ใช้ที่เข้าสู่ระบบ)
 * - /products/create (เฉพาะ admin หรือ manager)
 * 
 * บทบาทพื้นฐาน:
 * - guest: ผู้ใช้ที่ไม่ได้เข้าสู่ระบบ
 * - user: ผู้ใช้ทั่วไป
 * - manager: ผู้จัดการ
 * - admin: ผู้ดูแลระบบ
 * 
 * วิธีการทำงาน:
 * 1. ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
 * 2. ดึงบทบาทของผู้ใช้จากฐานข้อมูล
 * 3. ตรวจสอบว่าบทบาทตรงกับที่ต้องการหรือไม่
 * 4. อนุญาตหรือปฏิเสธการเข้าถึง
 * 
 * การตั้งค่า:
 * - ใน User model ควรมี field 'role'
 * - บทบาทสามารถมีลำดับชั้นได้ (admin > manager > user)
 */

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Logger;
use App\Core\Database;
use App\Core\Response;
use App\Core\Auth;
use App\Core\Session;

class RoleMiddleware extends Middleware
{
    /**
     * ตัวบันทึกเหตุการณ์ (Logger) สำหรับบันทึกความพยายามเข้าถึงที่ไม่ได้รับอนุญาตและเหตุการณ์ที่เกี่ยวข้องกับการตรวจสอบบทบาท
     */
    private Logger $logger;
    
    /**
     * บทบาทที่อนุญาต
     */
    private array $allowedRoles = [];

    /**
     * ลำดับชั้นของบทบาท (จากต่ำไปสูง)
     */
    private array $roleHierarchy = [
        'guest' => 0,
        'user' => 1,
        'manager' => 2,
        'admin' => 3,
    ];

    /**
     * สร้างอินสแตนซ์ RoleMiddleware ใหม่
     * จุดประสงค์: เตรียมตัวบันทึกเหตุการณ์และเริ่มต้นเซสชันเพื่อใช้ในการตรวจสอบบทบาท และตั้งค่าบทบาทที่อนุญาตผ่านพารามิเตอร์
     * 
     * @param string|array $roles บทบาทที่อนุญาต
     */
    public function __construct($roles = [])
    {
        // Ensure session via Session wrapper
        Session::start();

        $this->logger = new Logger();

        // ตั้งค่าบทบาทที่อนุญาต
        if (is_string($roles)) {
            $this->allowedRoles = [$roles];
        } elseif (is_array($roles)) {
            $this->allowedRoles = $roles;
        }
    }

    /**
     * จัดการการตรวจสอบบทบาท
     * จุดประสงค์: ตรวจสอบว่าผู้ใช้มีบทบาทที่อนุญาตหรือไม่ และดำเนินการตามนั้น (อนุญาตหรือปฏิเสธคำขอ)
     * 
     * @param \App\Core\Request|null $request คำขอ HTTP ปัจจุบัน (ไม่จำเป็นต้องใช้ในกรณีนี้ แต่สามารถรับได้ถ้าต้องการ)
     * @return bool|Response True เพื่อดำเนินการต่อ, false เพื่อหยุด, หรือ Response เพื่อส่งกลับทันที
     */
        public function handle(?\App\Core\Request $request = null): bool|Response
    {
        // ถ้าไม่ได้กำหนดบทบาท อนุญาตทุกคน
        if (empty($this->allowedRoles)) {
            return true;
        }

        // ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
        if (!$this->isAuthenticated()) {
            $this->logger->security('role.unauthenticated', [
                'route' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'required_roles' => $this->allowedRoles,
            ]);

            return $this->handleUnauthorized();
        }

        // ดึงข้อมูลผู้ใช้
        $userId = $this->getUserId();
        $user = $this->getUserById($userId);
        if (!$user) {
            $this->logger->error('role.user_not_found', [
                'user_id' => $userId,
            ]);

            return $this->handleUnauthorized();
        }

        // ดึงบทบาทของผู้ใช้
        $userRole = $user['role'] ?? 'user';

        // ตรวจสอบบทบาท
        if (!$this->hasRequiredRole($userRole)) {
            $this->logger->security('role.insufficient_permissions', [
                'user_id' => $userId,
                'user_role' => $userRole,
                'required_roles' => $this->allowedRoles,
                'route' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            ]);

            return $this->handleForbidden();
        }

        // บทบาทถูกต้อง
        $this->logger->info('role.access_granted', [
            'user_id' => $userId,
            'user_role' => $userRole,
            'route' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        ]);

        return true;
    }

    /**
     * ตรวจสอบว่าผู้ใช้มีบทบาทที่ต้องการหรือไม่
     * จุดประสงค์: ตรวจสอบว่าบทบาทของผู้ใช้ตรงกับบทบาทที่อนุญาตหรือมีลำดับชั้นสูงกว่า เพื่อให้สามารถกำหนดสิทธิ์การเข้าถึงได้อย่างยืดหยุ่น
     * 
     * @param string $userRole บทบาทของผู้ใช้
     * @return bool ผลลัพธ์การตรวจสอบว่าผู้ใช้มีบทบาทที่ต้องการหรือไม่
     */
    private function hasRequiredRole(string $userRole): bool
    {
        // ตรวจสอบว่าบทบาทตรงกันโดยตรง
        if (in_array($userRole, $this->allowedRoles)) {
            return true;
        }

        // ตรวจสอบตามลำดับชั้น (ถ้าผู้ใช้มีบทบาทสูงกว่า)
        $userLevel = $this->roleHierarchy[$userRole] ?? 0;

        foreach ($this->allowedRoles as $allowedRole) {
            $requiredLevel = $this->roleHierarchy[$allowedRole] ?? 0;
            
            // ถ้าผู้ใช้มีระดับสูงกว่าหรือเท่ากับที่ต้องการ
            if ($userLevel >= $requiredLevel) {
                return true;
            }
        }

        return false;
    }

    /**
     * จัดการกรณียังไม่เข้าสู่ระบบ
     * จุดประสงค์: จัดการการตอบกลับเมื่อผู้ใช้ยังไม่เข้าสู่ระบบ โดยแยกการตอบกลับสำหรับ API และ Web เพื่อให้เหมาะสมกับแต่ละประเภทของคำขอ
     * 
     * @return \App\Core\Response การตอบกลับที่เหมาะสมสำหรับกรณียังไม่เข้าสู่ระบบ
     */
    private function handleUnauthorized(): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $isApiRequest = preg_match('#^/api(/|$)#', $uri);

        if ($isApiRequest) {
            return $this->jsonError('Authentication required', 401);
        }

        // เก็บ URL เดิมเพื่อกลับมาหลังเข้าสู่ระบบ
        Session::set('redirect_after_login', $uri);
        return $this->redirect('/login');
    }

    /**
     * จัดการกรณีไม่มีสิทธิ์
     * จุดประสงค์: จัดการการตอบกลับเมื่อผู้ใช้ไม่มีสิทธิ์เข้าถึงทรัพยากร โดยแยกการตอบกลับสำหรับ API และ Web เพื่อให้เหมาะสมกับแต่ละประเภทของคำขอ
     * 
     * @return \App\Core\Response การตอบกลับที่เหมาะสมสำหรับกรณีไม่มีสิทธิ์
     */
    private function handleForbidden(): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $isApiRequest = preg_match('#^/api(/|$)#', $uri);

        if ($isApiRequest) {
            return $this->jsonError('Insufficient permissions', 403);
        }

        return $this->redirect('/403'); // หน้า Forbidden
    }

    /**
     * ดึงข้อมูลผู้ใช้จาก database
     * จุดประสงค์: ดึงข้อมูลผู้ใช้จากฐานข้อมูลตาม ID ที่ระบุ เพื่อใช้ในการตรวจสอบบทบาทและสิทธิ์ของผู้ใช้
     * 
     * @param int $userId ID ของผู้ใช้ที่ต้องการดึงข้อมูล
     * @return array|null คืนค่าข้อมูลผู้ใช้ในรูปแบบ array หรือ null หากไม่พบผู้ใช้
     */
    private function getUserById(int $userId): ?array
    {
        try {
            $db = Database::getInstance();
            $sql = "SELECT * FROM users WHERE id = :id LIMIT 1";
            $user = $db->fetch($sql, ['id' => $userId]);
            return $user ?: null;
        } catch (\Throwable $e) {
            // In test environments the users table may not exist; treat as not found
            return null;
        }
    }

    /**
     * เพิ่มบทบาทที่อนุญาต
     * จุดประสงค์: เพิ่มบทบาทที่อนุญาตให้เข้าถึงทรัพยากร โดยสามารถเพิ่มได้หลายบทบาทพร้อมกัน เพื่อให้สามารถกำหนดสิทธิ์การเข้าถึงได้อย่างยืดหยุ่น
     * 
     * @param string|array $roles บทบาทที่ต้องการเพิ่ม (สามารถเป็น string เดียวหรือ array ของบทบาท)
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะเพิ่มบทบาทที่อนุญาตในตัวแปร $allowedRoles
     */
    public function addAllowedRoles($roles): void
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $this->allowedRoles = array_merge($this->allowedRoles, $roles);
    }

    /**
     * ตั้งค่าลำดับชั้นของบทบาท
     * จุดประสงค์: ให้สามารถกำหนดลำดับชั้นของบทบาทได้อย่างยืดหยุ่น เพื่อให้การตรวจสอบบทบาทสามารถพิจารณาลำดับชั้นได้ตามที่กำหนด
     * 
     * @param array $hierarchy ลำดับชั้นของบทบาทในรูปแบบ associative array เช่น ['guest' => 0, 'user' => 1, 'manager' => 2, 'admin' => 3]
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะตั้งค่าลำดับชั้นของบทบาทในตัวแปร $roleHierarchy
     */
    public function setRoleHierarchy(array $hierarchy): void
    {
        $this->roleHierarchy = $hierarchy;
    }

    /**
     * ตรวจสอบว่าผู้ใช้มีบทบาทเฉพาะหรือไม่ (static method)
     * จุดประสงค์: ตรวจสอบว่าผู้ใช้มีบทบาทเฉพาะหรือไม่ โดยใช้ ID ของผู้ใช้ที่เข้าสู่ระบบและดึงข้อมูลจากฐานข้อมูลเพื่อเปรียบเทียบบทบาท ซึ่งสามารถใช้ได้ในส่วนอื่นของแอปพลิเคชันที่ต้องการตรวจสอบบทบาทโดยไม่ต้องผ่าน middleware
     * 
     * @param string $role บทบาทที่ต้องการตรวจสอบ
     * @return bool ผลลัพธ์การตรวจสอบว่าผู้ใช้มีบทบาทที่ต้องการหรือไม่
     */
    public static function userHasRole(string $role): bool
    {
        $userId = Auth::id();
        if ($userId === null) {
            return false;
        }
        try {
            $db = Database::getInstance();
            $sql = "SELECT role FROM users WHERE id = :id LIMIT 1";
            $user = $db->fetch($sql, ['id' => $userId]);
            if (!$user) {
                return false;
            }
            return ($user['role'] ?? 'user') === $role;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * ตรวจสอบว่าผู้ใช้เป็น admin หรือไม่ (static method)
     * จุดประสงค์: ตรวจสอบว่าผู้ใช้ที่เข้าสู่ระบบมีบทบาทเป็น admin หรือไม่ โดยใช้เมธอด userHasRole
     * 
     * @return bool ผลลัพธ์การตรวจสอบว่าผู้ใช้เป็น admin หรือไม่
     */
    public static function isAdmin(): bool
    {
        return self::userHasRole('admin');
    }
}
