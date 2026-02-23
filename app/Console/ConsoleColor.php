<?php
/**
 * class ConsoleColor
 * จุดประสงค์: ให้คอนสตรัคเตอร์สำหรับรหัสสี ANSI ที่ใช้ในการแสดงข้อความสีต่างๆ ในคอนโซล เพื่อเพิ่มความชัดเจนและความสวยงามในการแสดงผล
 * ตัวอย่างการใช้งาน:
 * ```php
 * echo ConsoleColor::RED . "This is red text" . ConsoleColor::RESET . "\n";
 * ```
 */

// Namespace และการประกาศ strict types เพื่อความปลอดภัยและความชัดเจนในการใช้งานประเภทข้อมูล
declare(strict_types=1);

namespace App\Console;

/**
 * คลาสนี้ประกอบด้วยคอนสตรัคเตอร์สำหรับรหัสสี ANSI ที่ใช้ในการแสดงข้อความสีต่างๆ ในคอนโซล
 * จุดประสงค์: เพื่อให้สามารถแสดงข้อความในสีต่างๆ ได้ง่ายขึ้นในคำสั่ง CLI โดยไม่ต้องจำรหัสสีเอง
 * ตัวอย่างการใช้งาน:
 * ```php
 * echo ConsoleColor::RED . "This is red text" . ConsoleColor::RESET . "\n";
 * echo ConsoleColor::GREEN . "This is green text" . ConsoleColor::RESET . "\n";
 * echo ConsoleColor::YELLOW . "This is yellow text" . ConsoleColor::RESET . "\n";
 * ```
 */
final class ConsoleColor
{
    public const RESET = "\033[0m"; // รีเซ็ตสีกลับเป็นค่าเริ่มต้น
    public const RED = "\033[31m"; // สีแดงสำหรับข้อความผิดพลาดหรือคำเตือน
    public const GREEN = "\033[32m"; // สีเขียวสำหรับข้อความสำเร็จ
    public const YELLOW = "\033[33m"; // สีเหลืองสำหรับข้อความเตือน
    public const BLUE = "\033[34m"; // สีฟ้าสำหรับข้อความข้อมูลทั่วไป
    public const CYAN = "\033[36m"; // สีฟ้าอ่อนสำหรับข้อความเน้น
    public const WHITE = "\033[37m"; // สีขาวสำหรับข้อความปกติ
    public const GRAY = "\033[90m"; // สีเทาสำหรับข้อความที่ไม่สำคัญหรือคำอธิบาย
    public const BOLD = "\033[1m"; // ตัวหนาสำหรับข้อความเน้น
}
