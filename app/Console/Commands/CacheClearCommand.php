<?php
/**
 * class CacheClearCommand
 * 
 * จุดประสงค์: เป็นคำสั่ง CLI ที่ใช้สำหรับลบไฟล์ cache ทั้งหมดในโฟลเดอร์ storage/cache โดยจะทำการลบไฟล์ทั้งหมดยกเว้นไฟล์ .gitkeep เพื่อให้สามารถเคลียร์ cache ได้อย่างง่ายดายผ่านคอมมานด์ไลน์
 * 
 * การใช้งาน:
 * ```bash
 * php console.php cache:clear
 * ```
 */

// Namespace และการประกาศ strict types เพื่อความปลอดภัยและความชัดเจนในการใช้งานประเภทข้อมูล
declare(strict_types=1);

namespace App\Console\Commands;

class CacheClearCommand extends BaseCommand
{
    /**
     * ชื่อของคำสั่ง เช่น 'cache:clear'
     * จุดประสงค์: ใช้ในการเรียกคำสั่งจาก CLI และในการลงทะเบียนคำสั่ง
     * ตัวอย่างหารใช้งาน:
     * ```php
     * public function name(): string {
     *     return 'cache:clear';
     * }
     * ```
     * 
     * @return string ชื่อของคำสั่ง
     */
    public function name(): string
    {
        return 'cache:clear';
    }

    /**
     * รายการ alias ของคำสั่ง เช่น ['cc'] สำหรับ 'cache:clear'
     * จุดประสงค์: ให้ผู้ใช้สามารถเรียกคำสั่งด้วยชื่อย่อได้ เพิ่มความสะดวกในการใช้งาน
     * ตัวอย่างการใช้งาน:
     * ```php
     * public function aliases(): array {
     *     return ['cc'];
     * }
     * ```
     * 
     * @return string[] รายการ alias ของคำสั่ง
     */
    public function aliases(): array
    {
        return ['c:c'];
    }

    /**
     * เมธอดหลักสำหรับประมวลผลคำสั่ง
     * จุดประสงค์: รับอาร์กิวเมนต์จาก CLI และบริบท context เพื่อดำเนินการตามคำสั่งที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * public function handle(array $args, ConsoleContext $context): void {
     *     // Logic ของคำสั่ง
     * }
     * ```
     * @param string[] $args อาร์กิวเมนต์ที่ได้รับจาก CLI
     * @param ConsoleContext $context บริบทต่างๆ ที่จำเป็นสำหรับการรันคำสั่ง CLI
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะทำการประมวลผลคำสั่งตามอาร์กิวเมนต์และบริบทที่ได้รับ
     */
    protected function execute(array $args): void
    {
        $this->info("กำลังลบ cache...");

        try {
            $cacheDir = $this->path('storage/cache');
            if (is_dir($cacheDir)) {
                $items = glob($cacheDir . '/*', GLOB_NOSORT);
                $deletedFiles = 0;
                $deletedDirs = 0;

                foreach ($items as $item) {
                    if (basename($item) === '.gitkeep') {
                        continue;
                    }

                    if (is_dir($item)) {
                        $this->removeDirectory($item);
                        $deletedDirs++;
                        continue;
                    }

                    if (is_file($item)) {
                        if (unlink($item)) {
                            $deletedFiles++;
                        } else {
                            $this->warning("ไม่สามารถลบไฟล์: " . basename($item));
                        }
                    }
                }

                $this->success("ลบ cache สำเร็จ! ({$deletedFiles} ไฟล์, {$deletedDirs} โฟลเดอร์)");
                echo "\n";
            } else {
                $this->warning("ไม่พบโฟลเดอร์ cache");
                echo "\n";
            }
        } catch (\Exception $e) {
            echo "\n";
            echo "┌─ Cache Clear Error ─────────────────────────────────┐\n";
            $this->error("เกิดข้อผิดพลาด: " . $e->getMessage());
            $this->error("ไฟล์: " . $e->getFile());
            $this->error("บรรทัด: " . $e->getLine());
            echo "└────────────────────────────────────────────────────┘\n";
            echo "\n";
        }
    }
}
