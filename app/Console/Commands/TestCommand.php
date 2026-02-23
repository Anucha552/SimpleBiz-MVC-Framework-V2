<?php
/**
 * TestCommand
 *
 * จุดประสงค์: เป็นคำสั่ง CLI ที่ใช้สำหรับรัน PHPUnit tests ทั้งหมดในโปรเจกต์ เพื่อให้ผู้ใช้สามารถตรวจสอบความถูกต้องของโค้ดและการทำงานของแอปพลิเคชันได้อย่างง่ายดายผ่านทางคอนโซล
 */

declare(strict_types=1);

namespace App\Console\Commands;

class TestCommand extends BaseCommand
{
    public function name(): string
    {
        return 'test';
    }

    public function aliases(): array
    {
        return ['t'];
    }

    protected function execute(array $args): void
    {
        $this->info("กำลังรัน tests...");
        echo "\n";
        $phpunitPath = $this->path('vendor/bin/phpunit');

        if (PHP_OS_FAMILY === 'Windows') {
            $phpunitPath .= '.bat';
        }

        $command = $phpunitPath;
        if (!empty($args)) {
            $command .= ' ' . implode(' ', $args);
        }

        passthru($command);
    }
}
