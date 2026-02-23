<?php
/**
 * class ConsoleIO
 * 
 * จุดประสงค์: ให้คอนสตรัคเตอร์สำหรับการแสดงผลข้อความต่างๆ ในคอนโซล เช่น ข้อความสำเร็จ ข้อความผิดพลาด หรือข้อความข้อมูลทั่วไป เพื่อเพิ่มความชัดเจนและความสวยงามในการแสดงผลของคำสั่ง CLI
 * ตัวอย่างการใช้งาน:
 * ```php
 * $io = new ConsoleIO();
 * $io->success("Operation completed successfully!");
 * $io->error("An error occurred while processing the request.");
 * $io->info("This is some informational message.");
 * $io->warning("This is a warning message.");
 * if ($io->confirm("Do you want to continue?", true)) {
 *    echo "User confirmed to continue.\n";
 * } else {
 *   echo "User declined to continue.\n";
 * }
 * ```
 */

// Namespace และการประกาศ strict types เพื่อความปลอดภัยและความชัดเจนในการใช้งานประเภทข้อมูล
declare(strict_types=1);

namespace App\Console;

class ConsoleIO
{
    /**
     * แสดงแบนเนอร์ต้อนรับเมื่อเริ่มต้นคำสั่ง CLI
     * จุดประสงค์: เพื่อเพิ่มความน่าสนใจและความเป็นมืออาชีพในการแสดงผลของคำสั่ง CLI โดยการแสดงแบนเนอร์ที่มีสีสันและข้อความต้อนรับ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $io = new ConsoleIO();
     * $io->printBanner();
     * ```
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะแสดงแบนเนอร์ต้อนรับในคอนโซล
     */
    public function printBanner(): void
    {
        echo ConsoleColor::CYAN . ConsoleColor::BOLD;
        echo "\n╔════════════════════════════════════════╗\n";
        echo "║   SimpleBiz MVC Framework Console      ║\n";
        echo "╚════════════════════════════════════════╝\n";
        echo ConsoleColor::RESET . "\n";
    }

    /**
     * เมธอดสำหรับแสดงข้อความประเภทต่างๆ ในคอนโซล โดยใช้สีที่แตกต่างกันเพื่อเพิ่มความชัดเจนในการแสดงผล
     * จุดประสงค์: ให้สามารถแสดงข้อความในรูปแบบที่แตกต่างกัน เช่น ข้อความสำเร็จ ข้อความผิดพลาด หรือข้อความข้อมูลทั่วไป เพื่อให้ผู้ใช้สามารถเข้าใจสถานะของคำสั่งได้อย่างรวดเร็ว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $io = new ConsoleIO();
     * $io->success("Operation completed successfully!");
     * $io->error("An error occurred while processing the request.");
     * $io->info("This is some informational message.");
     * $io->warning("This is a warning message.");
     * ```
     * 
     * @param string $message ข้อความที่ต้องการแสดงในคอนโซล
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะแสดงข้อความในคอนโซลในรูปแบบที่แตกต่างกันตามประเภทของข้อความ
     */
    public function success(string $message): void
    {
        echo ConsoleColor::GREEN . "[OK] {$message}" . ConsoleColor::RESET . "\n";
    }

    /**
    * แสดงข้อความผิดพลาดในคอนโซล โดยใช้สีแดงเพื่อเพิ่มความชัดเจนในการแสดงผล
    * จุดประสงค์: ให้สามารถแสดงข้อความผิดพลาดที่ชัดเจนและโดดเด่นในคอนโซล เพื่อให้ผู้ใช้สามารถเข้าใจสถานะของคำสั่งได้อย่างรวดเร็ว
    * ตัวอย่างการใช้งาน:
    * ```php
    * $io = new ConsoleIO();
    * $io->error("An error occurred while processing the request.");
    * ```
    * 
    * @param string $message ข้อความที่ต้องการแสดงในคอนโซล
    * @return void ไม่มีค่าที่ส่งกลับ แต่จะแสดงข้อความในคอนโซลในรูปแบบที่แตกต่างกันตามประเภทของข้อความ
    */
    public function error(string $message): void
    {
        echo ConsoleColor::RED . "[X] {$message}" . ConsoleColor::RESET . "\n";
    }

