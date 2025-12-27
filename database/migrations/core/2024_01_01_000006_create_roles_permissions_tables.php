<?php

use App\Core\Migration;

class CreateRolesPermissionsTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create roles table
        $roles = "
        CREATE TABLE IF NOT EXISTS roles (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัสบทบาท',
            name VARCHAR(50) NOT NULL UNIQUE COMMENT 'ชื่อบทบาท (unique key)',
            display_name VARCHAR(100) NOT NULL COMMENT 'ชื่อแสดง',
            description TEXT NULL COMMENT 'คำอธิบายบทบาท',
            level INT UNSIGNED DEFAULT 0 COMMENT 'ระดับสิทธิ์ เช่น 100=Admin, 50=Manager, 10=User',
            is_system BOOLEAN DEFAULT FALSE COMMENT 'Role ระบบที่ไม่สามารถลบได้',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่แก้ไข',
            
            INDEX idx_name (name),
            INDEX idx_level (level)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางบทบาทผู้ใช้ (RBAC)'
        ";

        $this->execute($roles);

        // Create permissions table
        $permissions = "
        CREATE TABLE IF NOT EXISTS permissions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัสสิทธิ์',
            name VARCHAR(100) NOT NULL UNIQUE COMMENT 'ชื่อสิทธิ์ (unique key)',
            display_name VARCHAR(150) NOT NULL COMMENT 'ชื่อแสดง',
            description TEXT NULL COMMENT 'คำอธิบายสิทธิ์',
            group_name VARCHAR(50) NULL COMMENT 'จัดกลุ่มสิทธิ์ เช่น users, products, orders',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่แก้ไข',
            
            INDEX idx_name (name),
            INDEX idx_group_name (group_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางสิทธิ์การใช้งาน (RBAC)'
        ";

        $this->execute($permissions);

        // Create role_permissions pivot table
        $rolePermissions = "
        CREATE TABLE IF NOT EXISTS role_permissions (
            role_id INT UNSIGNED NOT NULL COMMENT 'รหัสบทบาท',
            permission_id INT UNSIGNED NOT NULL COMMENT 'รหัสสิทธิ์',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
            
            PRIMARY KEY (role_id, permission_id),
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางเชื่อมโยงบทบาทกับสิทธิ์ (Pivot Table)'
        ";

        $this->execute($rolePermissions);

        // Create user_roles pivot table
        $userRoles = "
        CREATE TABLE IF NOT EXISTS user_roles (
            user_id INT UNSIGNED NOT NULL COMMENT 'รหัสผู้ใช้',
            role_id INT UNSIGNED NOT NULL COMMENT 'รหัสบทบาท',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่กำหนดบทบาท',
            
            PRIMARY KEY (user_id, role_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางเชื่อมโยงผู้ใช้กับบทบาท (Pivot Table)'
        ";

        $this->execute($userRoles);

        // Insert sample roles
        $sampleRoles = "
        INSERT INTO roles (name, display_name, description, level, is_system) VALUES
        ('admin', 'ผู้ดูแลระบบ', 'สิทธิ์เต็มในการจัดการระบบทั้งหมด', 100, 1),
        ('manager', 'ผู้จัดการ', 'จัดการสินค้า คำสั่งซื้อ และลูกค้า', 50, 1),
        ('customer', 'ลูกค้า', 'ผู้ใช้ทั่วไปที่สามารถซื้อสินค้าได้', 10, 1)
        ";

        $this->execute($sampleRoles);

        // Insert sample permissions
        $samplePermissions = "
        INSERT INTO permissions (name, display_name, description, group_name) VALUES
        ('users.view', 'ดูข้อมูลผู้ใช้', 'สามารถดูรายการผู้ใช้', 'users'),
        ('users.create', 'สร้างผู้ใช้', 'สามารถสร้างผู้ใช้ใหม่', 'users'),
        ('users.edit', 'แก้ไขผู้ใช้', 'สามารถแก้ไขข้อมูลผู้ใช้', 'users'),
        ('users.delete', 'ลบผู้ใช้', 'สามารถลบผู้ใช้', 'users'),
        ('products.view', 'ดูสินค้า', 'สามารถดูรายการสินค้า', 'products'),
        ('products.create', 'สร้างสินค้า', 'สามารถสร้างสินค้าใหม่', 'products'),
        ('products.edit', 'แก้ไขสินค้า', 'สามารถแก้ไขข้อมูลสินค้า', 'products'),
        ('products.delete', 'ลบสินค้า', 'สามารถลบสินค้า', 'products'),
        ('orders.view', 'ดูคำสั่งซื้อ', 'สามารถดูรายการคำสั่งซื้อ', 'orders'),
        ('orders.manage', 'จัดการคำสั่งซื้อ', 'สามารถจัดการสถานะคำสั่งซื้อ', 'orders')
        ";

        $this->execute($samplePermissions);

        // Assign permissions to admin role
        $adminPermissions = "
        INSERT INTO role_permissions (role_id, permission_id)
        SELECT 1, id FROM permissions
        ";

        $this->execute($adminPermissions);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS user_roles");
        $this->execute("DROP TABLE IF EXISTS role_permissions");
        $this->execute("DROP TABLE IF EXISTS permissions");
        $this->execute("DROP TABLE IF EXISTS roles");
    }
}
