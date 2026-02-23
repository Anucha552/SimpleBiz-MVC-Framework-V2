<?php
/**
 * MIDDLEWARE CORS (Cross-Origin Resource Sharing)
 * 
 * Middleware สำหรับนระบบ หรือ API Global Middleware
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

namespace App\Middleware\Systems;

use App\Core\Middleware;
use App\Core\Logger;
use App\Core\Response;
use App\Core\Config;

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
    private array $allowedOrigins = [];

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

    /**
     * สร้าง instance ของ CorsMiddleware
     * จุดประสงค์: โหลดการตั้งค่า allowed origins จาก config และตรวจสอบความปลอดภัยของการตั้งค่า
      * 
      * ในโปรดักชัน:
      * - โหลด allowed origins จาก config หรือ .env เท่านั้น
      * - ไม่อนุญาต wildcard origins ร่วมกับ credentials
     */
    public function __construct()
    {
        $this->logger = new Logger();

        // โหลด allowed origins จาก config เท่านั้น
        $configOrigins = (array) Config::get('cors.allowed_origins', []);
        $this->allowedOrigins = array_values(array_filter(array_map('trim', $configOrigins), 'strlen'));

        // Guard: do not allow wildcard origins with credentials
        if ($this->hasWildcardOrigin() && $this->allowCredentials) {
            $this->allowCredentials = false;
            $this->logger->warning('cors.wildcard_credentials_disabled', [
                'reason' => 'Wildcard origin with credentials is unsafe; disabling credentials.',
            ]);
        }
    }

    /**
     * จัดการ CORS headers
     * จุดประสงค์: ตรวจสอบ origin ของคำขอและตั้งค่า CORS headers ที่เหมาะสมสำหรับคำขอ cross-origin
     * 
     * การทำงาน:
     * 1. ตรวจสอบว่าเป็น cross-origin request หรือไม่
     * 2. ตรวจสอบว่า origin อนุญาตหรือไม่
     * 3. ตั้งค่า CORS headers ที่เหมาะสม
     * 4. จัดการ preflight requests (OPTIONS)
     * 
     * @return bool|Response True เพื่อดำเนินการต่อ, false เพื่อหยุด, หรือ Response เพื่อส่งกลับทันที
     */
        public function handle(?\App\Core\Request $request = null): bool|Response
    {
            if ($request === null) {
                return true;
            }

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
        $this->setCorsHeaders($request, $origin);

        // จัดการ preflight request (OPTIONS)
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        if ($method === 'OPTIONS') {
            $this->logger->info('cors.preflight_request', [
                'origin' => $origin,
                'route' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            ]);

            // ส่งการตอบกลับสำหรับ preflight
            return Response::noContent();
        }

        return true; // ดำเนินการคำขอปกติต่อ
    }

    /**
     * ตรวจสอบว่า origin อนุญาตหรือไม่
     * จุดประสงค์: ตรวจสอบว่า origin ของคำขออยู่ในรายการ allowed origins หรือไม่ เพื่อป้องกันการเข้าถึงจากโดเมนที่ไม่ได้รับอนุญาต
     * 
     * @param string $origin Origin ของคำขอ
     * @return bool ผลลัพธ์การตรวจสอบว่า origin อนุญาตหรือไม่
     */
    private function isOriginAllowed(string $origin): bool
    {
        // อนุญาตทุก origin (ไม่แนะนำในโปรดักชัน)
        if ($this->hasWildcardOrigin()) {
            return true;
        }

        // ตรวจสอบ origin ที่ระบุ
        return in_array($origin, $this->allowedOrigins);
    }

    /**
     * ตั้งค่า CORS headers
     * จุดประสงค์: ตั้งค่า CORS headers ที่เหมาะสมสำหรับคำขอ cross-origin เพื่อให้เบราว์เซอร์สามารถจัดการคำขอได้อย่างถูกต้องและปลอดภัย
     * 
     * @param \App\Core\Request $request
     * @param string $origin Origin ของคำขอ
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    private function setCorsHeaders(\App\Core\Request $request, string $origin): void
    {
        // Origin ที่อนุญาต
        if ($this->hasWildcardOrigin() && $this->allowCredentials === false) {
            $request->setResponseHeader('Access-Control-Allow-Origin', '*');
        } else {
            $request->setResponseHeader('Access-Control-Allow-Origin', $origin);
        }

        // Methods ที่อนุญาต
        $request->setResponseHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));

        // Headers ที่อนุญาต
        $request->setResponseHeader('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));

        // Headers ที่ส่งกลับได้
        if (!empty($this->exposedHeaders)) {
            $request->setResponseHeader('Access-Control-Expose-Headers', implode(', ', $this->exposedHeaders));
        }

        // อนุญาต credentials
        if ($this->allowCredentials) {
            $request->setResponseHeader('Access-Control-Allow-Credentials', 'true');
        }

        // เวลา cache สำหรับ preflight
        $request->setResponseHeader('Access-Control-Max-Age', (string) $this->maxAge);
    }

    /**
     * ตรวจสอบว่ามี wildcard origin หรือไม่
     * จุดประสงค์: ตรวจสอบว่ารายการ allowed origins มี wildcard ('*') หรือไม่ เพื่อใช้ในการตัดสินใจเกี่ยวกับการตั้งค่า CORS headers และความปลอดภัย
     * 
     * @return bool ผลลัพธ์การตรวจสอบว่ามี wildcard origin หรือไม่
     */
    private function hasWildcardOrigin(): bool
    {
        return in_array('*', $this->allowedOrigins, true);
    }

    /**
     * เพิ่ม origin ที่อนุญาต
     * จุดประสงค์: เพิ่ม origin ใหม่ลงในรายการ allowed origins เพื่อให้สามารถเข้าถึงได้จากโดเมนที่ระบุ
     * 
     * ในโปรดักชัน:
     * - ควรใช้วิธีนี้ร่วมกับการโหลดค่าเริ่มต้นจาก config เท่านั้น
     * - ไม่ควรอนุญาต wildcard origins ร่วมกับ credentials
     * 
     * @param string|array $origins Origin หรือรายการ origin ที่ต้องการเพิ่ม
     * @return void ไม่มีค่าที่ส่งกลับ
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
     * จุดประสงค์: กำหนดรายการ HTTP methods ที่อนุญาตสำหรับคำขอ cross-origin
     * 
     * @param array $methods รายการ HTTP methods ที่อนุญาต (เช่น ['GET', 'POST'])
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function setAllowedMethods(array $methods): void
    {
        $this->allowedMethods = $methods;
    }

    /**
     * ตั้งค่า allowed headers
     * จุดประสงค์: กำหนดรายการ headers ที่อนุญาตสำหรับคำขอ cross-origin เพื่อให้เบราว์เซอร์สามารถส่งคำขอได้อย่างถูกต้องและปลอดภัย
     * 
     * @param array $headers รายการ headers ที่อนุญาต (เช่น ['Content-Type', 'Authorization'])
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function setAllowedHeaders(array $headers): void
    {
        $this->allowedHeaders = $headers;
    }

    /**
     * เปิด/ปิด credentials
     * จุดประสงค์: กำหนดว่าคำขอ cross-origin สามารถส่ง credentials (เช่น cookies, HTTP authentication) ได้หรือไม่
     * 
     * @param bool $allow กำหนดว่าควรอนุญาต credentials หรือไม่
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function setAllowCredentials(bool $allow): void
    {
        $this->allowCredentials = $allow;
    }
}
