# Quick Start - เริ่มต้นใช้งานอย่างรวดเร็ว

คู่มือเริ่มต้นใช้งาน SimpleBiz MVC Framework V2 แบบรวดเร็วที่สุด!

---

## ⚡ เริ่มต้นแบบรวดเร็ว (5 นาที)

### ขั้นตอนที่ 1: Clone Framework

```bash
git clone https://github.com/Anucha552/SimpleBiz-MVC-Framework-V2.git
```

---

### ขั้นตอนที่ 2: เปลี่ยนชื่อโฟลเดอร์

```bash
# เปลี่ยนชื่อเป็นชื่อโปรเจคของคุณ
mv SimpleBiz-MVC-Framework-V2 my-project
cd my-project
```

---

### ขั้นตอนที่ 3: รันคำสั่ง Setup (แนะนำ!)

```bash
php console setup
```

คำสั่งนี้จะถามคำถามและทำทุกอย่างอัตโนมัติ:
- ✅ แก้ไข composer.json
- ✅ สร้างไฟล์ .env
- ✅ สร้าง APP_KEY
- ✅ ติดตั้ง Composer dependencies

**ตัวอย่างการตอบคำถาม:**

```
🚀 SimpleBiz Framework Project Setup

ชื่อโปรเจค: bookstore
คำอธิบายโปรเจค: Online Bookstore Platform
Vendor/Company name: mycompany
ชื่อแอปพลิเคชัน: My Bookstore
ชื่อ Database: bookstore_db
Database Username [root]: root
Database Password: (กด Enter ถ้าไม่มี password)
```

---

### ขั้นตอนที่ 4: สร้าง Database

```bash
# เข้า MySQL
mysql -u root -p

# สร้าง Database (ใช้ชื่อเดียวกับที่ระบุในขั้นตอนที่ 3)
CREATE DATABASE bookstore_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

---

### ขั้นตอนที่ 5: รัน Migrations

```bash
php console migrate
```

คำสั่งนี้จะสร้างตารางในฐานข้อมูลทั้งหมด

---

### ขั้นตอนที่ 6 (Optional): รัน Seeders

```bash
php console seed
```

คำสั่งนี้จะเพิ่มข้อมูลตัวอย่างลงในฐานข้อมูล (ถ้าต้องการ)

---

### ขั้นตอนที่ 7: เริ่มเซิร์ฟเวอร์

```bash
php console serve
```

เปิดเบราว์เซอร์ไปที่: **http://localhost:8000**

---

## 🎉 เสร็จแล้ว!

Framework พร้อมใช้งานแล้ว! ใช้เวลาแค่ 5 นาที

---

## 🚀 เริ่มพัฒนา

### สร้าง Controller ใหม่

```bash
php console make:controller ProductController
```

ไฟล์จะถูกสร้างที่: `app/Controllers/ProductController.php`

---

### สร้าง Model ใหม่

```bash
php console make:model Product
```

ไฟล์จะถูกสร้างที่: `app/Models/Product.php`

---

### สร้าง Middleware ใหม่

```bash
php console make:middleware CheckAgeMiddleware
```

ไฟล์จะถูกสร้างที่: `app/Middleware/CheckAgeMiddleware.php`

---

## 📝 คำสั่ง CLI ทั้งหมด

| คำสั่ง | คำอธิบาย |
|--------|----------|
| `php console setup` | ตั้งค่าโปรเจคใหม่อัตโนมัติ |
| `php console serve` | รันเซิร์ฟเวอร์พัฒนา (localhost:8000) |
| `php console migrate` | รัน database migrations |
| `php console seed` | รัน database seeders |
| `php console cache:clear` | ลบ cache ทั้งหมด |
| `php console make:controller <name>` | สร้าง controller ใหม่ |
| `php console make:model <name>` | สร้าง model ใหม่ |
| `php console make:middleware <name>` | สร้าง middleware ใหม่ |
| `php console test` | รัน PHPUnit tests |
| `php console help` | แสดงคำสั่งทั้งหมด |

---

## 🔧 ถ้าไม่ใช้คำสั่ง `setup`

ถ้าต้องการตั้งค่าด้วยมือ:

### 1. คัดลอก .env.example

```bash
cp .env.example .env
```

### 2. แก้ไขไฟล์ .env

```env
APP_NAME="My Project"
APP_URL=http://localhost

DB_DATABASE=my_database
DB_USERNAME=root
DB_PASSWORD=

# สร้าง APP_KEY ใหม่
APP_KEY=your-32-character-random-key
```

### 3. สร้าง APP_KEY

```bash
# วิธีที่ 1: ใช้ openssl
openssl rand -hex 16

# วิธีที่ 2: ใช้ PHP
php -r "echo bin2hex(random_bytes(16));"
```

คัดลอกค่าที่ได้ไปใส่ใน `.env` ที่ `APP_KEY=`

### 4. แก้ไข composer.json

```json
{
    "name": "yourcompany/your-project",
    "description": "Your project description",
    ...
}
```

### 5. ติดตั้ง Dependencies

```bash
composer install
```

### 6. สร้าง Database และรัน Migrations

```bash
# สร้าง Database
mysql -u root -p
CREATE DATABASE my_database;
exit;

