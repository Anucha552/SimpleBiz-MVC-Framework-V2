<?php
/**
 * Migration: Departments
 */

namespace Database\Migrations;

use App\Core\Migration;
use App\Core\Blueprint;

class Departments extends Migration
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
        $this->createTable('departments', function(Blueprint $table) {
            $table->increments('id')->comment('รหัส');
            $table->string('department_name', 100)->comment('ชื่อแผนก');
            $table->timestamp('created_at')->comment('วันที่สร้าง');

            // เพิ่มดัชนีสำหรับคอลัมน์ department_name เพื่อเพิ่มประสิทธิภาพในการค้นหา
            $table->index('department_name');
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
        $this->dropTable('departments');
    }
}