<?php
/**
 * คลาสล็อกเกอร์ สำหรับบันทึกเหตุการณ์ต่างๆ ในแอปพลิเคชัน
 * 
 * จุดประสงค์: จัดเตรียมการบันทึกล็อกเชิงความหมายโดยเน้นความปลอดภัย
 * ความปลอดภัย: บันทึกกิจกรรมที่น่าสงสัยสำหรับการตรวจสอบ
 * Logger ควรใช้กับอะไร: เมื่อคุณต้องการบันทึกเหตุการณ์ต่างๆ ในแอปพลิเคชัน
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
 * 
 * ตัวอย่างการใช้งานโดยรวม:
    * ```php
    * // You can pass a file path:
    * $logger = new Logger('/path/to/logs/YYYY-MM-DD.log');
    * // Or pass a directory (ending with slash or an existing directory):
    * $logger = new Logger('/path/to/logs/'); // uses /path/to/logs/YYYY-MM-DD.log
 * $logger->info('cart.add', ['product_id' => 123, 'quantity' => 2]);
 * $logger->security('login.failed', ['username' => 'hacker']);
 * $logger->error('db.failure', ['error' => $e->getMessage()]);
 * $logger->warning('rate_limit.exceeded', ['ip' => '192.168.1.1']);
 * ```
 */

namespace App\Core;

use App\Core\Config;
use App\Core\Session;
use DateTimeImmutable;
use RuntimeException;

class Logger
{
    /**
     * เส้นทางไฟล์ล็อก สำหรับบันทึกข้อมูล
     */
    private string $logFile;
    /**
     * หากต้องการหมุนตามขนาด (bytes) สำหรับไฟล์ล็อก
     */
    private int $maxFileSize;

    /**
     * จำนวนวันที่เก็บไฟล์ก่อนลบ (days) สำหรับการทำความสะอาดไฟล์เก่า
     */
    private int $retentionDays;

    /**
     * @var string[] keys that are allowed to be logged without redaction
     */
    private array $redactionAllowList = [];

    /**
     * @var string[] keys that must be redacted
     */
    private array $redactionDenyList = [
        'password',
        'pass',
        'pwd',
        'secret',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'auth',
        'cookie',
        'set-cookie',
        'api_key',
        'apikey',
    ];

    /**
     * สร้างอินสแตนซ์ logger
     * จุดประสงค์: กำหนดค่าเริ่มต้นสำหรับ logger รวมถึงไฟล์ล็อก ขนาดสูงสุด และการเก็บรักษา
     * ตัวอย่างการใช้งาน:
     * ```php
     * $logger = new Logger('/path/to/logs/'); หรือใช้ค่าเริ่มต้น
     * ```
     * 
     * @param string $logFile เส้นทางไฟล์ล็อก
     */
    public function __construct(?string $logFile = null)
    {
        if ($logFile === null) {
            $root = realpath(__DIR__ . '/../../') ?: (__DIR__ . '/../../');
            $logDir = rtrim($root, '/\\') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
            $this->logFile = rtrim($logDir, '/\\') . '/' . date('Y-m-d') . '.log';
        } else {
            $this->logFile = $this->normalizeLogPath($logFile);
        }
        // ค่าเริ่มต้นจาก config ถ้ามี (หน่วยเป็น bytes สำหรับ max_log_size)
        $this->maxFileSize = (int) Config::get('logging.max_log_size', 0); // 0 = ไม่ตรวจขนาด
        // จำนวนวันที่เก็บไฟล์ก่อนลบ (days) สำหรับการทำความสะอาดไฟล์เก่า
        $this->retentionDays = (int) Config::get('logging.retention_days', 7);
        
        // ตรวจสอบว่ามีไดเรกทอรีล็อกหรือไม่ — สร้างและตรวจสอบสิทธิ์
        $logDir = dirname($this->logFile);

        if (!is_dir($logDir)) {
            if (!@mkdir($logDir, 0755, true) && !is_dir($logDir)) {
                throw new RuntimeException("Logger: unable to create log directory: {$logDir}");
            }
        }

        // ตรวจสอบว่าไดเรกทอรีสามารถเขียนได้
        if (!is_writable($logDir)) {
            throw new \RuntimeException("Logger: log directory not writable: {$logDir}");
        }
    }

