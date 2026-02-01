<?php
/**
 * คลาส MigrationRunner สำหรับจัดการการรัน migrations
 * 
 * จุดประสงค์: จัดการการรันและติดตาม migrations
 * MigrationRunner ควรใช้กับอะไร: เมื่อคุณต้องการรัน, rollback, fresh, หรือดูสถานะของ migrations
 * 
 * ฟีเจอร์หลัก:
 * - รัน migrations ที่ยังไม่ได้รัน
 * - ย้อนกลับ migrations ตาม batch
 * - รีเซ็ตฐานข้อมูลทั้งหมดแล้วรัน migrations ใหม่
 * - แสดงสถานะ migrations
 * ตัวอย่างการใช้งาน:
 * 
 * ```php
 * $migrationRunner = new MigrationRunner();
 * $migrationRunner->run(); // รัน migrations ที่ยังไม่ได้รัน
 * ```
 */

namespace App\Core;

use PDOException;

class MigrationRunner
{
    /**
     * การเชื่อมต่อฐานข้อมูล (Database wrapper)
     */
    private Database $db;

    /**
     * Logger สำหรับบันทึกข้อผิดพลาด
     */
    private Logger $logger;

    /**
     * เส้นทางไปยังโฟลเดอร์ migrations
     */
    private string $migrationsPath;

    /**
     * ชื่อตารางสำหรับติดตาม migrations
     */
    private string $migrationsTable = 'migrations';

    /**
     * โมดูลที่ระบุ (ถ้ามี)
     */
    private ?string $modulePath = null;
    
    /**
     * สร้างอินสแตนซ์ของ MigrationRunner
     * จุดประสงค์: เตรียมการเชื่อมต่อฐานข้อมูลและตั้งค่าต่างๆ สำหรับการรัน migrations
     * ตัวอย่างการใช้งาน:
     * ```php
     * $migrationRunner = new MigrationRunner();
     * ```
     * 
     * @param string|null $migrationsPath เส้นทางไปยังโฟลเดอร์ migrations (ถ้าไม่ระบุจะใช้ค่าเริ่มต้น)
     */
    public function __construct(?string $migrationsPath = null)
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
        $this->migrationsPath = $migrationsPath ?? dirname(__DIR__, 2) . '/database/migrations';
        
