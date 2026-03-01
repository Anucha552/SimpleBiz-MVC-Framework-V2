<?php
/**
 * คลาสการเชื่อมต่อฐานข้อมูล จาก Core
 * 
 * จุดประสงค์: จัดเตรียมการเชื่อมต่อฐานข้อมูล PDO ที่ปลอดภัยโดยใช้รูปแบบ singleton
 * ความปลอดภัย: ใช้ prepared statements เท่านั้นเพื่อป้องกัน SQL injection
 * Database ควรใช้กับอะไร: เมื่อใดก็ตามที่คุณต้องการเชื่อมต่อกับฐานข้อมูล MySQL ในแอปพลิเคชันของคุณ
 * 
 * ทำไมต้อง Singleton
 * - รับประกันว่ามีการเชื่อมต่อฐานข้อมูลเพียงหนึ่งเดียวตลอดช่วงชีวิตแอปพลิเคชัน
 * - ลดภาระการเชื่อมต่อและการใช้ทรัพยากร
 * - รวมศูนย์การจัดการการเชื่อมต่อ
 * 
 * กฎความปลอดภัย:
 * - ห้ามใช้การต่อสตริงสำหรับคำสั่ง query
 * - ต้องใช้ prepared statements กับพารามิเตอร์ที่ผูกไว้เสมอ
 * - ปิดการใช้งาน emulated prepares เพื่อใช้ prepared statements จริง
 * - ตั้งค่าโหมดข้อผิดพลาดให้แสดง exceptions เพื่อการดีบักที่ดีขึ้น
 * 
 * ตัวอย่างการใช้งานโดยรวม:
 * ```php
 * $db = Database::getInstance();
 * $stmt = $db->query("SELECT * FROM users WHERE id = :id", ['id' => $userId]);
 * $user = $stmt->fetch();
 * ```
 */

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use App\Core\Logger;
use App\Core\Config;

class Database
{
    /**
     * return $this->getConnection()->prepare($sql);
     * จุดประสงค์: อินสแตนซ์ singleton ของ Database
     */
    private static ?Database $instance = null;
    
    /**
     * อ็อบเจ็กต์การเชื่อมต่อ PDO สำหรับฐานข้อมูล
     */
    private ?PDO $connection = null;

    /** 
     * Logger สำหรับบันทึกคำสั่ง query (ถ้ามี)
     */
    private ?Logger $logger = null;

    /** 
     * เปิด/ปิดการบันทึกคำสั่ง query
     */
    private bool $queryLoggingEnabled = false;

    /** 
     * บันทึกคำสั่ง query ที่ดำเนินการ
     */
    private array $queryLog = [];

    /**
     * เตรียมคำสั่ง SQL
     * จุดประสงค์: สร้างคำสั่ง SQL ที่เตรียมไว้เพื่อใช้กับการดำเนินการฐานข้อมูล
     * prepare() ควรใช้กับอะไร: เมื่อคุณต้องการเตรียมคำสั่ง SQL ที่ปลอดภัยโดยใช้ prepared statements
     * ตัวอย่างการใช้งาน:
     * ```php
     * $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
     * ```
     *
     * @param string $sql กำหนดคำสั่ง SQL ที่จะเตรียม
     * @return PDOStatement คืนค่าอ็อบเจ็กต์ PDOStatement ที่เตรียมไว้
     */
    public function prepare(string $sql)
    {
        return $this->connection->prepare($sql);
    }

    /**
     * เปิดหรือปิดการบันทึกคำสั่ง query
     * จุดประสงค์: เปิดหรือปิดฟีเจอร์การบันทึกคำสั่ง query ที่ดำเนินการ
     * enableQueryLog() ควรใช้กับอะไร: เมื่อคุณต้องการเปิดหรือปิดการบันทึกคำสั่ง query
     * ตัวอย่างการใช้งาน:
     * ```php
     * $db->enableQueryLog(true); // เปิดการบันทึก
     * ```
     *
     * @param bool $enable กำหนด true เพื่อเปิดการบันทึก, false เพื่อปิด
     * @return void ไม่คืนค่าอะไร
     */
    public function enableQueryLog(bool $enable = true): void
    {
        $this->queryLoggingEnabled = $enable;
        if ($this->logger === null) {
            $this->logger = new Logger();
        }
    }

