<?php
/**
 * class OptimizeCommand
 * 
 * จุดประสงค์: เป็นคำสั่ง CLI ที่ใช้สำหรับเพิ่มประสิทธิภาพแอปพลิเคชัน โดยจะทำการลบ cache เก่า, เตรียม cache ใหม่ และสร้าง flag เพื่อบ่งบอกว่าแอปพลิเคชันถูก optimized แล้ว
 */

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
        $clear->handle([], $this->context); // เรียกใช้คำสั่ง CacheClearCommand เพื่อทำการลบ cache เก่า

        $this->info("2. เตรียม cache (routes + config)...");
        $warm = new CacheWarmCommand();
        $warm->handle([], $this->context);
        $this->success("เตรียม cache สำเร็จ");
        
        $this->info("3. สร้าง optimization flag...");
        // สร้างไฟล์ flag เพื่อบ่งบอกว่าแอปพลิเคชันถูก optimized แล้ว โดยใช้ชื่อไฟล์ว่า optimized.flag และเก็บข้อมูลเวลาที่ถูกสร้างขึ้นในรูปแบบ timestamp
        $routeCacheDir = $this->path('storage/cache');
        if (!is_dir($routeCacheDir)) {
            mkdir($routeCacheDir, 0755, true);
        }
        // เวลาและวันที่ถูกสร้างขึ้นจะถูกเก็บในรูปแบบ timestamp เพื่อให้สามารถตรวจสอบได้ว่า cache ถูกสร้างขึ้นเมื่อใด และสามารถใช้ข้อมูลนี้ในการตัดสินใจว่าจะต้องทำการ optimize ใหม่หรือไม่ในอนาคต
        $dateAndTime = date('Y-m-d H:i:s');
        file_put_contents($routeCacheDir . '/optimized.flag', $dateAndTime);
        $this->success("สร้าง flag สำเร็จ");

        echo "\n";
        $this->success("เพิ่มประสิทธิภาพแอปพลิเคชันเรียบร้อย!");
        $this->info("แอปพลิเคชันพร้อมสำหรับ production");
        echo "\n";
    }
}
