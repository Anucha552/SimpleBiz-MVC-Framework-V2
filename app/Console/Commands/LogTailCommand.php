<?php
/**
 * LogTailCommand
 *
 * จุดประสงค์: เป็นคำสั่ง CLI ที่ใช้สำหรับแสดงบรรทัดล่าสุดของไฟล์ log โดยสามารถระบุจำนวนบรรทัดที่ต้องการแสดงได้ และยังสามารถระบุไฟล์ log ที่ต้องการดูได้ด้วยการใช้ชื่อไฟล์หรือโฟลเดอร์ย่อยภายในโฟลเดอร์ logs เพื่อให้ผู้ใช้สามารถตรวจสอบและวิเคราะห์ข้อมูลจากไฟล์ log ได้อย่างรวดเร็วและมีประสิทธิภาพ
 * ตัวอย่างการใช้งาน:
 * ```
 * php console.php log:tail
 * php console.php log:tail 100
 * php console.php log:tail 100 storage/logs/old_logs
 * php console.php log:tail 100 --path=storage/logs/old_logs
 * php console.php log:tail 100 --path=storage/logs/old_logs/app.log
 * php console.php log:tail 100 --path=/var/logs/app.log
 * ```
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\ConsoleColor;
use SplFileObject;

class LogTailCommand extends BaseCommand
{
    public function name(): string
    {
        return 'log:tail';
    }

    protected function execute(array $args): void
    {
        $lines = isset($args[0]) && is_numeric($args[0]) ? (int) $args[0] : 50;

        $logRoot = $this->path('storage/logs');
        $requested = isset($args[1]) ? trim((string) $args[1]) : '';
        $logFile = $this->resolveLogFile($logRoot, $requested);

        if (!file_exists($logFile)) {
            $this->error("ไม่พบไฟล์ log: {$logFile}");
            return;
        }

        $this->info("กำลังแสดง {$lines} บรรทัดล่าสุดจาก " . basename($logFile) . "...");
        echo str_repeat("─", 80) . "\n";

        $file = new SplFileObject($logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        $startLine = max(0, $lastLine - $lines);

        $file->seek($startLine);
        while (!$file->eof()) {
            $line = $file->current();

            if (strpos($line, 'ERROR') !== false) {
                echo ConsoleColor::RED . $line . ConsoleColor::RESET;
            } elseif (strpos($line, 'WARNING') !== false) {
                echo ConsoleColor::YELLOW . $line . ConsoleColor::RESET;
            } elseif (strpos($line, 'INFO') !== false) {
                echo ConsoleColor::BLUE . $line . ConsoleColor::RESET;
            } else {
                echo $line;
            }

            $file->next();
        }

        echo str_repeat("─", 80) . "\n";
        $this->success("แสดง logs เรียบร้อยแล้ว");
        echo "\n";
    }

    private function resolveLogFile(string $logRoot, string $requested): string
    {
        $todayFile = rtrim($logRoot, '/\\') . '/' . date('Y-m-d') . '.log';
        if ($requested === '') {
            return file_exists($todayFile) ? $todayFile : (rtrim($logRoot, '/\\') . '/app.log');
        }

        $path = $this->isAbsolutePath($requested)
            ? $requested
            : (rtrim($logRoot, '/\\') . '/' . ltrim($requested, '/\\'));

        if (is_dir($path)) {
            $todayInDir = rtrim($path, '/\\') . '/' . date('Y-m-d') . '.log';
            if (file_exists($todayInDir)) {
                return $todayInDir;
            }

            $candidates = glob(rtrim($path, '/\\') . '/*.log*');
            if ($candidates) {
                usort($candidates, function ($a, $b) {
                    return filemtime($b) <=> filemtime($a);
                });
                return $candidates[0];
            }
        }

        return $path;
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
