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
use App\Models\User;

class RoleMiddleware extends Middleware
{
    private Logger $logger;
    private User $userModel;
    
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
     * Constructor
     * 
     * @param string|array $roles บทบาทที่อนุญาต
     */
    public function __construct($roles = [])
    {
        // เริ่มเซสชันถ้ายังไม่ได้เริ่ม
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->logger = new Logger();
        $this->userModel = new User();

        // ตั้งค่าบทบาทที่อนุญาต
        if (is_string($roles)) {
            $this->allowedRoles = [$roles];
        } elseif (is_array($roles)) {
            $this->allowedRoles = $roles;
        }
    }

    /**
     * จัดการการตรวจสอบบทบาท
     * 
     * @return bool True เพื่อดำเนินการต่อ, false เพื่อหยุด
     */
    public function handle(): bool
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

            $this->handleUnauthorized();
            return false;
        }

        // ดึงข้อมูลผู้ใช้
        $userId = $this->getUserId();
        $user = $this->userModel->findById($userId);

        if (!$user) {
            $this->logger->error('role.user_not_found', [
                'user_id' => $userId,
            ]);

            $this->handleUnauthorized();
            return false;
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

            $this->handleForbidden();
            return false;
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
     * 
     * @param string $userRole
     * @return bool
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
     */
    private function handleUnauthorized(): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $isApiRequest = strpos($uri, '/api/') === 0;

        if ($isApiRequest) {
            $this->jsonError('Authentication required', 401);
        } else {
            // เก็บ URL เดิมเพื่อกลับมาหลังเข้าสู่ระบบ
            $_SESSION['redirect_after_login'] = $uri;
            $this->redirect('/login');
        }
    }

    /**
     * จัดการกรณีไม่มีสิทธิ์
     */
    private function handleForbidden(): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $isApiRequest = strpos($uri, '/api/') === 0;

        if ($isApiRequest) {
            $this->jsonError('Insufficient permissions', 403);
        } else {
            $this->redirect('/403'); // หน้า Forbidden
        }
    }

    /**
     * เพิ่มบทบาทที่อนุญาต
     * 
     * @param string|array $roles
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
     * 
     * @param array $hierarchy
     */
    public function setRoleHierarchy(array $hierarchy): void
    {
        $this->roleHierarchy = $hierarchy;
    }

    /**
     * ตรวจสอบว่าผู้ใช้มีบทบาทเฉพาะหรือไม่ (static method)
     * 
     * @param string $role
     * @return bool
     */
    public static function userHasRole(string $role): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        $userModel = new User();
        $user = $userModel->findById($_SESSION['user_id']);

        if (!$user) {
            return false;
        }

        return ($user['role'] ?? 'user') === $role;
    }

    /**
     * ตรวจสอบว่าผู้ใช้เป็น admin หรือไม่ (static method)
     * 
     * @return bool
     */
    public static function isAdmin(): bool
    {
        return self::userHasRole('admin');
    }
}
