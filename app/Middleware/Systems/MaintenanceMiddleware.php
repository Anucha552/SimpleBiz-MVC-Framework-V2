<?php
/**
 * MIDDLEWARE MAINTENANCE MODE (โหมดปิดปรุงระบบ)
 * 
 * Middleware สำหรับนระบบ หรือ Global Middleware
 * 
 * จุดประสงค์: ปิดเว็บไซต์ชั่วคระว่างปรับปรุง/บำรุงรักษา
 * 
 * การใช้งาน:
 * เปิดใช้งานเมื่อ:
 * - อัปเดตระบบ
 * - ทำการบำรุงรักษาฐานข้อมูล
 * - แก้ไขปัญหาด่วน
 * - ทดสอบฟีเจอร์ใหม่
 * 
 * วิธีการทำงาน:
 * 1. ตรวจสอบว่าเปิดโหมดปิดปรุงหรือไม่
 * 2. ถ้าเปิด แสดงหน้า maintenance
 * 3. ยกเว้น IP addresses ที่กำหนด (admin/developer)
 * 4. ยกเว้นเส้นทาง route ที่จำเป็น
 * 
 * การเปิด/ปิด:
 * - สร้างไฟล์ storage/maintenance.json
 * - ลบไฟล์เพื่อปิด maintenance mode
 * - ตั้งค่าผ่าน environment variable
 * 
 * ข้อความแจ้งเตือน:
 * - แสดงเวลาประมาณที่จะกลับมา
 * - ข้อความสำหรับผู้ใช้
 * - ข้อมูลติดต่อถ้าจำเป็น
 */

namespace App\Middleware\Systems;

use App\Core\Middleware;
use App\Core\Logger;
use App\Core\Response;
use App\Core\Config;

class MaintenanceMiddleware extends Middleware
{
    /**
     * Logger สำหรับบันทึกข้อมูลการเข้าถึงระหว่าง maintenance
     */
    private Logger $logger;
    
    /**
     * ไฟล์สถานะ maintenance
     */
    private string $maintenanceFile;

    /**
     * IP addresses ที่ได้รับยกเว้น (admin/developer)
     */
    private array $allowedIPs = [];

    /**
     * เส้นทางที่ได้รับยกเว้น
     */
    private array $exceptRoutes = [
        '/api/health',
        '/api/status',
    ];

    /**
     * สร้าง instance ของ MaintenanceMiddleware
     * 
     * จุดประสงค์: เตรียมการสำหรับ middleware โดยการกำหนด path ของไฟล์ maintenance และโหลด allowed IPs จาก config เพื่อให้สามารถตรวจสอบได้ว่าเปิด maintenance mode หรือไม่ และจัดการ exceptions ได้อย่างถูกต้อง
     */
    public function __construct()
    {
        $this->logger = new Logger();
        
        // กำหนด path ของไฟล์ maintenance
        $basePath = dirname(__DIR__, 3);
        $this->maintenanceFile = $basePath . '/storage/maintenance.json';

        // โหลด allowed IPs จาก config
        $configIPs = (array) Config::get('maintenance.allowed_ips', []);
        $this->allowedIPs = array_values(array_filter(array_map('trim', $configIPs), 'strlen'));
    }

    /**
     * จัดการโหมดปิดปรุงระบบ
     * จุดประสงค์: ตรวจสอบว่าเปิด maintenance mode หรือไม่ และถ้าเปิด ให้แสดงหน้า maintenance โดยตรวจสอบ exceptions ทั้ง IP addresses และเส้นทางที่ได้รับยกเว้น และบันทึกการเข้าถึงระหว่าง maintenance เพื่อให้สามารถวิเคราะห์ได้ว่ามีใครพยายามเข้าถึงระบบในช่วงเวลาที่ปิดปรุงอยู่หรือไม่
     * 
     * @return bool|Response True เพื่อดำเนินการต่อ, false เพื่อหยุด, หรือ Response เพื่อส่งกลับทันที
     */
        public function handle(?\App\Core\Request $request = null): bool|Response
    {
        // ตรวจสอบว่าเปิด maintenance mode หรือไม่
        if (!$this->isMaintenanceMode()) {
            return true; // ไม่ได้เปิด maintenance
        }
        
        // ตรวจสอบ exceptions
        if ($this->isExcepted()) {
            return true; // อนุญาตให้เข้าถึง
        }

        // บันทึกการเข้าถึงระหว่าง maintenance
        $this->logger->info('maintenance.blocked_access', [
            'ip' => $this->getClientIP(),
            'route' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        ]);

        // แสดงหน้า maintenance
        return $this->displayMaintenancePage();
    }

