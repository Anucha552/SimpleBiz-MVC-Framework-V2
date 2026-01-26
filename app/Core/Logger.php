<?php
/**
 * คลาสล็อกเกอร์
 * 
 * จุดประสงค์: จัดเตรียมการบันทึกล็อกเชิงความหมายโดยเน้นความปลอดภัย
 * ความปลอดภัย: บันทึกกิจกรรมที่น่าสงสัยสำหรับการตรวจสอบ
 * 
 * ระดับล็อก:
 * - info: เหตุการณ์แอปพลิเคชันทั่วไป (cart.add, order.created)
 * - security: เหตุการณ์ที่เกี่ยวข้องกับความปลอดภัย (login.failed, price.tamper)
 * - error: ข้อผิดพลาดของแอปพลิเคชัน (db.failure, validation.failed)
 * 
 * รูปแบบล็อก:
 * [timestamp] [level] [event] [context] [user_id] [ip] [route]
 * 
 * ทำไมการบันทึกล็อกถึงสำคัญ:
 * - ติดตามพฤติกรรมผู้ใช้และสุขภาพระบบ
 * - ตรวจจับภัยคุกคามด้านความปลอดภัยและความพยายามฉ้อโกง
 * - ดีบักปัญหาในโปรดักชัน
 * - ข้อกำหนดด้านการปฏิบัติตามและการตรวจสอบ
 * 
 * เน้นความปลอดภัย:
 * - บันทึกความพยายามในการยืนยันตัวตนทั้งหมด
 * - บันทึกความพยายามจัดการราคา
 * - บันทึกกิจกรรมตะกร้าที่น่าสงสัย
 * - บันทึกความล้มเหลวในการตรวจสอบสต็อก
 */

namespace App\Core;

class Logger
{
    /**
     * เส้นทางไฟล์ล็อก
     */
    private string $logFile;
    /**
     * หากต้องการหมุนตามขนาด (bytes)
     */
    private int $maxFileSize;

    /**
     * จำนวนวันที่เก็บไฟล์ก่อนลบ (days)
     */
    private int $retentionDays;

    /**
     * สร้างอินสแตนซ์ logger
     * 
     * @param string $logFile เส้นทางไฟล์ล็อก
     */
    public function __construct(?string $logFile = null)
    {
        $this->logFile = $logFile ?? __DIR__ . '/../../storage/logs/app.log';
        // ค่าเริ่มต้นจาก env ถ้ามี (หน่วยเป็น bytes สำหรับ MAX_LOG_SIZE)
        $this->maxFileSize = (int) (getenv('MAX_LOG_SIZE') ?: 0); // 0 = ไม่ตรวจขนาด
        // retention days (default 7)
        $this->retentionDays = (int) (getenv('LOG_RETENTION_DAYS') ?: 7);
        
        // ตรวจสอบว่ามีไดเรกทอรีล็อกหรือไม่
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * บันทึกข้อความข้อมูล
     * 
     * ใช้สำหรับ: เหตุการณ์แอปพลิเคชันปกติ
     * ตัวอย่าง: cart.add, product.view, order.created
     * 
     * @param string $event ตัวระบุเหตุการณ์
     * @param array $context ข้อมูลบริบทเพิ่มเติม
     */
    public function info(string $event, array $context = []): void
    {
        $this->log('INFO', $event, $context);
    }

    /**
     * บันทึกเหตุการณ์ความปลอดภัย
     * 
     * ใช้สำหรับ: เหตุการณ์ที่เกี่ยวข้องกับความปลอดภัย
     * ตัวอย่าง: login.failed, price.tamper, api.unauthorized
     * 
     * @param string $event ตัวระบุเหตุการณ์
     * @param array $context ข้อมูลบริบทเพิ่มเติม
     */
    public function security(string $event, array $context = []): void
    {
        $this->log('SECURITY', $event, $context);
    }

    /**
     * บันทึกข้อผิดพลาด
     * 
     * ใช้สำหรับ: ข้อผิดพลาดของแอปพลิเคชัน
     * ตัวอย่าง: db.failure, validation.failed, exception.thrown
     * 
     * @param string $event ตัวระบุเหตุการณ์
     * @param array $context ข้อมูลบริบทเพิ่มเติม
     */
    public function error(string $event, array $context = []): void
    {
        $this->log('ERROR', $event, $context);
    }

    /**
     * บันทึกคำเตือน
     * 
     * ใช้สำหรับ: สถานการณ์ที่น่าสนใจแต่ไม่ใช่ข้อผิดพลาด
     * ตัวอย่าง: validation.failed, rate_limit.exceeded, slow_query
     * 
     * @param string $event ตัวระบุเหตุการณ์
     * @param array $context ข้อมูลบริบทเพิ่มเติม
     */
    public function warning(string $event, array $context = []): void
    {
        $this->log('WARNING', $event, $context);
    }

    /**
     * เขียนรายการล็อก
     * 
     * รูปแบบล็อก:
     * [2024-01-15 10:30:45] [SECURITY] order.price_tamper 
     * {"expected":100,"received":50,"product_id":5} 
     * user_id=12 ip=192.168.1.1 route=/checkout
     * 
     * @param string $level ระดับล็อก
     * @param string $event ตัวระบุเหตุการณ์
     * @param array $context ข้อมูลบริบท
     */
    private function log(string $level, string $event, array $context): void
    {
        $timestamp = date('Y-m-d H:i:s');
        
        // รับบริบทคำขอ
        Session::start();
        $userId = Session::get('user_id', 'guest');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $route = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'unknown';
        $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? 'unknown';

        // สร้างข้อความล็อก
        $contextJson = !empty($context) ? json_encode($context) : '{}';
        
        $logMessage = sprintf(
            "[%s] [%s] %s %s user_id=%s ip=%s method=%s route=%s request_id=%s\n",
            $timestamp,
            $level,
            $event,
            $contextJson,
            $userId,
            $ip,
            $method,
            $route,
            $requestId
        );

        // ก่อนเขียน ให้ตรวจสอบการหมุนไฟล์
        $this->maybeRotate();

        // เขียนลงไฟล์ล็อก
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);

        // สำหรับเหตุการณ์ความปลอดภัยที่สำคัญ ให้บันทึกลงล็อกข้อผิดพลาด PHP ด้วย
        if ($level === 'SECURITY') {
            error_log("SECURITY EVENT: {$event} - " . json_encode($context));
        }
    }

