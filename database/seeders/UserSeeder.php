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
        $this->log('Seeding users...');
        
        // ลบข้อมูลเก่า
        $this->truncate('users');
        
        // สร้างข้อมูลผู้ใช้
        $users = [
            [
                'username' => 'admin',
                'email' => 'admin@simplebiz.local',
                'password' => password_hash('password123', PASSWORD_BCRYPT),
                'first_name' => 'Admin',
                'last_name' => 'User',
                'phone' => '0812345678',
                'role' => 'admin',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'john',
                'email' => 'john@example.com',
                'password' => password_hash('password123', PASSWORD_BCRYPT),
                'first_name' => 'John',
                'last_name' => 'Doe',
                'phone' => '0823456789',
                'role' => 'customer',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'jane',
                'email' => 'jane@example.com',
                'password' => password_hash('password123', PASSWORD_BCRYPT),
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'phone' => '0834567890',
                'role' => 'customer',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];
        
        $this->insert('users', $users);
        
        $this->log('Users seeded successfully! (Total: ' . count($users) . ')');
        $this->log('Default credentials: admin / password123');
    }
}
