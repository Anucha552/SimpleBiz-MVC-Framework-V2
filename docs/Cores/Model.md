# Model Usage Guide

เอกสารนี้อธิบายการใช้งาน `Model`, `ModelQueryBuilder` และ `QueryBuilder` ตาม implementation ปัจจุบัน

## 1. สร้าง Model

สร้างคลาสสืบทอดจาก `App\Core\Model` และกำหนดชื่อตาราง

```php
namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected static string $table = 'users';
    protected static string $primaryKey = 'id';
    protected static array $fillable = ['name', 'email', 'password'];
    protected static bool $timestamps = true;
    protected static bool $softDeletes = true;
}
```

การตั้งค่าที่รองรับ:

- `$table` ต้องกำหนดใน Model ลูก
- `$primaryKey` ใช้โดย `Model::find()` และมีค่าเริ่มต้นเป็น `id`
- `$fillable` ระบุคอลัมน์ที่อนุญาตให้ `create()` และ `update()` รับจาก array
- ถ้า `$fillable` ว่าง จะอนุญาตทุกคอลัมน์ยกเว้น `$guarded` ซึ่งค่าเริ่มต้นคือ `['id']`
- `$timestamps` เปิดโดยปริยาย และเพิ่ม `created_at` / `updated_at` ตอนเพิ่มข้อมูล รวมถึง `updated_at` ตอนอัปเดตข้อมูลปกติ
- `$softDeletes` ปิดโดยปริยาย เมื่อเปิดใช้จะกรอง `deleted_at IS NULL` ใน query ปกติ

`fillable` เป็นตัวกรอง mass assignment ไม่ได้เข้ารหัสหรือแฮชค่าของคอลัมน์ เช่น `password` ให้โดยอัตโนมัติ

## 2. ตั้งค่าการเชื่อมต่อฐานข้อมูล

ตั้ง connection ก่อนเรียกใช้ Model โดยปกติทำครั้งเดียวในขั้นตอน bootstrap ของแอป:

```php
use App\Core\Database;
use App\Core\Model;

Model::setConnection(Database::getInstance());
```

หากยังไม่ได้ตั้ง connection หรือ Model ไม่มี `$table` จะเกิด `RuntimeException`

## 3. อ่านข้อมูล

`get()` คืนค่าเป็น array ของแถว และ `first()` / `find()` คืนค่าเป็น array ของแถวหรือ `null` ไม่ได้คืน Model object

```php
$users = User::query()->get();
$admins = User::where('role', '=', 'admin')->get();
$firstAdmin = User::where('role', '=', 'admin')->first();
$user = User::find(1); // ใช้ค่า $primaryKey ของ Model
```

การกรองเพิ่มเติม:

```php
$users = User::whereIn('status', ['active', 'pending'])->get();
$users = User::query()->whereNull('verified_at')->get();
$users = User::query()->whereNotNull('email')->get();
$users = User::where('status', '=', 'active')
    ->where('role', '=', 'member')
    ->orderBy('name')
    ->limit(20)
    ->get();
```

`whereIn($column, [])` สร้างเงื่อนไขที่ไม่ตรงกับแถวใด (`0 = 1`)

เมื่อเปิด `$softDeletes` อย่าใช้ `orWhere()` ระดับบนสุด หากต้องรับประกันว่าแถวที่ถูกลบจะไม่ถูกคืนมา เพราะ SQL precedence อาจทำให้ OR ข้ามเงื่อนไข `deleted_at IS NULL` ได้ และ nested closure ที่ใช้จัดกลุ่มเงื่อนไขยังมีข้อจำกัดตามหมายเหตุด้านล่าง

> **ข้อจำกัดปัจจุบัน:** อย่าใช้ closure แบบ nested เช่น `User::where(function ($q) { ... })` กับ Model ในขณะนี้ เส้นทางสร้าง nested builder ไม่เข้ากันกับ constructor ของ `ModelQueryBuilder` และอาจเกิด `TypeError` ส่วน `User::orWhere()` รับชื่อคอลัมน์และ operator/value ไม่รับ closure

## 4. เพิ่มและแก้ไขข้อมูล

### Create

```php
$id = User::create([
    'name' => 'John',
    'email' => 'john@example.com',
    'password' => $passwordHash,
]);
```

### Update

```php
$affectedRows = User::where('id', '=', $id)->update([
    'name' => 'John Smith',
]);
```

`create()` คืนค่า ID จากฐานข้อมูล (หรือ `0` หากไม่ได้ ID) และ `update()` คืนจำนวนแถวที่ได้รับผลกระทบ ทั้งสองเมธอดกรองข้อมูลตาม `$fillable` / `$guarded` ค่า timestamp ที่ส่งมาเองจะไม่ถูกแทนที่ ส่วน `update()` เพิ่ม `updated_at` หากไม่ได้ระบุและเปิด `$timestamps`

## 5. ลบ กู้คืน และอ่านข้อมูลที่ถูกลบ

เมื่อเปิด `$softDeletes`:

### Soft delete

```php
User::where('id', '=', $id)->delete();
```

### อ่านข้อมูลปกติและข้อมูลที่ถูกลบ

