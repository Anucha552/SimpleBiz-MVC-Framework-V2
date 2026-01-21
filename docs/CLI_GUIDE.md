# คู่มือ CLI Commands

SimpleBiz MVC Framework มาพร้อมกับ CLI (Command Line Interface) ที่ช่วยในการพัฒนาและจัดการ Framework

---

## การใช้งานพื้นฐาน

รันคำสั่งผ่าน:
```bash
php console <command> [arguments] [options]
```

---

## คำสั่งที่มีทั้งหมด

### 1. setup - ตั้งค่าโปรเจคใหม่

```bash
php console setup
```

คำสั่งนี้จะ:
- แก้ไข `composer.json` ด้วยข้อมูลโปรเจคของคุณ
- สร้างไฟล์ `.env` จาก `.env.example`
- สร้าง `APP_KEY` แบบสุ่มอัตโนมัติ
- ติดตั้ง Composer dependencies
- ถามคำถามเพื่อปรับแต่งการตั้งค่า

**ตัวอย่างการใช้งาน:**
```
🚀 SimpleBiz Framework Project Setup

ชื่อโปรเจค: my-shop
คำอธิบายโปรเจค: Online Shopping Platform
Vendor/Company name: mycompany
ชื่อแอปพลิเคชัน: My Shop
ชื่อ Database: myshop_db
Database Username [root]: root
Database Password: ****
```

---

### 2. serve - รันเซิร์ฟเวอร์พัฒนา

```bash
# รันที่ localhost:8000 (default)
php console serve

# รันที่พอร์ตอื่น
php console serve 8080

# รันที่ host และพอร์ตที่กำหนด
php console serve localhost 9000
```

**หมายเหตุ:** เซิร์ฟเวอร์นี้เหมาะสำหรับการพัฒนาเท่านั้น ห้ามใช้ใน production

---

### 3. migrate - รัน Database Migrations

```bash
# รัน migrations ทั้งหมดที่ยังไม่ได้รัน
php console migrate

# Rollback batch ล่าสุด
php console migrate:rollback

# Rollback หลาย batch
php console migrate:rollback 2

# Fresh migration (ลบทุกอย่างและรันใหม่)
php console migrate:fresh

# ดูสถานะ migrations
php console migrate:status

# สร้าง migration ใหม่
php console migrate:create CreateUsersTable

# ดูรายการ modules
php console migrate:modules

# รัน migration เฉพาะ module
php console migrate --path=core
php console migrate --path=ecommerce
php console migrate --path=content
php console migrate --path=system
```

---

### 4. seed - รัน Database Seeders

```bash
# รัน seeders ทั้งหมด
php console seed
```

คำสั่งนี้จะเพิ่มข้อมูลตัวอย่างลงในฐานข้อมูล:
- Users (admin และ user ทั่วไป)
- Categories
- Products
- และข้อมูลอื่นๆ ตาม seeders ที่มี

---

### 5. cache:clear - ลบ Cache

```bash
php console cache:clear
```

คำสั่งนี้จะลบไฟล์ cache ทั้งหมดใน `storage/cache/`

---

### 6. make:controller - สร้าง Controller

```bash
# สร้าง controller พื้นฐาน
php console make:controller ProductController

# สร้าง API controller
php console make:controller Api/ProductApiController
```

ไฟล์จะถูกสร้างที่ `app/Controllers/` พร้อม template พื้นฐาน

**ตัวอย่างไฟล์ที่สร้าง:**
```php
<?php
namespace App\Controllers;

use App\Core\Controller;

class ProductController extends Controller
{
    public function index()
    {
        // TODO: Implement index method
    }
}
```

---

### 7. make:model - สร้าง Model

```bash
# สร้าง model
php console make:model Product
```

ไฟล์จะถูกสร้างที่ `app/Models/` พร้อม template พื้นฐาน

**ตัวอย่างไฟล์ที่สร้าง:**
```php
<?php
namespace App\Models;

use App\Core\Model;

class Product extends Model
{
    protected string $table = 'products';
    
    protected array $fillable = [
        // TODO: Add fillable fields
    ];
}
```

---

### 8. make:middleware - สร้าง Middleware

```bash
# สร้าง middleware
php console make:middleware CheckAgeMiddleware
```

ไฟล์จะถูกสร้างที่ `app/Middleware/` พร้อม template พื้นฐาน

**ตัวอย่างไฟล์ที่สร้าง:**
```php
<?php
namespace App\Middleware;

use App\Core\Middleware;

class CheckAgeMiddleware extends Middleware
{
    public function handle(): bool
    {
        // TODO: Implement middleware logic
        return true;
    }
}
```

---

### 9. test - รัน Tests

```bash
# รัน tests ทั้งหมด
php console test

# หรือใช้ PHPUnit โดยตรง
./vendor/bin/phpunit

# รัน test เฉพาะกลุ่ม
./vendor/bin/phpunit tests/Unit
./vendor/bin/phpunit tests/Feature

# รัน test เฉพาะไฟล์
./vendor/bin/phpunit tests/Unit/ValidatorTest.php
```

---

### 10. help - แสดงความช่วยเหลือ

```bash
php console help
```

แสดงรายการคำสั่งทั้งหมดพร้อมคำอธิบายย่อ

---

## ตัวอย่างการใช้งานจริง

### เริ่มโปรเจคใหม่

```bash
# 1. Clone framework
git clone <repository-url> my-project
cd my-project

# 2. ตั้งค่าโปรเจค
php console setup

# 3. สร้างฐานข้อมูล (ใน MySQL)
mysql -u root -p
CREATE DATABASE myproject_db;
exit;

# 4. รัน migrations
php console migrate

# 5. เพิ่มข้อมูลตัวอย่าง
php console seed

# 6. รันเซิร์ฟเวอร์
php console serve
```

### สร้างฟีเจอร์ใหม่

```bash
# 1. สร้าง model
php console make:model Blog

# 2. สร้าง controller
php console make:controller BlogController

# 3. สร้าง middleware (ถ้าจำเป็น)
php console make:middleware CheckBlogOwnerMiddleware

# 4. แก้ไข routes ใน routes/web.php
# 5. สร้าง view ใน app/Views/blog/

# 6. ทดสอบ
php console test
```

---

## หมายเหตุสำคัญ

- คำสั่ง `console` รันได้บนระบบที่มี PHP CLI เท่านั้น
- ต้องมี PHP 8.0 ขึ้นไป
- บางคำสั่งต้องการ Composer dependencies (รัน `composer install` ก่อน)
- เซิร์ฟเวอร์พัฒนา (`serve`) ไม่ควรใช้ใน production
- คำสั่ง `fresh` จะลบข้อมูลทั้งหมดในฐานข้อมูล ใช้อย่างระมัดระวัง

---

## การขยายคำสั่ง CLI

หากต้องการเพิ่มคำสั่งใหม่ แก้ไขไฟล์ `console` และเพิ่ม method ใหม่ในคลาส `Console`:

```php
class Console
{
    // ... existing methods ...
    
    private function myCustomCommand(): void
    {
        $this->success("Running my custom command...");
        // Your command logic here
    }
}
```

จากนั้นเพิ่มใน switch statement:

```php
switch ($command) {
    // ... existing cases ...
    case 'my:command':
        $this->myCustomCommand();
        break;
}
```

---

ดูโค้ดเต็มได้ที่: [`console`](../console)
