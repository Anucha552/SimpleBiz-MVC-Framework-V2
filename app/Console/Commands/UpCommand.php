<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Config;

class UpCommand extends BaseCommand
{
    public function name(): string
    {
        return 'up';
    }

    protected function execute(array $args): void
    {
        $this->info("กำลังปิด Maintenance Mode...");

        $maintenanceFile = $this->path('storage/maintenance.json');

        if (!file_exists($maintenanceFile)) {
            $this->warning("ไม่ได้เปิด Maintenance Mode อยู่");
            return;
        }

        unlink($maintenanceFile);

        $this->success("ปิด Maintenance Mode เรียบร้อยแล้ว");
        $this->info("[OK] เว็บไซต์กลับมาใช้งานได้ปกติแล้ว");

        $allowedIps = (array) Config::get('maintenance.allowed_ips', []);
        $allowedIps = array_values(array_filter(array_map('trim', $allowedIps), 'strlen'));
        if (!empty($allowedIps)) {
            $this->info("MAINTENANCE_ALLOWED_IPS: " . implode(', ', $allowedIps));
        }

        if (Config::get('maintenance.enabled', false) === true) {
            $envPath = $this->path('.env');
            if (file_exists($envPath) && is_readable($envPath) && is_writable($envPath)) {
                $content = file_get_contents($envPath);
                if ($content !== false) {
                    $newline = str_contains($content, "\r\n") ? "\r\n" : "\n";
                    if (preg_match('/^\s*MAINTENANCE_MODE\s*=.*$/m', $content)) {
                        $content = preg_replace('/^\s*MAINTENANCE_MODE\s*=.*$/m', 'MAINTENANCE_MODE=false', $content);
                    } else {
                        $content = rtrim($content, "\r\n") . $newline . 'MAINTENANCE_MODE=false' . $newline;
                    }
                    file_put_contents($envPath, $content);
                    $this->info("ปิด MAINTENANCE_MODE ใน .env แล้ว");
                    return;
                }
            }

            $this->warning("[!] MAINTENANCE_MODE=true อยู่ใน .env ระบบยังคงอยู่ในโหมดปรับปรุง");
            $this->info("กรุณาแก้ .env เป็น MAINTENANCE_MODE=false");
        }
    }
}