```php
$activeUsers = User::query()->get();
$allUsers = User::withTrashed()->get();
$deletedUsers = User::onlyTrashed()->get();
```

### Restore and force delete

```php
User::onlyTrashed()->where('id', '=', $id)->restore();
User::withTrashed()->where('id', '=', $id)->forceDelete();
```

`restore()` ตั้ง `deleted_at` เป็น `NULL`; `forceDelete()` ลบแถวจริง ส่วน `delete()` ของ Model ที่ไม่ได้เปิด soft delete จะลบแถวจริง ทั้ง `restore()` และการลบแบบ soft delete จะตั้ง `updated_at` ด้วย

## 6. QueryBuilder API

`Model::query()` คืน `ModelQueryBuilder` ซึ่งสืบทอดเมธอด query จาก `QueryBuilder` และเพิ่มการกรอง soft delete รวมถึงการเตรียมข้อมูล mass assignment/timestamps เมธอดที่ใช้ได้ประกอบด้วย:

- เลือกและอ่าน: `select()`, `get()`, `first()`, `find()`
- เงื่อนไข: `where()`, `orWhere()`, `whereIn()`, `whereNull()`, `whereNotNull()`
- join: `join()`, `leftJoin()`
- จัดกลุ่มและเรียง: `groupBy()`, `having()`, `orHaving()`, `orderBy()`
- จำกัดผลลัพธ์: `limit()`, `offset()`
- เขียนข้อมูล: `insert()`, `insertGetId()`, `update()`, `delete()`
- transaction: `beginTransaction()`, `commit()`, `rollback()`
- อื่น ๆ: `toSql()`, `clear()`, `setLoggingEnabled()`

ตัวอย่าง:

```php
$users = User::select(['id', 'name'])
    ->where('status', '=', 'active')
    ->orderBy('name', 'DESC')
    ->limit(10)
    ->offset(20)
    ->get();

$usersWithProfiles = User::query()
    ->select(['users.id', 'users.name', 'profiles.bio'])
    ->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
    ->where('users.status', '=', 'active')
    ->get();
```

เมธอด `select()`, `where()`, `orWhere()`, `whereIn()`, `whereNull()`, `orderBy()`, `limit()` และ `offset()` มี static shortcut บน Model ด้วย ส่วนเมธอดอื่นเรียกผ่าน `Model::query()` หรือ `Model::withTrashed()`

`Model::find()` ใช้ `$primaryKey` ที่กำหนดใน Model ส่วน `find()` ของ `QueryBuilder` ทั่วไปใช้คอลัมน์ `id`

`QueryBuilder` ใช้เดี่ยวได้โดยส่ง `Database` และชื่อตารางให้ constructor แต่จะไม่มีการกรอง soft delete หรือเตรียมข้อมูลของ Model:

```php
use App\Core\Database;
use App\Core\QueryBuilder;

$query = new QueryBuilder(Database::getInstance(), 'users');
$users = $query->where('status', '=', 'active')->get();
```

## 7. ความปลอดภัยและข้อควรระวัง

- ค่าข้อมูลในเงื่อนไขและข้อมูลที่เพิ่ม/แก้ไขใช้ named bindings; อย่าต่อค่าจากผู้ใช้เข้า SQL เอง
- ชื่อตารางและคอลัมน์ถูกครอบด้วย backticks แต่ **operator** ใน `where`, `join` และ `having` ถูกนำไปต่อใน SQL โดยตรง จึงต้องใช้ operator ที่กำหนดไว้ในโค้ด ไม่รับจาก input โดยตรง
- `RawExpression` ใช้แทรก SQL ดิบ ควรใช้เฉพาะ expression ที่ผู้พัฒนากำหนดและเชื่อถือได้
- `QueryBuilder::update()` และ `delete()` ปฏิเสธคำสั่งเมื่อไม่มี `WHERE` ใน SQL แต่ Model ที่เปิด soft delete มีเงื่อนไข `deleted_at IS NULL` เพิ่มให้อัตโนมัติ เงื่อนไขนี้อาจทำให้คำสั่งที่ไม่ได้ระบุ ID ไปแก้ไข ลบแบบ soft delete หรือลบจริงหลายแถวได้
- ระบุเงื่อนไขที่เจาะจงเองทุกครั้งก่อน `update()`, `delete()`, `restore()` หรือ `forceDelete()` อย่าพึ่งพาเงื่อนไข soft delete เป็นตัวจำกัดแถว
- `withTrashed()` เอาเงื่อนไข soft delete ออก ส่วน `onlyTrashed()` เลือกเฉพาะแถวที่ `deleted_at` ไม่เป็น `NULL`

## 8. ขอบเขตการทำงาน

- `Model` เป็น facade แบบ static สำหรับสร้าง `ModelQueryBuilder`; query แต่ละครั้งได้ builder ใหม่
- `ModelQueryBuilder` เพิ่ม mass assignment, timestamps และ soft delete ให้กับ `QueryBuilder`
- `QueryBuilder` สร้างและรันคำสั่ง SQL แบบ chain โดยคืนข้อมูลเป็น array
