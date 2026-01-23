<?php
/**
 * MIDDLEWARE API KEY
 * 
 * จุดประสงค์: ตรวจสอบ API key สำหรับ API endpoints ที่ละเอียดอ่อน
 * 
 * การใช้งาน:
 * เส้นทาง API ที่ป้องกันต้องการ API key ที่ถูกต้อง:
 * - POST /api/v1/orders/* (การสร้างคำสั่งซื้อ)
 * - PUT /api/v1/orders/* (การอัปเดตคำสั่งซื้อ)
 * 
 * วิธีการทำงาน:
 * 1. ตรวจสอบ API key ใน header (X-API-Key) หรือ query string
 * 2. ตรวจสอบคีย์กับคีย์ที่เก็บไว้
 * 3. อนุญาตหรือปฏิเสธคำขอ
 * 
 * ความปลอดภัย:
 * - บันทึกความพยายามใช้ API key ที่ไม่ถูกต้องทั้งหมด
 * - จำกัดอัตราความล้มเหลว (การปรับปรุงในอนาคต)
 * - รองรับคีย์ที่ถูกต้องหลายตัว
 * 
 * รูปแบบ API Key:
 * - Header: X-API-Key: your-api-key-here
 * - Query: ?api_key=your-api-key-here
 * 
 * ที่เก็บคีย์:
 * - ตัวแปรสภาพแวดล้อม (.env)
 * - ฐานข้อมูล (สำหรับแอป multi-tenant)
 * - ไฟล์กำหนดค่า (สำหรับแอปง่ายๆ)
 */

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Logger;
use App\Core\Response;

class ApiKeyMiddleware extends Middleware
{
    private Logger $logger;

    /**
     * API keys ที่ถูกต้อง
     * 
     * ในโปรดักชัน:
     * - โหลดจากฐานข้อมูล
     * - โหลดจากตัวแปรสภาพแวดล้อม
     * - ใช้คีย์ที่เข้ารหัสแล้ว
     * 
     * ตัวอย่างคีย์สำหรับการสาธิต:
     */
    private array $validKeys = [
        'demo-api-key-12345',
        'test-key-67890',
    ];

    public function __construct()
    {
        $this->logger = new Logger();

        // โหลด API keys จากสภาพแวดล้อมถ้ามี
        $envKey = getenv('API_KEY');
        if ($envKey) {
            $this->validKeys[] = $envKey;
        }
    }

    /**
     * จัดการการตรวจสอบ API key
     * 
        * @return bool|Response True เพื่อดำเนินการต่อ, false เพื่อหยุด, หรือ Response เพื่อส่งกลับทันที
     */
        public function handle(?\App\Core\Request $request = null): bool|Response
    {
        // รับ API key จาก header หรือ query string
        $apiKey = $this->getApiKey();

        if (!$apiKey) {
            $this->logger->security('api.missing_key', [
                'route' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            ]);

            return $this->jsonError('API key required', 401);
        }

        // ตรวจสอบ API key
        if (!$this->isValidKey($apiKey)) {
            $this->logger->security('api.invalid_key', [
                'route' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'key_provided' => substr($apiKey, 0, 10) . '...', // บันทึกเฉพาะส่วนของคีย์
            ]);

            return $this->jsonError('Invalid API key', 401);
        }

        // คีย์ถูกต้อง - บันทึกการเข้าถึงที่สำเร็จ
        $this->logger->info('api.key_validated', [
            'route' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        ]);

        return true; // ดำเนินการต่อไปยังตัวควบคุม
    }

    /**
     * รับ API key จากคำขอ
     * 
     * ตรวจสอบ:
     * 1. X-API-Key header
     * 2. Authorization: Bearer {key} header
     * 3. api_key query parameter
     * 
     * @return string|null API key หรือ null
     */
    private function getApiKey(): ?string
    {
        // ตรวจสอบ X-API-Key header
        $headers = getallheaders();
        if (isset($headers['X-API-Key'])) {
            return trim($headers['X-API-Key']);
        }

        // ตรวจสอบ Authorization Bearer header
        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
            if (preg_match('/^Bearer\s+(.+)$/i', $auth, $matches)) {
                return trim($matches[1]);
            }
        }

        // ตรวจสอบ query parameter
        if (isset($_GET['api_key'])) {
            return trim($_GET['api_key']);
        }

        return null;
    }

    /**
     * ตรวจสอบว่า API key ถูกต้องหรือไม่
     * 
     * @param string $key API key ที่จะตรวจสอบ
     * @return bool True ถ้าถูกต้อง
     */
    private function isValidKey(string $key): bool
    {
        return in_array($key, $this->validKeys, true);
    }

    /**
     * เพิ่ม API key ลงในรายการคีย์ที่ถูกต้อง
     * 
     * ใช้โดยฟังก์ชันผู้ดูแลระบบเพื่อจัดการ API keys
     * 
     * @param string $key API key ใหม่
     */
    public function addKey(string $key): void
    {
        if (!in_array($key, $this->validKeys, true)) {
            $this->validKeys[] = $key;
        }
    }

    /**
     * ลบ API key ออกจากรายการคีย์ที่ถูกต้อง
     * 
     * @param string $key API key ที่จะลบ
     */
    public function removeKey(string $key): void
    {
        $this->validKeys = array_filter($this->validKeys, function($k) use ($key) {
            return $k !== $key;
        });
    }
}
