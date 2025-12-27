<?php
/**
 * Migration Base Class
 * 
 * จุดประสงค์: คลาสแม่สำหรับ migrations ทั้งหมด
 * ฟีเจอร์: Run, Rollback, Status tracking
 */

namespace App\Core;

use PDO;
use PDOException;

abstract class Migration
{
    protected PDO $db;
    protected Logger $logger;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }
    
    /**
     * Run the migration (สร้างตาราง/เพิ่มฟิลด์)
     * 
     * @return void
     */
    abstract public function up(): void;
    
    /**
     * Reverse the migration (ลบตาราง/ลบฟิลด์)
     * 
     * @return void
     */
    abstract public function down(): void;
    
    /**
     * รัน SQL statement
     * 
     * @param string $sql
     * @return bool
     */
    protected function execute(string $sql): bool
    {
        try {
            $this->db->exec($sql);
            return true;
        } catch (PDOException $e) {
            $this->logger->error('Migration SQL Error', [
                'error' => $e->getMessage(),
                'sql' => $sql
            ]);
            throw $e;
        }
    }
    
    /**
     * รัน SQL statements หลายตัว
     * 
     * @param array $statements
     * @return bool
     */
    protected function executeMultiple(array $statements): bool
    {
        try {
            $this->db->beginTransaction();
            
            foreach ($statements as $sql) {
                $this->db->exec($sql);
            }
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            $this->logger->error('Migration Error', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * ตรวจสอบว่าตารางมีอยู่หรือไม่
     * 
     * @param string $tableName
     * @return bool
     */
    protected function tableExists(string $tableName): bool
    {
        try {
            $result = $this->db->query("SHOW TABLES LIKE '$tableName'");
            return $result->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * ตรวจสอบว่าคอลัมน์มีอยู่ในตารางหรือไม่
     * 
     * @param string $tableName
     * @param string $columnName
     * @return bool
     */
    protected function columnExists(string $tableName, string $columnName): bool
    {
        try {
            $result = $this->db->query("SHOW COLUMNS FROM $tableName LIKE '$columnName'");
            return $result->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * ดึงชื่อ migration จากชื่อ class
     * 
     * @return string
     */
    public function getName(): string
    {
        return basename(str_replace('\\', '/', get_class($this)));
    }
}
