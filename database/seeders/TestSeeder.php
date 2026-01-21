<?php
/**
 * TestSeeder
 * 
 * จุดประสงค์: [อธิบายหน้าที่ของ seeder]
 */

namespace Database\Seeders;

use App\Core\Seeder;

class TestSeeder extends Seeder
{
    /**
     * รัน seeder
     */
    public function run(): void
    {
        $this->log('Seeding data...');
        
        // ลบข้อมูลเก่า (ถ้าต้องการ)
        // $this->truncate('table_name');
        
        // TODO: เพิ่มข้อมูลตัวอย่าง
        $data = [
            // เพิ่มข้อมูลที่นี่
        ];
        
        foreach ($data as $item) {
            $this->insert('table_name', $item);
        }
        
        $this->success('Seeded successfully!');
    }
}
