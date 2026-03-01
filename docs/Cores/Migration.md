# Migration Guide – Simplibiz Framework

คลาส Migration ถูกออกแบบมาเพื่อจัดการการเปลี่ยนแปลงโครงสร้างฐานข้อมูล (Database Schema) เช่น สร้างตาราง เพิ่มคอลัมน์ ลบตาราง หรือย้อนกลับโครงสร้างเดิม โดยแนวคิดคือ “โครงสร้างฐานข้อมูลต้องถูกควบคุมด้วยโค้ด” เพื่อให้ทุกเครื่องมี schema เหมือนกัน

---

## 1) Migration คืออะไร

Migration คือคลาสที่สืบทอดจาก `App\Core\Migration` และต้องมีเมธอดสำคัญ 2 ตัวเสมอ:

- `up()` → ใช้สร้างหรือแก้ไขโครงสร้าง
- `down()` → ใช้ย้อนกลับสิ่งที่ทำใน up()

แนวคิดง่าย ๆ:
- `up()`   = ทำไปข้างหน้า
- `down()` = ย้อนกลับ

---

## 2) โครงสร้างไฟล์ Migration

**ตัวอย่างไฟล์:**

`database/migrations/2026_03_01_000001_create_users_table.php`

**ตัวอย่างคลาส:**

```php
<?php
use App\Core\Migration;
use App\Core\Blueprint;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->createTable('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropTable('users');
    }
}
```

---

## 3) วิธีใช้งานหลัก

### 3.1 สร้างตารางใหม่
```php
public function up(): void
{
    $this->createTable('posts', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title');
        $table->text('content');
        $table->boolean('is_published')->default(false);
        $table->timestamps();
    });
}

public function down(): void
{
    $this->dropTable('posts');
}
```

### 3.2 เพิ่มคอลัมน์ในตารางที่มีอยู่
```php
public function up(): void
{
    $this->table('users', function (Blueprint $table) {
        $table->string('phone')->nullable();
    });
}

public function down(): void
{
    $this->dropColumn('users', 'phone');
}
```

### 3.3 ลบคอลัมน์
```php
public function up(): void
{
    $this->dropColumn('users', 'old_column');
}

public function down(): void
{
    $this->table('users', function (Blueprint $table) {
        $table->string('old_column')->nullable();
    });
}
```

### 3.4 เปลี่ยนชื่อตาราง
```php
public function up(): void
{
    $this->renameTable('old_users', 'users');
}

public function down(): void
{
    $this->renameTable('users', 'old_users');
}
```

### 3.5 ใช้ SQL ดิบ
ในบางกรณีที่ Blueprint ไม่รองรับ สามารถใช้ SQL ตรงได้

```php
public function up(): void
{
    $this->execute("CREATE INDEX idx_email ON users(email)");
}

public function down(): void
{
    $this->execute("DROP INDEX idx_email ON users");
}
```

### 3.6 รันหลายคำสั่งพร้อมกัน (Transaction)
```php
public function up(): void
{
    $this->executeMultiple([
        "ALTER TABLE users ADD COLUMN age INT",
        "UPDATE users SET age = 0"
    ]);
}
```
ระบบจะจัดการ transaction ให้อัตโนมัติ

---

## 4) เมธอดที่มีให้ใช้ใน Migration

| เมธอด                | ใช้ทำอะไร                        |
|----------------------|-----------------------------------|
| createTable()        | สร้างตาราง                        |
| dropTable()          | ลบตาราง                           |
| table()              | แก้ไขตาราง                        |
| dropColumn()         | ลบคอลัมน์                         |
| renameTable()        | เปลี่ยนชื่อตาราง                  |
| execute()            | รัน SQL ดิบ 1 คำสั่ง              |
| executeMultiple()    | รัน SQL หลายคำสั่ง                |
| tableExists()        | ตรวจสอบว่าตารางมีอยู่             |
| columnExists()       | ตรวจสอบว่าคอลัมน์มีอยู่           |

---

## 5) ตรวจสอบก่อนสร้าง (Best Practice)

เพื่อป้องกัน error ซ้ำซ้อน ควรตรวจสอบก่อน

```php
public function up(): void
{
    if (!$this->tableExists('users')) {
        $this->createTable('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });
    }
}
```

---

## 6) แนวปฏิบัติที่ดี (Best Practices)
- หนึ่ง Migration ต่อหนึ่งการเปลี่ยนแปลงหลัก
- เขียน down() ให้ย้อนกลับได้จริงเสมอ
- อย่าแก้ไฟล์ Migration เก่าในระบบ production
- ตั้งชื่อไฟล์ให้สื่อความหมาย เช่น:
    - create_users_table
    - add_phone_to_users_table
    - rename_orders_to_purchases

---

## 7) Lifecycle การทำงาน
1. สร้างไฟล์ Migration
2. เขียน up() และ down()
3. สั่งรัน migration ผ่าน migration runner ของระบบ
4. ระบบจะเรียก up()
5. หาก rollback → ระบบจะเรียก down()

---

## 8) ตัวอย่างจริงครบวงจร
```php
<?php
use App\Core\Migration;
use App\Core\Blueprint;

class CreateProductsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropTable('products');
    }
}
```

---

## สรุปแนวคิดสำคัญ
- Migration ไม่ใช่แค่ “คำสั่ง SQL” แต่คือ “ประวัติการเปลี่ยนแปลงโครงสร้างฐานข้อมูล”
- ถ้าคุณกำลังสร้าง Framework ของตัวเอง การทำให้ Migration:
    - อ่านง่าย
    - ย้อนกลับได้
    - รองรับหลาย database driver
    - มี transaction
    - มี logging

ถือว่าเป็นสถาปัตยกรรมระดับ Production แล้ว
