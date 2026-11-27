# Seeder Class – คู่มือการใช้งาน

คลาส Seeder ถูกออกแบบมาเพื่อใช้เติมข้อมูลตัวอย่าง (Sample Data) ลงในฐานข้อมูลอย่างเป็นระบบ เหมาะสำหรับใช้ตอนพัฒนาโปรเจกต์ ทดสอบระบบ หรือเตรียมข้อมูลเริ่มต้นหลัง migrate เสร็จ

**แนวคิดหลักคือ**

- Seeder หนึ่งคลาส = หนึ่งชุดข้อมูลที่ต้องการสร้าง

## 1️⃣ โครงสร้างพื้นฐานของ Seeder

**ตัวอย่างการสร้าง Seeder**

```php
<?php
namespace Database\Seeders;
use App\Core\Seeder;
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->insert('users', [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ]);
        $this->success('Users seeded successfully.');
    }
}
```

## 2️⃣ วิธีใช้งานหลัก

### 2.1 เมธอด run()
เมธอดนี้ จำเป็นต้องมี และจะถูกเรียกเมื่อสั่งรัน Seeder

```php
public function run(): void
{
    // เขียน logic seeding ที่นี่
}
```

### 2.2 เมธอด insert()
ใช้สำหรับแทรกข้อมูลลงตาราง

**รูปแบบ**
```php
$this->insert(string $table, array $data);
```
**ตัวอย่าง**
```php
$this->insert('products', [
    ['name' => 'Keyboard', 'price' => 500],
    ['name' => 'Mouse', 'price' => 300],
]);
```

**สิ่งที่ระบบทำให้อัตโนมัติ**
- รวมคอลัมน์ให้ตรงกันทุก row
- เติม null ถ้าบาง row ไม่มีคอลัมน์นั้น
- ใช้ prepared statement ป้องกัน SQL injection
- จัดการ error ให้พร้อมคำแนะนำ

## 3️⃣ การล้างข้อมูลก่อน Seed

**เมธอด truncate()**
ใช้ลบข้อมูลทั้งหมดจากตารางก่อน insert ใหม่

```php
$this->truncate('users');
```

**ตัวอย่างใช้งานร่วมกัน**
```php
public function run(): void
{
    $this->truncate('users');
    $this->insert('users', [
        ['name' => 'Admin', 'email' => 'admin@example.com'],
    ]);
    $this->success('Users table reset and seeded.');
}
```

## 4️⃣ ระบบแสดงข้อความใน Console

Seeder มีเมธอดช่วยแสดงข้อความแบบมีสี

- แสดงข้อความทั่วไป: `$this->log('Seeding users...');`
- แสดงข้อความสำเร็จ: `$this->success('Done.');`
- แสดง Warning: `$this->warning('Some data may be missing.');`
- แสดง Error: `$this->error('Insert failed.');`
- แสดง Info: `$this->info('Connecting to database...');`

## 5️⃣ ตัวอย่าง Seeder แบบสมบูรณ์

```php
<?php
namespace Database\Seeders;
use App\Core\Seeder;
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->log('Starting product seeding...');
        $this->truncate('products');
        $this->insert('products', [
            [
                'name' => 'Laptop',
                'price' => 25000,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Monitor',
                'price' => 7000,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Keyboard',
                'price' => 1200,
                'created_at' => date('Y-m-d H:i:s')
            ],
        ]);
        $this->success('Product seeding completed.');
    }
}
```

## 6️⃣ โครงสร้างที่แนะนำในโปรเจกต์

```
database/
 └── seeders/
      ├── UserSeeder.php
      ├── ProductSeeder.php
      └── DatabaseSeeder.php
```

Seeder ในโปรเจกต์นี้ใช้ namespace `Database\Seeders` และคลาส Seeder อื่น ๆ สืบทอดจาก `App\Core\Seeder` สร้างไฟล์ใหม่ได้ด้วยคำสั่ง:

```bash
php console make:seeder ProductSeeder
```

## 7️⃣ การใช้ DatabaseSeeder รวมและเรียก Seeder ตามลำดับ

