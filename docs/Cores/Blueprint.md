# Blueprint Class Guide

คลาส `Blueprint` ใช้สำหรับกำหนดโครงสร้างตารางฐานข้อมูลแบบเป็นขั้นตอน (Fluent API) และสามารถแปลงเป็นคำสั่ง SQL ได้อัตโนมัติ รองรับทั้งการสร้างตารางใหม่ และการแก้ไขตารางเดิม

เหมาะสำหรับใช้ร่วมกับระบบ Migration หรือระบบติดตั้งฐานข้อมูลของ Framework

---

# 1) การเริ่มต้นใช้งาน

```php
use App\Core\Blueprint;

$blueprint = new Blueprint('users');
```

กำหนดชื่อ table ตอนสร้าง instance

---

# 2) การสร้างตาราง (Create Table)

ตัวอย่างพื้นฐาน

```php
$blueprint = new Blueprint('users');

$blueprint->increments('id');
$blueprint->string('username', 150)->unique();
$blueprint->string('email')->nullable();
$blueprint->boolean('is_active', false, 1);
$blueprint->timestamps();

$sql = $blueprint->toCreateSql();
```

ผลลัพธ์จะเป็นคำสั่ง SQL สำหรับสร้างตาราง

---

# 3) ประเภทคอลัมน์ที่รองรับ

## กลุ่มตัวเลข

```php
$blueprint->integer('age');
$blueprint->smallInteger('level');
$blueprint->bigInteger('total');
$blueprint->unsignedInteger('points');
$blueprint->decimal('price', 10, 2);
$blueprint->float('rating');
$blueprint->double('score');
```

---

## กลุ่มข้อความ

```php
$blueprint->string('username', 150);
$blueprint->char('country_code', 2);
$blueprint->text('description');
$blueprint->mediumText('content');
$blueprint->longText('full_content');
```

---

## Boolean

```php
$blueprint->boolean('is_active', false, 1);
```

---

## วันที่และเวลา

```php
$blueprint->date('birth_date');
$blueprint->time('start_time');
$blueprint->dateTime('event_time');
$blueprint->timestamp('created_at');
```

---

## JSON

```php
$blueprint->json('settings');
```

---

## UUID

```php
$blueprint->uuid('uuid');
```

---

## ENUM

```php
$blueprint->enum('status', ['active', 'inactive'], false, 'active');
```

---

## Binary

```php
$blueprint->binary('file_data');
```

---

# 4) การกำหนดค่าเพิ่มเติมของคอลัมน์

สามารถ chain method ต่อได้ เช่น

```php
$blueprint->string('email')
          ->nullable()
          ->unique()
          ->default('example@email.com');
```

---

# 5) Primary Key

แบบ single column

```php
$blueprint->increments('id');
```

แบบหลายคอลัมน์

```php
$blueprint->primary(['user_id', 'order_id']);
```

---

# 6) Index

เพิ่ม index ธรรมดา

```php
$blueprint->index('username');
```

เพิ่ม unique index

```php
$blueprint->unique('email');
```

หลายคอลัมน์

```php
$blueprint->index(['first_name', 'last_name']);
```

---

# 7) Foreign Key

แบบเต็มรูปแบบ

```php
$blueprint->unsignedBigInteger('user_id');

$blueprint->foreign('user_id')
          ->references('id')
          ->on('users')
          ->onDelete('CASCADE');
```

แบบ shorthand

```php
$blueprint->foreignId('user_id')
          ->references('id')
          ->on('users');
```

---

# 8) Timestamps

```php
$blueprint->timestamps();
```

จะสร้าง:

- created_at
- updated_at

---

# 9) Soft Deletes

```php
$blueprint->softDeletes();
```

จะสร้าง:

- deleted_at

---

# 10) การแปลงเป็น SQL

## สร้างตาราง

```php
$sql = $blueprint->toCreateSql();
```

ถ้าใช้ SQLite อาจได้ array ของ SQL statements

---

## แก้ไขตาราง (Add Column)

```php
$stmts = $blueprint->toAlterAddSql();
```

จะได้ array ของ ALTER TABLE statements

---

# 11) ตัวอย่าง Migration แบบเต็ม

```php
$blueprint = new Blueprint('posts');

$blueprint->increments('id');
$blueprint->string('title', 200);
$blueprint->text('content');
$blueprint->unsignedBigInteger('user_id');

$blueprint->foreign('user_id')
          ->references('id')
          ->on('users')
          ->onDelete('CASCADE');

$blueprint->timestamps();

$sql = $blueprint->toCreateSql();
```

---

# 12) แนวทางการใช้งานที่แนะนำ

- ใช้ `increments()` สำหรับ primary key มาตรฐาน
- ใช้ `foreignId()` สำหรับ foreign key
- ใช้ `decimal()` สำหรับเงิน หลีกเลี่ยง float
- ใช้ `timestamps()` ในทุกตารางที่เป็นข้อมูลธุรกิจ
- ใช้ `softDeletes()` ถ้าต้องการระบบลบแบบไม่ทำลายข้อมูลจริง

---

# 13) หมายเหตุเรื่อง Database Driver

Blueprint รองรับ:
- MySQL
- SQLite

การสร้าง index และ auto increment อาจมีพฤติกรรมแตกต่างกันตาม driver

---

จบคู่มือ Blueprint