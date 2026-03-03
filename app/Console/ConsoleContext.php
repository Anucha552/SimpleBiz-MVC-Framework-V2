<?php
/**
 * class ConsoleContext
 * 
 * จุดประสงค์: ให้บริบทสำหรับคำสั่ง CLI ที่รวมข้อมูลเกี่ยวกับเส้นทาง root ของโปรเจกต์และเครื่องมือสำหรับการแสดงผลในคอนโซล เช่น ConsoleIO
 * 
 * ตัวอย่างการใช้งาน:
 * ```php
 * $context = new ConsoleContext('/path/to/project', new ConsoleIO());
 * $context->path('config/app.php'); // คืนค่า '/path/to/project/config/app.php'
 * $context->io()->info("This is an info message");
 * ```
 */

// Namespace และการประกาศ strict types เพื่อความปลอดภัยและความชัดเจนในการใช้งานประเภทข้อมูล
declare(strict_types=1);

namespace App\Console;

use Exception;
use App\Core\Database;
use App\Core\Model;

class ConsoleContext
{
    /**
     * $rootPath เส้นทาง root ของโปรเจกต์ ซึ่งใช้ในการคำนวณเส้นทางไฟล์ต่างๆ ที่เกี่ยวข้องกับคำสั่ง CLI
     */
    private string $rootPath;

    /**
     * $io เป็น instance ของ ConsoleIO ที่ใช้สำหรับแสดงผลข้อความต่างๆ ในคอนโซล เช่น ข้อความสำเร็จ ข้อความผิดพลาด หรือข้อความข้อมูลทั่วไป
     */
    private ConsoleIO $io;

