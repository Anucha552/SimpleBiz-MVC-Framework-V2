<?php
/**
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
        // ถามก่อนว่าผู้ใช้ต้องการลบ optimization caches จริงหรือไม่ เพื่อป้องกันการลบโดยไม่ได้ตั้งใจ
        if (!$this->confirm("คุณแน่ใจหรือไม่ว่าต้องการลบ optimization caches ทั้งหมด?")) {
            $this->info("ยกเลิกการลบ optimization caches");
            echo "\n";
            return;
        }

        $this->info("กำลังลบ optimization caches...");
        $clear = new CacheClearCommand();
        $clear->handle([], $this->context);
        $this->success("ลบ optimization caches เรียบร้อยแล้ว");
        echo "\n";
    }
}