    /**
     * แสดงข้อความข้อมูลทั่วไปในคอนโซล โดยใช้สีฟ้าเพื่อเพิ่มความชัดเจนในการแสดงผล
     * จุดประสงค์: ให้สามารถแสดงข้อความข้อมูลทั่วไปที่ไม่ใช่ข้อผิดพลาดหรือความสำเร็จ เพื่อให้ผู้ใช้สามารถเข้าใจสถานะของคำสั่งได้อย่างรวดเร็ว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $io = new ConsoleIO();
     * $io->info("This is some informational message.");
     * ```
     * 
     * @param string $message ข้อความที่ต้องการแสดงในคอนโซล
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะแสดงข้อความในคอนโซลในรูปแบบข้อมูลทั่วไป
     */
    public function info(string $message): void
    {
        echo ConsoleColor::BLUE . "[i] {$message}" . ConsoleColor::RESET . "\n";
    }

    /**
     * แสดงข้อความทั่วไปในคอนโซล โดยใช้สีขาวเพื่อเพิ่มความชัดเจนในการแสดงผล
     * จุดประสงค์: ให้สามารถแสดงข้อความทั่วไปที่ไม่ใช่ข้อผิดพลาดหรือความสำเร็จ เพื่อให้ผู้ใช้สามารถเข้าใจสถานะของคำสั่งได้อย่างรวดเร็ว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $io = new ConsoleIO();
     * $io->infoWhite("This is some informational message.");
     * ```
     */
    public function infoWhite(string $message): void
    {
        echo ConsoleColor::WHITE . "[i] {$message}" . ConsoleColor::RESET . "\n";
    }

    /**
     * แสดงข้อความเตือนในคอนโซล โดยใช้สีเหลืองเพื่อเพิ่มความชัดเจนในการแสดงผล
     * จุดประสงค์: ให้สามารถแสดงข้อความเตือนที่ไม่ใช่ข้อผิดพลาดหรือความสำเร็จ เพื่อให้ผู้ใช้สามารถเข้าใจสถานะของคำสั่งได้อย่างรวดเร็ว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $io = new ConsoleIO();
     * $io->warning("This is a warning message.");
     * ```
     * 
     * @param string $message ข้อความที่ต้องการแสดงในคอนโซล
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะแสดงข้อความในคอนโซลในรูปแบบข้อความเตือน
     */
    public function warning(string $message): void
    {
        echo ConsoleColor::YELLOW . "[!] {$message}" . ConsoleColor::RESET . "\n";
    }

    /**
     * แสดงข้อความยืนยันและรับการตอบกลับจากผู้ใช้ในคอนโซล
     * จุดประสงค์: ให้สามารถแสดงข้อความยืนยันที่ต้องการการตอบกลับจากผู้ใช้ เช่น การยืนยันการดำเนินการที่สำคัญ เพื่อให้ผู้ใช้สามารถตัดสินใจได้อย่างรวดเร็ว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $io = new ConsoleIO();
     * if ($io->confirm("Do you want to continue?", true)) {
     *    echo "User confirmed to continue.\n";
     * } else {
     *   echo "User declined to continue.\n";
     * }
     * ```
     * 
     * @param string $message ข้อความที่ต้องการแสดงในคอนโซลเพื่อขอการยืนยันจากผู้ใช้
     * @param bool $default ค่าดีฟอลต์ที่ใช้เมื่อผู้ใช้กด Enter โดยไม่พิมพ์อะไร (true = ยืนยัน, false = ปฏิเสธ)
     * @return bool คืนค่าการตอบกลับจากผู้ใช้ (true = ยืนยัน, false = ปฏิเสธ)
     */
    public function confirm(string $message, bool $default = false): bool
    {
        $defaultText = $default ? 'Y/n' : 'y/N';
        echo ConsoleColor::YELLOW . "[?] {$message} [{$defaultText}]: " . ConsoleColor::RESET;

        $input = strtolower(trim(fgets(STDIN)));

        if ($input === '') {
            return $default;
        }

        return in_array($input, ['y', 'yes', 'ใช่'], true);
    }
}
