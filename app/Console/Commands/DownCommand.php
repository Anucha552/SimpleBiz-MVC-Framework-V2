<?php

declare(strict_types=1);

namespace App\Console\Commands;

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

        $data = [
            'message' => 'ขออภัย เว็บไซต์อยู่ระหว่างการปรับปรุง',
            'retry_after' => null,
            'enabled_at' => date('Y-m-d H:i:s'),
        ];

        file_put_contents($maintenanceFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->success("เปิด Maintenance Mode เรียบร้อยแล้ว");
        $this->warning("[!] เว็บไซต์จะแสดงหน้า maintenance สำหรับผู้ใช้ทั่วไป");
        $this->info("รัน 'php console up' เพื่อปิด maintenance mode");
    }
}