    /**
     * บันทึกข้อความข้อมูล
     * จุดประสงค์: บันทึกเหตุการณ์ข้อมูลทั่วไป
     * info() ควรใช้กับอะไร: เมื่อคุณต้องการบันทึกเหตุการณ์ข้อมูลทั่วไป
     * ตัวอย่างการใช้งาน:
     * ```php
     * $logger->info('cart.add', ['product_id' => 123, 'quantity' => 2]);
     * ```
     * @param string $event กำหนดตัวระบุเหตุการณ์
     * @param array $context กำหนดข้อมูลบริบทเพิ่มเติม
     * @return void ไม่คืนค่าอะไร
     */
    public function info(string $event, array $context = []): void
    {
        $this->log('INFO', $event, $context);
    }

    /**
     * บันทึกเหตุการณ์ความปลอดภัย
     * จุดประสงค์: บันทึกเหตุการณ์ที่เกี่ยวข้องกับความปลอดภัย
     * security() ควรใช้กับอะไร: เมื่อคุณต้องการบันทึกเหตุการณ์ที่เกี่ยวข้องกับความปลอดภัย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $logger->security('login.failed', ['username' => 'hacker']);
     * ```
     * 
     * @param string $event กำหนดตัวระบุเหตุการณ์
     * @param array $context กำหนดข้อมูลบริบทเพิ่มเติม
     * @return void ไม่คืนค่าอะไร
     */
    public function security(string $event, array $context = []): void
    {
        $this->log('SECURITY', $event, $context);
    }

    /**
     * บันทึกข้อผิดพลาด
     * จุดประสงค์: บันทึกข้อผิดพลาดของแอปพลิเคชัน
     * error() ควรใช้กับอะไร: เมื่อคุณต้องการบันทึกข้อผิดพลาดของแอปพลิเคชัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $logger->error('db.failure', ['error' => $e->getMessage()]);
     * ```
     * 
     * @param string $event กำหนดตัวระบุเหตุการณ์
     * @param array $context กำหนดข้อมูลบริบทเพิ่มเติม
     * @return void ไม่คืนค่าอะไร
     */
    public function error(string $event, array $context = []): void
    {
        $this->log('ERROR', $event, $context);
    }

    /**
     * บันทึกคำเตือน
     * จุดประสงค์: บันทึกเหตุการณ์ที่น่าสนใจแต่ไม่ใช่ข้อผิดพลาด
     * warning() ควรใช้กับอะไร: เมื่อคุณต้องการบันทึกเหตุการณ์ที่น่าสนใจแต่ไม่ใช่ข้อผิดพลาด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $logger->warning('rate_limit.exceeded', ['ip' => '192.168.1.1']);
     * ```
     * 
     * @param string $event กำหนดตัวระบุเหตุการณ์
     * @param array $context กำหนดข้อมูลบริบทเพิ่มเติม
     * @return void ไม่คืนค่าอะไร
     */
    public function warning(string $event, array $context = []): void
    {
        $this->log('WARNING', $event, $context);
    }

