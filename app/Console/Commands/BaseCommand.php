<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Console\ConsoleContext;
use App\Console\ConsoleIO;

abstract class BaseCommand implements CommandInterface
{
    /**
     * $context เป็น instance ของ ConsoleContext ที่ใช้ในการส่งผ่านบริบทต่างๆ เช่น เส้นทาง root ของโปรเจกต์ และเครื่องมือสำหรับแสดงผลในคอนโซล ให้กับคำสั่งที่ถูกเรียกใช้
     */
    protected ConsoleContext $context;

    /**
     * $io เป็น instance ของ ConsoleIO ที่ใช้สำหรับแสดงผลข้อความต่างๆ ในคอนโซล เช่น ข้อความสำเร็จ ข้อความผิดพลาด หรือข้อความข้อมูลทั่วไป
     */
    protected ConsoleIO $io;

    /**
     * ชื่อของคำสั่ง เช่น 'cache:clear'
     * จุดประสงค์: ใช้ในการเรียกคำสั่งจาก CLI และในการลงทะเบียนคำสั่ง
     * ตัวอย่างหารใช้งาน:
     * ```php
     * public function name(): string {
     *     return 'cache:clear';
     * }
     * ```
     * 
     * @return string ชื่อของคำสั่ง
     */
    public function aliases(): array
    {
        return [];
    }

    /**
     * เมธอดหลักสำหรับประมวลผลคำสั่ง
     * จุดประสงค์: รับอาร์กิวเมนต์จาก CLI และบริบท context เพื่อดำเนินการตามคำสั่งที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * public function handle(array $args, ConsoleContext $context): void {
     *     // Logic ของคำสั่ง
     * }
     * ```
     * @param string[] $args อาร์กิวเมนต์ที่ได้รับจาก CLI
     * @param ConsoleContext $context บริบทต่างๆ ที่จำเป็นสำหรับการรันคำสั่ง CLI
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะทำการประมวลผลคำสั่งตามอาร์กิวเมนต์และบริบทที่ได้รับ
     */
    final public function handle(array $args, ConsoleContext $context): void
    {
        $this->context = $context;
        $this->io = $context->io();
        $this->execute($args);
    }

    /**
     * เมธอดหลักสำหรับประมวลผลคำสั่ง
     * จุดประสงค์: เป็นเมธอดที่ต้องถูก implement โดยคลาสที่สืบทอดจาก BaseCommand เพื่อให้สามารถประมวลผลคำสั่งตามอาร์กิวเมนต์ที่ได้รับ
     * ตัวอย่างการใช้งาน:
     * ```php
     * protected function execute(array $args): void {
     *    // Logic ของคำสั่ง
     * }
     * ```
     * 
     * @param string[] $args อาร์กิวเมนต์ที่ได้รับจาก CLI
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะทำการประมวลผล
     */
    abstract protected function execute(array $args): void;

    /**
     * คืนค่าเส้นทางไฟล์จากเส้นทาง root ของโปรเจกต์
     * จุดประสงค์: ให้สามารถคำนวณเส้นทางไฟล์ต่างๆ ที่เกี่ยวข้องกับคำสั่ง CLI ได้อย่างง่ายดาย โดยรับเส้นทางสัมพัทธ์และคืนค่าเส้นทางเต็ม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->path('config/app.php'); // คืนค่า '/path/to/project/config/app.php'
     * ```
     * @param string $relative เส้นทางสัมพัทธ์จาก root ของโปรเจกต์
     * @return string เส้นทางเต็มของไฟล์ที่คำนวณได้
     */
    protected function path(string $relative): string
    {
        return $this->context->path($relative);
    }

    /**
     * แสดงข้อความสำเร็จในคอนโซล โดยใช้สีเขียวเพื่อเพิ่มความชัดเจนในการแสดงผล
     * จุดประสงค์: ให้สามารถแสดงข้อความสำเร็จที่ชัดเจนและโดดเด่นในคอนโซล เพื่อให้ผู้ใช้สามารถเข้าใจสถานะของคำสั่งได้อย่างรวดเร็ว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->success("Operation completed successfully.");
     * ```
     * 
     * @param string $message ข้อความที่ต้องการแสดงในคอนโซล
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะแสดงข้อความในคอนโซลในรูปแบบที่แตกต่างกันตามประเภทของข้อความ
     */
    protected function success(string $message): void
    {
        $this->io->success($message);
    }

