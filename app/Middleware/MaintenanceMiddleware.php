<?php
/**
 * MIDDLEWARE MAINTENANCE MODE (โหมดปิดปรุงระบบ)
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
 * 4. ยกเว้นเส้นทางบางอ route ที่จำเป็น
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

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Logger;
use App\Core\Response;

class MaintenanceMiddleware extends Middleware
{
    private Logger $logger;
    
    /**
     * ไฟล์สถานะ maintenance
     */
    private string $maintenanceFile;

    /**
     * IP addresses ที่ได้รับยกเว้น (admin/developer)
     */
    private array $allowedIPs = [
        '127.0.0.1',
        '::1',
    ];

    /**
     * เส้นทางที่ได้รับยกเว้น
     */
    private array $exceptRoutes = [
        '/api/health',
        '/api/status',
    ];

    public function __construct()
    {
        $this->logger = new Logger();
        
        // กำหนด path ของไฟล์ maintenance
        $basePath = dirname(dirname(__DIR__));
        $this->maintenanceFile = $basePath . '/storage/maintenance.json';

        // โหลด allowed IPs จาก config
        $configIPs = \env('MAINTENANCE_ALLOWED_IPS');
        if ($configIPs) {
            $ips = explode(',', $configIPs);
            $this->allowedIPs = array_merge($this->allowedIPs, array_map('trim', $ips));
        }
    }

    /**
     * จัดการโหมดปิดปรุงระบบ
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
     * 
     * @return bool
     */
    private function isMaintenanceMode(): bool
    {
        // ตรวจสอบจาก environment variable
        if (\env('MAINTENANCE_MODE') === 'true') {
            return true;
        }

        // ตรวจสอบจากไฟล์
        return file_exists($this->maintenanceFile);
    }

    /**
     * ตรวจสอบว่าควรได้รับยกเว้นหรือไม่
     * 
     * @return bool
     */
    private function isExcepted(): bool
    {
        // ตรวจสอบ IP address
        $clientIP = $this->getClientIP();
        if (in_array($clientIP, $this->allowedIPs)) {
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
     * รับ IP address ของ client
     * 
     * @return string
     */
    private function getClientIP(): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // ถ้ามีหลาย IP (proxy chain) ใช้ตัวแรก
        if (strpos($ip, ',') !== false) {
            $ip = explode(',', $ip)[0];
        }

        return trim($ip);
    }

    /**
     * แสดงหน้า maintenance
     */
    private function displayMaintenancePage(): Response
    {
        // โหลดข้อมูล maintenance
        $maintenanceData = $this->getMaintenanceData();

        // กำหนดว่าเป็นคำขอ API หรือไม่
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $isApiRequest = strpos($uri, '/api/') === 0;

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
     * 
     * @return array
     */
    private function getMaintenanceData(): array
    {
        $default = [
            'message' => 'ขออภัย เว็บไซต์อยู่ระหว่างการปรับปรุง',
            'retry_after' => null,
        ];

        if (!file_exists($this->maintenanceFile)) {
            return $default;
        }

        $content = file_get_contents($this->maintenanceFile);
        $data = json_decode($content, true);

        return $data ?? $default;
    }

    /**
     * สร้าง HTML สำหรับหน้า maintenance
     * 
     * @param array $data
     * @return string
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
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
     * 
     * @param string $message ข้อความแจ้งเตือน
     * @param string|null $retryAfter เวลาที่จะกลับมา
     */
    public static function enable(string $message = '', ?string $retryAfter = null): void
    {
        $basePath = dirname(dirname(__DIR__));
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
     */
    public static function disable(): void
    {
        $basePath = dirname(dirname(__DIR__));
        $maintenanceFile = $basePath . '/storage/maintenance.json';

        if (file_exists($maintenanceFile)) {
            unlink($maintenanceFile);
        }
    }
}
