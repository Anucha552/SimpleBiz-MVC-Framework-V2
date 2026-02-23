<?php
/**
 * ยังทำงานไม่ถูกต้อง
 * 
 * class ConfigCacheCommand
 * 
 * จุดประสงค์: เป็นคำสั่ง CLI ที่ใช้สำหรับ cache configuration files ของแอปพลิเคชัน โดยจะทำการสร้างไฟล์ cache สำหรับ config เพื่อเพิ่มประสิทธิภาพในการโหลดข้อมูลในครั้งถัดไปที่แอปพลิเคชันถูกเรียกใช้
 */

declare(strict_types=1);

namespace App\Console\Commands;

class ConfigCacheCommand extends BaseCommand
{
    public function name(): string
    {
        return 'config:cache';
    }

    protected function execute(array $args): void
    {
        $this->info("กำลัง cache configuration files...");

        // สร้างโฟลเดอร์ cache และโฟลเดอร์ย่อยสำหรับ config หากยังไม่มี
        $cacheDir = $this->path('storage/cache/config');
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // สร้างไฟล์ cache สำหรับ config โดยเก็บข้อมูลเมตาของ config เช่น เวลาที่ cache ถูกสร้างขึ้น และสถานะของไฟล์ config ที่ถูก cache
        $configFiles = glob($this->path('config/*.php'));
        $cachedConfig = [];

        // อ่านไฟล์ config ทั้งหมดและเก็บข้อมูลเมตาของแต่ละไฟล์ลงในอาร์เรย์ $cachedConfig โดยใช้ชื่อไฟล์เป็นคีย์และเนื้อหาของไฟล์เป็นค่า
        foreach ($configFiles as $file) {
            $key = basename($file, '.php');
            $cachedConfig[$key] = require $file;
            $this->info("  - Cached: {$key}.php");
        }

        // เขียนข้อมูลเมตาของ config ลงในไฟล์ cache โดยใช้ฟังก์ชัน var_export เพื่อแปลงข้อมูลเป็นรูปแบบที่สามารถนำกลับมาใช้ได้ใน PHP
        file_put_contents(
            $cacheDir . '/config_cached.php',
            "<?php\nreturn " . var_export($cachedConfig, true) . ";"
        );

        $this->success("Cache config files เรียบร้อยแล้ว");
        echo "\n";
    }
}
