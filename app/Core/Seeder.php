<?php
/**
 * Database Seeder
 * 
 * จุดประสงค์: สร้างข้อมูลตัวอย่างในฐานข้อมูล
 */

namespace App\Core;

use PDO;

abstract class Seeder
{
    /**
     * Database connection
     */
    protected PDO $db;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * รัน seeder
     */
    abstract public function run(): void;

    /**
     * Insert ข้อมูลเข้าตาราง
     * 
     * @param string $table ชื่อตาราง
     * @param array $data ข้อมูล (array of arrays)
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
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
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
     * 
     * @param string $table ชื่อตาราง
     */
    protected function truncate(string $table): void
    {
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");
        $this->db->exec("TRUNCATE TABLE {$table}");
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
    }

    /**
     * แสดงข้อความ
     * 
     * @param string $message
     */
    protected function log(string $message): void
    {
        echo "[" . date('Y-m-d H:i:s') . "] {$message}\n";
    }

    /**
     * แสดงข้อความ error
     */
    protected function error(string $message): void
    {
        echo "\033[31m✗ {$message}\033[0m\n";
    }

    /**
     * แสดงข้อความ warning
     */
    protected function warning(string $message): void
    {
        echo "\033[33m⚠ {$message}\033[0m\n";
    }

    /**
     * แสดงข้อความ info
     */
    protected function info(string $message): void
    {
        echo "\033[36mℹ {$message}\033[0m\n";
    }

    /**
     * แสดงข้อความ success
     */
    protected function success(string $message): void
    {
        echo "\033[32m✓ {$message}\033[0m\n";
    }
}