# รัน Migrations
php console migrate
```

---

## 🎯 โครงสร้างโปรเจค

```
my-project/
├── app/
│   ├── Controllers/     # Controllers ของคุณ
│   ├── Models/          # Models ของคุณ
│   ├── Views/           # Views (HTML/PHP)
│   ├── Core/            # ⛔ อย่าแก้! Core Framework
│   ├── Helpers/         # ⛔ อย่าแก้! Helper Functions
│   └── Middleware/      # Middleware classes
├── config/              # Configuration files
├── database/
│   └── migrations/      # Database migrations
├── public/
│   ├── index.php        # Entry point
│   └── assets/          # CSS, JS, Images
├── routes/
│   ├── web.php          # Web routes
│   └── api.php          # API routes
├── storage/             # Cache, logs
├── docs/                # เอกสาร
├── .env                 # Environment variables
├── composer.json        # Composer dependencies
└── console              # CLI tool
```

---

## 📚 เอกสารเพิ่มเติม

### เอกสารสำคัญ (ควรอ่าน):
- [RENAME_PROJECT.md](RENAME_PROJECT.md) - วิธีเปลี่ยนชื่อโปรเจค
- [USE_CASES.md](USE_CASES.md) - Framework ทำเว็บอะไรได้บ้าง
- [FRAMEWORK_VS_EXAMPLES.md](FRAMEWORK_VS_EXAMPLES.md) - แยกระหว่าง Core และ Examples
- [CLI_GUIDE.md](CLI_GUIDE.md) - คู่มือคำสั่ง CLI ทั้งหมด

### เอกสารการใช้งาน:
- [CORE_USAGE.md](CORE_USAGE.md) - วิธีใช้งาน Core Classes
- [HELPERS_GUIDE.md](HELPERS_GUIDE.md) - วิธีใช้งาน Helper Functions
- [MIDDLEWARE_GUIDE.md](MIDDLEWARE_GUIDE.md) - วิธีใช้งาน Middleware
- [MODELS_GUIDE.md](MODELS_GUIDE.md) - วิธีใช้งาน Models
- [VIEWS_GUIDE.md](VIEWS_GUIDE.md) - วิธีสร้าง Views
- [API_REFERENCE.md](API_REFERENCE.md) - API Documentation

### เอกสารขั้นสูง:
- [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md) - Database Migrations
- [SEEDING_GUIDE.md](SEEDING_GUIDE.md) - Database Seeding
- [TESTING_GUIDE.md](TESTING_GUIDE.md) - การเขียน Tests
- [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) - การ Deploy Production
- [SECURITY_HARDENING.md](SECURITY_HARDENING.md) - ความปลอดภัย

---

## 💡 Tips & Tricks

### 1. ใช้ Helper Functions

```php
// String Helper
use function App\Helpers\slugify;
$slug = slugify('My Product Name'); // my-product-name

// URL Helper
use function App\Helpers\url;
$url = url('/products/view/123'); // http://localhost/products/view/123

// Array Helper
use function App\Helpers\array_get;
$value = array_get($data, 'user.name', 'Default');
```

### 2. ใช้ Environment Variables

```php
// ในโค้ด PHP
$appName = getenv('APP_NAME');
$dbName = getenv('DB_DATABASE');
$debug = getenv('APP_DEBUG') === 'true';
```

### 3. ใช้ Cache

```php
use App\Core\Cache;

$cache = new Cache();
$cache->set('key', 'value', 3600); // 1 hour
$value = $cache->get('key');
```

### 4. ใช้ Logger

```php
use App\Core\Logger;

$logger = new Logger();
$logger->info('User logged in', ['user_id' => 123]);
$logger->error('Payment failed', ['order_id' => 456]);
```

### 5. ใช้ Validator

```php
use App\Core\Validator;

$validator = new Validator($_POST, [
    'email' => ['required', 'email'],
    'password' => ['required', 'min:8']
]);

if ($validator->fails()) {
    $errors = $validator->errors();
}
```

---

## ❓ คำถามที่พบบ่อย

### Q: ต้องใช้คำสั่ง `setup` ทุกครั้งไหม?
A: ไม่ใช้ทุกครั้ง ใช้แค่ครั้งแรกหลัง clone เท่านั้น

### Q: ถ้าไม่มี MySQL จะทำยังไง?
A: สามารถแก้ไข `.env` เป็น SQLite หรือ PostgreSQL ได้

### Q: ลบ Examples ยังไง?
A: ดูเอกสาร [FRAMEWORK_VS_EXAMPLES.md](FRAMEWORK_VS_EXAMPLES.md)

### Q: เปลี่ยนชื่อโปรเจคภายหลังได้ไหม?
A: ได้ แก้ `composer.json` และ `.env` ใหม่

### Q: Deploy ยังไง?
A: ดูเอกสาร [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)

---

## 🆘 ต้องการความช่วยเหลือ?

### แสดงคำสั่งทั้งหมด:
```bash
php console help
```

### ตรวจสอบ errors:
```bash
# ดู logs
cat storage/logs/app.log

# รัน tests
php console test
```

### ล้าง cache:
```bash
php console cache:clear
```

---

## 🎊 เริ่มต้นสร้างโปรเจคของคุณกันเลย!

```bash
# สรุปคำสั่งทั้งหมด
git clone https://github.com/Anucha552/SimpleBiz-MVC-Framework-V2.git
mv SimpleBiz-MVC-Framework-V2 my-project
cd my-project
php console setup
# ตอบคำถามตามที่ถูกถาม
mysql -u root -p
CREATE DATABASE my_database;
exit;
php console migrate
php console serve
```

**เปิดเบราว์เซอร์ไปที่: http://localhost:8000**

**เสร็จแล้ว! เริ่มพัฒนากันได้เลย! 🚀**
