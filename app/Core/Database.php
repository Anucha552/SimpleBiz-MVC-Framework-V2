<?php
/**
 * คลาสการเชื่อมต่อฐานข้อมูล
 * 
 * จุดประสงค์: จัดเตรียมการเชื่อมต่อฐานข้อมูล PDO ที่ปลอดภัยโดยใช้รูปแบบ singleton
 * ความปลอดภัย: ใช้ prepared statements เท่านั้นเพื่อป้องกัน SQL injection
 * 
 * ทำไมต้อง Singleton?
 * - รับประกันว่ามีการเชื่อมต่อฐานข้อมูลเพียงหนึ่งเดียวตลอดช่วงชีวิตแอปพลิเคชัน
 * - ลดภาระการเชื่อมต่อและการใช้ทรัพยากร
 * - รวมศูนย์การจัดการการเชื่อมต่อ
 * 
 * กฎความปลอดภัย:
 * - ห้ามใช้การต่อสตริงสำหรับคำสั่ง query
 * - ต้องใช้ prepared statements กับพารามิเตอร์ที่ผูกไว้เสมอ
 * - ปิดการใช้งาน emulated prepares เพื่อใช้ prepared statements จริง
 * - ตั้งค่าโหมดข้อผิดพลาดให้แสดง exceptions เพื่อการดีบักที่ดีขึ้น
 */

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    /**
     * อินสแตนซ์ Singleton
     */
    private static ?Database $instance = null;
    
    /**
     * อ็อบเจ็กต์การเชื่อมต่อ PDO
     */
    private ?PDO $connection = null;

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
     * 
     * @return Database
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
     * 
     * การกำหนดค่าโหลดจาก config/database.php
     * 
     * ตัวเลือก PDO อธิบาย:
     * - ATTR_ERRMODE: แสดง exceptions เมื่อเกิดข้อผิดพลาด (ดีกว่าการล้มเหลวอย่างเงียบ)
     * - ATTR_DEFAULT_FETCH_MODE: คืนค่าอาร์เรย์แบบ associative โดยค่าเริ่มต้น
     * - ATTR_EMULATE_PREPARES: False สำหรับ prepared statements จริง (ปลอดภัยกว่า)
     * - ATTR_STRINGIFY_FETCHES: False เพื่อรักษาประเภทข้อมูล
     * 
     * @throws PDOException ถ้าการเชื่อมต่อล้มเหลว
     */
    private function connect(): void
    {
        // โหลดการกำหนดค่าฐานข้อมูล
        $config = require __DIR__ . '/../../config/database.php';

        $host = $config['host'];
        $port = $config['port'];
        $dbname = $config['database'];
        $charset = $config['charset'];
        $username = $config['username'];
        $password = $config['password'];

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        try {
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // Real prepared statements
                PDO::ATTR_STRINGIFY_FETCHES => false, // Preserve data types
            ]);
        } catch (PDOException $e) {
            // ในโหมดโปรดักชัน ให้บันทึกข้อผิดพลาดนี้แทนการแสดงผล
            if (\env('APP_ENV') === 'production') {
                error_log("Database connection failed: " . $e->getMessage());
                throw new PDOException("Database connection failed");
            } else {
                throw $e;
            }
        }
    }

    /**
     * ดึงการเชื่อมต่อ PDO
     * 
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * ป้องกันการโคลน singleton
     */
    private function __clone() {}

    /**
     * ป้องกันการ unserialize ของ singleton
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}
