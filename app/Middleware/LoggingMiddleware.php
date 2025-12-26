<?php
/**
 * MIDDLEWARE LOGGING (บันทึกคำขอ)
 * 
 * จุดประสงค์: บันทึกข้อมูลคำขอ HTTP ทั้งหมดเพื่อการวิเคราะห์และดีบัก
 * 
 * การใช้งาน:
 * ใช้กับทุกคำขอหรือเฉพาะส่วนที่ต้องการติดตาม:
 * - บันทึกคำขอ API ทั้งหมด
 * - ติดตามพฤติกรรมผู้ใช้
 * - วิเคราะห์ประสิทธิภาพ
 * - ดีบักปัญหา
 * 
 * ข้อมูลที่บันทึก:
 * - เวลา timestamp
 * - HTTP method
 * - URL/route
 * - IP address
 * - User agent
 * - User ID (ถ้าเข้าสู่ระบบ)
 * - เวลาในการประมวลผล
 * - Response status code
 * 
 * การตั้งค่า:
 * - เลือกว่าจะบันทึกข้อมูลไหนบ้าง
 * - กำหนดเส้นทางที่ไม่ต้องบันทึก
 * - ตั้งค่าระดับ detail
 */

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Logger;

class LoggingMiddleware extends Middleware
{
    private Logger $logger;
    
    /**
     * เวลาเริ่มต้นคำขอ
     */
    private float $startTime;

    /**
     * เส้นทางที่ไม่ต้องบันทึก
     */
    private array $exceptRoutes = [
        '/health',
        '/ping',
    ];

    /**
     * บันทึกรายละเอียดเพิ่มเติมหรือไม่
     */
    private bool $detailed = false;

    /**
     * บันทึก request body หรือไม่
     */
    private bool $logBody = false;

    public function __construct(bool $detailed = false, bool $logBody = false)
    {
        $this->logger = new Logger();
        $this->startTime = microtime(true);
        $this->detailed = $detailed;
        $this->logBody = $logBody;

        // โหลดการตั้งค่าจาก config
        if (getenv('LOG_DETAILED') === 'true') {
            $this->detailed = true;
        }
        if (getenv('LOG_REQUEST_BODY') === 'true') {
            $this->logBody = true;
        }
    }

    /**
     * จัดการการบันทึกคำขอ
     * 
     * @return bool True เพื่อดำเนินการต่อ
     */
    public function handle(): bool
    {
        // ตรวจสอบว่าควรบันทึกหรือไม่
        if ($this->shouldSkipLogging()) {
            return true;
        }

        // บันทึกข้อมูลคำขอ
        $this->logRequest();

        // ลงทะเบียน shutdown function เพื่อบันทึกการตอบกลับ
        register_shutdown_function([$this, 'logResponse']);

        return true;
    }

    /**
     * ตรวจสอบว่าควรข้ามการบันทึกหรือไม่
     * 
     * @return bool
     */
    private function shouldSkipLogging(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        foreach ($this->exceptRoutes as $route) {
            if (strpos($uri, $route) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * บันทึกข้อมูลคำขอ
     */
    private function logRequest(): void
    {
        $data = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'ip' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ];

        // เพิ่มข้อมูลผู้ใช้ถ้าเข้าสู่ระบบ
        if ($this->isAuthenticated()) {
            $data['user_id'] = $this->getUserId();
        }

        // เพิ่มรายละเอียด
        if ($this->detailed) {
            $data['referer'] = $_SERVER['HTTP_REFERER'] ?? null;
            $data['protocol'] = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
            $data['headers'] = $this->getRelevantHeaders();
        }

        // บันทึก request body (ระวัง: อย่าบันทึกข้อมูลละเอียดอ่อน)
        if ($this->logBody && in_array($data['method'], ['POST', 'PUT', 'PATCH'])) {
            $body = file_get_contents('php://input');
            // ซ่อนข้อมูลละเอียดอ่อน
            $data['body'] = $this->sanitizeBody($body);
        }

        $this->logger->info('request.incoming', $data);
    }

    /**
     * บันทึกการตอบกลับ (เรียกเมื่อ script จบ)
     */
    public function logResponse(): void
    {
        $executionTime = microtime(true) - $this->startTime;
        $statusCode = http_response_code();

        $data = [
            'status_code' => $statusCode,
            'execution_time' => round($executionTime, 4),
            'memory_usage' => $this->formatBytes(memory_get_peak_usage(true)),
        ];

        // บันทึกตามระดับ status code
        if ($statusCode >= 500) {
            $this->logger->error('request.completed', $data);
        } elseif ($statusCode >= 400) {
            $this->logger->warning('request.completed', $data);
        } else {
            $this->logger->info('request.completed', $data);
        }

        // บันทึกคำขอที่ช้า (> 1 วินาที)
        if ($executionTime > 1.0) {
            $this->logger->warning('request.slow', [
                'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'execution_time' => round($executionTime, 4),
            ]);
        }
    }

    /**
     * รับ IP address ของ client
     * 
     * @return string
     */
    private function getClientIP(): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        if (strpos($ip, ',') !== false) {
            $ip = explode(',', $ip)[0];
        }

        return trim($ip);
    }

    /**
     * รับ headers ที่เกี่ยวข้อง
     * 
     * @return array
     */
    private function getRelevantHeaders(): array
    {
        $headers = [];
        $relevantHeaders = [
            'HTTP_ACCEPT',
            'HTTP_ACCEPT_LANGUAGE',
            'HTTP_ACCEPT_ENCODING',
            'HTTP_CONTENT_TYPE',
            'HTTP_AUTHORIZATION',
        ];

        foreach ($relevantHeaders as $header) {
            if (isset($_SERVER[$header])) {
                $headerName = str_replace('HTTP_', '', $header);
                $headerName = str_replace('_', '-', $headerName);
                $headers[$headerName] = $_SERVER[$header];
            }
        }

        // ซ่อน authorization token
        if (isset($headers['AUTHORIZATION'])) {
            $headers['AUTHORIZATION'] = 'Bearer ***';
        }

        return $headers;
    }

    /**
     * ทำความสะอาด request body (ซ่อนข้อมูลละเอียดอ่อน)
     * 
     * @param string $body
     * @return string
     */
    private function sanitizeBody(string $body): string
    {
        // ลองแปลงเป็น JSON
        $data = json_decode($body, true);
        
        if ($data === null) {
            // ไม่ใช่ JSON หรือข้อมูล POST ธรรมดา
            parse_str($body, $data);
        }

        // ซ่อนฟิลด์ที่ละเอียดอ่อน
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'token',
            'api_key',
            'secret',
            'credit_card',
            'cvv',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***HIDDEN***';
            }
        }

        return json_encode($data);
    }

    /**
     * แปลง bytes เป็นรูปแบบที่อ่านง่าย
     * 
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * เพิ่มเส้นทางที่ไม่ต้องบันทึก
     * 
     * @param string|array $routes
     */
    public function addExceptRoutes($routes): void
    {
        if (is_string($routes)) {
            $routes = [$routes];
        }

        $this->exceptRoutes = array_merge($this->exceptRoutes, $routes);
    }

    /**
     * เปิด/ปิด detailed logging
     * 
     * @param bool $detailed
     */
    public function setDetailed(bool $detailed): void
    {
        $this->detailed = $detailed;
    }

    /**
     * เปิด/ปิด body logging
     * 
     * @param bool $logBody
     */
    public function setLogBody(bool $logBody): void
    {
        $this->logBody = $logBody;
    }
}
