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
    protected static string $table = 'employees';

    /**
     * Primary key (ปกติใช้ id)
     */
    protected static string $primaryKey = 'id';

    /**
     * ฟิลด์ที่อนุญาตให้ mass assignment
     * fillable: รายชื่อคอลัมน์ที่ “อนุญาต” ให้ตั้งค่าผ่าน fill() หรือ 
     * create() ได้ ถ้าใส่ไว้ ระบบจะเซฟเฉพาะคอลัมน์ในลิสต์นี้เท่านั้น
     */
    protected static array $fillable = [
        'department_id',
        'employee_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'salary',
        'status',
        'created_at'
    ];

    /**
     * ฟิลด์ที่ห้าม mass assignment
     * guarded: รายชื่อคอลัมน์ที่ “ห้าม” ให้ตั้งค่าผ่าน fill() หรือ 
     * create() ได้ ถ้าใส่ไว้ ระบบจะไม่เซฟคอลัมน์ในลิสต์นี้เลย
     */
    protected static array $guarded = ['id'];

    /**
     * เปิด/ปิด timestamps (created_at, updated_at)
     * ถ้าเปิด ระบบจะจัดการคอลัมน์ created_at และ updated_at 
     * ให้อัตโนมัติเมื่อสร้างหรืออัพเดตเรคคอร์ด
     */
    protected static bool $timestamps = false;

    /**
     * เปิด/ปิด soft deletes (deleted_at)
     * ถ้าเปิด ระบบจะจัดการคอลัมน์ deleted_at 
     * ให้อัตโนมัติเมื่อทำการลบเรคคอร์ด
     */
    protected static bool $softDeletes = false;

    /**
     * ดึงข้อมูลพนักงานทั้งหมด
     */
    public static function getAllEmployees(): array
    {
        return static::select()->get(); // ดึงข้อมูลพนักงานทั้งหมด
    }

    /**
     * บันทึกพนักงานใหม่
     */
    public static function createEmployee(array $data): int
    {
        return static::create($data); // ใช้เมธอด create() ของ Model เพื่อบันทึกข้อมูลพนักงานใหม่
    }

    /**
     * แสดงรายละเอียดพนักงานตาม ID
     */
    public static function getEmployeeById(int $id): ?array
    {
        return static::select()
            ->join('departments', 'employees.department_id', '=', 'departments.id') // เชื่อมตาราง departments เพื่อดึงชื่อแผนก
            ->where('employees.id', '=', $id)
            ->first(); // ดึงข้อมูลพนักงานตาม ID ที่ส่งมา
    }

    /**
     * อัพเดตข้อมูลพนักงานตาม ID
     */
    public static function updateEmployee(int $id, array $data): int
    {
        // ใช้เมธอด update() ของ QueryBuilder เพื่ออัพเดตข้อมูลพนักงานตาม ID ที่ส่งมา
        return static::query()->where('id', '=', $id)->update($data);
    }

    /**
     * ลบพนักงานตาม ID
     */
    public static function deleteEmployee(int $id): int
    {
        // ใช้เมธอด delete() ของ QueryBuilder เพื่อทำการลบพนักงานตาม ID ที่ส่งมา
        return static::query()->where('id', '=', $id)->delete();
    }
    }
