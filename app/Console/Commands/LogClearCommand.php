<?php

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

        $force = $this->hasForceFlag($args);
        $cleanArgs = array_values(array_filter($args, function ($a) {
            return trim($a) !== '--force';
        }));

        $logDir = $this->resolveLogDir($logRoot, $cleanArgs);

        if (!is_dir($logDir)) {
            $this->warning("โฟลเดอร์ logs ไม่พบ: {$logDir}");
            return;
        }

        $filesToDelete = [];

        if (empty($cleanArgs)) {
            $filesToDelete = glob($logDir . '/*.log');
        } else {
            foreach ($cleanArgs as $pattern) {
                $pattern = trim((string) $pattern);
                if ($pattern === '') {
                    continue;
                }

                $resolved = $this->resolvePath($logDir, $pattern);
                if (strpbrk($pattern, '*?[]') !== false) {
                    $matches = glob($resolved);
                    if ($matches) {
                        $filesToDelete = array_merge($filesToDelete, $matches);
                    }
                } else {
                    if (is_dir($resolved)) {
                        $matches = glob(rtrim($resolved, '/\\') . '/*.log');
                        if ($matches) {
                            $filesToDelete = array_merge($filesToDelete, $matches);
                        }
                        continue;
                    }

                    if (file_exists($resolved)) {
                        $filesToDelete[] = $resolved;
                    }
                }
            }
        }

        $filesToDelete = array_values(array_unique($filesToDelete));
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