    /**
     * ตรวจสอบว่าการบันทึกคำสั่ง query เปิดอยู่หรือไม่
     * จุดประสงค์: ตรวจสอบสถานะของฟีเจอร์การบันทึกคำสั่ง query
     * isQueryLogEnabled() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าการบันทึกคำสั่ง query เปิดอยู่หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * if ($db->isQueryLogEnabled()) {
     *     // การบันทึกคำสั่ง query เปิดอยู่
     * }
     * ```
     *
     * @return bool คืนค่า true หากการบันทึกเปิดอยู่, false หากปิดอยู่
     */
    public function isQueryLogEnabled(): bool
    {
        return $this->queryLoggingEnabled;
    }

    /**
     * ดึงบันทึกคำสั่ง query ที่ดำเนินการ
     * จุดประสงค์: รับรายการของคำสั่ง query ที่ถูกบันทึก
     * getQueryLog() ควรใช้กับอะไร: เมื่อคุณต้องการดึงบันทึกคำสั่ง query ที่ดำเนินการ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $log = $db->getQueryLog();
     * ```
     *
     * @return array คืนค่าอาร์เรย์ของบันทึกคำสั่ง query
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * ล้างบันทึกคำสั่ง query
     * จุดประสงค์: ล้างรายการของคำสั่ง query ที่ถูกบันทึก
     * clearQueryLog() ควรใช้กับอะไร: เมื่อคุณต้องการล้างบันทึกคำสั่ง query
     * ตัวอย่างการใช้งาน:
     * ```php
     * $db->clearQueryLog();
     * ```
     *
     * @return void ไม่คืนค่าอะไร
     */
    public function clearQueryLog(): void
    {
        $this->queryLog = [];
    }

    /**
     * บันทึกคำสั่ง query ที่ดำเนินการ
     * จุดประสงค์: บันทึกรายละเอียดของคำสั่ง query ที่ดำเนินการ
     * recordQuery() ควรใช้กับอะไร: ใช้ภายในคลาสเพื่อบันทึกคำสั่ง query เมื่อการบันทึกเปิดอยู่
     * ตัวอย่างการใช้งาน:
     * ```php
     * // ใช้ภายในคลาส Database เท่านั้น
     * $this->recordQuery($sql, $params, $durationMs);
     * ```
     * 
     * @param string $sql กำหนดคำสั่ง SQL ที่ดำเนินการ
     * @param array $params กำหนดอาร์เรย์ของพารามิเตอร์ที่ผูกกับคำสั่ง SQL
     * @param float $durationMs กำหนดระยะเวลาที่ใช้ในการดำเนินการเป็นมิลลิวินาที
     * @return void ไม่คืนค่าอะไร
     */
    private function recordQuery(string $sql, array $params, float $durationMs): void
    {
        if (!$this->queryLoggingEnabled) {
            return;
        }
        $entry = [
            'sql' => $sql,
            'params' => $params,
            'time_ms' => $durationMs,
            'ts' => microtime(true),
        ];
        $this->queryLog[] = $entry;
        if ($this->logger) {
            $this->logger->info('db.query', $entry);
        }
    }

    /**
     * Lazily initialize and return Logger instance.
     *
     * @return Logger
     */
    private function getLogger(): Logger
    {
        if ($this->logger === null) {
            $this->logger = new Logger();
        }
        return $this->logger;
    }

    /**
     * คอนสตรัคเตอร์แบบ private เพื่อป้องกันการสร้างอินสแตนซ์โดยตรง
     * บังคับใช้รูปแบบ singleton
     */
    private function __construct()
    {
        $this->connect();
    }