    /**
     * ตรวจสอบว่าต้องหมุนไฟล์หรือไม่: หมุนตามวันที่ (แยกไฟล์ตามวัน) และตามขนาด
     */
    private function maybeRotate(): void
    {
        $logDir = dirname($this->logFile);

        // หมุนตามวันที่: ถ้าชื่อไฟล์ปัจจุบันไม่ใช่ไฟล์สำหรับวันนี้ ให้เปลี่ยนเป็น YYYY-MM-DD.log
        $todayFile = $logDir . '/' . date('Y-m-d') . '.log';

        if ($this->logFile !== $todayFile) {
            // ย้าย/สร้างไฟล์ daily หากจำเป็น: ถ้าไฟล์ปัจจุบันมีข้อมูล ให้ย้ายเป็นไฟล์ชื่อวันที่
            if (file_exists($this->logFile) && filesize($this->logFile) > 0) {
                // ถ้าไฟล์วันที่มีอยู่แล้ว ให้ต่อท้ายด้วย suffix
                if (file_exists($todayFile)) {
                    // append current content to today's file then clear current
                    $content = file_get_contents($this->logFile);
                    file_put_contents($todayFile, $content, FILE_APPEND | LOCK_EX);
                    file_put_contents($this->logFile, '');
                } else {
                    rename($this->logFile, $todayFile);
                }
            }

            // เปลี่ยน logFile ไปชี้ไฟล์วันนี้เพื่อการเขียนถัดไป
            $this->logFile = $todayFile;
        }

        // หมุนตามขนาด: ถ้ากำหนด maxFileSize และไฟล์เกิน ให้เปลี่ยนชื่อเป็น .1, .2 ...
        if ($this->maxFileSize > 0 && file_exists($this->logFile) && filesize($this->logFile) >= $this->maxFileSize) {
            // หาชื่อใหม่ด้วย suffix increment
            $index = 1;
            do {
                $rotated = $this->logFile . '.' . $index;
                $index++;
            } while (file_exists($rotated) && $index < 1000);

            // ย้ายไฟล์ปัจจุบันไปเป็น rotated
            rename($this->logFile, $rotated);
            // สร้างไฟล์ใหม่ว่างๆ
            file_put_contents($this->logFile, '');
        }

        // ลบไฟล์เก่าตาม retention days
        $this->cleanupOldLogs($logDir);
    }

    /**
     * ลบไฟล์ log ที่เก่ากว่า retentionDays
     */
    private function cleanupOldLogs(string $logDir): void
    {
        // ป้องกัน loop ถ้าโฟลเดอร์ไม่มี
        if (!is_dir($logDir)) {
            return;
        }

        $files = glob($logDir . '/*.log*');
        if (!$files) {
            return;
        }

        $now = time();
        foreach ($files as $file) {
            // ข้ามไฟล์ปัจจุบัน
            if ($file === $this->logFile) {
                continue;
            }

            $mtime = filemtime($file);
            if ($mtime === false) {
                continue;
            }

            $ageDays = ($now - $mtime) / 86400;
            if ($ageDays > $this->retentionDays) {
                @unlink($file);
            }
        }
    }

    /**
     * ดึงรายการล็อกล่าสุด
     * 
     * มีประโยชน์สำหรับแดชบอร์ดผู้ดูแลระบบ
     * 
     * @param int $lines จำนวนบรรทัดที่จะดึง
     * @return array รายการล็อก
     */
    public function getRecent(int $lines = 100): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $file = file($this->logFile);
        return array_slice($file, -$lines);
    }

    /**
     * ล้างไฟล์ล็อก
     * 
     * คำเตือน: ใช้ด้วยความระมัดระวัง
     */
    public function clear(): void
    {
        if (file_exists($this->logFile)) {
            file_put_contents($this->logFile, '');
        }
    }
}