    /**
     * เขียนรายการล็อก
     * จุดประสงค์: เขียนรายการล็อกลงในไฟล์ล็อกด้วยรูปแบบที่กำหนด
     * log() ควรใช้กับอะไร: เมื่อคุณต้องการเขียนรายการล็อกลงในไฟล์ล็อก
     * ตัวอย่างการใช้งาน:
     * ```php
     * $logger->log('INFO', 'cart.add', ['product_id' => 123, 'quantity' => 2]);
     * ```
     * 
     * รูปแบบล็อก:
     * [2024-01-15 10:30:45] [SECURITY] order.price_tamper 
     * {"expected":100,"received":50,"product_id":5} 
     * user_id=12 ip=192.168.1.1 route=/checkout
     * 
     * @param string $level กำหนดระดับล็อก
     * @param string $event กำหนดตัวระบุเหตุการณ์
     * @param array $context กำหนดข้อมูลบริบทเพิ่มเติม
     * @return void ไม่คืนค่าอะไร
     */
    private function log(string $level, string $event, array $context): void
    {
        $timestamp = date('Y-m-d H:i:s');
        
        // รับบริบทคำขอ (หลีกเลี่ยงการ start session ใน logger)
        $userId = 'guest';
        if (Session::isStarted()) {
            $userId = Session::get('user_id', 'guest');
        } elseif (isset($_SESSION) && is_array($_SESSION) && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $route = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'unknown';
        $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? 'unknown';

        // สร้างข้อความล็อก
        $safeContext = $this->redactContext($context);
        $contextJson = !empty($safeContext)
            ? json_encode($safeContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '{}';
        
        $appName = (string) Config::get('app.name', 'app');

        $logMessage = sprintf(
            "[%s] [%s] %s %s app=%s user_id=%s ip=%s method=%s route=%s request_id=%s\n",
            $timestamp,
            $level,
            $event,
            $contextJson,
            $appName,
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
     * จุดประสงค์: จัดการการหมุนไฟล์ล็อกตามวันที่และขนาดไฟล์
     * maybeRotate() ควรใช้กับอะไร: เมื่อคุณต้องการจัดการการหมุนไฟล์ล็อก
     * ตัวอย่างการใช้งาน:
     * ```php
     * $logger->maybeRotate();
     * ```
     * 
     * @return void ไม่คืนค่าอะไร
     */
    private function maybeRotate(): void
    {
        $logDir = dirname($this->logFile);

        // หมุนตามวันที่: ถ้าชื่อไฟล์ปัจจุบันไม่ใช่ไฟล์สำหรับวันนี้ ให้เปลี่ยนเป็น YYYY-MM-DD.log
        $todayFile = $logDir . '/' . date('Y-m-d') . '.log';

        // ถ้าไฟล์ปัจจุบันไม่ใช่ไฟล์ของวันนี้
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
            // หาชื่อใหม่ด้วย suffix increment (วางตัวเลขก่อนนามสกุล .log)
            $index = 1;
            do {
                $indexPadded = str_pad((string)$index, 3, '0', STR_PAD_LEFT); // 001, 002, ...
                if (preg_match('/(\.log)$/', $this->logFile)) {
                    $rotated = preg_replace('/(\.log)$/', "-{$indexPadded}$1", $this->logFile);
                } else {
                    // ถ้าไฟล์ไม่ได้ลงท้ายด้วย .log ให้แนบ suffix แล้วตามด้วย .log
                    $rotated = $this->logFile . '-' . $indexPadded . '.log';
                }
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
     * จุดประสงค์: ลบไฟล์ล็อกที่เก่ากว่าเวลาที่กำหนดเพื่อประหยัดพื้นที่
     * cleanupOldLogs() ควรใช้กับอะไร: เมื่อคุณต้องการลบไฟล์ล็อกเก่า
     * ตัวอย่างการใช้งาน:
     * ```php
     * $logger->cleanupOldLogs('/path/to/logs');
     * ```
     * 
    * @param string $logDir กำหนดไดเรกทอรีล็อก
    * @return int จำนวนไฟล์ที่ลบสำเร็จ
     */
    private function cleanupOldLogs(string $logDir): int
    {
        // ป้องกัน loop ถ้าโฟลเดอร์ไม่มี
        if (!is_dir($logDir)) {
            return 0;
        }
        $files = glob($logDir . '/*.log*');
        if (!$files) {
            return 0;
        }

        // Keep N calendar days (1 = keep today only). Non-positive = delete anything before today.
        $days = (int) $this->retentionDays;
        $keepDays = $days > 0 ? $days : 1;
        $cutoffDate = (new DateTimeImmutable('today'))->modify('-' . ($keepDays - 1) . ' days');
        $cutoffDateStr = $cutoffDate->format('Y-m-d');

        // Normalize current log path for safe comparison (realpath may return false if file missing)
        $currentReal = realpath($this->logFile) ?: $this->logFile;

        $deleted = 0;

        foreach ($files as $file) {
            $fileReal = realpath($file) ?: $file;

            // Skip the current log file (normalized)
            if ($fileReal === $currentReal) {
                continue;
            }

            $base = basename($file);
            if (preg_match('/(\d{4}-\d{2}-\d{2})/', $base, $matches)) {
                $fileDate = $matches[1];
                if ($fileDate < $cutoffDateStr) {
                    if (@unlink($file)) {
                        $deleted++;
                    }
                }
                continue;
            }

            // Fallback to mtime when no date in filename
            $mtime = filemtime($file);
            if ($mtime === false) {
                continue;
            }

            if ($mtime < $cutoffDate->getTimestamp()) {
                if (@unlink($file)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * ล้างไฟล์ล็อกเก่าตาม retention days (แบบวันปฏิทิน)
     * จุดประสงค์: ลบไฟล์ล็อกที่เก่ากว่าเวลาที่กำหนดเพื่อประหยัดพื้นที่ โดยใช้ retention days แบบวันปฏิทิน
     * cleanup() ควรใช้กับอะไร: เมื่อคุณต้องการลบไฟล์ล็อกเก่าตาม retention days แบบวันปฏิทิน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $deletedCount = $logger->cleanup(); // ล้างไฟล์ล็อกเก่าตาม retention days แบบวันปฏิทิน
     * echo "Deleted $deletedCount old log files.";
     * ```
     * 
     * @param string|null $logDir ไดเรกทอรีล็อก ถ้าไม่ระบุจะใช้ของ logger นี้
     * @return int จำนวนไฟล์ที่ลบสำเร็จ
     */
    public function cleanup(?string $logDir = null): int
    {
        $targetDir = $logDir ? $this->makeAbsolutePath($logDir) : dirname($this->logFile);
        if (is_file($targetDir)) {
            $targetDir = dirname($targetDir);
        }

        return $this->cleanupOldLogs($targetDir);
    }

    /**
     * ดึงรายการล็อกล่าสุด
     * จุดประสงค์: ดึงบรรทัดล็อกล่าสุดจากไฟล์ล็อก
     * getRecent() ควรใช้กับอะไร: เมื่อคุณต้องการดึงรายการล็อกล่าสุด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $recentLogs = $logger->getRecent(50); // ดึง 50 บรรทัดล่าสุด
     * ```
     * 
     * @param int $lines กำหนดจำนวนบรรทัดที่จะดึง
     * @return array คืนรายการล็อก
     */
    public function getRecent(int $lines = 100): array
    {
        if ($lines <= 0) {
            return [];
        }

        // Determine target file: prefer configured logFile, but if it's missing/empty
        // fall back to the most recent *.log* in the same directory (rotated files)
        $targetFile = $this->logFile;
        if (!file_exists($targetFile) || filesize($targetFile) === 0) {
            $logDir = dirname($this->logFile);
            $candidates = glob($logDir . '/*.log*');
            if ($candidates) {
                usort($candidates, function ($a, $b) {
                    return filemtime($b) <=> filemtime($a);
                });
                $targetFile = $candidates[0];
            }
        }

        if (!file_exists($targetFile)) {
            return [];
        }

        // Read file from the end in chunks to avoid loading whole file into memory
        $fp = fopen($targetFile, 'rb');
        if (!$fp) {
            return [];
        }

        $chunkSize = 4096;
        $pos = -1;
        $buffer = '';
        $linesFound = 0;

        fseek($fp, 0, SEEK_END);
        $fileSize = ftell($fp);

        while ($fileSize > 0 && $linesFound <= $lines) {
            $readSize = ($fileSize - $chunkSize) > 0 ? $chunkSize : $fileSize;
            fseek($fp, $fileSize - $readSize);
            $data = fread($fp, $readSize);
            if ($data === false) {
                break;
            }
            $buffer = $data . $buffer;
            $linesFound = substr_count($buffer, "\n");
            $fileSize -= $readSize;
        }

        fclose($fp);

        $linesArr = preg_split('/\r?\n/', trim($buffer, "\n"));
        if ($linesArr === false) {
            return [];
        }

        $result = array_slice($linesArr, -$lines);
        // Remove any trailing CR characters
        return array_map(fn($l) => rtrim($l, "\r"), $result);
    }

    /**
     * ล้างไฟล์ล็อก
     * จุดประสงค์: ล้างเนื้อหาของไฟล์ล็อกทั้งหมด
     * clear() ควรใช้กับอะไร: เมื่อคุณต้องการล้างไฟล์ล็อกทั้งหมด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $logger->clear();
     * ```
     * 
     * @return void ไม่คืนค่าอะไร
     */
    public function clear(): void
    {
        // ล้างไฟล์ล็อกทั้งหมดในไดเรกทอรีล็อก
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            return;
        }

        $files = glob($logDir . '/*.log*');
        if (!$files) {
            return;
        }

        foreach ($files as $file) {
            // safety: ensure it's a file (not directory) and writable before unlink
            if (!is_file($file)) {
                continue;
            }

            // try to unlink; if fails, fall back to truncating
            if (!@unlink($file)) {
                @file_put_contents($file, '', LOCK_EX);
            }
        }
    }

    /**
     * ตั้งรายการอนุญาตการลบข้อมูล (allow list) สำหรับคีย์ในบริบท
     * จุดประสงค์: กำหนดคีย์ที่อนุญาตให้บันทึกโดยไม่ต้องลบข้อมูล
     * setRedactionAllowList() ควรใช้กับอะไร: เมื่อคุณต้องการกำหนดคีย์ที่อนุญาตให้บันทึกโดยไม่ต้องลบข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $logger->setRedactionAllowList(['product_id', 'quantity']);
     * ```
     * 
     * @param string[] $keys กำหนดรายการคีย์ที่อนุญาตให้บันทึกโดยไม่ต้องลบข้อมูล
     * @return void ไม่คืนค่าอะไร
     */
    public function setRedactionAllowList(array $keys): void
    {
        $this->redactionAllowList = $this->normalizeKeyList($keys);
    }

    /**
     * ตั้งรายการห้ามการลบข้อมูล (deny list) สำหรับคีย์ในบริบท
     * จุดประสงค์: กำหนดคีย์ที่ห้ามบันทึกโดยไม่ต้องลบข้อมูล
     * setRedactionDenyList() ควรใช้กับอะไร: เมื่อคุณต้องการกำหนดคีย์ที่ห้ามบันทึกโดยไม่ต้องลบข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $logger->setRedactionDenyList(['password', 'credit_card']);
     * ```
     * 
     * @param string[] $keys กำหนดรายการคีย์ที่ห้ามบันทึกโดยไม่ต้องลบข้อมูล
     * @return void ไม่คืนค่าอะไร
     */
    public function setRedactionDenyList(array $keys): void
    {
        $this->redactionDenyList = $this->normalizeKeyList($keys);
    }

    /**
     * เพิ่มคีย์ลงในรายการอนุญาตการลบข้อมูล (allow list)
     * จุดประสงค์: เพิ่มคีย์ลงในรายการอนุญาตให้
     * addRedactionAllowKeys() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มคีย์ลงในรายการอนุญาตให้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $logger->addRedactionAllowKeys(['email']);
     * ```
     * 
     * @param string[] $keys กำหนดรายการคีย์ที่อนุญาตให้บันทึกโดยไม่ต้องลบข้อมูล
     * @return void ไม่คืนค่าอะไร
     */
    public function addRedactionAllowKeys(array $keys): void
    {
        $this->redactionAllowList = $this->mergeKeyList($this->redactionAllowList, $keys);
    }

    /**
     * เพิ่มคีย์ลงในรายการห้ามการลบข้อมูล (deny list)
     * จุดประสงค์: เพิ่มคีย์ลงในรายการห้ามบันทึกโดยไม่ต้องลบข้อมูล
     * addRedactionDenyKeys() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มคีย์ลงในรายการห้ามบันทึกโดยไม่ต้องลบข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $logger->addRedactionDenyKeys(['password']);
     * ```
     * 
     * @param string[] $keys กำหนดรายการคีย์ที่ห้ามบันทึกโดยไม่ต้องลบข้อมูล
     * @return void ไม่คืนค่าอะไร
     */
    public function addRedactionDenyKeys(array $keys): void
    {
        $this->redactionDenyList = $this->mergeKeyList($this->redactionDenyList, $keys);
    }

    /**
     * ทำให้เส้นทางล็อกเป็นแบบมาตรฐาน (normalize) โดยรองรับทั้งไฟล์และไดเรกทอรี
     * จุดประสงค์: ทำให้เส้นทางล็อกเป็นแบบมาตรฐานโดยรองรับทั้งไฟล์และไดเรกทอรี
     * normalizeLogPath() ควรใช้กับอะไร: เมื่อคุณต้องการทำให้เส้นทางล็อกเป็นแบบมาตรฐาน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $normalizedPath = $logger->normalizeLogPath('/path/to/logs/');
     * ```
     * 
     * @param string $provided กำหนดเส้นทางล็อกที่ผู้ใช้ให้มา
     * @return string คืนเส้นทางล็อกที่ถูกปรับให้เป็นแบบมาตรฐาน
     */
    private function normalizeLogPath(string $provided): string
    {
        $path = $this->makeAbsolutePath($provided);

        if (preg_match('#[A-Za-z]:[\\/]#', $path, $matches, PREG_OFFSET_CAPTURE)) {
            $drivePos = $matches[0][1];
            if ($drivePos > 0) {
                $path = substr($path, $drivePos);
            }
        }

        if (preg_match('#[\/]$#', $path) || is_dir($path)) {
            $logDir = rtrim($path, '/\\');
            return $logDir . '/' . date('Y-m-d') . '.log';
        }

        return $path;
    }

    /**
     * ทำให้เส้นทางเป็นแบบสัมบูรณ์ (absolute) โดยรองรับทั้ง Windows และ Unix
     * จุดประสงค์: ทำให้เส้นทางเป็นแบบสัมบูรณ์โดยรองรับทั้ง Windows และ Unix
     * makeAbsolutePath() ควรใช้กับอะไร: เมื่อคุณต้องการทำให้เส้นทางเป็นแบบสัมบูรณ์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $absolutePath = $logger->makeAbsolutePath('logs/');
     * ```
     * 
     * @param string $path กำหนดเส้นทางที่ต้องการทำให้เป็นแบบสัมบูรณ์
     * @return string คืนเส้นทางที่ถูกปรับให้เป็นแบบสัมบูรณ์
     */
    private function makeAbsolutePath(string $path): string
    {
        $path = trim($path);

        if (preg_match('#[A-Za-z]:[\\/]#', $path)) {
            return $path;
        }

        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        $root = realpath(__DIR__ . '/../../') ?: (__DIR__ . '/../../');
        return rtrim($root, '/\\') . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }

    /**
     * ตรวจสอบว่าเส้นทางเป็นแบบสัมบูรณ์หรือไม่ โดยรองรับทั้ง Windows และ Unix
     * จุดประสงค์: ตรวจสอบว่าเส้นทางเป็นแบบสัมบูรณ์หรือไม่ โดยรองรับทั้ง Windows และ Unix
     * isAbsolutePath() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าเส้นทางเป็นแบบสัมบูรณ์หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isAbsolute = $logger->isAbsolutePath('/var/logs/');
     * ```
     * 
     * @param string $path กำหนดเส้นทางที่ต้องการตรวจสอบ
     * @return bool คืนค่า true ถ้าเป็นเส้นทางแบบสัมบูรณ์, false ถ้าไม่ใช่
     */
    private function isAbsolutePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if (preg_match('#^[A-Za-z]:[\\/]#', $path)) {
            return true;
        }

        return ($path[0] === '/' || $path[0] === '\\');
    }

    /**
     * ลบข้อมูลที่ละเอียดอ่อนจากบริบทตามรายการอนุญาตและห้าม
     * จุดประสงค์: ลบข้อมูลที่ละเอียดอ่อนจากบริบทตามรายการอนุญาตและห้าม
     * redactContext() ควรใช้กับอะไร: เมื่อคุณต้องการลบข้อมูลที่ละเอียดอ่อนจากบริบทก่อนบันทึก
     * ตัวอย่างการใช้งาน:
     * ```php
     * $safeContext = $logger->redactContext($context);
     * ```
     * 
     * @param array $context กำหนดข้อมูลบริบทที่ต้องการลบข้อมูลที่ละเอียดอ่อนออก
     * @return array คืนข้อมูลบริบทที่ถูกลบข้อมูลที่ละเอียดอ่อนออกแล้ว
     */
    private function redactContext(array $context): array
    {
        $redacted = [];
        foreach ($context as $key => $value) {
            $keyLower = is_string($key) ? strtolower($key) : null;
            if ($keyLower !== null && !empty($this->redactionAllowList) && !in_array($keyLower, $this->redactionAllowList, true)) {
                $redacted[$key] = '[REDACTED]';
                continue;
            }

            if ($keyLower !== null && in_array($keyLower, $this->redactionDenyList, true)) {
                $redacted[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $redacted[$key] = $this->redactContext($value);
                continue;
            }

            $redacted[$key] = $value;
        }

        return $redacted;
    }

    /**
     * ทำให้รายการคีย์เป็นแบบมาตรฐาน (normalize) โดยแปลงเป็นตัวพิมพ์เล็กและลบค่าซ้ำ
     * จุดประสงค์: ทำให้รายการคีย์เป็นแบบมาตรฐานโดยแปลงเป็นตัวพิมพ์เล็กและลบค่าซ้ำ
     * normalizeKeyList() ควรใช้กับอะไร: เมื่อคุณต้องการทำให้รายการคีย์เป็นแบบมาตรฐาน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $normalizedKeys = $logger->normalizeKeyList(['Password', 'USERNAME']);
     * ```
     * 
     * @param string[] $keys กำหนดรายการคีย์ที่ต้องการทำให้เป็นแบบมาตรฐาน
     * @return string[] คืนรายการคีย์ที่ถูกปรับให้เป็นแบบมาตรฐาน
     */
    private function normalizeKeyList(array $keys): array
    {
        $normalized = [];
        foreach ($keys as $key) {
            if (!is_string($key)) {
                continue;
            }
            $normalized[] = strtolower($key);
        }

        return array_values(array_unique($normalized));
    }

    /**
     * รวมรายการคีย์ใหม่กับรายการคีย์ปัจจุบันโดยทำให้เป็นแบบมาตรฐานและลบค่าซ้ำ
     * จุดประสงค์: รวมรายการคีย์ใหม่กับรายการคีย์ปัจจุบันโดยทำให้เป็นแบบมาตรฐานและลบค่าซ้ำ
     * mergeKeyList() ควรใช้กับอะไร: เมื่อคุณต้องการรวมรายการคีย์ใหม่กับรายการคีย์ปัจจุบัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $mergedKeys = $logger->mergeKeyList($currentKeys, $newKeys);
     * ```
     * 
     * @param string[] $current กำหนดรายการคีย์ปัจจุบัน
     * @param string[] $keys กำหนดรายการคีย์ใหม่ที่ต้องการรวมกับรายการคีย์ปัจจุบัน
     * @return string[] คืนรายการคีย์ที่ถูกปรับให้เป็นแบบมาตรฐานและรวมกันแล้วโดยลบค่าซ้ำ
     */
    private function mergeKeyList(array $current, array $keys): array
    {
        $merged = array_merge($current, $this->normalizeKeyList($keys));
        return array_values(array_unique($merged));
    }
}