    /**
     * ดึงอินสแตนซ์ singleton
     * จุดประสงค์: รับอินสแตนซ์เดียวของคลาส Database
     * getInstance() ควรใช้กับอะไร: เมื่อคุณต้องการรับอินสแตนซ์ของ Database เพื่อเชื่อมต่อกับฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $db = Database::getInstance();
     * ```
     * 
     * @return Database คืนค่าอินสแตนซ์ของ Database
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * สร้างการเชื่อมต่อฐานข้อมูล
     * จุดประสงค์: สร้างการเชื่อมต่อฐานข้อมูล PDO โดยใช้การกำหนดค่าจากไฟล์ config/database.php
     * connect() ควรใช้กับอะไร: ใช้ภายในคลาส Database เท่านั้นเพื่อสร้างการเชื่อมต่อฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * // ใช้ภายในคลาส Database เท่านั้น
     * $this->connect();
     * ```
     *
     * @return void ไม่คืนค่าอะไร
     */
    private function connect(): void
    {
        // โหลดการกำหนดค่าฐานข้อมูล
        $config = require __DIR__ . '/../../config/database.php';

        $driver = $config['connection'] ?? 'mysql';
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $dbname = $config['database'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        // สร้าง DSN สำหรับการเชื่อมต่อ PDO
        if ($driver === 'sqlite') {
            // สำหรับ SQLite เราจะใช้ชื่อไฟล์เป็นฐานข้อมูล
            $path = $dbname !== '' ? $dbname : 'storage/database.sqlite';
            
            // ตรวจสอบและสร้างไฟล์ฐานข้อมูล SQLite ถ้ายังไม่มี
            if (!preg_match('/^([A-Za-z]:\\|\/)/', $path)) {
                $root = dirname(__DIR__, 2);
                $path = $root . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
            }

            // ตรวจสอบว่าไดเรกทอรีที่เก็บไฟล์ SQLite สามารถเขียนได้หรือไม่
            $dir = dirname($path);

            // สร้างไดเรกทอรีถ้ายังไม่มี และตรวจสอบสิทธิ์การเขียน
            if (!is_dir($dir)) {
                if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                    throw new PDOException("SQLite directory not writable: {$dir}");
                }
            }

            // สร้างไฟล์ฐานข้อมูล SQLite ถ้ายังไม่มี และตรวจสอบสิทธิ์การเขียน
            if (!file_exists($path)) {
                @touch($path);
            }

            // ตรวจสอบว่าไฟล์ฐานข้อมูล SQLite สามารถเขียนได้หรือไม่
            if (!is_writable($path)) {
                throw new PDOException("SQLite database file not writable: {$path}");
            }

            // DSN สำหรับ SQLite
            $dsn = 'sqlite:' . $path;
            $username = '';
            $password = '';
        } else {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
        }

        try {
            // สร้างการเชื่อมต่อ PDO ด้วยการตั้งค่าความปลอดภัยที่เหมาะสม
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // Real prepared statements
                PDO::ATTR_STRINGIFY_FETCHES => false, // Preserve data types
            ]);

