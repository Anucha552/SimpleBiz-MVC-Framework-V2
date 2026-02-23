<?php
/**
 * class ConsoleRunner
 * 
 * จุดประสงค์: เป็นคลาสหลักที่รับผิดชอบในการรันคำสั่ง CLI โดยจะทำการประมวลผลอาร์กิวเมนต์ที่ได้รับจากคอมมานด์ไลน์ ค้นหาคำสั่งที่ตรงกับอาร์กิวเมนต์ และเรียกใช้คำสั่งนั้นๆ พร้อมกับบริบทที่จำเป็น
 * 
 * การใช้งาน:
 * ```php
 * $registry = new CommandRegistry();
 * $context = new ConsoleContext('/path/to/project', new ConsoleIO());
 * $runner = new ConsoleRunner($registry, $context);
 * $runner->run($argv);
 * ```
 */

// Namespace และการประกาศ strict types เพื่อความปลอดภัยและความชัดเจนในการใช้งานประเภทข้อมูล
declare(strict_types=1);

namespace App\Console;

class ConsoleRunner
{
    /**
     * $registry เป็น instance ของ CommandRegistry ที่ใช้ในการค้นหาคำสั่งที่ตรงกับอาร์กิวเมนต์ที่ได้รับจากคอมมานด์ไลน์
     */
    private CommandRegistry $registry;

    /**
     * $context เป็น instance ของ ConsoleContext ที่ใช้ในการส่งผ่านบริบทต่างๆ เช่น เส้นทาง root ของโปรเจกต์ และเครื่องมือสำหรับแสดงผลในคอนโซล ให้กับคำสั่งที่ถูกเรียกใช้
     */
    private ConsoleContext $context;

    /**
     * คอนสตรัคเตอร์สำหรับ ConsoleRunner
     * จุดประสงค์: รับ instance ของ CommandRegistry และ ConsoleContext เพื่อเตรียมพร้อมสำหรับการรันคำสั่ง CLI
     * ตัวอย่างการใช้งาน:
     * ```php
     * $registry = new CommandRegistry();
     * $context = new ConsoleContext('/path/to/project', new ConsoleIO());
     * $runner = new ConsoleRunner($registry, $context);
     * ```
     * @param CommandRegistry $registry เครื่องมือสำหรับค้นหาคำสั่งที่ตรงกับอาร์กิวเมนต์ที่ได้รับจากคอมมานด์ไลน์
     * @param ConsoleContext $context บริบทต่างๆ ที่จำเป็นสำหรับการรันคำสั่ง CLI
     */
    public function __construct(CommandRegistry $registry, ConsoleContext $context)
    {
        $this->registry = $registry;
        $this->context = $context;
    }

    /**
     * รันคำสั่ง CLI โดยประมวลผลอาร์กิวเมนต์ที่ได้รับจากคอมมานด์ไลน์
     * จุดประสงค์: รับอาร์กิวเมนต์จากคอมมานด์ไลน์ ค้นหาคำสั่งที่ตรงกับอาร์กิวเมนต์ และเรียกใช้คำสั่งนั้นๆ พร้อมกับบริบทที่จำเป็น
     * ตัวอย่างการใช้งาน:
     * ```php
     * $runner = new ConsoleRunner($registry, $context);
     * $runner->run($argv);
     * ```
     * 
     * @param string[] $argv อาร์กิวเมนต์ที่ได้รับจากคอมมานด์ไลน์ โดยปกติจะเป็นตัวแปร $argv ที่ PHP กำหนดให้เมื่อรันสคริปต์จาก CLI
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะทำการประมวลผลคำสั่งและแสดงผลในคอนโซลตามคำสั่งที่ถูกเรียกใช้
     */
    public function run(array $argv): void
    {
        $this->context->io()->printBanner();

        if (count($argv) < 2) {
            $this->runHelp();
            return;
        }

        $command = $argv[1];
        $args = array_slice($argv, 2);

        $handler = $this->registry->find($command);
        if ($handler === null) {
            $this->context->io()->error("ไม่พบคำสั่ง '{$command}'");

            $suggestions = $this->findSimilarCommands($command);
            if (!empty($suggestions)) {
                echo "\n" . ConsoleColor::YELLOW . "คุณหมายถึง:" . ConsoleColor::RESET . "\n";
                foreach ($suggestions as $suggestion) {
                    echo "  " . ConsoleColor::CYAN . $suggestion . ConsoleColor::RESET . "\n";
                }
            }

            $this->context->io()->info("รันคำสั่ง 'php console help' เพื่อดูรายการคำสั่งที่มี \n");
            exit(1);
        }

        try {
            $handler->handle($args, $this->context);
        } catch (\Throwable $e) {
            $this->context->io()->error("Internal error dispatching command: " . $e->getMessage());
            exit(1);
        }
    }

    /**
     * รันคำสั่ง help เพื่อแสดงรายการคำสั่งที่มีอยู่
     * จุดประสงค์: ให้ผู้ใช้สามารถดูรายการคำสั่งที่มีอยู่และวิธีการใช้งานได้อย่างง่ายดาย โดยการรันคำสั่ง help
     * ตัวอย่างการใช้งาน:
     * ```php
     * $runner->run(['console.php', 'help']);
     * ```
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะทำการแสดงรายการคำสั่งที่มีอยู่ในคอนโซล
     */
    private function runHelp(): void
    {
        $handler = $this->registry->find('help');
        if ($handler === null) {
            $this->context->io()->error("ไม่พบคำสั่ง 'help'");
            return;
        }

        $handler->handle([], $this->context);
    }

    /**
     * ค้นหาคำสั่งที่คล้ายกับคำสั่งที่ผู้ใช้ป้อน
     * จุดประสงค์: ช่วยแนะนำคำสั่งที่ใกล้เคียงกับคำสั่งที่ผู้ใช้ป้อนเมื่อไม่พบคำสั่งที่ตรงกัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $suggestions = $runner->findSimilarCommands('hel');
     * ```
     * @param string $input คำสั่งที่ผู้ใช้ป้อน
     * @return string[] รายการคำสั่งที่คล้ายกับคำสั่งที่ผู้ใช้ป้อน
     */
    private function findSimilarCommands(string $input): array
    {
        $allCommands = $this->registry->getCommandNames();
        $suggestions = [];

        foreach ($allCommands as $cmd) {
            if (strpos($cmd, $input) === 0) {
                $suggestions[] = $cmd;
            } elseif (levenshtein($input, $cmd) <= 2) {
                $suggestions[] = $cmd;
            }
        }

        return array_slice($suggestions, 0, 5);
    }
}
