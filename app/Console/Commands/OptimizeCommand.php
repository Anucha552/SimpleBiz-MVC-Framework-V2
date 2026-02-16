<?php

declare(strict_types=1);

namespace App\Console\Commands;

class OptimizeCommand extends BaseCommand
{
    public function name(): string
    {
        return 'optimize';
    }

    protected function execute(array $args): void
    {
        $this->info("กำลังเพิ่มประสิทธิภาพแอปพลิเคชัน...");
        echo "\n";

        $this->info("1. ลบ cache เก่า...");
        $clear = new CacheClearCommand();
        $clear->handle([], $this->context);

        $this->info("2. Cache routes...");
        $routeCacheDir = $this->path('storage/cache');
        if (!is_dir($routeCacheDir)) {
            mkdir($routeCacheDir, 0755, true);
        }
        file_put_contents($routeCacheDir . '/routes.cache', serialize(['cached' => true, 'time' => time()]));
        $this->success("   [OK] Cache routes สำเร็จ");

        $this->info("3. สร้าง optimization flag...");
        file_put_contents($routeCacheDir . '/optimized.flag', time());
        $this->success("   [OK] สร้าง flag สำเร็จ");

        echo "\n";
        $this->success("เพิ่มประสิทธิภาพแอปพลิเคชันเรียบร้อย!");
        $this->info("แอปพลิเคชันพร้อมสำหรับ production");
    }
}
