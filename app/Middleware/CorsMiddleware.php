<?php
/**
 * MIDDLEWARE CORS (Cross-Origin Resource Sharing)
 * 
 * จุดประสงค์: จัดการ CORS headers สำหรับ API requests ข้าม origin
 * 
 * การใช้งาน:
 * ใช้กับ API endpoints ทั้งหมดที่อนุญาตให้เข้าถึงจากโดเมนอื่น:
 * - /api/* (API endpoints ทั้งหมด)
 * 
 * CORS คืออะไร?
 * - กลไกความปลอดภัยของเบราว์เซอร์
 * - ป้องกันการเรียก API จาก origin ที่ไม่ได้รับอนุญาต
 * - จำเป็นสำหรับ frontend/backend แยกกัน
 * 
 * วิธีการทำงาน:
 * 1. ตรวจสอบ origin ของคำขอ
 * 2. ตั้งค่า CORS headers ที่เหมาะสม
 * 3. จัดการ preflight requests (OPTIONS)
 * 4. อนุญาตเฉพาะ origins ที่กำหนด
 * 
 * การกำหนดค่า:
 * - allowedOrigins: โดเมนที่อนุญาต
 * - allowedMethods: HTTP methods ที่อนุญาต
 * - allowedHeaders: Headers ที่อนุญาต
 * - maxAge: เวลา cache สำหรับ preflight
 */

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Logger;

class CorsMiddleware extends Middleware
{
    private Logger $logger;

    /**
     * Origins ที่อนุญาต
     * 
     * ในโปรดักชัน:
     * - ระบุเฉพาะโดเมนที่ต้องการ
     * - ไม่ใช้ '*' (อนุญาตทุก origin)
     * - โหลดจาก config หรือ .env
     */
    private array $allowedOrigins = [
        'http://localhost:3000',
        'http://localhost:8080',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:8080',
        // เพิ่ม production domains ที่นี่
    ];

    /**
     * HTTP methods ที่อนุญาต
     */
    private array $allowedMethods = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'PATCH',
        'OPTIONS',
    ];

    /**
     * Headers ที่อนุญาต
     */
    private array $allowedHeaders = [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'X-API-Key',
        'X-CSRF-Token',
    ];

    /**
     * Headers ที่ส่งกลับได้
     */
    private array $exposedHeaders = [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
    ];

    /**
     * เวลา cache สำหรับ preflight request (วินาที)
     */
    private int $maxAge = 86400; // 24 ชั่วโมง

    /**
     * อนุญาต credentials (cookies, authorization headers)
     */
    private bool $allowCredentials = true;

    public function __construct()
    {
        $this->logger = new Logger();

        // โหลด allowed origins จาก config ถ้ามี
        $configOrigins = getenv('CORS_ALLOWED_ORIGINS');
        if ($configOrigins) {
            $this->allowedOrigins = array_merge(
                $this->allowedOrigins,
                explode(',', $configOrigins)
            );
        }
    }

    /**
     * จัดการ CORS headers
     * 
     * @return bool True เพื่อดำเนินการต่อ, false เพื่อหยุด
     */
    public function handle(): bool
    {
        // รับ origin ของคำขอ
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // ถ้าไม่มี origin หรือไม่ใช่ cross-origin request ข้ามได้
        if (empty($origin)) {
            return true;
        }

        // ตรวจสอบว่า origin อนุญาตหรือไม่
        if (!$this->isOriginAllowed($origin)) {
            $this->logger->security('cors.origin_not_allowed', [
                'origin' => $origin,
                'route' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            ]);

            // ไม่ตั้ง CORS headers = เบราว์เซอร์จะบล็อก
            return true; // ให้คำขอดำเนินการต่อแต่เบราว์เซอร์จะบล็อกการตอบกลับ
        }

        // ตั้งค่า CORS headers
        $this->setCorsHeaders($origin);

        // จัดการ preflight request (OPTIONS)
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        if ($method === 'OPTIONS') {
            $this->logger->info('cors.preflight_request', [
                'origin' => $origin,
                'route' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            ]);

            // ส่งการตอบกลับ 200 OK สำหรับ preflight
            http_response_code(200);
            exit;
        }

        return true; // ดำเนินการคำขอปกติต่อ
    }

    /**
     * ตรวจสอบว่า origin อนุญาตหรือไม่
     * 
     * @param string $origin
     * @return bool
     */
    private function isOriginAllowed(string $origin): bool
    {
        // อนุญาตทุก origin (ไม่แนะนำในโปรดักชัน)
        if (in_array('*', $this->allowedOrigins)) {
            return true;
        }

        // ตรวจสอบ origin ที่ระบุ
        return in_array($origin, $this->allowedOrigins);
    }

    /**
     * ตั้งค่า CORS headers
     * 
     * @param string $origin
     */
    private function setCorsHeaders(string $origin): void
    {
        // Origin ที่อนุญาต
        header("Access-Control-Allow-Origin: {$origin}");

        // Methods ที่อนุญาต
        header('Access-Control-Allow-Methods: ' . implode(', ', $this->allowedMethods));

        // Headers ที่อนุญาต
        header('Access-Control-Allow-Headers: ' . implode(', ', $this->allowedHeaders));

        // Headers ที่ส่งกลับได้
        if (!empty($this->exposedHeaders)) {
            header('Access-Control-Expose-Headers: ' . implode(', ', $this->exposedHeaders));
        }

        // อนุญาต credentials
        if ($this->allowCredentials) {
            header('Access-Control-Allow-Credentials: true');
        }

        // เวลา cache สำหรับ preflight
        header("Access-Control-Max-Age: {$this->maxAge}");
    }

    /**
     * เพิ่ม origin ที่อนุญาต
     * 
     * @param string|array $origins
     */
    public function addAllowedOrigins($origins): void
    {
        if (is_string($origins)) {
            $origins = [$origins];
        }

        $this->allowedOrigins = array_merge($this->allowedOrigins, $origins);
    }

    /**
     * ตั้งค่า allowed methods
     * 
     * @param array $methods
     */
    public function setAllowedMethods(array $methods): void
    {
        $this->allowedMethods = $methods;
    }

    /**
     * ตั้งค่า allowed headers
     * 
     * @param array $headers
     */
    public function setAllowedHeaders(array $headers): void
    {
        $this->allowedHeaders = $headers;
    }

    /**
     * เปิด/ปิด credentials
     * 
     * @param bool $allow
     */
    public function setAllowCredentials(bool $allow): void
    {
        $this->allowCredentials = $allow;
    }
}