        $this->createMigrationsTable();
    }
    
    /**
     * กำหนด module ที่จะรัน migration
     * จุดประสงค์: ระบุโมดูลเฉพาะสำหรับรัน migrations
     * setModule() ควรใช้กับอะไร: เมื่อคุณต้องการรัน migrations สำหรับโมดูลเฉพาะ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $migrationRunner->setModule('UserModule')->run();
     * ```
     * 
     * @param string|null $module กำหนดชื่อโมดูล (หรือ null เพื่อรันทุกโมดูล)
     * @return self คืนค่าอินสแตนซ์ของ MigrationRunner เพื่อให้สามารถเรียกใช้แบบ method chaining ได้
     */
    public function setModule(?string $module): self
    {
        $this->modulePath = $module;
        return $this;
    }
    
    /**
     * สร้างตารางสำหรับติดตาม migrations
     * จุดประสงค์: สร้างตาราง migrations ในฐานข้อมูลหากยังไม่มี
     * createMigrationsTable() ควรใช้กับอะไร: เมื่อคุณต้องการเตรียมตารางสำหรับติดตามสถานะของ migrations   
     * ตัวอย่างการใช้งาน:
     * ```php
     * $migrationRunner = new MigrationRunner();
     * $migrationRunner->createMigrationsTable();
     * ```  
     * @return void ไม่คืนค่าอะไร
     */
    private function createMigrationsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                batch INT NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
        
        try {
            $this->db->execRaw($sql);
        } catch (PDOException $e) {
            die("Error creating migrations table: " . $e->getMessage() . "\n");
        }
    }
    
    /**
     * รัน migrations ทั้งหมดที่ยังไม่ได้รัน
     * จุดประสงค์: รัน migrations ที่ยังไม่ถูกนำไปใช้ในฐานข้อมูล
     * run() ควรใช้กับอะไร: เมื่อคุณต้องการรัน migrations ที่ยังไม่ได้รัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $migrationRunner->run();
     * ```
     * 
     * @return array ผลลัพธ์: คืนค่าข้อความสรุปและรายการ migrations ที่ถูกรัน   
     */
    public function run(): array
    {
        $pending = $this->getPendingMigrations();
        
        if (empty($pending)) {
            return ['message' => 'ไม่มี migrations ที่ต้องรัน', 'ran' => []];
        }
        
        $batch = $this->getNextBatchNumber();
        $ran = [];
        
        foreach ($pending as $file) {
            $migrationName = $this->getMigrationName($file);
            
            echo "Migrating: $migrationName ... ";
            
            try {
                $this->runMigration($file);
                $this->recordMigration($migrationName, $batch);
                
                echo "✓ Done\n";
                $ran[] = $migrationName;
            } catch (\Exception $e) {
                echo "✗ Failed\n";
                echo "\n";
                echo "┌─ Error Details ─────────────────────────────────────┐\n";
                echo "│ Migration: $migrationName\n";
                echo "│ Error: " . $e->getMessage() . "\n";
                echo "│ File: " . $e->getFile() . "\n";
                echo "│ Line: " . $e->getLine() . "\n";
                echo "└──────────────────────────────────────────────────────┘\n";
                echo "\n";
                
                // Log error
                $this->logger->error('Migration failed', [
                    'migration' => $migrationName,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                
                break;
            }
        }
        
        return [
            'message' => count($ran) . ' migrations ran successfully.',
            'ran' => $ran
        ];
    }
    
    /**
     * Rollback migrations batch ล่าสุด
     * จุดประสงค์: ย้อนกลับ migrations ตามจำนวน batch ที่ระบุ
     * rollback() ควรใช้กับอะไร: เมื่อคุณต้องการย้อนกลับ migrations ที่ถูกรันล่าสุด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $migrationRunner->rollback(2);
     * ```
     * 
     * @param int $steps กำหนดจำนวน batch ที่ต้องการ rollback
     * @return array คืนค่าข้อความสรุปและรายการ migrations ที่ถูก rollback
     */
    public function rollback(int $steps = 1): array
    {
        $rolledBack = [];
        
        for ($i = 0; $i < $steps; $i++) {
            $batch = $this->getLastBatchNumber();
            
            if ($batch === 0) {
                break;
            }
            
            $migrations = $this->getMigrationsByBatch($batch);
            
            foreach (array_reverse($migrations) as $migration) {
                echo "Rolling back: {$migration['migration']} ... ";
                
                try {
                    $file = $this->findMigrationFile($migration['migration']);
                    
                    if ($file) {
                        $this->rollbackMigration($file);
                        $this->removeMigrationRecord($migration['migration']);
                        
                        echo "✓ Done\n";
                        $rolledBack[] = $migration['migration'];
                    } else {
                        echo "✗ File not found\n";
                        echo "⚠ Migration file is missing: {$migration['migration']}.php\n";
                        
                        // Log warning
                        $this->logger->warning('Migration file not found for rollback', [
                            'migration' => $migration['migration'],
                        ]);
                    }
                } catch (\Exception $e) {
                    echo "✗ Failed\n";
                    echo "Error: " . $e->getMessage() . "\n";
                    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
                    
                    // Log error
                    $this->logger->error('Rollback failed', [
                        'migration' => $migration['migration'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
        
        return [
            'message' => count($rolledBack) . ' migrations rolled back.',
            'rolled_back' => $rolledBack
        ];
    }
    
    /**
     * รีเซ็ตฐานข้อมูลทั้งหมดแล้วรันใหม่
     * จุดประสงค์: ลบตารางทั้งหมดในฐานข้อมูลแล้วรัน migrations ใหม่ทั้งหมด
     * fresh() ควรใช้กับอะไร: เมื่อคุณต้องการรีเซ็ตฐานข้อมูลและเริ่มต้นใหม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $migrationRunner->fresh();
     * ```
     * 
     * @return array คืนค่ารายการ migrations ที่ถูกรันใหม่
     */
    public function fresh(): array
    {
        echo "Dropping all tables...\n";
        $this->dropAllTables();

        // หลังจากลบตารางทั้งหมด จะต้องสร้างตารางสำหรับติดตาม migrations ขึ้นมาใหม่
        // มิฉะนั้นคำสั่ง run() จะพยายามอ่านจากตารางที่ถูกลบแล้วและเกิดข้อผิดพลาด
        $this->createMigrationsTable();

        echo "\nRunning migrations...\n";
        return $this->run();
    }
    
    /**
     * แสดงสถานะ migrations
     * จุดประสงค์: แสดงรายการ migrations ทั้งหมดพร้อมสถานะการรัน
     * status() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบสถานะของ migrations
     * ตัวอย่างการใช้งาน:
     * ```php
     * $status = $migrationRunner->status();
     * ```
     * 
     * @return array คืนค่ารายการ migrations พร้อมสถานะการรัน
     */
    public function status(): array
    {
        $ran = $this->getRanMigrations();
        $all = $this->getAllMigrationFiles();
        
        $status = [];
        
        foreach ($all as $file) {
            $name = $this->getMigrationName($file);
            $status[] = [
                'migration' => $name,
                'ran' => in_array($name, array_column($ran, 'migration')),
                'batch' => $this->getMigrationBatch($name)
            ];
        }
        
        return $status;
    }
    
    /**
     * รัน migration จากไฟล์
     * จุดประสงค์: รัน migration ที่ระบุในไฟล์                         
     * runMigration() ควรใช้กับอะไร: เมื่อคุณต้องการรัน migration จากไฟล์เฉพาะ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->runMigration('2024_01_01_000001_create_users_table.php');
     * ```
     * 
     * @param string $file กำหนดเส้นทางไปยังไฟล์ migration  
     * @return void ไม่คืนค่าอะไร
     */
    private function runMigration(string $file): void
    {
        $migration = $this->loadMigration($file);
        $migration->up();
    }
    
    /**
     * ย้อนกลับ migration จากไฟล์
     * จุดประสงค์: ย้อนกลับ migration ที่ระบุในไฟล์
     * rollbackMigration() ควรใช้กับอะไร: เมื่อคุณต้องการย้อนกลับ migration จากไฟล์เฉพาะ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->rollbackMigration('2024_01_01_000001_create_users_table.php');
     * ```
     * 
     * @param string $file กำหนดเส้นทางไปยังไฟล์ migration  
     * @return void ไม่คืนค่าอะไร
     */
    private function rollbackMigration(string $file): void
    {
        $migration = $this->loadMigration($file);
        $migration->down();
    }
    
    /**
     * โหลด migration class
     * จุดประสงค์: โหลดและสร้างอินสแตนซ์ของ migration จากไฟล์
     * loadMigration() ควรใช้กับอะไร: เมื่อคุณต้องการโหลด migration จากไฟล์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $migration = $this->loadMigration('2024_01_01_000001_create_users_table.php');
     * ```
     * 
     * @param string $file กำหนดเส้นทางไปยังไฟล์ migration  
     * @return Migration คืนค่าอินสแตนซ์ของ migration class
     */
    private function loadMigration(string $file): Migration
    {
        require_once $file;
        
        $className = $this->getClassNameFromFile($file);
        
        if (!class_exists($className)) {
            throw new \Exception("Migration class $className not found in $file");
        }
        
        return new $className();
    }
    
    /**
     * ดึงชื่อ class จากไฟล์
     * จุดประสงค์: ดึงชื่อ class ที่ประกาศในไฟล์ migration
     * getClassNameFromFile() ควรใช้กับอะไร: เมื่อคุณต้องการดึงชื่อ class จากไฟล์ migration
     * ตัวอย่างการใช้งาน:
     * ```php
     * $className = $this->getClassNameFromFile('2024_01_01_000001_create_users_table.php');
     * ```
     * 
     * @param string $file กำหนดเส้นทางไปยังไฟล์ migration  
     * @return string คืนค่าชื่อ class พร้อม namespace
     */
    private function getClassNameFromFile(string $file): string
    {
        $content = file_get_contents($file);
        
        // หา namespace
        preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch);
        $namespace = $namespaceMatch[1] ?? '';
        
        // หาชื่อ class
        preg_match('/class\s+(\w+)/', $content, $classMatch);
        $className = $classMatch[1] ?? '';
        
        return $namespace ? "$namespace\\$className" : $className;
    }
    
    /**
     * ดึงรายการ migrations ที่ยังไม่ได้รัน
     * จุดประสงค์: คืนค่ารายการไฟล์ migration ที่ยังไม่ได้ถูกรัน
     * getPendingMigrations() ควรใช้กับอะไร: เมื่อคุณต้องการดึงรายการ migrations ที่ยังไม่ได้รัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $pending = $this->getPendingMigrations();
     * ```
     * 
     * @return array คืนค่ารายการไฟล์ migration ที่ยังไม่ได้รัน
     */
    private function getPendingMigrations(): array
    {
        $ran = $this->getRanMigrations();
        $ranNames = array_column($ran, 'migration');
        
        $all = $this->getAllMigrationFiles();
        
        return array_filter($all, function($file) use ($ranNames) {
            return !in_array($this->getMigrationName($file), $ranNames);
        });
    }
    
    /**
     * ดึงรายการไฟล์ migration ทั้งหมด
     * จุดประสงค์: คืนค่ารายการไฟล์ migration ทั้งหมดในระบบ
     * getAllMigrationFiles() ควรใช้กับอะไร: เมื่อคุณต้องการดึงรายการไฟล์ migration ทั้งหมด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $allFiles = $this->getAllMigrationFiles();
     * ```
     * 
     * @return array คืนค่ารายการไฟล์ migration ทั้งหมด
     */
    private function getAllMigrationFiles(): array
    {
        $files = [];
        
        if ($this->modulePath) {
            // รันเฉพาะ module ที่ระบุ
            $path = $this->migrationsPath . '/' . $this->modulePath;
            if (is_dir($path)) {
                $files = glob($path . '/*.php');
            }
        } else {
            // รันทุก module
            $files = glob($this->migrationsPath . '/*.php') ?: [];
            
            // สแกน subdirectories
            $directories = glob($this->migrationsPath . '/*', GLOB_ONLYDIR);
            foreach ($directories as $dir) {
                $moduleFiles = glob($dir . '/*.php');
                if ($moduleFiles) {
                    $files = array_merge($files, $moduleFiles);
                }
            }
        }
        
        // เรียงลำดับตามชื่อไฟล์ (basename) ไม่ใช่ full path
        usort($files, function($a, $b) {
            return strcmp(basename($a), basename($b));
        });
        
        return $files ?: [];
    }
    
    /**
     * ดึงชื่อ migration จากไฟล์
     * จุดประสงค์: คืนค่าชื่อ migration จากไฟล์
     * getMigrationName() ควรใช้กับอะไร: เมื่อคุณต้องการดึงชื่อ migration จากไฟล์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $name = $this->getMigrationName('2024_01_01_000001_create_users_table.php');
     * ```
     * 
     * @param string $file กำหนดเส้นทางไปยังไฟล์ migration
     * @return string คืนค่าชื่อ migration
     */
    private function getMigrationName(string $file): string
    {
        return basename($file, '.php');
    }
    
    /**
     * ดึงรายการ migrations ที่รันแล้ว
     * จุดประสงค์: คืนค่ารายการ migrations ที่ถูกรันแล้วจากฐานข้อมูล
     * getRanMigrations() ควรใช้กับอะไร: เมื่อคุณต้องการดึงรายการ migrations ที่ถูกรันแล้ว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $ranMigrations = $this->getRanMigrations();
     * ```
     * 
     * @return array คืนค่ารายการ migrations ที่ถูกรันแล้ว
     */
    private function getRanMigrations(): array
    {
        return $this->db->fetchAll("SELECT * FROM {$this->migrationsTable} ORDER BY id");
    }
    
    /**
     * ดึง migrations ตาม batch
     * จุดประสงค์: คืนค่ารายการ migrations ใน batch ที่ระบุ
     * getMigrationsByBatch() ควรใช้กับอะไร: เมื่อคุณต้องการดึงรายการ migrations ตาม batch
     * ตัวอย่างการใช้งาน:
     * ```php
     * $migrations = $this->getMigrationsByBatch(2);
     * ```
     * 
     * @param int $batch กำหนดหมายเลข batch
     * @return array คืนค่ารายการ migrations ใน batch ที่ระบุ
     */
    private function getMigrationsByBatch(int $batch): array
    {
        return $this->db->fetchAll("SELECT * FROM {$this->migrationsTable} WHERE batch = ? ORDER BY id", [$batch]);
    }
    
    /**
     * ดึงเลข batch ล่าสุด
     * จุดประสงค์: คืนค่าเลข batch ล่าสุดที่รัน
     * getLastBatchNumber() ควรใช้กับอะไร: เมื่อคุณต้องการทราบเลข batch ล่าสุด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $lastBatch = $this->getLastBatchNumber();
     * ```
     * 
     * @return int คืนค่าเลข batch ล่าสุดที่รัน
     */
    private function getLastBatchNumber(): int
    {
        $result = $this->db->fetch("SELECT MAX(batch) as batch FROM {$this->migrationsTable}");
        return (int) ($result['batch'] ?? 0);
    }
    
    /**
     * ดึงเลข batch ถัดไป
     * จุดประสงค์: คืนค่าเลข batch ถัดไปที่จะใช้ในการรัน migrations
     * getNextBatchNumber() ควรใช้กับอะไร: เมื่อคุณต้องการทราบเลข batch ถัดไป
     * ตัวอย่างการใช้งาน:
     * ```php
     * $nextBatch = $this->getNextBatchNumber();
     * ```
     * 
     * @return int คืนค่าเลข batch ถัดไปที่จะใช้ในการรัน migrations
     */
    private function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }
    
    /**
     * ดึงเลข batch ของ migration
     * จุดประสงค์: คืนค่าเลข batch ของ migration ที่ระบุ
     * getMigrationBatch() ควรใช้กับอะไร: เมื่อคุณต้องการทราบเลข batch ของ migration ที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $batch = $this->getMigrationBatch('2024_01_01_000001_create_users_table');
     * ```
     * 
     * @param string $name กำหนดชื่อ migration
     * @return int|null คืนค่าเลข batch ของ migration หรือ null หากไม่พบ
     */
    private function getMigrationBatch(string $name): ?int
    {
        $result = $this->db->fetch("SELECT batch FROM {$this->migrationsTable} WHERE migration = ?", [$name]);
        return $result ? (int) $result['batch'] : null;
    }
    
    /**
     * บันทึก migration ที่รันแล้ว
     * จุดประสงค์: บันทึกชื่อ migration และ batch ลงในตาราง migrations
     * recordMigration() ควรใช้กับอะไร: เมื่อคุณต้องการบันทึก migration ที่รันแล้ว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->recordMigration('2024_01_01_000001_create_users_table', 1);
     * ```
     * 
     * @param string $migration กำหนดชื่อ migration
     * @param int $batch กำหนดหมายเลข batch
     * @return void ไม่คืนค่าอะไร
     */
    private function recordMigration(string $migration, int $batch): void
    {
        $this->db->execute("INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)", [$migration, $batch]);
    }
    
    /**
     * ลบบันทึก migration
     * จุดประสงค์: ลบชื่อ migration ออกจากตาราง migrations
     * removeMigrationRecord() ควรใช้กับอะไร: เมื่อคุณต้องการลบบันทึก migration ที่ถูก rollback
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->removeMigrationRecord('2024_01_01_000001_create_users_table');
     * ```
     * 
     * @param string $migration กำหนดชื่อ migration
     * @return void ไม่คืนค่าอะไร
     */
    private function removeMigrationRecord(string $migration): void
    {
        $this->db->execute("DELETE FROM {$this->migrationsTable} WHERE migration = ?", [$migration]);
    }
    
    /**
     * หาไฟล์ migration
     * จุดประสงค์: ค้นหาไฟล์ migration ตามชื่อ
     * findMigrationFile() ควรใช้กับอะไร: เมื่อคุณต้องการค้นหาไฟล์ migration ตามชื่อ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $file = $this->findMigrationFile('2024_01_01_000001_create_users_table');
     * ```
     * 
     * @param string $name กำหนดชื่อ migration
     * @return string|null คืนค่า path ของไฟล์ migration หรือ null หากไม่พบ
     */
    private function findMigrationFile(string $name): ?string
    {
        // ค้นหาไฟล์ใน path หลัก
        $file = $this->migrationsPath . '/' . $name . '.php';
        if (file_exists($file)) {
            return $file;
        }
        
        // ค้นหาใน subdirectories
        $directories = glob($this->migrationsPath . '/*', GLOB_ONLYDIR);
        foreach ($directories as $dir) {
            $file = $dir . '/' . $name . '.php';
            if (file_exists($file)) {
                return $file;
            }
        }
        
        return null;
    }
    
    /**
     * ลบตารางทั้งหมด
     * จุดประสงค์: ลบตารางทั้งหมดในฐานข้อมูล
     * dropAllTables() ควรใช้กับอะไร: เมื่อคุณต้องการลบตารางทั้งหมดในฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->dropAllTables();
     * ```
     * 
     * @return void ไม่คืนค่าอะไร
     */
    private function dropAllTables(): void
    {
        // Disable foreign key checks
        $this->db->execRaw("SET FOREIGN_KEY_CHECKS = 0");

        // Get all tables
        $tables = $this->db->fetchAll("SHOW TABLES");

        // Drop each table
        foreach ($tables as $table) {
            echo "Dropping table: $table\n";
            $this->db->execRaw("DROP TABLE IF EXISTS `$table`");
        }

        // Re-enable foreign key checks
        $this->db->execRaw("SET FOREIGN_KEY_CHECKS = 1");
    }
}
