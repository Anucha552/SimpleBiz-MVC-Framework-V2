<?php
/**
 * LogPruneCommand
 *
 * จุดประสงค์: เป็นคำสั่ง CLI ที่ใช้สำหรับลบไฟล์ log ที่เก่ากว่าระยะเวลาที่กำหนดไว้ใน retention policy เพื่อช่วยลดพื้นที่จัดเก็บและทำให้การจัดการ log ง่ายขึ้น
 * ตัวอย่างการใช้งาน:
 * ```
 * php console.php log:prune
 * php console.php log:prune --days=30
 * php console.php log:prune --path=storage/logs/old_logs
 * ```
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Logger;

class LogPruneCommand extends BaseCommand
{
    public function name(): string
    {
        return 'log:prune';
    }

    /**
     * @return string[]
     */
    public function aliases(): array
    {
        return ['log:cleanup'];
    }

    protected function execute(array $args): void
    {
        $logRoot = $this->path('storage/logs');
        $logDir = $this->resolveLogPath($logRoot, $args[0] ?? '');

        if (!is_dir($logDir)) {
            $this->warning("โฟลเดอร์ logs ไม่พบ: {$logDir}");
            return;
        }

        try {
            $logger = new Logger($logDir);
        } catch (\Throwable $e) {
            $this->error("ไม่สามารถเตรียม Logger: {$e->getMessage()}");
            return;
        }

        $deleted = $logger->cleanup($logDir);

        if ($deleted === 0) {
            $this->warning('ไม่มีไฟล์ที่เข้าเงื่อนไขการลบ');
            return;
        }

        $this->success("ลบไฟล์ล็อกตาม retention สำเร็จ ({$deleted} files)");
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
