<?php
/**
 * โมเดลบทบาท (Role-Based Access Control)
 * 
 * จุดประสงค์: จัดการบทบาทผู้ใช้และสิทธิ์การเข้าถึง
 * 
 * กฎทางธุรกิจ:
 * - แต่ละผู้ใช้มีได้หลายบทบาท (many-to-many)
 * - แต่ละบทบาทมีหลาย permissions
 * - มีบทบาทเริ่มต้น: admin, manager, user, guest
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class Role
{
    private PDO $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }

    /**
     * ดึงบทบาททั้งหมด
     * 
     * @return array
     */
    public function getAll(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT * FROM roles 
                ORDER BY sort_order ASC, name ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch roles', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * ดึงบทบาทตาม ID
     * 
     * @param int $id
     * @return array|null
     */
    public function find(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM roles WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to find role', ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * ดึงบทบาทตามชื่อ
     * 
     * @param string $name
     * @return array|null
     */
    public function findByName(string $name): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM roles WHERE name = :name");
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to find role by name', ['name' => $name, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * ดึง permissions ของบทบาท
     * 
     * @param int $roleId
     * @return array
     */
    public function getPermissions(int $roleId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT p.* FROM permissions p
                INNER JOIN role_permissions rp ON p.id = rp.permission_id
                WHERE rp.role_id = :role_id
                ORDER BY p.name ASC
            ");
            $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch role permissions', [
                'role_id' => $roleId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * ดึงบทบาทของผู้ใช้
     * 
     * @param int $userId
     * @return array
     */
    public function getUserRoles(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT r.* FROM roles r
                INNER JOIN user_roles ur ON r.id = ur.role_id
                WHERE ur.user_id = :user_id
                ORDER BY r.sort_order ASC
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch user roles', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * กำหนดบทบาทให้ผู้ใช้
     * 
     * @param int $userId
     * @param int $roleId
     * @return array
     */
    public function assignToUser(int $userId, int $roleId): array
    {
        try {
            // ตรวจสอบว่ามีอยู่แล้วหรือไม่
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM user_roles 
                WHERE user_id = :user_id AND role_id = :role_id
            ");
            $stmt->execute([':user_id' => $userId, ':role_id' => $roleId]);
            
            if ($stmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'ผู้ใช้มีบทบาทนี้อยู่แล้ว'
                ];
            }

            $stmt = $this->db->prepare("
                INSERT INTO user_roles (user_id, role_id, created_at)
                VALUES (:user_id, :role_id, NOW())
            ");
            $stmt->execute([':user_id' => $userId, ':role_id' => $roleId]);
            
            $this->logger->info('Role assigned to user', [
                'user_id' => $userId,
                'role_id' => $roleId
            ]);
            
            return [
                'success' => true,
                'message' => 'กำหนดบทบาทสำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to assign role', [
                'user_id' => $userId,
                'role_id' => $roleId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * ถอนบทบาทจากผู้ใช้
     * 
     * @param int $userId
     * @param int $roleId
     * @return array
     */
    public function removeFromUser(int $userId, int $roleId): array
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM user_roles 
                WHERE user_id = :user_id AND role_id = :role_id
            ");
            $stmt->execute([':user_id' => $userId, ':role_id' => $roleId]);
            
            $this->logger->info('Role removed from user', [
                'user_id' => $userId,
                'role_id' => $roleId
            ]);
            
            return [
                'success' => true,
                'message' => 'ถอนบทบาทสำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to remove role', [
                'user_id' => $userId,
                'role_id' => $roleId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * กำหนด permission ให้บทบาท
     * 
     * @param int $roleId
     * @param int $permissionId
     * @return array
     */
    public function attachPermission(int $roleId, int $permissionId): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
                VALUES (:role_id, :permission_id, NOW())
            ");
            $stmt->execute([':role_id' => $roleId, ':permission_id' => $permissionId]);
            
            $this->logger->info('Permission attached to role', [
                'role_id' => $roleId,
                'permission_id' => $permissionId
            ]);
            
            return [
                'success' => true,
                'message' => 'กำหนดสิทธิ์สำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to attach permission', [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * ถอน permission จากบทบาท
     * 
     * @param int $roleId
     * @param int $permissionId
     * @return array
     */
    public function detachPermission(int $roleId, int $permissionId): array
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM role_permissions 
                WHERE role_id = :role_id AND permission_id = :permission_id
            ");
            $stmt->execute([':role_id' => $roleId, ':permission_id' => $permissionId]);
            
            $this->logger->info('Permission detached from role', [
                'role_id' => $roleId,
                'permission_id' => $permissionId
            ]);
            
            return [
                'success' => true,
                'message' => 'ถอนสิทธิ์สำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to detach permission', [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * สร้างบทบาทใหม่
     * 
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO roles (name, display_name, description, sort_order, created_at)
                VALUES (:name, :display_name, :description, :sort_order, NOW())
            ");
            
            $stmt->execute([
                ':name' => $data['name'],
                ':display_name' => $data['display_name'],
                ':description' => $data['description'] ?? null,
                ':sort_order' => $data['sort_order'] ?? 0
            ]);
            
            $roleId = $this->db->lastInsertId();
            
            $this->logger->info('Role created', ['role_id' => $roleId]);
            
            return [
                'success' => true,
                'message' => 'สร้างบทบาทสำเร็จ',
                'role_id' => $roleId
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to create role', [
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
