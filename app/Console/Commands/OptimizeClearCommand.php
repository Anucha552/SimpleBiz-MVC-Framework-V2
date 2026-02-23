<?php
/**
 * ยังทำงานไม่ถูกต้อง
 * 
 * class OptimizeClearCommand
 * 
 * จุดประสงค์: เป็นคำสั่ง CLI ที่ใช้สำหรับลบ optimization caches ทั้งหมด เพื่อให้แอปพลิเคชันกลับสู่สถานะก่อนการ optimize และสามารถทำการ optimize ใหม่ได้อีกครั้ง
 */

declare(strict_types=1);

namespace App\Console\Commands;

class OptimizeClearCommand extends BaseCommand
{
    public function name(): string
    {
        return 'optimize:clear';
    }

    protected function execute(array $args): void
    {
        $this->info("กำลังลบ optimization caches...");
        $clear = new CacheClearCommand();
        $clear->handle([], $this->context);
        $this->success("ลบ optimization caches เรียบร้อยแล้ว");
    }
}
