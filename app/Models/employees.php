<?php
declare(strict_types=1);

/**
 * โมเดล employees
 *
 * จุดประสงค์: อธิบายหน้าที่ของโมเดลนี้ (เช่น จัดการข้อมูลผู้ใช้)
 */

namespace App\Models;

use App\Core\Model;

class employees extends Model
{
    /**
     * ชื่อตารางในฐานข้อมูล
     */
    protected string $table = 'employees';

    /**
     * Primary key (ปกติใช้ id)
     */
    protected string $primaryKey = 'id';

    /**
     * ฟิลด์ที่อนุญาตให้ mass assignment
     * fillable: รายชื่อคอลัมน์ที่ “อนุญาต” ให้ตั้งค่าผ่าน fill() หรือ 
     * create() ได้ ถ้าใส่ไว้ ระบบจะเซฟเฉพาะคอลัมน์ในลิสต์นี้เท่านั้น
     */
    protected array $fillable = [
        // ตัวอย่าง: 'name', 'email', 'status'
    ];

    /**
     * ฟิลด์ที่ห้าม mass assignment
     * guarded: รายชื่อคอลัมน์ที่ “ห้าม” ให้ตั้งค่าผ่าน fill() หรือ 
     * create() ได้ ถ้าใส่ไว้ ระบบจะไม่เซฟคอลัมน์ในลิสต์นี้เลย
     */
    protected array $guarded = ['id'];

    /**
     * เปิด/ปิด timestamps (created_at, updated_at)
     * ถ้าเปิด ระบบจะจัดการคอลัมน์ created_at และ updated_at 
     * ให้อัตโนมัติเมื่อสร้างหรืออัพเดตเรคคอร์ด
     */
    protected bool $timestamps = true;

    /**
     * เปิด/ปิด soft deletes (deleted_at)
     * ถ้าเปิด ระบบจะจัดการคอลัมน์ deleted_at 
     * ให้อัตโนมัติเมื่อทำการลบเรคคอร์ด
     */
    protected bool $softDeletes = false;

    /**
     * ดีงข้อมูลพนักงานทั้งหมด
     */
    public static function getAllEmployees(): array
    {
        return static::join('departments', 'employees.department_id', '=', 'departments.id')->get();
    }
}