    /**
     * คอนสตรัคเตอร์สำหรับ ConsoleContext
     * จุดประสงค์: รับเส้นทาง root ของโปรเจกต์และ instance ของ ConsoleIO เพื่อเตรียมบริบทสำหรับการประมวลผลคำสั่ง CLI
     * ตัวอย่างการใช้งาน:
     * ```php
     * $context = new ConsoleContext('/path/to/project', new ConsoleIO());
     * ```
     * @param string $rootPath เส้นทาง root ของโปรเจกต์
     * @param ConsoleIO $io เครื่องมือสำหรับแสดงผลในคอนโซล
     */
    public function __construct(string $rootPath, ConsoleIO $io)
    {
        $this->rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);
        $this->io = $io;
    }

    /**
     * คืนค่าเส้นทาง root ของโปรเจกต์
     * จุดประสงค์: ให้สามารถเข้าถึงเส้นทาง root ของโปรเจกต์ได้จากบริบทของคำสั่ง CLI เพื่อใช้ในการคำนวณเส้นทางไฟล์ต่างๆ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $context->rootPath(); // คืนค่า '/path/to/project'
     * ```
     * @return string เส้นทาง root ของโปรเจกต์
     */
    public function rootPath(): string
    {
        return $this->rootPath;
    }

    /**
     * คำนวณเส้นทางไฟล์จากเส้นทาง root ของโปรเจกต์
     * จุดประสงค์: ให้สามารถคำนวณเส้นทางไฟล์ต่างๆ ที่เกี่ยวข้องกับคำสั่ง CLI ได้อย่างง่ายดาย โดยรับเส้นทางสัมพัทธ์และคืนค่าเส้นทางเต็ม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $context->path('config/app.php'); // คืนค่า '/path/to/project/config/app.php'
     * ```
     * @param string $relative เส้นทางสัมพัทธ์จาก root ของโปรเจกต์
     * @return string เส้นทางเต็มของไฟล์ที่คำนวณได้
     */
    public function path(string $relative): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . ltrim($relative, DIRECTORY_SEPARATOR);
    }

    /**
     * คืนค่า instance ของ ConsoleIO
     * จุดประสงค์: ให้สามารถเข้าถึงเครื่องมือสำหรับแสดงผลในคอนโซลได้จากบริบทของคำสั่ง CLI
     * ตัวอย่างการใช้งาน:
     * ```php
     * $context->io()->info("This is an info message");
     * ```
     * @return ConsoleIO instance ของ ConsoleIO
     */
    public function io(): ConsoleIO
    {
        return $this->io;
    }

    /**
     * ตรวจสอบว่ามี flag --force หรือ -f ใน arguments หรือไม่
     * จุดประสงค์: ให้สามารถตรวจสอบได้ว่าผู้ใช้ต้องการบังคับให้คำสั่งทำงานโดยไม่ต้องยืนยันหรือไม่ ซึ่งมักใช้ในคำสั่งที่มีผลกระทบสูง เช่น การลบข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * if ($context->hasForceFlag($args)) {
     *     // ทำงานโดยไม่ต้องยืนยัน
     * } else {
     *     // ขอการยืนยันจากผู้ใช้ก่อนทำงาน
     * }
     * ```
     * 
     * @param string[] $args อาร์กิวเมนต์ที่ส่งมาจาก CLI
     * @return bool คืนค่า true หากมี flag --force หรือ -f อยู่ใน arguments
     */
    public function hasForceFlag(array $args): bool
    {
        return in_array('--force', $args, true) || in_array('-f', $args, true);
    }

    /**
     * ตรวจสอบการเชื่อมต่อฐานข้อมูล
     * จุดประสงค์: ให้สามารถตรวจสอบได้ว่าการเชื่อมต่อฐานข้อมูลสามารถทำงานได้หรือไม่ โดยจะพยายามเชื่อมต่อกับฐานข้อมูลตามการตั้งค่าในไฟล์ config/database.php และแสดงข้อความแนะนำวิธีแก้ไขหากไม่สามารถเชื่อมต่อได้
     * ตัวอย่างการใช้งาน:
     * ```php
     * if (!$context->checkDatabaseConnection()) {
     *     // จัดการกรณีที่ไม่สามารถเชื่อมต่อฐานข้อมูลได้ เช่น หยุดการทำงานของคำสั่ง
     * }
     * ```
     * @return bool คืนค่า true หากสามารถเชื่อมต่อฐานข้อมูลได้สำเร็จ หรือ false หากเกิดข้อผิดพลาดในการเชื่อมต่อ
     */
    public function checkDatabaseConnection(): bool
    {
        try {
            $db = Database::getInstance();
            $db->fetchColumn('SELECT 1');
            Model::setConnection($db);

            return true;
        } catch (Exception $e) {
            $this->io->error("ไม่สามารถเชื่อมต่อฐานข้อมูล");
            echo ConsoleColor::RED . "Error: " . $e->getMessage() . ConsoleColor::RESET . "\n\n";

            echo ConsoleColor::YELLOW . "[TIP] วิธีแก้ไข:" . ConsoleColor::RESET . "\n";
            echo "  1. ตรวจสอบค่า DB_CONNECTION/DB_DATABASE ในไฟล์ .env\n";
            echo "  2. ถ้าใช้ mysql หรือ mysqli ให้ตรวจสอบว่า server ทำงานอยู่\n";
            echo "  3. ถ้าใช้ sqlite ให้ตรวจสอบว่า path เข้าถึงได้\n";
            echo "  4. ตรวจสอบ username/password ในไฟล์ .env (เฉพาะ mysql)\n\n";

            return false;
        }
    }

    /**
     * ลบไดเรกทอรีและไฟล์ทั้งหมดภายในไดเรกทอรีนั้น
     * จุดประสงค์: ให้สามารถลบไดเรกทอรีและไฟล์ทั้งหมดภายในไดเรกทอรีได้อย่างง่ายดาย ซึ่งมักใช้ในคำสั่งที่ต้องการล้างข้อมูลหรือรีเซ็ตสถานะของโปรเจกต์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $context->removeDirectory($context->path('storage/cache'));
     * ```
     * @param string $dir เส้นทางของไดเรกทอรีที่ต้องการลบ
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะลบไดเรกทอรีและไฟล์ทั้งหมดภายในไดเรกทอรีนั้น
     */
    public function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    /**
     * แปลงขนาดไฟล์จาก bytes เป็นรูปแบบที่อ่านง่าย เช่น KB, MB, GB
     * จุดประสงค์: ให้สามารถแสดงขนาดไฟล์ในรูปแบบที่อ่านง่ายขึ้นในคำสั่ง CLI โดยไม่ต้องคำนวณเอง
     * ตัวอย่างการใช้งาน:
     * ```php
     * echo $context->humanFilesize(2048); // คืนค่า '2 KB'
     * echo $context->humanFilesize(1048576); // คืนค่า '1 MB'
     * ```
     * @param int $bytes ขนาดไฟล์ในหน่วย bytes
     * @param int $decimals จำนวนทศนิยมที่ต้องการแสดง (ค่าเริ่มต้นคือ 2)
     * @return string ขนาดไฟล์ในรูปแบบที่อ่านง่าย เช่น '2 KB', '1 MB', '500 B'
     */
    public function humanFilesize(int $bytes, int $decimals = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = $bytes > 0 ? floor((log($bytes) / log(1024))) : 0;
        $factor = min($factor, count($units) - 1);
        $size = $bytes / pow(1024, $factor);
        return round($size, $decimals) . ' ' . $units[$factor];
    }
}
