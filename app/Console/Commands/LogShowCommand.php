<?php
/**
 * LogShowCommand
 *
 * จุดประสงค์: เป็นคำสั่ง CLI ที่ใช้สำหรับแสดงรายการไฟล์ log ทั้งหมดในโฟลเดอร์ logs โดยจะแสดงชื่อไฟล์ ขนาด และวันที่แก้ไขล่าสุดของแต่ละไฟล์ log เพื่อให้ผู้ใช้สามารถตรวจสอบและจัดการกับไฟล์ log ได้อย่างง่ายดาย
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\ConsoleColor;

class LogShowCommand extends BaseCommand
{
    public function name(): string
    {
        return 'log:show';
    }

    protected function execute(array $args): void
    {
        $logRoot = $this->path('storage/logs');
        $logDir = $this->resolveLogPath($logRoot, $args[0] ?? '');

        if (!is_dir($logDir)) {
            $this->error("โฟลเดอร์ logs ไม่พบ: {$logDir}");
            return;
        }

        $files = glob($logDir . '/*');
        if (!$files) {
            $this->warning("ไม่พบไฟล์ในโฟลเดอร์: {$logDir}");
            return;
        }

        usort($files, function ($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });

        $heading = $logDir === $logRoot ? 'storage/logs' : $logDir;
        echo ConsoleColor::CYAN . "\nFiles in {$heading}:\n" . ConsoleColor::RESET;
        printf("% -40s %12s %22s\n", "Filename", "Size", "Modified (local)");
        echo str_repeat('─', 80) . "\n";

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            $basename = basename($file);
            $size = is_file($file) ? filesize($file) : 0;
            $mtime = filemtime($file);
            $sizeHr = $this->humanFilesize($size);
            $timeStr = $mtime ? date('Y-m-d H:i:s', $mtime) : 'unknown';

            printf("%-40s %12s %22s\n", $basename, $sizeHr, $timeStr);
        }

        echo str_repeat('─', 80) . "\n";
        $this->success("เสร็จสิ้น");
        echo "\n";
    }

    private function resolveLogPath(string $logRoot, string $requested): string
    {
        $requested = trim($requested);
        if ($requested === '') {
            return $logRoot;
        }

        if ($this->isAbsolutePath($requested)) {
            return $requested;
        }

        return rtrim($logRoot, '/\\') . '/' . ltrim($requested, '/\\');
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
