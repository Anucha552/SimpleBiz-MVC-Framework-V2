<?php
/**
 * employeeSeeder
 *
 * จุดประสงค์: ใช้สำหรับเติมข้อมูลตัวอย่างลงในตารางที่เกี่ยวข้องกับพนักงาน (เช่น departments, employees)
 */

namespace Database\Seeders;

use App\Core\Seeder;

class employeeSeeder extends Seeder
{
    /**
     * รัน seeder
     */
    public function run(): void
    {
          $this->log('Seeding departments...');

        // ลบข้อมูลเก่า (ถ้าต้องการ)
        $this->truncate('departments');

        $data = [
            [
                'department_name' => 'ฝ่ายบุคคล',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'department_name' => 'ฝ่ายบัญชี',
                'created_at' => date('Y-m-d H:i:s'),
            ]
        ];

        // เพิ่มข้อมูลลงในตาราง
        $this->insert('departments', $data);

        $this->log('✓ Seeded departments successfully!');

        $this->log('Seeding employees...');

        // ลบข้อมูลเก่า (ถ้าต้องการ)
        $this->truncate('employees');

        // TODO: เพิ่มข้อมูลตัวอย่าง
        $data = [
            [
                'department_id' => 1,
                'employee_code' => 'EMP001',
                'first_name' => 'สมชาย',
                'last_name' => 'ใจดี',
                'email' => 'somchai.jaidee@example.com',
                'phone' => '0812345678',
                'salary' => 15000.00,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'department_id' => 2,
                'employee_code' => 'EMP002',
                'first_name' => 'สมหญิง',
                'last_name' => 'ใจดี',
                'email' => 'somying.jaidee@example.com',
                'phone' => '0898765432',
                'salary' => 16000.00,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
            ]
        ];

        // เพิ่มข้อมูลลงในตาราง
        $this->insert('employees', $data);

        $this->log('✓ Seeded employees successfully!');
    }
}
