<?php
/**
 * class LogClearCommand
 * 
 * จุดประสงค์: เป็นคำสั่ง CLI ที่ใช้สำหรับลบไฟล์ log ทั้งหมดในโฟลเดอร์ storage/logs โดยสามารถระบุไฟล์หรือโฟลเดอร์ย่อยที่ต้องการลบได้ และมีตัวเลือก --force เพื่อข้ามการยืนยันก่อนลบ
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\ConsoleColor;

class LogClearCommand extends BaseCommand
{
    public function name(): string
    {
        return 'log:clear';
    }

    protected function execute(array $args): void
    {
        $logRoot = $this->path('storage/logs');

        // ตรวจสอบและแยกอาร์กิวเมนต์เพื่อหาตัวเลือก --force และทำความสะอาดอาร์กิวเมนต์ที่เหลือสำหรับการประมวลผล
        $force = $this->hasForceFlag($args);
        $cleanArgs = array_values(array_filter($args, function ($a) {
            return trim($a) !== '--force';
        }));

        // หาตำแหน่งโฟลเดอร์ logs ที่จะทำการลบไฟล์ log โดยตรวจสอบอาร์กิวเมนต์ที่ระบุมาเพื่อหาตัวเลือกโฟลเดอร์ย่อยหรือไฟล์ที่ต้องการลบ หากไม่มีการระบุโฟลเดอร์ย่อยหรือไฟล์ใดๆ จะใช้โฟลเดอร์ logs ทั้งหมดเป็นเป้าหมายในการลบ
        $logDir = $this->resolveLogDir($logRoot, $cleanArgs);

        // ตรวจสอบว่าโฟลเดอร์ logs ที่ระบุมีอยู่จริงหรือไม่ หากไม่มีจะแสดงข้อความเตือนและยกเลิกการทำงาน
        if (!is_dir($logDir)) {
            $this->warning("โฟลเดอร์ logs ไม่พบ: {$logDir}");
            return;
        }

        $filesToDelete = [];

        // หากไม่มีอาร์กิวเมนต์เพิ่มเติม จะลบไฟล์ log ทั้งหมดในโฟลเดอร์ logs แต่หากมีอาร์กิวเมนต์ที่ระบุมา จะทำการประมวลผลเพื่อหาตัวเลือกไฟล์หรือโฟลเดอร์ย่อยที่ต้องการลบ โดยรองรับการใช้ wildcards เช่น * หรือ ? เพื่อระบุกลุ่มของไฟล์ log ที่ต้องการลบ
        if (empty($cleanArgs)) {
            $filesToDelete = glob($logDir . '/*.log');
        } else {

            // ประมวลผลอาร์กิวเมนต์ที่ระบุมาเพื่อหาตัวเลือกไฟล์หรือโฟลเดอร์ย่อยที่ต้องการลบ โดยรองรับการใช้ wildcards เช่น * หรือ ? เพื่อระบุกลุ่มของไฟล์ log ที่ต้องการลบ
            foreach ($cleanArgs as $pattern) {
                $pattern = trim((string) $pattern);
                
                // ข้ามอาร์กิวเมนต์ที่เป็นตัวเลือกหรือว่างเปล่า
                if ($pattern === '') {
                    continue;
                }

                // แปลง pattern ที่ระบุมาเป็นเส้นทางเต็มและตรวจสอบว่าเป็นไฟล์หรือโฟลเดอร์ จากนั้นใช้ glob เพื่อค้นหาไฟล์ log ที่ตรงกับ pattern และเพิ่มเข้าไปในรายการไฟล์ที่จะลบ
                $resolved = $this->resolvePath($logDir, $pattern);
                if (strpbrk($pattern, '*?[]') !== false) {
                    $matches = glob($resolved);
                    if ($matches) {
                        $filesToDelete = array_merge($filesToDelete, $matches);
                    }
                } else {

                   // หาก pattern ที่ระบุมาไม่มี wildcards ให้ตรวจสอบว่าเป็นโฟลเดอร์หรือไฟล์ จากนั้นใช้ glob เพื่อค้นหาไฟล์ log ที่ตรงกับ pattern และเพิ่มเข้าไปในรายการไฟล์ที่จะลบ
                    if (is_dir($resolved)) {
                        $matches = glob(rtrim($resolved, '/\\') . '/*.log');
                        if ($matches) {
                            $filesToDelete = array_merge($filesToDelete, $matches);
                        }
                        continue;
                    }

                    // หาก pattern ที่ระบุมาไม่มี wildcards และไม่ใช่โฟลเดอร์ ให้ตรวจสอบว่าเป็นไฟล์ log ที่มีอยู่จริงหรือไม่ หากมีอยู่จริงให้เพิ่มเข้าไปในรายการไฟล์ที่จะลบ
                    if (file_exists($resolved)) {
                        $filesToDelete[] = $resolved;
                    }
                }
            }
        }

        // ทำความสะอาดรายการไฟล์ที่จะลบโดยการลบรายการที่ซ้ำกันและตรวจสอบว่าเป็นไฟล์จริงๆ เท่านั้น หากไม่มีไฟล์ที่จะลบจะแสดงข้อความเตือนและยกเลิกการทำงาน
        $filesToDelete = array_values(array_unique($filesToDelete));

        // ตรวจสอบว่าไฟล์ที่จะลบเป็นไฟล์จริงๆ เท่านั้น โดยใช้ฟังก์ชัน is_file เพื่อกรองรายการไฟล์ที่จะลบให้เหลือเฉพาะไฟล์ที่มีอยู่จริงในระบบเท่านั้น หากไม่มีไฟล์ที่จะลบจะแสดงข้อความเตือนและยกเลิกการทำงาน
        $filesToDelete = array_filter($filesToDelete, 'is_file');

        if (empty($filesToDelete)) {
            $this->warning("ไม่พบไฟล์ที่จะลบ");
            return;
        }

        echo ConsoleColor::YELLOW . "[WARNING] จะลบไฟล์ต่อไปนี้ (" . count($filesToDelete) . " files)" . ConsoleColor::RESET . "\n\n";
        foreach ($filesToDelete as $f) {
            echo "  - " . basename($f) . "\n";
        }
        echo "\n";

        if (!$force) {
            if (!$this->confirm("คุณแน่ใจหรือไม่?")) {
                $this->warning("ยกเลิกการทำงาน");
                echo "\n";
                return;
            }
            echo "\n";
        }

        $this->info("กำลังลบ log files...");

        $count = 0;
        foreach ($filesToDelete as $file) {
            if (@unlink($file)) {
                $count++;
                $this->info("  - ลบแล้ว: " . basename($file));
            } else {
                $this->warning("  - ลบไม่สำเร็จ: " . basename($file));
            }
        }

        $this->success("ลบ log files เรียบร้อยแล้ว ({$count} files) \n");
    }

    private function resolveLogDir(string $logRoot, array &$cleanArgs): string
    {
        foreach ($cleanArgs as $index => $arg) {
            $candidate = $this->resolvePath($logRoot, (string) $arg);
            if (is_dir($candidate)) {
                unset($cleanArgs[$index]);
                $cleanArgs = array_values($cleanArgs);
                return $candidate;
            }
        }

        return $logRoot;
    }

    private function resolvePath(string $baseDir, string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return $baseDir;
        }

        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        return rtrim($baseDir, '/\\') . '/' . ltrim($path, '/\\');
    }

    private function isAbsolutePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if (preg_match('#^[A-Za-z]:[\\/]#', $path)) {
            return true;
        }

        return ($path[0] === '/' || $path[0] === '\\');
    }
}