`DatabaseSeeder` เป็นจุดรวมรายชื่อ Seeder ที่ต้องการรัน โดยสืบทอดจาก `Seeder` แล้วใช้ `$this->call()` เรียกคลาส Seeder ตามลำดับใน array:

```php
<?php
namespace Database\Seeders;

use App\Core\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
```

วางไฟล์นี้ที่ `database/seeders/DatabaseSeeder.php` และเพิ่ม Seeder ที่ต้องการใน `$this->call([...])` โดยเรียงลำดับให้ Seeder ที่สร้างข้อมูลที่จำเป็นต่อ Seeder อื่นทำงานก่อน

**รัน Seeder**

```bash
php console migrate       # สร้างตารางก่อน หากยังไม่ได้ migrate
php console seed          # รัน Seeder ทั้งหมดตาม DatabaseSeeder
php console seed UserSeeder  # รัน UserSeeder เพียงตัวเดียว
```

เมื่อมี `DatabaseSeeder` คำสั่ง `php console seed` จะใช้รายชื่อและลำดับจากไฟล์นี้ ส่วนคำสั่งที่ระบุชื่อ เช่น `php console seed UserSeeder` จะรัน Seeder นั้นโดยตรง ไม่ได้รันลำดับทั้งหมดจาก `DatabaseSeeder`

**ตัวอย่างกับ Seeder ที่มีในโปรเจกต์**

```php
$this->call([
    UserSeeder::class,
    employeeSeeder::class,
]);
```

ตรวจชื่อคลาสและชื่อไฟล์ให้ตรงกับที่มีอยู่จริง โดยเฉพาะระบบไฟล์ที่แยกตัวพิมพ์เล็ก/ใหญ่ หากระบุคลาสที่ไม่มีอยู่ `call()` จะแสดงคำเตือนและข้ามคลาสนั้น ปัจจุบันให้แทน `testSeeder::class` ใน `DatabaseSeeder.php` ด้วย Seeder ที่มีอยู่จริงก่อนรัน `php console seed`

> **ข้อควรระวัง:** Seeder บางตัวเรียก `truncate()` เพื่อลบข้อมูลเดิมก่อนเพิ่มข้อมูลใหม่ ตรวจสอบการทำงานของแต่ละ Seeder ก่อนรัน โดยเฉพาะกับฐานข้อมูลที่มีข้อมูลสำคัญ

## 8️⃣ Error ที่พบบ่อยและแนวทางแก้

- ❌ **Column not found**
  - สาเหตุ: ชื่อคอลัมน์ไม่ตรงกับ Migration
  - แนวทางแก้: ตรวจสอบชื่อคอลัมน์ให้ตรงกัน
- ❌ **Duplicate entry**
  - สาเหตุ: มีข้อมูลซ้ำใน Primary Key / Unique
  - แนวทางแก้: `php console migrate:fresh`
- ❌ **Foreign key constraint**
  - สาเหตุ: ความสัมพันธ์ของตารางไม่ถูกต้อง
  - แนวทางแก้: ตรวจสอบว่า foreign key มีค่าที่มีอยู่จริง

## 9️⃣ Best Practice สำหรับ Seeder

- Seeder ควรมีหน้าที่เดียว (Single Responsibility)
- ไม่ควรเขียน logic ธุรกิจใน Seeder
- ควร truncate ก่อน seed ถ้าเป็นข้อมูลทดสอบ
- แยก Seeder ตามตารางชัดเจน
- ใช้ DatabaseSeeder รวมศูนย์กลาง

## 🔟 แนวคิดการออกแบบที่ดี

Seeder ของคุณออกแบบได้ดีในระดับ Framework เพราะมี:
- ระบบจัดการ Error อัตโนมัติ
- Logging แยกออกจาก logic
- รองรับ SQLite และ MySQL
- ป้องกันปัญหา column mismatch
- โครงสร้างชัดเจนและ extend ได้ง่าย

**ถ้าจะพัฒนาเพิ่มในอนาคต อาจเพิ่ม:**
- Transaction รองรับ rollback
- รองรับ batch insert ขนาดใหญ่
- รองรับ factory/random data
