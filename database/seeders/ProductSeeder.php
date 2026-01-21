<?php
/**
 * Product Seeder
 * 
 * สร้างข้อมูลสินค้าตัวอย่าง
 */

namespace Database\Seeders;

use App\Core\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->log('Seeding products...');
        
        // ลบข้อมูลเก่า
        $this->truncate('products');
        
        // สร้างข้อมูลสินค้า
        $products = [
            [
                'name' => 'iPhone 15 Pro',
                'slug' => 'iphone-15-pro',
                'description' => 'สมาร์ทโฟนเรือธงจาก Apple พร้อมชิป A17 Pro',
                'price' => 45900.00,
                'stock_quantity' => 50,
                'category_id' => 1,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'slug' => 'samsung-galaxy-s24-ultra',
                'description' => 'สมาร์ทโฟนเรือธงพร้อม S Pen และกล้อง 200MP',
                'price' => 42900.00,
                'stock_quantity' => 40,
                'category_id' => 1,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'MacBook Pro 14" M3',
                'slug' => 'macbook-pro-14-m3',
                'description' => 'แล็ปท็อปสำหรับมืออาชีพพร้อมชิป M3',
                'price' => 67900.00,
                'stock_quantity' => 30,
                'category_id' => 2,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'iPad Pro 11"',
                'slug' => 'ipad-pro-11',
                'description' => 'แท็บเล็ตระดับโปรพร้อมชิป M2',
                'price' => 29900.00,
                'stock_quantity' => 60,
                'category_id' => 3,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'AirPods Pro (2nd Gen)',
                'slug' => 'airpods-pro-2',
                'description' => 'หูฟังไร้สายพร้อม Active Noise Cancellation',
                'price' => 8900.00,
                'stock_quantity' => 100,
                'category_id' => 4,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Apple Watch Series 9',
                'slug' => 'apple-watch-series-9',
                'description' => 'สมาร์ทวอทช์พร้อมฟีเจอร์สุขภาพครบครัน',
                'price' => 14900.00,
                'stock_quantity' => 70,
                'category_id' => 5,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Magic Keyboard',
                'slug' => 'magic-keyboard',
                'description' => 'คีย์บอร์ดไร้สายจาก Apple',
                'price' => 3900.00,
                'stock_quantity' => 80,
                'category_id' => 6,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Magic Mouse',
                'slug' => 'magic-mouse',
                'description' => 'เมาส์ไร้สายพร้อมพื้นผิว Multi-Touch',
                'price' => 2900.00,
                'stock_quantity' => 90,
                'category_id' => 6,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];
        
        $this->insert('products', $products);
        
        $this->log('Products seeded successfully! (Total: ' . count($products) . ')');
    }
}
