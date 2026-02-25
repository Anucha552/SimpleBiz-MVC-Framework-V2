# Model Usage Guide

เอกสารนี้อธิบายการใช้งานคลาส Model, ModelQueryBuilder และ QueryBuilder
ภายในโครงสร้าง Framework ของคุณ

------------------------------------------------------------------------

## 1) การสร้าง Model

สร้างคลาสใหม่และสืบทอดจาก Model

``` php
namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected static string $table = 'users';
    protected static string $primaryKey = 'id';

    protected static array $fillable = ['name', 'email', 'password'];
    protected static bool $softDeletes = true;
}
```

สิ่งสำคัญ: - ต้องกำหนด `$table` - `$fillable` ใช้ควบคุม mass
assignment - เปิด soft delete ด้วย `$softDeletes = true`

------------------------------------------------------------------------

## 2) การตั้งค่า Database Connection

ต้องเรียกก่อนใช้งาน Model

``` php
use App\Core\Database;
use App\Core\Model;

$db = Database::getInstance();
Model::setConnection($db);
```

ถ้าไม่เรียก จะเกิด RuntimeException

------------------------------------------------------------------------

## 3) การดึงข้อมูล

### ดึงทั้งหมด

``` php
$users = User::query()->get();
```

### เงื่อนไข where

``` php
$users = User::where('role', '=', 'admin')->get();
```

### where แบบซ้อน

``` php
$users = User::where(function($q) {
    $q->where('age', '>', 18)
      ->orWhere('role', '=', 'admin');
})->get();
```

### find ตาม primary key

``` php
$user = User::find(1);
```

------------------------------------------------------------------------

## 4) การเพิ่มข้อมูล

``` php
$id = User::create([
    'name' => 'John',
    'email' => 'john@example.com',
    'password' => '1234'
]);
```

ระบบจะ: - กรองตาม fillable - เพิ่ม created_at / updated_at อัตโนมัติ

------------------------------------------------------------------------

## 5) การอัปเดตข้อมูล

``` php
User::where('id', '=', 1)
    ->update([
        'name' => 'New Name'
    ]);
```

จะอัปเดต updated_at ให้อัตโนมัติ

------------------------------------------------------------------------

## 6) การลบข้อมูล

### Soft Delete

``` php
User::where('id', '=', 1)->delete();
```

จะอัปเดต deleted_at แทนการลบจริง

### ดูข้อมูลที่ถูกลบ

``` php
$all = User::withTrashed()->get();
$onlyDeleted = User::onlyTrashed()->get();
```

### กู้คืนข้อมูล

``` php
User::onlyTrashed()
    ->where('id', '=', 1)
    ->restore();
```

### ลบจริง

``` php
User::where('id', '=', 1)->forceDelete();
```

------------------------------------------------------------------------

## 7) การใช้ QueryBuilder เพิ่มเติม

### orderBy

``` php
User::orderBy('name', 'DESC')->get();
```

### limit / offset

``` php
User::limit(10)->offset(20)->get();
```

### join

``` php
User::query()
    ->join('profiles', 'users.id', '=', 'profiles.user_id')
    ->get();
```

------------------------------------------------------------------------

## 8) ความปลอดภัย

-   UPDATE และ DELETE ต้องมี WHERE
-   ระบบใช้ Named Binding ป้องกัน SQL Injection
-   escapeIdentifier ครอบ column/table ด้วย backticks อัตโนมัติ

------------------------------------------------------------------------

## 9) สรุปแนวคิดการออกแบบ

Model: - ไม่เก็บ state ของ query - ทำหน้าที่เป็น proxy ไปยัง
QueryBuilder - จัดการ mass assignment และ timestamps

ModelQueryBuilder: - จัดการ soft delete อัตโนมัติ - override
insert/update/delete

QueryBuilder: - สร้าง SQL แบบ chain method - ใช้ binding แทน string
concatenation

------------------------------------------------------------------------

จบคู่มือการใช้งาน
