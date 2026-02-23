<?php

/**
 * คลาสนี้เป็นมิดเดิลแวร์สำหรับการตรวจสอบ API key
 * 
 * จุดประสงค์: ตรวจสอบ API key สำหรับ API endpoints ที่ละเอียดอ่อน
 * ApiKeyMiddleware ควรใช้กับอะไร: มิดเดิลแวร์ที่ใช้ในการป้องกันเส้นทาง API
 * 
 * การใช้งาน:
 * เส้นทาง API ที่ป้องกันต้องการ API key ที่ถูกต้อง:
 * - POST /api/* (การสร้างคำสั่งซื้อ)
 * - PUT /api/* (การอัปเดตคำสั่งซื้อ)
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
use App\Core\Request;
use App\Core\Logger;
use App\Core\Response;
use App\Core\Config;

class ApiKeyMiddleware extends Middleware
{
    /**
     * ตัวบันทึกเหตุการณ์ (Logger)
     */
    private Logger $logger;

    /**
     * API keys ที่ถูกต้อง
     *
     * ในโปรดักชัน: โหลดจากฐานข้อมูลหรือ environment (ไม่ควรมีคีย์ตัวอย่างในโค้ด)
     */
    private array $validKeys = [];

    /**
     * สร้างอินสแตนซ์ ApiKeyMiddleware ใหม่
     * จุดประสงค์: เตรียมรายการ API keys ที่ถูกต้อง
     * __construct() ควรใช้กับอะไร: เมื่อสร้างมิดเดิลแวร์สำหรับตรวจสอบ API key
     * ตัวอย่างการใช้งาน:
     * ```php
     * $middleware = new ApiKeyMiddleware();
     * ```
     */
    public function __construct()
    {
        $this->logger = new Logger();

        // โหลด API keys จาก config
        $this->validKeys = (array) Config::get('api.keys', []);
    }

    /**
     * จัดการการตรวจสอบ API key
     * จุดประสงค์: ตรวจสอบว่า API key ที่ให้มาถูกต้องหรือไม่
     * handle() ควรใช้กับอะไร: เมื่อมีคำขอเข้ามาที่ต้องการการตรวจสอบ API key
     * ตัวอย่างการใช้งาน:
     * ```php
     * $response = $middleware->handle($request);
     * ```
     * 
     * @return bool|Response True เพื่อดำเนินการต่อ, false เพื่อหยุด, หรือ Response เพื่อส่งกลับทันที
     * @return bool|Response True ถ้าคีย์ถูกต้อง, Response 401 ถ้าไม่ถูกต้อง
     */
    public function handle(?Request $request = null): bool|Response
    {
        // รับ API key จาก header หรือ query string (รองรับ Request wrapper ถ้ามี)
        $apiKey = $this->getApiKey($request);
        
        // ถ้าไม่มีคีย์ ให้บันทึกและส่งกลับ 401 Unauthorized
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
     * จุดประสงค์: ดึง API key จาก header หรือ query string
     * getApiKey() ควรใช้กับอะไร: เมื่อมีคำขอเข้ามาที่ต้องการตรวจสอบ API key
     * ตัวอย่างการใช้งาน:
     * ```php
     * $key = $middleware->getApiKey($request);
     * ```
     * 
     * ตรวจสอบ:
     * 1. X-API-Key header
     * 2. Authorization: Bearer {key} header
     * 3. api_key query parameter
     * 
     * @param Request|null $request คำขอที่เข้ามา ซึ่งสามารถเป็น null ได้ในกรณีที่ middleware นี้ถูกเรียกโดยไม่มีคำขอ (เช่น ในบางสถานการณ์ของ CLI)
     * @return string|null คืนค่า API key ที่ดึงมาได้ หรือ null หากไม่พบคีย์ในคำขอ
     */
    private function getApiKey(?\App\Core\Request $request = null): ?string
    {
        // ตรวจสอบ Request wrapper ก่อน (ถ้ามี)
        if ($request !== null) {
            $headerKey = $request->header('X-API-Key');
            if ($headerKey) {
                return trim($headerKey); // คืนค่า key
            }

            $bearer = $request->bearerToken();
            if ($bearer) {
                return trim($bearer);
            }

            $queryKey = $request->get('api_key');
            if ($queryKey) {
                return trim($queryKey);
            }
        }

        // ตรวจสอบ headers โดยตรง
        if (function_exists('getallheaders')) {

            // ตรวจสอบ X-API-Key header
            $headers = getallheaders();
            if (isset($headers['X-API-Key'])) {
                return trim($headers['X-API-Key']); // คืนค่า key
            }
            if (isset($headers['Authorization'])) {
                $auth = $headers['Authorization'];

                // ตรวจสอบรูปแบบ Bearer token
                if (preg_match('/^Bearer\s+(.+)$/i', $auth, $matches)) {
                    return trim($matches[1]); // คืนค่า token
                }
            }
        }

        // ไม่รับ API key จาก query string (เพื่อหลีกเลี่ยงการรั่วไหลผ่าน Referer/logs)
        // ไม่พบ API key
        return null;
    }

    /**
     * ตรวจสอบว่า API key ถูกต้องหรือไม่
     * จุดประสงค์: เปรียบเทียบ API key ที่ได้รับกับรายการคีย์ที่ถูกต้องเพื่ออนุญาตหรือปฏิเสธคำขอ
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
     * จุดประสงค์: ให้สามารถเพิ่ม API key ใหม่ได้อย่างง่ายดายผ่านฟังก์ชันนี้
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
     * จุดประสงค์: ให้สามารถลบ API key ที่ไม่ต้องการใช้งานอีกต่อไปได้อย่างง่ายดายผ่านฟังก์ชันนี้
     * 
     * @param string $key API key ที่จะลบ
     */
    public function removeKey(string $key): void
    {
        $this->validKeys = array_filter($this->validKeys, function ($k) use ($key) {
            return $k !== $key;
        });
    }
}
