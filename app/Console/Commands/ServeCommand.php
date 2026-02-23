<?php
/**
 * ServeCommand
 *
 * จุดประสงค์: เป็นคำสั่ง CLI ที่ใช้สำหรับเริ่มเซิร์ฟเวอร์สำหรับพัฒนา โดยจะใช้ PHP built-in server เพื่อให้ผู้ใช้สามารถทดสอบและพัฒนาแอปพลิเคชันได้อย่างง่ายดายผ่านทางคอนโซล
 */

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

        // ใช้ PHP built-in server เพื่อเริ่มเซิร์ฟเวอร์
        $phpBinary = PHP_BINARY;
        // ถ้า path ของ PHP มีช่องว่าง ให้ใส่เครื่องหมายคำพูด
        $phpBinaryQuoted = (str_contains($phpBinary, ' ') ? '"' . $phpBinary . '"' : $phpBinary);

        // ใช้คำสั่ง passthru เพื่อรันเซิร์ฟเวอร์
        passthru("{$phpBinaryQuoted} -S {$host}:{$port} -t public");
    }
}
