<?php
/**
 * คลาส Migration สำหรับจัดการการเปลี่ยนแปลงโครงสร้างฐานข้อมูล
 * 
 * จุดประสงค์: คลาสแม่สำหรับ migrations ทั้งหมด
 * Migration ควรใช้กับอะไร: เมื่อคุณต้องการสร้างการเปลี่ยนแปลงโครงสร้างฐานข้อมูล เช่น สร้างตารางใหม่ เพิ่มคอลัมน์ หรือลบตาราง
 * 
 * ฟีเจอร์หลัก:
 * - สร้าง/ลบตาราง
 * - เพิ่ม/ลบคอลัมน์
 * - รันคำสั่ง SQL ดิบ
 * - ตรวจสอบสถานะ migration
 * ความปลอดภัย: ใช้ prepared statements เพื่อป้องกัน SQL injection
 * 
 * ตัวอย่างการใช้งานโดยรวม:
 * ```php
 * class CreateUsersTable extends Migration {
 *     public function up(): void {
 *         $this->execute("CREATE TABLE users (id INT PRIMARY KEY, name VARCHAR(100))");
 *     }
 *     public function down(): void {
 *         $this->execute("DROP TABLE users");
 *     }
 * }
 * ```
 */

namespace App\Core;


abstract class Migration
{
    /**
     * การเชื่อมต่อฐานข้อมูล (Database wrapper)
     */ 
    protected Database $db;

    /**
     * Logger สำหรับบันทึกข้อผิดพลาด
     */
    protected Logger $logger;
    
    /**
     * สร้างอินสแตนซ์ของ Migration
     * จุดประสงค์: กำหนดการเชื่อมต่อฐานข้อมูลและ Logger
     * ตัวอย่างการใช้งาน:
     * ```php
     * $migration = new CreateUsersTable();
     * ```
     * 
     * ผลลัพธ์: คืนค่าอินสแตนซ์ของ Migration ที่มีการเชื่อมต่อฐานข้อมูลและ Logger
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }
    
    /**
     * Run the migration (สร้างตาราง/เพิ่มฟิลด์)
     * จุดประสงค์: เมธอดหลักสำหรับรัน migration
     * up() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างตารางใหม่หรือเพิ่มฟิลด์ในตารางที่มีอยู่
     * ตัวอย่างการใช้งาน:
     * ```php
     * public function up(): void {
     *    $this->execute("CREATE TABLE users (id INT PRIMARY KEY, name VARCHAR(100))");
     * }
     * ```
     * @return void ไม่คืนค่าอะไร
     */
    abstract public function up(): void;
    
    /**
     * Reverse the migration (ลบตาราง/ลบฟิลด์)
     * จุดประสงค์: เมธอดหลักสำหรับย้อนกลับ migration
     * down() ควรใช้กับอะไร: เมื่อคุณต้องการลบตารางหรือฟิลด์ที่สร้างขึ้นในเมธอด up()
     * ตัวอย่างการใช้งาน:
     * ```php
     * public function down(): void {
     *    $this->execute("DROP TABLE users");
     * }
     * ```
     * 
     * @return void ไม่คืนค่าอะไร
     */
    abstract public function down(): void;
    
