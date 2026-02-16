<?php

declare(strict_types=1);

namespace App\Console\Commands;

class CacheClearCommand extends BaseCommand
{
    public function name(): string
    {
        return 'cache:clear';
    }

    public function aliases(): array
    {
        return ['c:c'];
    }

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
            } else {
                $this->warning("ไม่พบโฟลเดอร์ cache");
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
