# คู่มือ CLI Commands

คู่มือการใช้งาน Command Line Interface สำหรับ SimpleBiz MVC Framework V2

## การรันคำสั่ง

```bash
php console <command> [arguments] [options]
```

---

## 📋 คำสั่งทั้งหมด

### 1. serve - เริ่มเซิร์ฟเวอร์ PHP Built-in

เริ่มเซิร์ฟเวอร์พัฒนาสำหรับทดสอบแอปพลิเคชัน

**Syntax:**
```bash
php console serve [host] [port]
```

**Parameters:**
- `host` (optional): IP address หรือ hostname (default: localhost)
- `port` (optional): Port number (default: 8000)

**ตัวอย่าง:**
```bash
# เริ่มที่ localhost:8000
php console serve

# เริ่มที่ localhost:3000
php console serve localhost 3000

# เริ่มที่ 0.0.0.0:8080 (เปิดทุก interface)
php console serve 0.0.0.0 8080

# เริ่มที่ IP address เฉพาะ
php console serve 192.168.1.100 8000
```

**Output:**
```
SimpleBiz Development Server Started
-----------------------------------
URL: http://localhost:8000
Press Ctrl+C to quit
```

**เมื่อไหร่ใช้:**
- พัฒนาและทดสอบแอปพลิเคชัน
- ไม่ต้องการตั้งค่า Apache/Nginx
- สาธิตแอปพลิเคชันในเครือข่ายท้องถิ่น

**ข้อควรระวัง:**
⚠️ อย่าใช้ใน production - ใช้สำหรับพัฒนาเท่านั้น

---

### 2. migrate - รัน Database Migrations

รัน migrations เพื่อสร้างหรืออัพเดทโครงสร้างฐานข้อมูล

**Syntax:**
```bash
php console migrate [--rollback=N] [--status]
```

**Options:**
- `--rollback=N`: Rollback N batch ล่าสุด
- `--status`: แสดงสถานะ migrations

**ตัวอย่าง:**
```bash
# รัน migrations ทั้งหมดที่ยังไม่ได้รัน
php console migrate

# แสดงสถานะ migrations
php console migrate --status

# Rollback 1 batch ล่าสุด
php console migrate --rollback=1

# Rollback 3 batch ล่าสุด
php console migrate --rollback=3
```

**Output (migrate):**
```
Running migrations...
✓ 001_create_users_table.sql
✓ 002_create_products_table.sql
✓ 003_create_orders_table.sql
Migrations completed: 3 files
```

**Output (status):**
```
Migration Status:
---------------------------------
Batch  Migration                     Status
---------------------------------
1      001_create_users_table        ✓ Ran
1      002_create_products_table     ✓ Ran
1      003_create_orders_table       ✓ Ran
2      004_create_reviews_table      ✓ Ran
-      005_create_settings_table     ○ Pending
```

**เมื่อไหร่ใช้:**
- ติดตั้งโปรเจคครั้งแรก
- มีการเปลี่ยนแปลงโครงสร้างฐานข้อมูล
- Deploy ไป staging/production
- Rollback เมื่อพบปัญหา

---

### 3. seed - เพิ่มข้อมูลตัวอย่าง

รัน seeders เพื่อเพิ่มข้อมูลตัวอย่างลงฐานข้อมูล

**Syntax:**
```bash
php console seed [seeder_name]
```

**Parameters:**
- `seeder_name` (optional): ชื่อ seeder เฉพาะ (ไม่ต้องระบุ Seeder)

**ตัวอย่าง:**
```bash
# รัน seeders ทั้งหมด (ตามลำดับใน seed.php)
php console seed

# รัน UserSeeder เท่านั้น
php console seed User

# รัน ProductSeeder เท่านั้น
php console seed Product

# รัน CategorySeeder เท่านั้น
php console seed Category
```

**Output:**
```
Running seeders...
✓ CategorySeeder - Created 10 categories
✓ UserSeeder - Created 25 users
✓ ProductSeeder - Created 50 products
Seeding completed successfully
```

