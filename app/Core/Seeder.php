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
}