    /**
     * ตรวจสอบว่าเปิด maintenance mode หรือไม่
     * จุดประสงค์: ตรวจสอบสถานะของระบบว่ากำลังอยู่ในโหมดปิดปรุงหรือไม่ โดยการตรวจสอบทั้ง environment variable และการมีอยู่ของไฟล์ maintenance เพื่อให้สามารถเปิด/ปิด maintenance mode ได้อย่างยืดหยุ่นและง่ายดาย
     * 
     * @return bool true ถ้าเปิด maintenance mode, false ถ้าไม่เปิด
     */
    private function isMaintenanceMode(): bool
    {
        // ตรวจสอบจาก environment variable
        if (Config::get('maintenance.enabled', false) === true) {
            return true;
        }

        // ตรวจสอบจากไฟล์
        return file_exists($this->maintenanceFile);
    }

    /**
     * ตรวจสอบว่าควรได้รับยกเว้นหรือไม่
     * จุดประสงค์: ตรวจสอบว่า IP address ของ client หรือเส้นทางที่เข้าถึงได้รับยกเว้นจากการแสดงหน้า maintenance หรือไม่ เพื่อให้ผู้ดูแลระบบหรือผู้พัฒนาสามารถเข้าถึงระบบได้แม้ในช่วงเวลาที่ปิดปรุงอยู่ และเพื่อให้แน่ใจว่าการบำรุงรักษาไม่ส่งผลกระทบต่อการทำงานของทีมพัฒนาและการดูแลระบบ
     * 
     * @return bool true ถ้าควรได้รับยกเว้น, false ถ้าไม่ควรได้รับยกเว้น
     */
    private function isExcepted(): bool
    {
        // ตรวจสอบ IP address
        $allowedIps = $this->allowedIPs;
        $maintenanceData = $this->getMaintenanceData();
        
        // ถ้ามี allowed IPs ในไฟล์ maintenance ให้รวมกับ config
        if (isset($maintenanceData['allowed_ips']) && is_array($maintenanceData['allowed_ips'])) {
            $allowedIps = array_merge($allowedIps, $maintenanceData['allowed_ips']);
        }

        // ทำความสะอาดรายการ IPs (trim, filter empty, unique)
        $allowedIps = array_values(array_unique(array_filter(array_map('trim', $allowedIps), 'strlen')));
        
        // ตรวจสอบ IP ของ client
        $clientIP = $this->getClientIP();

        // Debug: แสดงรายการ IP ที่อนุญาตและ IP ของ client
        // print_r($allowedIps);
        // echo '<br>';
        // echo $clientIP;
        // die();
        
        if (in_array($clientIP, $allowedIps, true)) {
            return true;
        }
        
        // ตรวจสอบเส้นทาง
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        foreach ($this->exceptRoutes as $route) {
            if (strpos($uri, $route) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * แสดงหน้า maintenance
     * จุดประสงค์: แสดงหน้า maintenance ที่สวยงามและให้ข้อมูลที่ชัดเจนสำหรับผู้ใช้เมื่อระบบอยู่ในโหมดปิดปรุง โดยมีการแสดงข้อความแจ้งเตือน, เวลาที่คาดว่าจะกลับมา, และข้อมูลติดต่อถ้าจำเป็น รวมถึงการจัดการคำขอ API ให้คืนค่า JSON ที่เหมาะสมเพื่อให้ผู้ใช้หรือระบบอื่นๆ สามารถเข้าใจสถานะของระบบได้อย่างชัดเจน
     * 
     * @return Response คืนค่าหน้า maintenance ในรูปแบบ HTML สำหรับคำขอ web หรือ JSON สำหรับคำขอ API พร้อมกับสถานะ HTTP 503 และ header Retry-After เพื่อแจ้งให้ผู้ใช้ทราบว่าระบบอยู่ในระหว่างการปรับปรุงและเมื่อคาดว่าจะกลับมาให้บริการ
     */
    private function displayMaintenancePage(): Response
    {
        // โหลดข้อมูล maintenance
        $maintenanceData = $this->getMaintenanceData();

        // กำหนดว่าเป็นคำขอ API หรือไม่
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $isApiRequest = preg_match('#^/api(/|$)#', $uri);

        if ($isApiRequest) {
            // คืนค่า JSON สำหรับ API
            $retryAfter = 3600;
            $meta = [];
            if (isset($maintenanceData['retry_after'])) {
                $meta['retry_after'] = $maintenanceData['retry_after'];
            }

            return Response::apiError($maintenanceData['message'], [], 503, $meta)
                ->withHeader('Retry-After', (string) $retryAfter);
        } else {
            // แสดงหน้า HTML
            $retryAfter = 3600;
            return Response::html($this->getMaintenanceHTML($maintenanceData), 503)
                ->withHeader('Retry-After', (string) $retryAfter);
        }
    }

    /**
     * รับข้อมูล maintenance
     * จุดประสงค์: รับข้อมูลที่เกี่ยวข้องกับสถานะ maintenance จากไฟล์หรือ config เพื่อให้สามารถแสดงข้อมูลที่ถูกต้องและเป็นปัจจุบันบนหน้า maintenance และในคำขอ API ได้อย่างเหมาะสม โดยมีการจัดการค่าเริ่มต้นและการตรวจสอบความถูกต้องของข้อมูลเพื่อให้แน่ใจว่าการแสดงผลจะไม่เกิดข้อผิดพลาดแม้ในกรณีที่ข้อมูลไม่สมบูรณ์หรือไฟล์เสียหาย
     * 
     * @return array ข้อมูล maintenance ที่ประกอบด้วย message, retry_after, และ allowed_ips เพื่อใช้ในการแสดงหน้า maintenance และจัดการ exceptions ได้อย่างถูกต้อง
     */
    private function getMaintenanceData(): array
    {
        $configRetryAfter = Config::get('maintenance.retry_after', null);
        $default = [
            'message' => 'ขออภัย เว็บไซต์อยู่ระหว่างการปรับปรุง',
            'retry_after' => $configRetryAfter ?: null,
        ];

        if (!file_exists($this->maintenanceFile)) {
            return $default;
        }

        $content = file_get_contents($this->maintenanceFile);
        $data = json_decode($content, true);

        if (!is_array($data)) {
            return $default;
        }

        if (!array_key_exists('retry_after', $data) || $data['retry_after'] === null || $data['retry_after'] === '') {
            $data['retry_after'] = $default['retry_after'];
        }

        return $data;
    }

    /**
     * สร้าง HTML สำหรับหน้า maintenance
     * จุดประสงค์: สร้างหน้า HTML ที่สวยงามและให้ข้อมูลที่ชัดเจนสำหรับผู้ใช้เมื่อระบบอยู่ในโหมดปิดปรุง โดยมีการแสดงข้อความแจ้งเตือน, เวลาที่คาดว่าจะกลับมา, และข้อมูลติดต่อถ้าจำเป็น เพื่อให้ผู้ใช้ได้รับประสบการณ์ที่ดีแม้ในช่วงเวลาที่ระบบไม่สามารถให้บริการได้ และเพื่อให้แน่ใจว่าข้อมูลที่แสดงเป็นปัจจุบันและถูกต้องตามที่กำหนดในไฟล์ maintenance หรือ config
     * 
     * @param array $data ข้อมูล maintenance ที่ประกอบด้วย message และ retry_after เพื่อใช้ในการแสดงข้อความแจ้งเตือนและเวลาที่คาดว่าจะกลับมาให้บริการบนหน้า maintenance
     * @return string HTML สำหรับหน้า maintenance ที่มีการจัดรูปแบบและสไตล์ที่เหมาะสมเพื่อให้ผู้ใช้ได้รับข้อมูลที่ชัดเจนและประสบการณ์ที่ดีในช่วงเวลาที่ระบบอยู่ในโหมดปิดปรุง
     */
    private function getMaintenanceHTML(array $data): string
    {
        $message = htmlspecialchars($data['message'] ?? 'ขออภัย เว็บไซต์อยู่ระหว่างการปรับปรุง', ENT_QUOTES, 'UTF-8');
        $retryAfter = $data['retry_after'] ?? null;
        
        $retryHTML = '';
        if ($retryAfter) {
            $retryHTML = '<p class="retry">คาดว่าจะกลับมาให้บริการ: ' . htmlspecialchars($retryAfter, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ปรับปรุงระบบ</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #667eea;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            max-width: 600px;
            text-align: center;
        }
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 20px;
        }
        p {
            color: #666;
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .retry {
            color: #667eea;
            font-weight: 600;
            margin-top: 20px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 30px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔧</div>
        <h1>กำลังปรับปรุงระบบ</h1>
        <p>{$message}</p>
        <div class="spinner"></div>
        {$retryHTML}
        <p style="font-size: 14px; color: #999; margin-top: 30px;">
            กรุณากลับมาอีกครั้งในภายหลัง ขออภัยในความไม่สะดวก
        </p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * เปิด maintenance mode
     * จุดประสงค์: เปิดโหมดปิดปรุงระบบโดยการสร้างไฟล์ maintenance พร้อมกับข้อมูลที่จำเป็น เช่น ข้อความแจ้งเตือนและเวลาที่คาดว่าจะกลับมาให้บริการ เพื่อให้ผู้ดูแลระบบสามารถเปิด maintenance mode ได้อย่างง่ายดายและมีข้อมูลที่ชัดเจนสำหรับผู้ใช้ในช่วงเวลาที่ระบบไม่สามารถให้บริการได้
     * 
     * @param string $message ข้อความแจ้งเตือน
     * @param string|null $retryAfter เวลาที่จะกลับมา
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะสร้างไฟล์ maintenance พร้อมกับข้อมูลที่จำเป็นเพื่อเปิดโหมดปิดปรุงระบบและให้ข้อมูลที่ชัดเจนสำหรับผู้ใช้ในช่วงเวลาที่ระบบไม่สามารถให้บริการได้
     */
    public static function enable(string $message = '', ?string $retryAfter = null): void
    {
        $basePath = dirname(__DIR__, 3);
        $maintenanceFile = $basePath . '/storage/maintenance.json';

        $data = [
            'message' => $message ?: 'ขออภัย เว็บไซต์อยู่ระหว่างการปรับปรุง',
            'retry_after' => $retryAfter,
            'enabled_at' => date('Y-m-d H:i:s'),
        ];

        file_put_contents($maintenanceFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * ปิด maintenance mode
     * จุดประสงค์: ปิดโหมดปิดปรุงระบบโดยการลบไฟล์ maintenance เพื่อให้ระบบกลับมาให้บริการตามปกติ และให้ผู้ใช้สามารถเข้าถึงเว็บไซต์ได้ตามปกติ
     * 
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะลบไฟล์ maintenance เพื่อปิดโหมดปิดปรุงระบบและให้ระบบกลับมาให้บริการตามปกติ
     */
    public static function disable(): void
    {
        $basePath = dirname(__DIR__, 3);
        $maintenanceFile = $basePath . '/storage/maintenance.json';

        if (file_exists($maintenanceFile)) {
            unlink($maintenanceFile);
        }
    }
}