    /**
     * แสดงข้อความผิดพลาดในคอนโซล โดยใช้สีแดงเพื่อเพิ่มความชัดเจนในการแสดงผล
     * จุดประสงค์: ให้สามารถแสดงข้อความผิดพลาดที่ชัดเจนและโดดเด่นในคอนโซล เพื่อให้ผู้ใช้สามารถเข้าใจสถานะของคำสั่งได้อย่างรวดเร็ว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->error("An error occurred while processing the request.");
     * ```
     * 
     * @param string $message ข้อความที่ต้องการแสดงในคอนโซล
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะแสดงข้อความในคอนโซลในรูปแบบที่แตกต่างกันตามประเภทของข้อความ
     */
    protected function error(string $message): void
    {
        $this->io->error($message);
    }

    /**
     * แสดงข้อความข้อมูลทั่วไปในคอนโซล โดยใช้สีฟ้าเพื่อเพิ่มความชัดเจนในการแสดงผล
     * จุดประสงค์: ให้สามารถแสดงข้อความข้อมูลทั่วไปที่ไม่ใช่ข้อผิดพลาดหรือความสำเร็จ เพื่อให้ผู้ใช้สามารถเข้าใจสถานะของคำสั่งได้อย่างรวดเร็ว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->info("This is some informational message.");
     * ```
     * 
     * @param string $message ข้อความที่ต้องการแสดงในคอนโซล
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะแสดงข้อความในคอนโซลในรูปแบบข้อมูลทั่วไป
     */
    protected function info(string $message): void
    {
        $this->io->info($message);
    }

    /**
     * แสดงข้อความทั่วไปในคอนโซล โดยใช้สีขาวเพื่อเพิ่มความชัดเจนในการแสดงผล
     * จุดประสงค์: ให้สามารถแสดงข้อความทั่วไปที่ไม่ใช่ข้อผิดพลาดหรือความสำเร็จ เพื่อให้ผู้ใช้สามารถเข้าใจสถานะของคำสั่งได้อย่างรวดเร็ว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->infoWhite("This is some informational message.");
     * ```
     * 
     * @param string $message ข้อความที่ต้องการแสดงในคอนโซล
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะแสดงข้อความในคอนโซลในรูปแบบข้อความทั่วไป
     */
    protected function infoWhite(string $message): void
    {
        $this->io->infoWhite($message);
    }

    /**
     * แสดงข้อความเตือนในคอนโซล โดยใช้สีเหลืองเพื่อเพิ่มความชัดเจนในการแสดงผล
     * จุดประสงค์: ให้สามารถแสดงข้อความเตือนที่ชัดเจนและโดดเด่นในคอนโซล เพื่อให้ผู้ใช้สามารถเข้าใจสถานะของคำสั่งได้อย่างรวดเร็ว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->warning("This is a warning message.");
     * ```
     * 
     * @param string $message ข้อความที่ต้องการแสดงในคอนโซล
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะแสดงข้อความในคอนโซลในรูปแบบข้อความเตือน
     */
    protected function warning(string $message): void
    {
        $this->io->warning($message);
    }

    /**
     * แสดงข้อความยืนยันในคอนโซล โดยให้ผู้ใช้สามารถตอบตกลงหรือไม่ตกลง
     * จุดประสงค์: ให้สามารถขอการยืนยันจากผู้ใช้ก่อนดำเนินการที่สำคัญ
     * ตัวอย่างการใช้งาน:
     * ```php
     * if ($this->confirm("Are you sure you want to proceed?")) {
     *     // Logic เมื่อผู้ใช้ยืนยัน
     * }
     * ```
     * 
     * @param string $message ข้อความที่ต้องการแสดงในคอนโซล
     * @param bool $default ค่าดีฟอลต์เมื่อผู้ใช้กด Enter โดยไม่ตอบ
     * @return bool คืนค่า true หากผู้ใช้ยืนยัน, false หากผู้ใช้ไม่ยืนยัน
     */
    protected function confirm(string $message, bool $default = false): bool
    {
        return $this->io->confirm($message, $default);
    }

