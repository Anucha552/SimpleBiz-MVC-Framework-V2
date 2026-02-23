<?php
/**
 * Migration: Employees
 */

namespace Database\Migrations;

use App\Core\Migration;
use App\Core\Blueprint;

class Employees extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        // ตัวอย่างการสร้างตารางแบบเขียน SQL ด้วยตนเอง
        // $sql = "
        //     CREATE TABLE IF NOT EXISTS example_table (
        //         id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        //         name VARCHAR(255) NOT NULL,
        //         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        //     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        // ";
        // $this->execute($sql);

        // ตัวอย่างการสร้างตารางแบบใช้ Schema Builder
        $this->createTable('employees', function(Blueprint $table) {
            $table->increments('id')->comment('รหัส');
            $table->integer('department_id')->comment('รหัสแผนก');
            $table->string('employee_code', 20)->unique()->comment('รหัสพนักงาน');
            $table->string('first_name', 100)->comment('ชื่อ');
            $table->string('last_name', 100)->comment('นามสกุล');
            $table->string('email', 150)->unique()->comment('อีเมล');
            $table->string('phone', 20)->nullable()->comment('เบอร์โทรศัพท์');
            $table->decimal('salary', 10, 2)->default(0)->comment('เงินเดือน');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('สถานะ');
            $table->timestamp('created_at')->comment('วันที่สร้าง');

            $table->foreign('department_id')->references('id')->on('departments')->onDelete('CASCADE');

            // เพิ่มดัชนีสำหรับคอลัมน์ที่ใช้บ่อยในการค้นหา
            $table->index('employee_code');
            $table->index('email');
            $table->index('last_name');
        });

    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        // ตัวอย่างการลบตารางแบบเขียน SQL ด้วยตนเอง
        // $this->execute("DROP TABLE IF EXISTS example_table");

        // ตัวอย่างการลบตารางแบบใช้ Schema Builder
        $this->dropTable('employees');
    }
}