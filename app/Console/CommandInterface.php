<?php
/**
 * CommandInterface สำหรับคำสั่ง CLI
 * 
 * จุดประสงค์: กำหนดโครงสร้างสำหรับคำสั่ง CLI ที่สามารถลงทะเบียนและเรียกใช้ได้ในระบบคอนโซล
 * 
 * การใช้งาน:
 * - สร้างคลาสที่ implements CommandInterface
 * - กำหนดชื่อคำสั่งและ alias (ถ้ามี)
 * - เขียน logic ในเมธอด handle() เพื่อประมวลผลคำสั่ง
 * - ลงทะเบียนคำสั่งใน CommandRegistry หรือใช้ discover() เพื่อค้นหาอัตโนมัติ
 * 
 * ตัวอย่างการสร้างคำสั่ง:
 * class ClearCacheCommand implements CommandInterface {
 *     public function name(): string {
 *         return 'cache:clear';
 *     }
 *
 *     public function aliases(): array {
 *         return ['cc'];
 *     }
 *
 *     public function handle(array $args, ConsoleContext $context): void {
 *         // Logic ล้าง cache
 *         echo "Cache cleared!\n";
 *     }
 * }
 */

// Namespace และการประกาศ strict types เพื่อความปลอดภัยและความชัดเจนในการใช้งานประเภทข้อมูล
declare(strict_types=1);

namespace App\Console;

interface CommandInterface
{
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
    public function name(): string;

    /**
     * รายการ alias ของคำสั่ง เช่น ['cc'] สำหรับ 'cache:clear'
     * จุดประสงค์: ให้ผู้ใช้สามารถเรียกคำสั่งด้วยชื่อย่อได้ เพิ่มความสะดวกในการใช้งาน
     * ตัวอย่างการใช้งาน:
     * ```php
     * public function aliases(): array {
     *     return ['cc'];
     * }
     * ```
     * @return string[] รายการ alias ของคำสั่ง
     */
    public function aliases(): array;

    /**
     * เมธอดหลักสำหรับประมวลผลคำสั่ง
     * จุดประสงค์: รับอาร์กิวเมนต์จาก CLI และบริบท context เพื่อดำเนินการตามคำสั่งที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * public function handle(array $args, ConsoleContext $context): void {
     *     // Logic ของคำสั่ง
     * }
     * ```
     * @param string[] $args อาร์กิวเมนต์ที่ส่งมาจาก CLI
     * @param ConsoleContext $context บริบทของคอนโซลที่ให้ข้อมูลและเครื่องมือสำหรับการประมวลผลคำสั่ง
     * @return void ไม่มีค่าที่ส่งกลับ แต่สามารถใช้ $context->io() เพื่อแสดงผลลัพธ์หรือข้อความต่างๆ ได้
     */
    public function handle(array $args, ConsoleContext $context): void;
}
