<?php
/**
 * MIDDLEWARE LOGGING (บันทึกคำขอ)
 * 
 * Middleware สำหรับนระบบ หรือ Global Middleware
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

namespace App\Middleware\Systems;

use App\Core\Middleware;
use App\Core\Logger;
use App\Core\Config;

class LoggingMiddleware extends Middleware
{
    /**
     * Logger สำหรับบันทึกข้อมูลคำขอ
     */
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

    /**
     * สร้าง instance ของ LoggingMiddleware
     * 
     * @param bool $detailed เลือกว่าจะบันทึกรายละเอียดเพิ่มเติมหรือไม่ (เช่น headers, referer)
     * @param bool $logBody เลือกว่าจะบันทึก request body หรือไม่ (ระวัง: อย่าบันทึกข้อมูลละเอียดอ่อน)
     */
    public function __construct(bool $detailed = false, bool $logBody = false)
    {
        $this->logger = new Logger();
        $this->startTime = 0.0;
        $this->detailed = $detailed;
        $this->logBody = $logBody;

        // โหลดการตั้งค่าจาก config
        if (Config::get('logging.detailed', false) === true) {
            $this->detailed = true;
        }
        if (Config::get('logging.request_body', false) === true) {
            $this->logBody = true;
        }
    }

    /**
    * จัดการการบันทึกคำขอ
    * จุดประสงค์: บันทึกข้อมูลคำขอ HTTP ทั้งหมดตามการตั้งค่าที่กำหนด
     * 
     * @param \App\Core\Request|null $request คำขอที่เข้ามา (สามารถเป็น null ได้)
     * @return bool|Response คืนค่า true เพื่อดำเนินการต่อ, false เพื่อหยุด หรือ Response เพื่อส่งกลับทันที
     */    
    public function handle(?\App\Core\Request $request = null): bool|\App\Core\Response
    {
        // ตรวจสอบว่าควรบันทึกหรือไม่
        if ($this->shouldSkipLogging()) {
            return true;
        }

        $this->startTime = microtime(true);

        // บันทึกข้อมูลคำขอ
        $this->logRequest();

        return true;
    }

    /**
     * ทำงานหลังจากตัวควบคุมเสร็จสิ้น เพื่อบันทึกผลการตอบกลับ
     *
     * @param \App\Core\Request|null $request คำขอที่เข้ามา (สามารถเป็น null ได้)
     * @param \App\Core\Response|string|null $response การตอบกลับที่ได้จากตัวควบคุม (สามารถเป็น null หรือ string ได้)
     * @return \App\Core\Response|string|null คืนค่าการตอบกลับที่ได้รับมา (สามารถเป็น null หรือ string ได้) เพื่อส่งกลับไปยังผู้ใช้
     */
    public function after(?\App\Core\Request $request = null, \App\Core\Response|string|null $response = null): \App\Core\Response|string|null
    {
        if ($this->shouldSkipLogging()) {
            return $response;
        }

        $this->logResponse($response instanceof \App\Core\Response ? $response : null);

        return $response;
    }

    /**
     * ตรวจสอบว่าควรข้ามการบันทึกหรือไม่
     * จุดประสงค์: ให้สามารถกำหนดเส้นทางที่ไม่ต้องการบันทึกข้อมูลคำขอได้ เช่น เส้นทางสำหรับ health check หรือ ping ที่มีการเรียกใช้บ่อยและไม่จำเป็นต้องบันทึก
     * 
     * @return bool คืนค่า true หากควรข้ามการบันทึก, false หากควรบันทึก
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
     * จุดประสงค์: บันทึกข้อมูลคำขอ HTTP ทั้งหมดตามการตั้งค่าที่กำหนด เช่น method, URI, IP, user agent และข้อมูลเพิ่มเติมถ้าเปิดใช้งาน detailed logging หรือ body logging
     * 
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    private function logRequest(): void
    {
        $data = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'ip' => $this->getClientIp(),
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
    * บันทึกการตอบกลับ (เรียกจาก after())
    * จุดประสงค์: บันทึกข้อมูลการตอบกลับ HTTP ทั้งหมดตามการตั้งค่าที่กำหนด เช่น status code, execution time, memory usage และคำขอที่ช้า
     * 
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    private function logResponse(?\App\Core\Response $response): void
    {
        $executionTime = microtime(true) - $this->startTime;
        $statusCode = $response ? $response->getStatusCode() : http_response_code();

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
     * รับ headers ที่เกี่ยวข้อง
     * จุดประสงค์: ดึงเฉพาะ headers ที่สำคัญและเกี่ยวข้องกับการวิเคราะห์คำขอ เช่น Accept, Content-Type, Authorization และซ่อนข้อมูลละเอียดอ่อนใน headers เหล่านี้
     * 
     * @return array รายการ headers ที่เกี่ยวข้องและถูกทำความสะอาดแล้ว
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
     * จุดประสงค์: ป้องกันการบันทึกข้อมูลที่ละเอียดอ่อนใน request body เช่น รหัสผ่าน, token, หรือข้อมูลบัตรเครดิต โดยการแปลงข้อมูลเหล่านี้เป็นค่า ***HIDDEN*** ก่อนบันทึก
     * 
     * @param string $body เนื้อหาของ request body ที่ต้องการทำความสะอาด
     * @return string เนื้อหาของ request body ที่ถูกทำความสะอาดแล้ว
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
     * จุดประสงค์: แปลงจำนวน bytes เป็นหน่วยที่อ่านง่าย เช่น KB, MB, GB เพื่อให้ง่ายต่อการเข้าใจและวิเคราะห์ข้อมูลการใช้หน่วยความจำ
     * 
     * @param int $bytes จำนวน bytes ที่ต้องการแปลง
     * @return string จำนวนที่ถูกแปลงเป็นรูปแบบที่อ่านง่าย เช่น 1.5 MB, 200 KB
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        $value = (float) $bytes;

        while ($value >= 1024 && $unitIndex < count($units) - 1) {
            $value /= 1024;
            $unitIndex++;
        }

        return sprintf('%.2f %s (%d B)', $value, $units[$unitIndex], $bytes);
    }

    /**
     * เพิ่มเส้นทางที่ไม่ต้องบันทึก
     * จุดประสงค์: ให้สามารถเพิ่มเส้นทางที่ไม่ต้องการบันทึกข้อมูลคำขอได้อย่างง่ายดาย โดยรับเส้นทางเป็น string หรือ array ของเส้นทาง และเพิ่มเข้าไปในรายการ exceptRoutes
     * 
     * @param string|array $routes เส้นทางที่ต้องการยกเว้นจากการบันทึก
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะเพิ่มเส้นทางที่ระบุเข้าไปในรายการ exceptRoutes เพื่อให้ middleware ข้ามการบันทึกสำหรับเส้นทางเหล่านี้
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
     * จุดประสงค์: ให้สามารถเลือกได้ว่าจะบันทึกรายละเอียดเพิ่มเติมของคำขอ เช่น headers, referer, protocol หรือไม่ เพื่อให้มีข้อมูลมากขึ้นสำหรับการวิเคราะห์และดีบัก หรือเพื่อประหยัดพื้นที่จัดเก็บและลดความซับซ้อนของข้อมูลที่บันทึก
     * 
     * @param bool $detailed กำหนดว่าจะเปิดหรือปิด detailed logging  
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะตั้งค่าการบันทึกรายละเอียดเพิ่มเติมตามที่ระบุ เพื่อให้ middleware บันทึกข้อมูลคำขออย่างละเอียดหรือไม่ตามการตั้งค่านี้
     */
    public function setDetailed(bool $detailed): void
    {
        $this->detailed = $detailed;
    }

    /**
     * เปิด/ปิด body logging
     * จุดประสงค์: ให้สามารถเลือกได้ว่าจะบันทึก request body หรือไม่ เพื่อให้มีข้อมูลมากขึ้นสำหรับการวิเคราะห์และดีบักคำขอที่มีข้อมูลใน body เช่น POST, PUT, PATCH หรือเพื่อป้องกันการบันทึกข้อมูลที่ละเอียดอ่อนใน body และลดความซับซ้อนของข้อมูลที่บันทึก
     * 
     * @param bool $logBody กำหนดว่าจะเปิดหรือปิดการบันทึก request body
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะตั้งค่าการบันทึก request body ตามที่ระบุ
     */
    public function setLogBody(bool $logBody): void
    {
        $this->logBody = $logBody;
    }
}
