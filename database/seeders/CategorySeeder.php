<?php
/**
 * Category Seeder
 * 
 * สร้างข้อมูลหมวดหมู่สินค้าตัวอย่าง
 */

namespace Database\Seeders;

use App\Core\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->log('Seeding categories...');
        
        // ลบข้อมูลเก่า
        $this->truncate('categories');
        
        // สร้างข้อมูลหมวดหมู่
        $categories = [
            [
                'name' => 'สมาร์ทโฟน',
                'slug' => 'smartphones',
                'description' => 'โทรศัพท์มือถือสมาร์ทโฟนทุกรุ่น',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'คอมพิวเตอร์',
                'slug' => 'computers',
                'description' => 'คอมพิวเตอร์ แล็ปท็อป และอุปกรณ์เสริม',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'แท็บเล็ต',
                'slug' => 'tablets',
                'description' => 'แท็บเล็ตทุกยี่ห้อ',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'หูฟัง',
                'slug' => 'headphones',
                'description' => 'หูฟังและอุปกรณ์เสียง',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'สมาร์ทวอทช์',
                'slug' => 'smartwatches',
                'description' => 'นาฬิกาอัจฉริยะ',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'อุปกรณ์เสริม',
                'slug' => 'accessories',
                'description' => 'อุปกรณ์เสริมต่างๆ',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];
        
        $this->insert('categories', $categories);
        
        $this->log('Categories seeded successfully! (Total: ' . count($categories) . ')');
    }
}
