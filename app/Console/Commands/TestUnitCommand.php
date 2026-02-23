<?php
/**
 * TestUnitCommand
 *
 * จุดประสงค์: เป็นคำสั่ง CLI ที่ใช้สำหรับรันชุดทดสอบที่อยู่ในโฟลเดอร์ Unit ของโปรเจกต์ โดยจะช่วยให้ผู้ใช้สามารถทดสอบฟีเจอร์ต่างๆ ของแอปพลิเคชันได้อย่างง่ายดายผ่านทางคอนโซล
 */

declare(strict_types=1);

namespace App\Console\Commands;

class TestUnitCommand extends BaseCommand
{
    public function name(): string
    {
        return 'test:unit';
    }

    public function aliases(): array
    {
        return ['t:u'];
    }

    protected function execute(array $args): void
    {
        $this->info("กำลังรัน Unit Tests...");
        echo "\n";

        array_unshift($args, '--testsuite=Unit');
        $runner = new TestCommand();
        $runner->handle($args, $this->context);
    }
}