    /**
     * รัน SQL statement
     * จุดประสงค์: รันคำสั่ง SQL ดิบ
     * execute() ควรใช้กับอะไร: เมื่อคุณต้องการรันคำสั่ง SQL ดิบโดยตรง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->execute("CREATE TABLE users (id INT PRIMARY KEY, name VARCHAR(100))");
     * ```            
     * 
     * @param string $sql กำหนดคำสั่ง SQL ที่จะรัน
     * @return bool คืนค่า true หากรันสำเร็จ, false หากไม่สำเร็จ
     */
    protected function execute(string $sql): bool
    {
        try {
            $this->db->execRaw($sql);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Migration SQL Error', [
                'error' => $e->getMessage(),
                'sql' => $sql
            ]);
            throw $e;
        }
    }
    
    /**
     * รัน SQL statements หลายตัว
     * จุดประสงค์: รันคำสั่ง SQL ดิบหลายตัวใน transaction
     * executeMultiple() ควรใช้กับอะไร: เมื่อคุณต้องการรันคำสั่ง SQL หลายตัวพร้อมกันใน transaction
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->executeMultiple([
     *     "CREATE TABLE users (id INT PRIMARY KEY, name VARCHAR(100))",
     *     "ALTER TABLE users ADD COLUMN email VARCHAR(100)"
     * ]);
     * ```
     * 
     * @param array $statements กำหนดคำสั่ง SQL หลายตัวในรูปแบบอาเรย์
     * @return bool คืนค่า true หากรันสำเร็จ, false หากไม่สำเร็จ
     */
    protected function executeMultiple(array $statements): bool
    {
        try {
            // Database::execMultiple จะจัดการ transaction ให้
            $this->db->execMultiple($statements);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Migration Error', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * ตรวจสอบว่าตารางมีอยู่หรือไม่
     * จุดประสงค์: ตรวจสอบว่าตารางที่ระบุมีอยู่ในฐานข้อมูลหรือไม่
     * tableExists() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าตารางมีอยู่ในฐานข้อมูลหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * if ($this->tableExists('users')) {
     *     // ตาราง users มีอยู่
     * }
     * ```
     * 
     * @param string $tableName กำหนดชื่อตารางที่จะตรวจสอบ
     * @return bool คืนค่า true หากตารางมีอยู่, false หากไม่มี
     */
    protected function tableExists(string $tableName): bool
    {
        try {
            $driver = $this->db->getDriverName();
            if ($driver === 'sqlite') {
                return (bool) $this->db->fetchColumn(
                    "SELECT name FROM sqlite_master WHERE type='table' AND name = :name",
                    ['name' => $tableName]
                );
            }
            return (bool) $this->db->fetchColumn("SHOW TABLES LIKE :name", ['name' => $tableName]);
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    /**
     * ตรวจสอบว่าคอลัมน์มีอยู่ในตารางหรือไม่
     * จุดประสงค์: ตรวจสอบว่าคอลัมน์ที่ระบุมีอยู่ในตารางหรือไม่
     * columnExists() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าคอลัมน์มีอยู่ในตารางหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * if ($this->columnExists('users', 'email')) {
     *     // คอลัมน์ email มีอยู่ในตาราง users
     * }
     * ```
     * 
     * @param string $tableName กำหนดชื่อตารางที่จะตรวจสอบ
     * @param string $columnName กำหนดชื่อคอลัมน์ที่จะตรวจสอบ
     * @return bool คืนค่า true หากคอลัมน์มีอยู่, false หากไม่มี
     */
    protected function columnExists(string $tableName, string $columnName): bool
    {
        try {
            $driver = $this->db->getDriverName();
            if ($driver === 'sqlite') {
                $rows = $this->db->fetchAll('PRAGMA table_info(' . $tableName . ')');
                foreach ($rows as $row) {
                    if (($row['name'] ?? '') === $columnName) {
                        return true;
                    }
                }
                return false;
            }
            return (bool) $this->db->fetchColumn(
                "SHOW COLUMNS FROM `{$tableName}` LIKE :col",
                ['col' => $columnName]
            );
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * สร้างตารางโดยไม่ต้องเขียน SQL ด้วยตนเอง
     * จุดประสงค์: ให้วิธีง่ายๆ ในการสร้างตารางในฐานข้อมูลโดยใช้โครงสร้างที่กำหนดผ่าน callback
     * createTable() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างตารางใหม่ในฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->createTable('users', function(Blueprint $table) {
     *     $table->increments('id');
     *     $table->string('name');
     *     $table->string('email')->nullable();
     *     $table->timestamps();
     * });
     * ```
     * 
     * @param string $table กำหนดชื่อตารางที่จะสร้าง
     * @param callable $callback ฟังก์ชันสำหรับกำหนดโครงสร้างตาราง
     * @return bool คืนค่า true หากสร้างตารางสำเร็จ, false หากไม่สำเร็จ
     */
    protected function createTable(string $table, callable $callback): bool
    {
        try {
            Schema::create($this->db, $table, $callback);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('createTable error', ['error' => $e->getMessage(), 'table' => $table]);
            throw $e;
        }
    }

    /**
     * แก้ไขตาราง (เพิ่มคอลัมน์) โดยไม่ต้องเขียน SQL ด้วยตนเอง
     * จุดประสงค์: ให้วิธีง่ายๆ ในการแก้ไขโครงสร้างตารางในฐานข้อมูลโดยใช้โครงสร้างที่กำหนดผ่าน callback
     * table() ควรใช้กับอะไร: เมื่อคุณต้องการแก้ไขตารางที่มีอยู่ในฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->table('users', function(Blueprint $table) {
     *     $table->string('phone')->nullable();
     * });
     * ```
     * 
     * @param string $table กำหนดชื่อตารางที่จะทำการแก้ไข
     * @param callable $callback ฟังก์ชันสำหรับกำหนดการแก้ไขตาราง
     * @return bool คืนค่า true หากแก้ไขตารางสำเร็จ, false หากไม่สำเร็จ
     */
    protected function table(string $table, callable $callback): bool
    {
        try {
            Schema::table($this->db, $table, $callback);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('table alteration error', ['error' => $e->getMessage(), 'table' => $table]);
            throw $e;
        }
    }

    /**
     * ลบตาราง
     * จุดประสงค์: ลบตารางที่ระบุออกจากฐานข้อมูล
     * dropTable() ควรใช้กับอะไร: เมื่อคุณต้องการลบตารางออกจากฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->dropTable('users');
     * ```
     * 
     * @param string $table กำหนดชื่อตารางที่จะลบ
     * @return bool คืนค่า true หากลบตารางสำเร็จ, false หากไม่สำเร็จ
     */
    protected function dropTable(string $table): bool
    {
        try {
            Schema::drop($this->db, $table);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('dropTable error', ['error' => $e->getMessage(), 'table' => $table]);
            throw $e;
        }
    }

    /**
     * ลบคอลัมน์จากตาราง
     * จุดประสงค์: ลบคอลัมน์ที่ระบุออกจากตาราง
     * dropColumn() ควรใช้กับอะไร: เมื่อคุณต้องการลบคอลัมน์ออกจากตาราง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->dropColumn('users', 'phone');
     * ```
     * 
     * @param string $table กำหนดชื่อตารางที่ต้องการลบคอลัมน์
     * @param string $column กำหนดชื่อตัวคอลัมน์ที่ต้องการลบ
     * @return bool คืนค่า true หากลบคอลัมน์สำเร็จ, false หากไม่สำเร็จ
     */
    protected function dropColumn(string $table, string $column): bool
    {
        try {
            Schema::dropColumn($this->db, $table, $column);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('dropColumn error', ['error' => $e->getMessage(), 'table' => $table, 'column' => $column]);
            throw $e;
        }
    }

    /**
     * เปลี่ยนชื่อตาราง
     * จุดประสงค์: เปลี่ยนชื่อตารางจากชื่อเดิมเป็นชื่อใหม่
     * renameTable() ควรใช้กับอะไร: เมื่อคุณต้องการเปลี่ยนชื่อตารางในฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->renameTable('old_table', 'new_table');
     * ```
     * 
     * @param string $from กำหนดชื่อเดิมของตาราง
     * @param string $to กำหนดชื่อใหม่ของตาราง
     * @return bool คืนค่า true หากเปลี่ยนชื่อตารางสำเร็จ, false หากไม่สำเร็จ
     */
    protected function renameTable(string $from, string $to): bool
    {
        try {
            Schema::renameTable($this->db, $from, $to);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('renameTable error', ['error' => $e->getMessage(), 'from' => $from, 'to' => $to]);
            throw $e;
        }
    }
    
    /**
     * ดึงชื่อ migration จากชื่อ class
     * จุดประสงค์: รับชื่อ migration จากชื่อคลาส
     * getName() ควรใช้กับอะไร: เมื่อคุณต้องการรับชื่อ migration ในรูปแบบที่อ่านง่าย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $migrationName = $this->getName();
     * ```
     * 
     * @return string คืนค่าชื่อ migration
     */
    public function getName(): string
    {
        return basename(str_replace('\\', '/', get_class($this)));
    }
}
