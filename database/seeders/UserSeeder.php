<?php
/**
 * User Seeder
 * 
 * สร้างข้อมูลผู้ใช้ตัวอย่าง
 */

namespace Database\Seeders;

use App\Core\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->log('Seeding users/roles/permissions...');

        $now = date('Y-m-d H:i:s');

        // ลบข้อมูลเก่า (เรียงตามความสัมพันธ์)
        $this->truncate('user_permissions');
        $this->truncate('role_permissions');
        $this->truncate('user_roles');
        $this->truncate('roles');
        $this->truncate('users');

        $this->log(' - roles');
        $roles = [
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'ผู้ดูแลระบบ',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'User',
                'slug' => 'user',
                'description' => 'ผู้ใช้ทั่วไป',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'ผู้จัดการ',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->insert('roles', $roles);

        $roleMap = $this->db->fetchPairs('SELECT slug, id FROM roles');
        $adminRoleId = $roleMap['admin'] ?? null;
        $userRoleId = $roleMap['user'] ?? null;
        $managerRoleId = $roleMap['manager'] ?? null;

        $this->log(' - users');
        $users = [
            [
                'username' => 'admin',
                'email' => 'admin@simplebiz.local',
                'password' => password_hash('password123', PASSWORD_BCRYPT),
                'name' => 'Admin User',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'phone' => '0812345678',
                'role' => 'admin',
                'role_id' => $adminRoleId,
                'is_admin' => 1,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'john',
                'email' => 'john@example.com',
                'password' => password_hash('password123', PASSWORD_BCRYPT),
                'name' => 'John Doe',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'phone' => '0823456789',
                'role' => 'user',
                'role_id' => $userRoleId,
                'is_admin' => 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'jane',
                'email' => 'jane@example.com',
                'password' => password_hash('password123', PASSWORD_BCRYPT),
                'name' => 'Jane Smith',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'phone' => '0834567890',
                'role' => 'user',
                'role_id' => $userRoleId,
                'is_admin' => 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->insert('users', $users);

        $userMap = $this->db->fetchPairs('SELECT username, id FROM users');
        $adminUserId = $userMap['admin'] ?? null;
        $johnUserId = $userMap['john'] ?? null;
        $janeUserId = $userMap['jane'] ?? null;

        $this->log(' - user_roles');
        $userRoles = array_filter([
            $this->buildUserRoleRow($adminUserId, $adminRoleId, $now),
            $this->buildUserRoleRow($johnUserId, $userRoleId, $now),
            $this->buildUserRoleRow($janeUserId, $userRoleId, $now),
        ]);

        if (!empty($userRoles)) {
            $this->insert('user_roles', $userRoles);
        }

        $this->log(' - role_permissions');
        $rolePermissions = array_filter([
            $this->buildRolePermissionRow($adminRoleId, 'system.manage', $now),
            $this->buildRolePermissionRow($adminRoleId, 'users.manage', $now),
            $this->buildRolePermissionRow($adminRoleId, 'roles.manage', $now),
            $this->buildRolePermissionRow($userRoleId, 'profile.view', $now),
            $this->buildRolePermissionRow($userRoleId, 'profile.update', $now),
            $this->buildRolePermissionRow($managerRoleId, 'users.view', $now),
        ]);

        if (!empty($rolePermissions)) {
            $this->insert('role_permissions', $rolePermissions);
        }

        $this->log(' - user_permissions');
        $userPermissions = array_filter([
            $this->buildUserPermissionRow($adminUserId, 'audit.view', $now),
        ]);

        if (!empty($userPermissions)) {
            $this->insert('user_permissions', $userPermissions);
        }

        $this->log('Users/roles/permissions seeded successfully!');
        $this->log('Default credentials: admin / password123');
    }

    private function buildUserRoleRow($userId, $roleId, string $now): ?array
    {
        if (empty($userId) || empty($roleId)) {
            return null;
        }

        return [
            'user_id' => $userId,
            'role_id' => $roleId,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function buildRolePermissionRow($roleId, string $permission, string $now): ?array
    {
        if (empty($roleId)) {
            return null;
        }

        return [
            'role_id' => $roleId,
            'permission' => $permission,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function buildUserPermissionRow($userId, string $permission, string $now): ?array
    {
        if (empty($userId)) {
            return null;
        }

        return [
            'user_id' => $userId,
            'permission' => $permission,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
