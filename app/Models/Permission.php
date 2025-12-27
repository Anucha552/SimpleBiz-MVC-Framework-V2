<?php
/**
 * โมเดลสิทธิ์การเข้าถึง (Permission)
 * 
 * จุดประสงค์: จัดการ permissions สำหรับระบบ RBAC
 * 
 * กฎทางธุรกิจ:
 * - Permission ใช้รูปแบบ: resource.action (เช่น product.create, order.delete)
 * - จัดกลุ่มตาม resource
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class Permission
{
    private PDO $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }

    /**
     * ดึง permissions ทั้งหมด
     * 
     * @return array
     */
    public function getAll(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT * FROM permissions 
                ORDER BY resource ASC, action ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch permissions', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * ดึง permissions แบบจัดกลุ่มตาม resource
     * 
     * @return array
     */
    public function getGroupedByResource(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT * FROM permissions 
                ORDER BY resource ASC, action ASC
            ");
            $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $grouped = [];
            foreach ($permissions as $permission) {
                $resource = $permission['resource'];
                if (!isset($grouped[$resource])) {
                    $grouped[$resource] = [];
                }
                $grouped[$resource][] = $permission;
            }
            
            return $grouped;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch grouped permissions', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * ตรวจสอบว่าผู้ใช้มี permission หรือไม่
     * 
     * @param int $userId
     * @param string $permissionName (format: resource.action)
     * @return bool
     */
    public function userHasPermission(int $userId, string $permissionName): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM permissions p
                INNER JOIN role_permissions rp ON p.id = rp.permission_id
                INNER JOIN user_roles ur ON rp.role_id = ur.role_id
                WHERE ur.user_id = :user_id AND p.name = :permission_name
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':permission_name' => $permissionName
            ]);
            
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to check user permission', [
                'user_id' => $userId,
                'permission' => $permissionName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * ดึง permissions ทั้งหมดของผู้ใช้
     * 
     * @param int $userId
     * @return array
     */
    public function getUserPermissions(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT p.* FROM permissions p
                INNER JOIN role_permissions rp ON p.id = rp.permission_id
                INNER JOIN user_roles ur ON rp.role_id = ur.role_id
                WHERE ur.user_id = :user_id
                ORDER BY p.resource ASC, p.action ASC
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch user permissions', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * สร้าง permission ใหม่
     * 
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO permissions (name, display_name, resource, action, description, created_at)
                VALUES (:name, :display_name, :resource, :action, :description, NOW())
            ");
            
            $stmt->execute([
                ':name' => $data['name'],
                ':display_name' => $data['display_name'],
                ':resource' => $data['resource'],
                ':action' => $data['action'],
                ':description' => $data['description'] ?? null
            ]);
            
            $permissionId = $this->db->lastInsertId();
            
            $this->logger->info('Permission created', ['permission_id' => $permissionId]);
            
            return [
                'success' => true,
                'message' => 'สร้างสิทธิ์สำเร็จ',
                'permission_id' => $permissionId
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to create permission', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }
}
