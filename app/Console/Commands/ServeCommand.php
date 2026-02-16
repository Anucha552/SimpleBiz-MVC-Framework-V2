<?php

declare(strict_types=1);

namespace App\Console\Commands;

class ServeCommand extends BaseCommand
{
    public function name(): string
    {
        return 'serve';
    }

    public function aliases(): array
    {
        return ['s'];
    }

    protected function execute(array $args): void
    {
        $host = $args[0] ?? 'localhost';
        $port = $args[1] ?? '8000';

        $this->info("กำลังเริ่มเซิร์ฟเวอร์สำหรับพัฒนา...");
        $this->success("เซิร์ฟเวอร์ทำงานที่ http://{$host}:{$port}");
        $this->info("กด Ctrl+C เพื่อหยุด");
        echo "\n";

        $phpBinary = PHP_BINARY;
        $phpBinaryQuoted = (str_contains($phpBinary, ' ') ? '"' . $phpBinary . '"' : $phpBinary);

        passthru("{$phpBinaryQuoted} -S {$host}:{$port} -t public");
    }
}
