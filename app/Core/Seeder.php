<?php
/**
 * คลาสนี้เป็นฐานสำหรับการสร้าง Seeder ซึ่งใช้ในการเติมข้อมูลตัวอย่างลงในฐานข้อมูล
 * 
 * จุดประสงค์: สร้างข้อมูลตัวอย่างในฐานข้อมูล
 * Seeder() ควรใช้กับอะไร: เมื่อคุณต้องการเติมข้อมูลตัวอย่างลงในฐานข้อมูล
 * 
 * ฟีเเจอร์หลัก:
 * - การเชื่อมต่อฐานข้อมูลผ่าน Database wrapper
 * - ฟังก์ชันช่วยเหลือสำหรับการแทรกและลบข้อมูล
 * - การจัดการข้อผิดพลาดอย่างละเอียดเมื่อเกิดปัญหาในการแทรกข้อมูล
 * 
 * ตัวอย่างการใช้งานโดยรวม:
 * ```php
 * class UserSeeder extends Seeder {
 *     public function run(): void {
 *         $this->insert('users', [
 *             ['name' => 'John Doe', 'email' => 'john@example.com'],
 *             ['name' => 'Jane Smith', 'email' => 'jane@example.com']
 *         ]);
 *     }
 * }
 * ```
 */

namespace App\Core;

use App\Core\Database;

abstract class Seeder
{
    /**
     * การเชื่อมต่อฐานข้อมูล
     */
    protected Database $db;

    /**
     * ตัวสร้าง Seeder
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * รัน seeder
     */
    abstract public function run(): void;

    /**
     * Insert ข้อมูลเข้าตาราง
     * จุดประสงค์: แทรกข้อมูลลงในตารางฐานข้อมูลด้วยการจัดการข้อผิดพลาดอย่างละเอียด
     * insert() ควรใช้กับอะไร: ชื่อตารางและข้อมูลที่ต้องการแทรก
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->insert('users', [
     *    ['name' => 'John Doe', 'email' => 'john@example.com'],
     *    ['name' => 'Jane Smith', 'email' => 'jane@example.com']
     * ]);
     * ```
     * 
     * @param string $table ชื่อตาราง
     * @param array $data ข้อมูล (array of arrays)
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    protected function insert(string $table, array $data): void
    {
        if (empty($data)) {
            return;
        }

        try {
            // สมมติว่าทุก row มีคอลัมน์เดียวกัน
            $columns = array_keys($data[0]);
            $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
            
            $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES ";
            $values = [];
            
            foreach ($data as $row) {
                $sql .= $placeholders . ', ';
                $values = array_merge($values, array_values($row));
            }
            
            $sql = rtrim($sql, ', ');
            
            $this->db->execute($sql, $values);
        } catch (\PDOException $e) {
            echo "\n";
            echo "┌─ Seeder Error ──────────────────────────────────────┐\n";
            $this->error('เกิดข้อผิดพลาด: ' . $e->getMessage());
            $this->error('ไฟล์: ' . $e->getFile());
            $this->error('บรรทัด: ' . $e->getLine());
            
            // แสดง SQL error code ถ้ามี
            if ($e->getCode()) {
                $this->error('Error Code: ' . $e->getCode());
            }
            
            // แนะนำการแก้ไข
            if (strpos($e->getMessage(), 'Column not found') !== false) {
                echo "\n";
                $this->warning('💡 คำแนะนำ: ตรวจสอบว่าชื่อคอลัมน์ใน Seeder ตรงกับ Migration หรือไม่');
                $this->info('   - รันคำสั่ง: php console db:table <table_name> เพื่อดูโครงสร้างตาราง');
            } elseif (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "\n";
                $this->warning('💡 คำแนะนำ: มีข้อมูลซ้ำในตาราง');
                $this->info('   - ลองรันคำสั่ง: php console migrate:fresh แล้วรัน seed ใหม่');
            } elseif (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                echo "\n";
                $this->warning('💡 คำแนะนำ: ตรวจสอบความสัมพันธ์ระหว่างตาราง');
                $this->info('   - ตรวจสอบว่า foreign key มีค่าที่ถูกต้องหรือไม่');
            }
            
            echo "└────────────────────────────────────────────────────┘\n";
            echo "\n";
            
            // Log error
            (new \App\Core\Logger())->error('Seeder error', [
                'table' => $table,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
            ]);
            
            exit(1);
        }
    }

    /**
     * Truncate ตาราง
     * จุดประสงค์: ลบข้อมูลทั้งหมดจากตารางอย่างรวดเร็ว
     * truncate() ควรใช้กับอะไร: ชื่อตารางที่ต้องการลบข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->truncate('users');
     * ```
     * 
     * @param string $table ชื่อตาราง
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    protected function truncate(string $table): void
    {
        $this->db->execRaw("SET FOREIGN_KEY_CHECKS = 0");
        $this->db->execRaw("TRUNCATE TABLE {$table}");
        $this->db->execRaw("SET FOREIGN_KEY_CHECKS = 1");
    }

    /**
     * แสดงข้อความ
     * จุดประสงค์: แสดงข้อความในคอนโซลด้วยรูปแบบที่กำหนด
     * log() ควรใช้กับอะไร: ข้อความที่ต้องการแสดง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->log('Seeding users...');
     * ```
     * 
     * @param string $message ข้อความที่ต้องการแสดง
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    protected function log(string $message): void
    {
        echo "[" . date('Y-m-d H:i:s') . "] {$message}\n";
    }

    /**
     * แสดงข้อความ error
     * จุดประสงค์: แสดงข้อความ error ในคอนโซลด้วยรูปแบบที่กำหนด
     * error() ควรใช้กับอะไร: ข้อความ error ที่ต้องการแสดง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->error('เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล');
     * ```
     * 
     * @param string $message ข้อความ error ที่ต้องการแสดง
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    protected function error(string $message): void
    {
        echo "\033[31m✗ {$message}\033[0m\n";
    }

    /**
     * แสดงข้อความ warning
     * จุดประสงค์: แสดงข้อความ warning ในคอนโซลด้วยรูปแบบที่กำหนด
     * warning() ควรใช้กับอะไร: ข้อความ warning ที่ต้องการแสดง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->warning('ข้อมูลบางส่วนอาจไม่ถูกต้อง');
     * ```
     * 
     * @param string $message ข้อความ warning ที่ต้องการแสดง
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    protected function warning(string $message): void
    {
        echo "\033[33m⚠ {$message}\033[0m\n";
    }

    /**
     * แสดงข้อความ info
     * จุดประสงค์: แสดงข้อความ info ในคอนโซลด้วยรูปแบบที่กำหนด
     * info() ควรใช้กับอะไร: ข้อความ info ที่ต้องการแสดง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->info('การเชื่อมต่อฐานข้อมูลสำเร็จ');
     * ```
     * 
     * @param string $message ข้อความ info ที่ต้องการแสดง
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    protected function info(string $message): void
    {
        echo "\033[36mℹ {$message}\033[0m\n";
    }

    /**
     * แสดงข้อความ success
     * จุดประสงค์: แสดงข้อความ success ในคอนโซลด้วยรูปแบบที่กำหนด
     * success() ควรใช้กับอะไร: ข้อความ success ที่ต้องการแสดง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->success('การดำเนินการเสร็จสิ้น');
     * ```
     * 
     * @param string $message ข้อความ success ที่ต้องการแสดง
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    protected function success(string $message): void
    {
        echo "\033[32m✓ {$message}\033[0m\n";
    }
}
