<?php

declare(strict_types=1);

namespace App\Console\Commands;

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
    }
}
