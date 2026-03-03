<?php
/**
 * class CacheClearExpiredCommand
 *
 * จุดประสงค์: เป็นคำสั่ง CLI สำหรับลบไฟล์ cache ที่หมดอายุเท่านั้น
 *
 * การใช้งาน:
 * ```bash
 * php console cache:clear-expired
 * ```
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Cache;

class CacheClearExpiredCommand extends BaseCommand
{
    /**
     * ชื่อของคำสั่ง เช่น 'cache:clear-expired'
     */
    public function name(): string
    {
        return 'cache:clear-expired';
    }

    /**
     * รายการ alias ของคำสั่ง เช่น ['c:ce'] สำหรับ 'cache:clear-expired'
     */
    public function aliases(): array
    {
        return ['c:ce'];
    }

    /**
     * เมธอดหลักสำหรับประมวลผลคำสั่ง
     */
    protected function execute(array $args): void
    {
        $this->info('กำลังลบ cache ที่หมดอายุ...');

        try {
            $deleted = Cache::clearExpired();
            $this->success("ลบ cache ที่หมดอายุสำเร็จ! ({$deleted} ไฟล์)");
            echo "\n";
        } catch (\Throwable $e) {
            echo "\n";
            echo "┌─ Cache Clear Expired Error ────────────────────────┐\n";
            $this->error('เกิดข้อผิดพลาด: ' . $e->getMessage());
            $this->error('ไฟล์: ' . $e->getFile());
            $this->error('บรรทัด: ' . $e->getLine());
            echo "└───────────────────────────────────────────────────┘\n";
            echo "\n";
        }
    }
}