    /**
     * ตรวจสอบว่ามี flag --force หรือ -f ใน arguments หรือไม่
     * จุดประสงค์: ให้สามารถตรวจสอบได้ว่าผู้ใช้ต้องการบังคับให้คำสั่งทำงานโดยไม่ต้องยืนยันหรือไม่ ซึ่งมักใช้ในคำสั่งที่มีผลกระทบสูง เช่น การลบข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * if ($this->hasForceFlag($args)) {
     *     // ทำงานโดยไม่ต้องยืนยัน
     * } else {
     *     // ขอการยืนยันจากผู้ใช้ก่อนทำงาน
     * }
     * ```
     * 
     * @param string[] $args อาร์กิวเมนต์ที่ส่งมาจาก CLI
     * @return bool คืนค่า true หากมี flag --force หรือ -f อยู่ใน arguments
     */
    protected function hasForceFlag(array $args): bool
    {
        return $this->context->hasForceFlag($args);
    }

    /**
     * ตรวจสอบการเชื่อมต่อฐานข้อมูล
     * จุดประสงค์: ให้สามารถตรวจสอบได้ว่าการเชื่อมต่อฐานข้อมูลสามารถทำงานได้หรือไม่ โดยจะพยายามเชื่อมต่อกับฐานข้อมูลตามการตั้งค่าในไฟล์ config/database.php และแสดงข้อความแนะนำวิธีแก้ไขหากไม่สามารถเชื่อมต่อได้
     * ตัวอย่างการใช้งาน:
     * ```php
     * if (!$this->checkDatabaseConnection()) {
     *     // จัดการกรณีที่ไม่สามารถเชื่อมต่อฐานข้อมูลได้ เช่น หยุดการทำงานของคำสั่ง
     * }
     * ```
     * @return bool คืนค่า true หากสามารถเชื่อมต่อฐานข้อมูลได้สำเร็จ หรือ false หากเกิดข้อผิดพลาดในการเชื่อมต่อ
     */
    protected function checkDatabaseConnection(): bool
    {
        return $this->context->checkDatabaseConnection();
    }

    /**
     * ลบไดเรกทอรีและไฟล์ทั้งหมดภายในไดเรกทอรีนั้น
     * จุดประสงค์: ให้สามารถลบไดเรกทอรีและไฟล์ทั้งหมดภายในไดเรกทอรีนั้นได้อย่างง่ายดาย โดยรับเส้นทางของไดเรกทอรีที่ต้องการลบ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->removeDirectory($this->path('storage/cache'));
     * ```
     * @param string $dir เส้นทางของไดเรกทอรีที่ต้องการลบ
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะทำการลบไดเรกทอรีและไฟล์ทั้งหมดภายในไดเรกทอรีนั้น
     */
    protected function removeDirectory(string $dir): void
    {
        $this->context->removeDirectory($dir);
    }

    /**
     * แปลงขนาดไฟล์จากหน่วยไบต์เป็นหน่วยที่อ่านง่าย เช่น KB, MB, GB เป็นต้น
     * จุดประสงค์: ให้สามารถแสดงขนาดไฟล์ในรูปแบบที่อ่านง่ายและเข้าใจได้อย่างรวดเร็ว โดยรับขนาดไฟล์ในหน่วยไบต์และคืนค่าขนาดไฟล์ในรูปแบบที่เหมาะสม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $readableSize = $this->humanFilesize(1048576); // คืนค่า "1 MB"
     * ```
     * @param int $bytes ขนาดไฟล์ในหน่วยไบต์
     * @param int $decimals จำนวนทศนิยมที่ต้องการแสดงในผลลัพธ์
     * @return string ขนาดไฟล์ในรูปแบบที่อ่านง่าย
     */
    protected function humanFilesize(int $bytes, int $decimals = 2): string
    {
        return $this->context->humanFilesize($bytes, $decimals);
    }
}
