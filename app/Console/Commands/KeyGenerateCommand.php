<?php
/**
 * class KeyGenerateCommand
 * 
 * จุดประสงค์: เป็นคำสั่ง CLI ที่ใช้สำหรับสร้างค่า APP_KEY ใหม่ในไฟล์ .env เพื่อเพิ่มความปลอดภัยให้กับแอปพลิเคชัน โดยจะทำการสร้างค่า APP_KEY ใหม่แบบสุ่มและอัปเดตในไฟล์ .env
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\ConsoleColor;

class KeyGenerateCommand extends BaseCommand
{
    public function name(): string
    {
        return 'key:generate';
    }

    protected function execute(array $args): void
    {
        $this->info("กำลังสร้าง APP_KEY ใหม่...");

        try {
            $key = bin2hex(random_bytes(16));
            $envFile = $this->path('.env');

            if (!file_exists($envFile)) {
                $this->error("ไม่พบไฟล์ .env");
                $this->info("กรุณารันคำสั่ง 'php console setup' ก่อน");
                return;
            }

            $content = file_get_contents($envFile);
            if ($content === false) {
                throw new \Exception("ไม่สามารถอ่านไฟล์ .env ได้");
            }

            $content = preg_replace('/APP_KEY=.*/', 'APP_KEY=' . $key, $content);

            if (file_put_contents($envFile, $content) === false) {
                throw new \Exception("ไม่สามารถเขียนไฟล์ .env ได้");
            }

            echo "\n";
            $this->success("สร้าง APP_KEY ใหม่สำเร็จ!");
            echo ConsoleColor::CYAN . "APP_KEY: " . ConsoleColor::WHITE . $key . ConsoleColor::RESET . "\n\n";
            echo "\n";
        } catch (\Exception $e) {
            echo "\n";
            echo "┌─ Generate Key Error ────────────────────────────────┐\n";
            $this->error("เกิดข้อผิดพลาด: " . $e->getMessage());
            $this->warning("[TIP] คำแนะนำ: ตรวจสอบสิทธิ์การเขียนไฟล์ .env");
            echo "└────────────────────────────────────────────────────┘\n";
            echo "\n";
        }
    }
}
