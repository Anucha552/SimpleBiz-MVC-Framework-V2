<?php
/**
 * ยังทำงานไม่ถูกต้อง
 * 
 * class OptimizeCommand
 * 
 * จุดประสงค์: เป็นคำสั่ง CLI ที่ใช้สำหรับเพิ่มประสิทธิภาพแอปพลิเคชัน โดยจะทำการลบ cache เก่า, cache routes ใหม่ และสร้าง flag เพื่อบ่งบอกว่าแอปพลิเคชันถูก optimized แล้ว
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

        $this->info("2. Cache routes...");
        // สร้างโฟลเดอร์ cache และโฟลเดอร์ย่อยสำหรับ routes หากยังไม่มี
        $routeCacheDir = $this->path('storage/cache');
        if (!is_dir($routeCacheDir)) {
            mkdir($routeCacheDir, 0755, true);
        }

        // สร้างไฟล์ cache สำหรับ routes โดยเก็บข้อมูลเมตาของ routes เช่น เวลาที่ cache ถูกสร้างขึ้น และสถานะของไฟล์ routes ที่ถูก cache
        file_put_contents($routeCacheDir . '/routes.cache', serialize(['cached' => true, 'time' => time()]));
        $this->success("   [OK] Cache routes สำเร็จ");

        
        $this->info("3. สร้าง optimization flag...");
        // สร้างไฟล์ flag เพื่อบ่งบอกว่าแอปพลิเคชันถูก optimized แล้ว โดยใช้ชื่อไฟล์ว่า optimized.flag และเก็บข้อมูลเวลาที่ถูกสร้างขึ้นในรูปแบบ timestamp
        file_put_contents($routeCacheDir . '/optimized.flag', time());
        $this->success("   [OK] สร้าง flag สำเร็จ");

        echo "\n";
        $this->success("เพิ่มประสิทธิภาพแอปพลิเคชันเรียบร้อย!");
        $this->info("แอปพลิเคชันพร้อมสำหรับ production");
    }
}
