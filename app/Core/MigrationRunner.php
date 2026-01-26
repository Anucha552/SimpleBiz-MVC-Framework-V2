<?php
/**
 * Migration Runner
 * 
 * จุดประสงค์: จัดการการรันและติดตาม migrations
 */

namespace App\Core;

use PDO;
use PDOException;

class MigrationRunner
{
    private PDO $db;
    private Logger $logger;
    private string $migrationsPath;
    private string $migrationsTable = 'migrations';
    private ?string $modulePath = null;
    
    public function __construct(?string $migrationsPath = null)
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
        $this->migrationsPath = $migrationsPath ?? dirname(__DIR__, 2) . '/database/migrations';
        
        $this->createMigrationsTable();
    }
    
    /**
     * กำหนด module ที่จะรัน migration
     * 
     * @param string|null $module
     * @return self
     */
    public function setModule(?string $module): self
    {
        $this->modulePath = $module;
        return $this;
    }
    
    /**
     * สร้างตารางสำหรับติดตาม migrations
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
            $this->db->exec($sql);
        } catch (PDOException $e) {
            die("Error creating migrations table: " . $e->getMessage() . "\n");
        }
    }
    
    /**
     * รัน migrations ทั้งหมดที่ยังไม่ได้รัน
     * 
     * @return array
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
     * 
     * @param int $steps จำนวน batch ที่ต้องการ rollback
     * @return array
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
     * 
     * @return array
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
     * 
     * @return array
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
     * 
     * @param string $file
     */
    private function runMigration(string $file): void
    {
        $migration = $this->loadMigration($file);
        $migration->up();
    }
    
    /**
     * ย้อนกลับ migration จากไฟล์
     * 
     * @param string $file
     */
    private function rollbackMigration(string $file): void
    {
        $migration = $this->loadMigration($file);
        $migration->down();
    }
    
    /**
     * โหลด migration class
     * 
     * @param string $file
     * @return Migration
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
     * 
     * @param string $file
     * @return string
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
     * 
     * @return array
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
     * 
     * @return array
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
     * 
     * @param string $file
     * @return string
     */
    private function getMigrationName(string $file): string
    {
        return basename($file, '.php');
    }
    
    /**
     * ดึงรายการ migrations ที่รันแล้ว
     * 
     * @return array
     */
    private function getRanMigrations(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->migrationsTable} ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * ดึง migrations ตาม batch
     * 
     * @param int $batch
     * @return array
     */
    private function getMigrationsByBatch(int $batch): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->migrationsTable} WHERE batch = ? ORDER BY id");
        $stmt->execute([$batch]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * ดึงเลข batch ล่าสุด
     * 
     * @return int
     */
    private function getLastBatchNumber(): int
    {
        $stmt = $this->db->query("SELECT MAX(batch) as batch FROM {$this->migrationsTable}");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['batch'] ?? 0);
    }
    
    /**
     * ดึงเลข batch ถัดไป
     * 
     * @return int
     */
    private function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }
    
    /**
     * ดึงเลข batch ของ migration
     * 
     * @param string $name
     * @return int|null
     */
    private function getMigrationBatch(string $name): ?int
    {
        $stmt = $this->db->prepare("SELECT batch FROM {$this->migrationsTable} WHERE migration = ?");
        $stmt->execute([$name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int) $result['batch'] : null;
    }
    
    /**
     * บันทึก migration ที่รันแล้ว
     * 
     * @param string $migration
     * @param int $batch
     */
    private function recordMigration(string $migration, int $batch): void
    {
        $stmt = $this->db->prepare("INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)");
        $stmt->execute([$migration, $batch]);
    }
    
    /**
     * ลบบันทึก migration
     * 
     * @param string $migration
     */
    private function removeMigrationRecord(string $migration): void
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->migrationsTable} WHERE migration = ?");
        $stmt->execute([$migration]);
    }
    
    /**
     * หาไฟล์ migration
     * 
     * @param string $name
     * @return string|null
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
     */
    private function dropAllTables(): void
    {
        // Disable foreign key checks
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Get all tables
        $stmt = $this->db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Drop each table
        foreach ($tables as $table) {
            echo "Dropping table: $table\n";
            $this->db->exec("DROP TABLE IF EXISTS `$table`");
        }
        
        // Re-enable foreign key checks
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
}