            // สำหรับ SQLite ให้เปิดใช้งาน foreign key constraints
            if ($driver === 'sqlite') {
                $this->connection->exec('PRAGMA foreign_keys = ON');
            }
        } catch (PDOException $e) {
            // ในโหมดโปรดักชัน ให้บันทึกข้อผิดพลาดนี้แทนการแสดงผล
            if (Config::get('app.env', 'development') === 'production') {
                error_log("Database connection failed: " . $e->getMessage());
                throw new PDOException("Database connection failed");
            } else {
                throw $e;
            }
        }
    }

    /**
     * ดึงการเชื่อมต่อ PDO ดิบ
     * จุดประสงค์: รับอ็อบเจ็กต์การเชื่อมต่อ PDO ดิบ
     * getConnection() ควรใช้กับอะไร: เมื่อคุณต้องการเข้าถึงอ็อบเจ็กต์ PDO ดิบเพื่อใช้ฟีเจอร์ขั้นสูงที่ไม่ได้ห่อหุ้มในคลาส Database
     * ตัวอย่างการใช้งาน:
     * ```php
     * $pdo = $db->getConnection();
     * ```
     * 
     * @return PDO คืนค่าอ็อบเจ็กต์การเชื่อมต่อ PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * ดึงอ็อบเจ็กต์ PDO ดิบ
     * จุดประสงค์: รับอ็อบเจ็กต์ PDO ดิบ (เหมือนกับ getConnection)
     * getPdo() ควรใช้กับอะไร: เมื่อคุณต้องการเข้าถึงอ็อบเจ็กต์ PDO ดิบเพื่อใช้ฟีเจอร์ขั้นสูงที่ไม่ได้ห่อหุ้มในคลาส Database
     * ตัวอย่างการใช้งาน:
     * ```php
     * $pdo = $db->getPdo();
     * ```
     * 
     * @return PDO คืนค่าอ็อบเจ็กต์การเชื่อมต่อ PDO
     */
    public function getPdo(): \PDO
    {
        return $this->getConnection();
    }

    /**
     * ดึงชื่อ driver ของ PDO (เช่น mysql, sqlite)
     * จุดประสงค์: รับชื่อ driver ของ PDO ที่กำลังใช้งานอยู่ เพื่อใช้ในการปรับแต่งการทำงานตาม driver ที่ใช้งาน
     * getDriverName() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบหรือปรับแต่งการทำงานตาม driver ของฐานข้อมูลที่กำลังใช้งาน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $driver = $db->getDriverName();
     * ```
     * 
     * @return string คืนค่าไดรเวอร์ของ PDO ที่กำลังใช้งานอยู่ (เช่น 'mysql', 'sqlite') หรือ 'mysql' เป็นค่าเริ่มต้นถ้าไม่สามารถดึงได้
     */
    public function getDriverName(): string
    {
        return $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME) ?? 'mysql';
    }

    /**
     * สร้างและดำเนินการคำสั่ง SQL ที่เตรียมไว้
     * จุดประสงค์: ดำเนินการคำสั่ง SQL ที่ปลอดภัยโดยใช้ prepared statements
     * query() ควรใช้กับอะไร: เมื่อคุณต้องการดำเนินการคำสั่ง SQL ที่ปลอดภัยโดยใช้ prepared statements
     * ตัวอย่างการใช้งาน:
     * ```php
     * $stmt = $db->query("SELECT * FROM users WHERE id = :id", ['id' => $userId]);
     * ```
     *
     * @param string $sql กำหนดคำสั่ง SQL ที่จะดำเนินการ
     * @param array $params กำหนดอาร์เรย์ของพารามิเตอร์ที่จะผูกกับคำสั่ง SQL
     * @return PDOStatement คืนค่าอ็อบเจ็กต์ PDOStatement ที่ดำเนินการแล้ว
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $start = microtime(true);
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $duration = (microtime(true) - $start) * 1000.0;
            $this->recordQuery($sql, $params, $duration);
            if ($duration > 500.0) {
                $this->getLogger()->warning('db.slow_query', [
                    'sql' => $sql,
                    'params' => $params,
                    'duration_ms' => $duration,
                ]);
            }
            return $stmt;
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $start) * 1000.0;
            $this->getLogger()->error('db.query_error', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
            ]);
            throw $e;
        }
    }

    /**
     * Fetch สำหรับแถวเดียว
     * จุดประสงค์: ดึงแถวเดียวจากผลลัพธ์ของคำสั่ง SQL ที่เตรียมไว้
     * fetch() ควรใช้กับอะไร: เมื่อคุณต้องการดึงแถวเดียวจากผลลัพธ์ของคำสั่ง SQL
     * ตัวอย่างการใช้งาน:
     * ```php
     * $user = $db->fetch("SELECT * FROM users WHERE id = :id", ['id' => $userId]);
     * ```
     *
     * @param string $sql กำหนดคำสั่ง SQL ที่จะดำเนินการ
     * @param array $params กำหนดอาร์เรย์ของพารามิเตอร์ที่จะผูกกับคำสั่ง SQL
     * @return mixed คืนค่าแถวเดียวหรือ false ถ้าไม่มีข้อมูล
     */
    public function fetch(string $sql, array $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Fetch all rows.
     * จุดประสงค์: ดึงแถวทั้งหมดจากผลลัพธ์ของคำสั่ง SQL ที่เตรียมไว้
     * fetchAll() ควรใช้กับอะไร: เมื่อคุณต้องการดึงแถวทั้งหมดจากผลลัพธ์ของคำสั่ง SQL
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = $db->fetchAll("SELECT * FROM users WHERE status = :status", ['status' => 'active']);
     * ```
     * 
     * @param string $sql กำหนดคำสั่ง SQL ที่จะดำเนินการ
     * @param array $params กำหนดอาร์เรย์ของพารามิเตอร์ที่จะผูกกับคำสั่ง SQL
     * @return array คืนค่าอาร์เรย์ของแถวทั้งหมด
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Fetch all rows as class instances.
     * จุดประสงค์: ดึงข้อมูลทั้งหมดและแมปเป็นอ็อบเจ็กต์ของคลาสที่กำหนด
     *
     * @param string $sql กำหนดคำสั่ง SQL ที่จะดำเนินการ
     * @param array $params กำหนดอาร์เรย์ของพารามิเตอร์ที่จะผูกกับคำสั่ง SQL
     * @param string $class กำหนดชื่อคลาสที่ต้องการแมปผลลัพธ์
     * @return array คืนค่าอาร์เรย์ของอ็อบเจ็กต์คลาสที่กำหนด
     */
    public function fetchAllAsClass(string $sql, array $params, string $class): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_CLASS, $class);
    }

    /**
     * Fetch a single row as a class instance.
     * จุดประสงค์: ดึงข้อมูลแถวเดียวและแมปเป็นอ็อบเจ็กต์ของคลาสที่กำหนด
     *
     * @param string $sql กำหนดคำสั่ง SQL ที่จะดำเนินการ
     * @param array $params กำหนดอาร์เรย์ของพารามิเตอร์ที่จะผูกกับคำสั่ง SQL
     * @param string $class กำหนดชื่อคลาสที่ต้องการแมปผลลัพธ์
     * @return mixed คืนค่าอ็อบเจ็กต์คลาสที่กำหนด หรือ null หากไม่พบข้อมูล
     */
    public function fetchAsClass(string $sql, array $params, string $class)
    {
        $stmt = $this->query($sql, $params);
        $stmt->setFetchMode(PDO::FETCH_CLASS, $class);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * ดึงข้อมูลคอลัมน์แรกของแถวแรก
     * จุดประสงค์: ดึงคอลัมน์แรกของแถวแรกจากผลลัพธ์ของคำสั่ง SQL ที่เตรียมไว้
     * fetchColumn() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่าของคอลัมน์แรกจากแถวแรกของผลลัพธ์
     * ตัวอย่างการใช้งาน: 
     * ```php
     * $count = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE status = :status", ['status' => 'active']);
     * ```
     * 
     * @param string $sql กำหนดคำสั่ง SQL ที่จะดำเนินการ
     * @param array $params กำหนดอาร์เรย์ของพารามิเตอร์ที่จะผูกกับคำสั่ง SQL
     * @return mixed คืนค่าของคอลัมน์แรกหรือ false ถ้าไม่มีข้อมูล
     */
    public function fetchColumn(string $sql, array $params = [])
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    /**
     * ดึงคอลัมน์แรกจากทุกแถว
     * จุดประสงค์: ดึงคอลัมน์แรกจากทุกแถวในผลลัพธ์ของคำสั่ง SQL ที่เตรียมไว้
     * fetchList() ควรใช้กับอะไร: เมื่อคุณต้องการดึงรายการของค่าจากคอลัมน์แรกของผลลัพธ์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $usernames = $db->fetchList("SELECT username FROM users WHERE status = :status", ['status' => 'active']);
     * ```
     * 
     * @param string $sql กำหนดคำสั่ง SQL ที่จะดำเนินการ
     * @param array $params กำหนดอาร์เรย์ของพารามิเตอร์ที่จะผูกกับคำสั่ง SQL
     * @return array คืนค่าอาร์เรย์ของค่าจากคอลัมน์แรก
     */
    public function fetchList(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * ดึงคู่คีย์-ค่า
     * จุดประสงค์: ดึงอาร์เรย์ของคู่คีย์-ค่า จากผลลัพธ์ของคำสั่ง SQL ที่เตรียมไว้
     * fetchPairs() ควรใช้กับอะไร: เมื่อคุณต้องการดึงอาร์เรย์ของคู่คีย์-ค่าจากผลลัพธ์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $userMap = $db->fetchPairs("SELECT id, username FROM users WHERE status = :status", ['status' => 'active']);
     * ```
     * 
     * @param string $sql กำหนดคำสั่ง SQL ที่จะดำเนินการ
     * @param array $params กำหนดอาร์เรย์ของพารามิเตอร์ที่จะผูกกับคำสั่ง SQL
     * @return array คืนค่าอาร์เรย์ของคู่คีย์-ค่า
     */
    public function fetchPairs(string $sql, array $params = []): array
    {
        $rows = $this->query($sql, $params)->fetchAll(PDO::FETCH_NUM);
        $result = [];
        foreach ($rows as $row) {
            if (isset($row[0])) {
                $key = $row[0];
                $value = $row[1] ?? null;
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Execute a statement (INSERT/UPDATE/DELETE).
     * จุดประสงค์: ดำเนินการคำสั่ง SQL ที่เปลี่ยนแปลงข้อมูลในฐานข้อมูล
     * execute() ควรใช้กับอะไร: เมื่อคุณต้องการดำเนินการคำสั่ง SQL ที่เปลี่ยนแปลงข้อมูล เช่น INSERT, UPDATE, DELETE
     * ตัวอย่างการใช้งาน:
     * ```php
     * $affectedRows = $db->execute("UPDATE users SET status = :status WHERE id = :id", ['status' => 'active', 'id' => $userId]);
     * ```
     *
     * @param string $sql กำหนดคำสั่ง SQL ที่จะดำเนินการ
     * @param array $params กำหนดอาร์เรย์ของพารามิเตอร์ที่จะผูกกับคำสั่ง SQL
     * @return int คืนค่าจำนวนแถวที่ได้รับผลกระทบ
     */
    public function execute(string $sql, array $params = []): int
    {
        $start = microtime(true);
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $duration = (microtime(true) - $start) * 1000.0;
            $this->recordQuery($sql, $params, $duration);
            if ($duration > 500.0) {
                $this->getLogger()->warning('db.slow_query', [
                    'sql' => $sql,
                    'params' => $params,
                    'duration_ms' => $duration,
                ]);
            }
            return $stmt->rowCount();
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $start) * 1000.0;
            $this->getLogger()->error('db.execute_error', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
            ]);
            throw $e;
        }
    }

    /**
     * Get last insert id.
     * จุดประสงค์: รับ ID ของแถวที่เพิ่งแทรกล่าสุด
     * lastInsertId() ควรใช้กับอะไร: เมื่อคุณต้องการรับ ID ของแถวที่เพิ่งแทรกในฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $lastId = $db->lastInsertId();
     * ```
     *
     * @param string|null $name กำหนดชื่อของลำดับ (ถ้ามี)
     * @return string คืนค่า ID ของแถวที่เพิ่งแทรกล่าสุด
     */
    public function lastInsertId(?string $name = null): string
    {
        return $this->connection->lastInsertId($name);
    }

    /** Transaction helpers
     * จุดประสงค์: จัดการธุรกรรมฐานข้อมูลอย่างปลอดภัย
     * beginTransaction() ควรใช้กับอะไร: เมื่อคุณต้องการเริ่มต้นธุรกรรมฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $db->beginTransaction();
     * ```
     * 
     * @return bool คืนค่า true หากเริ่มธุรกรรมสำเร็จ, false หากไม่สำเร็จ
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * ยืนยันธุรกรรมปัจจุบัน
     * จุดประสงค์: ยืนยันการเปลี่ยนแปลงในธุรกรรมฐานข้อมูล
     * commit() ควรใช้กับอะไร: เมื่อคุณต้องการยืนยันธุรกรรมฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $db->commit();
     * ```
     * 
     * @return bool คืนค่า true หากยืนยันธุรกรรมสำเร็จ, false หากไม่สำเร็จ
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * ยกเลิกธุรกรรมปัจจุบัน
     * จุดประสงค์: ยกเลิกการเปลี่ยนแปลงในธุรกรรมฐานข้อมูล
     * rollBack() ควรใช้กับอะไร: เมื่อคุณต้องการยกเลิกธุรกรรมฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $db->rollBack();
     * ```
     * 
     * @return bool คืนค่า true หากยกเลิกธุรกรรมสำเร็จ, false หากไม่สำเร็จ
     */
    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }

    /**
     * เรียกใช้คำสั่ง SQL ดิบ (ไม่มีพารามิเตอร์)
     * จุดประสงค์: ดำเนินการคำสั่ง SQL ดิบที่ไม่มีพารามิเตอร์
     * execRaw() ควรใช้กับอะไร: เมื่อคุณต้องการดำเนินการคำสั่ง SQL ดิบที่ไม่มีพารามิเตอร์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $affectedRows = $db->execRaw("DELETE FROM sessions WHERE last_active < NOW() - INTERVAL 30 DAY");
     * ```
     * 
     * @param string $sql กำหนดคำสั่ง SQL ดิบที่จะดำเนินการ
     * @return int คืนค่าจำนวนแถวที่ได้รับผลกระทบ
     */
    public function execRaw(string $sql): int
    {
        $start = microtime(true);
        try {
            $result = $this->connection->exec($sql);
            $duration = (microtime(true) - $start) * 1000.0;
            $this->recordQuery($sql, [], $duration);
            if ($duration > 500.0) {
                $this->getLogger()->warning('db.slow_query', [
                    'sql' => $sql,
                    'params' => [],
                    'duration_ms' => $duration,
                ]);
            }
            return $result;
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $start) * 1000.0;
            $this->getLogger()->error('db.exec_raw_error', [
                'sql' => $sql,
                'params' => [],
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
            ]);
            throw $e;
        }
    }

    /**
     * เรียกใช้หลายคำสั่ง SQL ดิบในธุรกรรมเดียว
     * จุดประสงค์: ดำเนินการหลายคำสั่ง SQL ดิบในธุรกรรมเดียว
     * execMultiple() ควรใช้กับอะไร: เมื่อคุณต้องการดำเนินการหลายคำสั่ง SQL ดิบในธุรกรรมเดียว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $statements = [
     *     "UPDATE accounts SET balance = balance - 100 WHERE id = 1",
     *     "UPDATE accounts SET balance = balance + 100 WHERE id = 2",
     * ];
     * $results = $db->execMultiple($statements);
     * ```
     * 
     * @param array $statements กำหนดอาร์เรย์ของคำสั่ง SQL ดิบที่จะดำเนินการ
     * @return array คืนค่าอาร์เรย์ของจำนวนแถวที่ได้รับผลกระทบสำหรับแต่ละคำสั่ง
     */
    public function execMultiple(array $statements): array
    {
        $this->beginTransaction();
        $results = [];
        try {
            foreach ($statements as $sql) {
                $results[] = $this->connection->exec($sql);
            }
            $this->commit();
            return $results;
        } catch (\Throwable $e) {
            if ($this->connection->inTransaction()) {
                $this->rollBack();
            }
            throw $e;
        }
    }

    /**
     * ตรวจสอบว่ากำลังอยู่ในธุรกรรมหรือไม่
     * จุดประสงค์: ตรวจสอบสถานะของธุรกรรมฐานข้อมูลปัจจุบัน
     * inTransaction() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่ากำลังอยู่ในธุรกรรมฐานข้อมูลหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * if ($db->inTransaction()) {
     *     // กำลังอยู่ในธุรกรรม
     * }
     * ```
     *
     * @return bool คืนค่า true หากอยู่ในธุรกรรม, false หากไม่อยู่
     */
    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }

    /**
     * Convenience transactional wrapper.
     * Rolls back automatically on exception.
     * จุดประสงค์: ดำเนินการธุรกรรมฐานข้อมูลโดยอัตโนมัติ
     * transaction() ควรใช้กับอะไร: เมื่อคุณต้องการดำเนินการธุรกรรมฐานข้อมูลโดยอัตโนมัติและจัดการข้อผิดพลาด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $result = $db->transaction(function($db) {
     *     // ดำเนินการฐานข้อมูลที่นี่
     *     return $someValue;
     * ```
     *
     * @param callable $cb กำหนดฟังก์ชันที่จะดำเนินการภายในธุรกรรม
     * @return mixed คืนค่าผลลัพธ์จากฟังก์ชัน callback
     */
    public function transaction(callable $cb)
    {
        $this->beginTransaction();
        try {
            $result = $cb($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->connection->inTransaction()) {
                $this->rollBack();
            }
            throw $e;
        }
    }

    /**
     * ป้องกันการโคลน singleton
     */
    private function __clone() {}

    /**
     * ป้องกันการ unserialize ของ singleton
     * จุดประสงค์: ป้องกันการสร้างอินสแตนซ์ใหม่ผ่าน unserialize
     * @throws \Exception เสมอเมื่อพยายาม unserialize
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}