**เมื่อไหร่ใช้:**
- พัฒนาและต้องการข้อมูลทดสอบ
- สาธิต features ต่างๆ
- รีเซ็ตฐานข้อมูลให้เหมือนเดิม
- ทดสอบ performance ด้วยข้อมูลจำนวนมาก

**ข้อควรระวัง:**
⚠️ อย่ารันใน production ที่มีข้อมูลจริง - จะเพิ่มข้อมูลปลอม

---

### 4. cache:clear - ล้าง Cache

ลบไฟล์ cache ทั้งหมดเพื่อบังคับให้สร้างใหม่

**Syntax:**
```bash
php console cache:clear
```

**ตัวอย่าง:**
```bash
php console cache:clear
```

**Output:**
```
Clearing cache...
✓ Deleted 15 cache files
✓ Cache directory cleaned
Cache cleared successfully!
```

**เมื่อไหร่ใช้:**
- แก้โค้ดแล้วไม่เห็นการเปลี่ยนแปลง
- Debug ปัญหาที่เกี่ยวข้องกับ cache
- ก่อน deploy version ใหม่
- ปัญหา performance ที่สงสัยว่าจาก cache เก่า

---

### 5. make:controller - สร้าง Controller

สร้างไฟล์ Controller ใหม่พร้อม boilerplate code

**Syntax:**
```bash
php console make:controller <name> [--type=api|web]
```

**Parameters:**
- `name`: ชื่อ Controller (ต้องลงท้ายด้วย Controller)

**Options:**
- `--type=api`: สร้าง API Controller
- `--type=web`: สร้าง Web Controller (default)

**ตัวอย่าง:**
```bash
# สร้าง Web Controller
php console make:controller ProductController

# สร้าง API Controller
php console make:controller ProductApiController --type=api

# สร้าง Controller อื่นๆ
php console make:controller AdminDashboardController
php console make:controller ReportController
```

**Output:**
```
Creating controller: ProductController
✓ Created: app/Controllers/ProductController.php
Controller created successfully!
```

**ไฟล์ที่สร้าง (Web Controller):**
```php
<?php

namespace App\Controllers;

use App\Core\Controller;

class ProductController extends Controller
{
    public function index()
    {
        // List all products
    }

    public function show($id)
    {
        // Show single product
    }

    public function create()
    {
        // Show create form
    }

    public function store()
    {
        // Store new product
    }

    public function edit($id)
    {
        // Show edit form
    }

    public function update($id)
    {
        // Update product
    }

    public function destroy($id)
    {
        // Delete product
    }
}
```

---

### 6. make:model - สร้าง Model

สร้างไฟล์ Model ใหม่พร้อม boilerplate code

**Syntax:**
```bash
php console make:model <name>
```

**Parameters:**
- `name`: ชื่อ Model (เอกพจน์, PascalCase)

**ตัวอย่าง:**
```bash
# สร้าง Model
php console make:model Product
php console make:model Category
php console make:model OrderItem
php console make:model CustomerAddress
```

**Output:**
```
Creating model: Product
✓ Created: app/Models/Product.php
Model created successfully!
```

**ไฟล์ที่สร้าง:**
```php
<?php

namespace App\Models;

use App\Core\Model;

class Product extends Model
{
    protected string $table = 'products';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'name',
        'description',
        'price',
    ];
    
    protected array $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];
}
```

**Tips:**
- ชื่อตาราง จะถูกสร้างจากชื่อ Model (พหูพจน์, snake_case)
  - Product → products
  - OrderItem → order_items
  - CustomerAddress → customer_addresses
- แก้ไข `$fillable` และ `$guarded` ตามต้องการ

---

### 7. make:middleware - สร้าง Middleware

สร้างไฟล์ Middleware ใหม่พร้อม boilerplate code

**Syntax:**
```bash
php console make:middleware <name>
```

**Parameters:**
- `name`: ชื่อ Middleware (ต้องลงท้ายด้วย Middleware)

**ตัวอย่าง:**
```bash
# สร้าง Middleware
php console make:middleware AdminMiddleware
php console make:middleware SubscriberMiddleware
php console make:middleware CheckAgeMiddleware
```

