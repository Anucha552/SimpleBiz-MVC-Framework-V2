<?php
/**
 * class ViewCacheCommand
 * 
 * จุดประสงค์: เป็นคำสั่ง CLI ที่ใช้สำหรับ compile view files ของแอปพลิเคชัน โดยจะทำการสร้างไฟล์ cache สำหรับ views เพื่อเพิ่มประสิทธิภาพในการโหลดข้อมูลในครั้งถัดไปที่แอปพลิเคชันถูกเรียกใช้
 */

declare(strict_types=1);

namespace App\Console\Commands;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ViewCacheCommand extends BaseCommand
{
    public function name(): string
    {
        return 'view:cache';
    }

    protected function execute(array $args): void
    {
        $this->info("กำลัง compile view files...");

        // สร้างโฟลเดอร์ cache และโฟลเดอร์ย่อยสำหรับ views หากยังไม่มี
        $cacheDir = $this->path('storage/cache/views');
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // ค้นหาไฟล์ view ทั้งหมดในโฟลเดอร์ app/Views และคัดลอกไปยังโฟลเดอร์ cache โดยใช้ชื่อไฟล์ที่ถูกแฮชด้วย MD5 เพื่อป้องกันการชนกันของชื่อไฟล์
        $viewsDir = $this->path('app/Views');
        $viewFiles = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($viewsDir)
        );

        $count = 0;
        foreach ($viewFiles as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace($viewsDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $cacheFile = $cacheDir . '/' . md5($relativePath) . '.php';

                copy($file->getPathname(), $cacheFile);
                $count++;
            }
        }

        $this->success("Compile views เรียบร้อยแล้ว ({$count} files)");
        echo "\n";
    }
}
