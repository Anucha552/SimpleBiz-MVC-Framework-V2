<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Config;

class DownCommand extends BaseCommand
{
    public function name(): string
    {
        return 'down';
    }

    protected function execute(array $args): void
    {
        $this->info("กำลังเปิด Maintenance Mode...");

        $maintenanceFile = $this->path('storage/maintenance.json');
        $maintenanceDir = dirname($maintenanceFile);

        if (!is_dir($maintenanceDir)) {
            mkdir($maintenanceDir, 0755, true);
        }

        $allowedIps = (array) Config::get('maintenance.allowed_ips', []);
        $allowedIps = array_values(array_filter(array_map('trim', $allowedIps), 'strlen'));
        $retryAfter = Config::get('maintenance.retry_after', null);

        $data = [
            'message' => 'ขออภัย เว็บไซต์อยู่ระหว่างการปรับปรุง',
            'retry_after' => $retryAfter ?: null,
            'enabled_at' => date('Y-m-d H:i:s'),
            'allowed_ips' => $allowedIps,
        ];

        file_put_contents($maintenanceFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->success("เปิด Maintenance Mode เรียบร้อยแล้ว");
        $this->warning("[!] เว็บไซต์จะแสดงหน้า maintenance สำหรับผู้ใช้ทั่วไป");
        if (empty($allowedIps)) {
            $this->warning("[!] ไม่มี IP ที่ถูกยกเว้น (MAINTENANCE_ALLOWED_IPS ว่างหรือไม่ได้ตั้งค่า)");
        } else {
            $this->info("IPs ที่ยกเว้น: " . implode(', ', $allowedIps));
        }

        $envPath = $this->path('.env');
        if (file_exists($envPath) && is_readable($envPath) && is_writable($envPath)) {
            $content = file_get_contents($envPath);
            if ($content !== false) {
                $newline = str_contains($content, "\r\n") ? "\r\n" : "\n";
                if (preg_match('/^\s*MAINTENANCE_MODE\s*=.*$/m', $content)) {
                    $content = preg_replace('/^\s*MAINTENANCE_MODE\s*=.*$/m', 'MAINTENANCE_MODE=true', $content);
                } else {
                    $content = rtrim($content, "\r\n") . $newline . 'MAINTENANCE_MODE=true' . $newline;
                }
                file_put_contents($envPath, $content);
                $this->info("ตั้ง MAINTENANCE_MODE=true ใน .env แล้ว");
            }
        }
        $this->info("รัน 'php console up' เพื่อปิด maintenance mode");
    }
}