**Output:**
```
Creating middleware: AdminMiddleware
✓ Created: app/Middleware/AdminMiddleware.php
Middleware created successfully!
```

**ไฟล์ที่สร้าง:**
```php
<?php

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Request;

class AdminMiddleware extends Middleware
{
    public function handle(Request $request): bool
    {
        // ใส่ logic ตรวจสอบที่นี่
        // return true เพื่อดำเนินการต่อ
        // return false เพื่อหยุด
        
        return true;
    }
}
```

**หลังสร้างแล้ว:**
1. แก้ไข logic ใน `handle()` method
2. Register middleware ใน `routes/web.php` หรือ `routes/api.php`

---

### 8. test - รัน PHPUnit Tests

รัน automated tests ทั้งหมดหรือบางส่วน

**Syntax:**
```bash
php console test [filter]
```

**Parameters:**
- `filter` (optional): ชื่อ test class หรือ method ที่ต้องการรัน

**ตัวอย่าง:**
```bash
# รัน tests ทั้งหมด
php console test

# รัน tests ที่มีคำว่า "Validator"
php console test Validator

# รัน tests ที่มีคำว่า "Auth"
php console test Auth

# รัน tests ใน feature tests เท่านั้น
php console test tests/Feature
```

**Output:**
```
PHPUnit 9.6.15 by Sebastian Bergmann and contributors.

.......                                                   7 / 7 (100%)

Time: 00:00.145, Memory: 8.00 MB

OK (7 tests, 18 assertions)
```

**Output (มี error):**
```
PHPUnit 9.6.15 by Sebastian Bergmann and contributors.

..F....                                                   7 / 7 (100%)

Time: 00:00.182, Memory: 8.00 MB

There was 1 failure:

1) ValidatorTest::testEmailValidation
Failed asserting that false is true.

/path/to/tests/Unit/ValidatorTest.php:45

FAILURES!
Tests: 7, Assertions: 18, Failures: 1.
```

**เมื่อไหร่ใช้:**
- ก่อน commit code ใหม่
- หลังแก้ไขโค้ดสำคัญ
- ก่อน deploy
- ใน CI/CD pipeline

---

### 9. help - แสดงความช่วยเหลือ

แสดงรายการคำสั่งทั้งหมดและคำอธิบาย

**Syntax:**
```bash
php console help
php console --help
php console
```

**Output:**
```
SimpleBiz MVC Framework V2 - CLI Tool
=====================================

Available Commands:
------------------
  serve                    Start development server
  migrate                  Run database migrations
  seed                     Run database seeders
  cache:clear             Clear all cache files
  make:controller <name>   Create a new controller
  make:model <name>        Create a new model
  make:middleware <name>   Create a new middleware
  test                     Run PHPUnit tests
  help                     Show this help message

Examples:
--------
  php console serve
  php console migrate
  php console make:controller ProductController
  php console test

For more info: docs/CLI_GUIDE.md
```

---

## 🔧 Advanced Usage

### การรันหลายคำสั่งต่อเนื่อง

```bash
# Windows (PowerShell)
php console migrate; php console seed; php console serve

# Linux/Mac (Bash)
php console migrate && php console seed && php console serve
```

### การสร้าง Workflow สำหรับ Deploy

```bash
# deploy.sh (Linux/Mac)
#!/bin/bash
php console cache:clear
php console migrate
echo "Deployment completed!"

# deploy.ps1 (Windows PowerShell)
php console cache:clear
php console migrate
Write-Host "Deployment completed!"
```

### การใช้ใน CI/CD

```yaml
# GitHub Actions example
- name: Run Tests
  run: php console test

- name: Run Migrations
  run: php console migrate
```

---

## 📚 เอกสารเพิ่มเติม

- [Migration Guide](MIGRATION_GUIDE.md) - รายละเอียด migration system
- [Seeding Guide](SEEDING_GUIDE.md) - วิธีสร้างและใช้งาน seeders
- [Testing Guide](TESTING_GUIDE.md) - วิธีเขียนและรัน tests
- [Core Usage](CORE_USAGE.md) - การใช้งาน Core classes
