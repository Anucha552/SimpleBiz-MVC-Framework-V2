<?php
/**
 * Core: RawExpression
 * 
 * คลาสนี้ใช้สำหรับเก็บคำสั่ง SQL ดิบที่ไม่ต้องการให้มีการจัดรูปแบบหรือแยกคอลัมน์เพิ่มเติม โดยสามารถใช้ร่วมกับ QueryBuilder เพื่อสร้างคำสั่ง SQL ที่ซับซ้อนได้
 */
declare(strict_types=1);

namespace App\Core;

class RawExpression
{
    public function __construct(protected string $value) {}

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
