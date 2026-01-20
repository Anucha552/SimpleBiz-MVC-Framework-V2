# SimpleBiz MVC Framework V2

**เฟรมเวิร์ก MVC สำหรับระบบอีคอมเมิร์ซที่สะอาด ปลอดภัย และขยายได้ง่าย**

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 🎯 ภาพรวมโปรเจค

SimpleBiz MVC Framework V2 เป็นเฟรมเวิร์ก PHP แบบ Custom-built ที่ออกแบบมาเพื่อเป็น **ฐานรากสำหรับพัฒนาระบบอีคอมเมิร์ซ** โดยมุ่งเน้นไปที่:

### 🏗️ สถาปัตยกรรม
- **รูปแบบ MVC ที่ชัดเจน** - แยกส่วนการทำงานระหว่าง Models (ตรรกะธุรกิจ), Controllers (ประสานงาน), และ Views (การแสดงผล)
- **PSR-4 Autoloading** - โครงสร้าง namespace มาตรฐาน
- **Router ที่ยืดหยุ่น** - รองรับ dynamic routes, parameters, และ middleware
- **Database Layer** - PDO singleton พร้อมการป้องกัน SQL Injection

### 🛒 ฟีเจอร์อีคอมเมิร์ซหลัก
- **ระบบสินค้า** - จัดการสินค้า ราคา สต็อก และสถานะ
- **ตะกร้าสินค้า** - เพิ่ม แก้ไข ลบสินค้าพร้อมการตรวจสอบความพร้อม
- **ระบบคำสั่งซื้อ** - สร้างคำสั่งซื้อจากตะกร้า พร้อมการคำนวณราคาฝั่งเซิร์ฟเวอร์
- **การจัดการสต็อก** - ระบบลดสต็อกแบบ atomic พร้อม optimistic locking
- **การยืนยันตัวตน** - ลงทะเบียน เข้าสู่ระบบด้วย bcrypt hashing

### 🔒 ความปลอดภัย
- **PDO Prepared Statements** - ป้องกัน SQL Injection 100%
- **Password Hashing** - bcrypt พร้อม automatic salt
- **Server-side Price Validation** - ห้ามเชื่อถือราคาจากฝั่ง client
- **Input Validation** - ตรวจสอบข้อมูลทุกประเภท
- **Security Logging** - บันทึกเหตุการณ์สำคัญทั้งหมด

### 🌐 RESTful API
- **JSON Endpoints** - API สำหรับ Products, Cart, Orders
- **Authentication** - Session-based authentication
- **API Key Protection** - รักษาความปลอดภัย sensitive endpoints
- **Versioned API** - `/api/v1` สำหรับการพัฒนาต่อเนื่อง

### 📚 จุดเด่นเพื่อการเรียนรู้
- **คอมเมนต์ภาษาไทยครบถ้วน** - อธิบายทุกส่วนของโค้ดอย่างละเอียด
- **Best Practices** - ใช้มาตรฐานการเขียนโค้ด PHP สมัยใหม่
- **Security-First Approach** - เน้นความปลอดภัยตั้งแต่เริ่มออกแบบ
- **Clean Code** - โครงสร้างที่อ่านและดูแลรักษาง่าย

---

## 📋 โครงสร้างโปรเจค

```
SimpleBiz-MVC-Framework-V2/
├── app/
│   ├── Controllers/      # จัดการ HTTP requests
│   │   ├── Api/V1/      # API controllers
│   │   └── Ecommerce/   # Web controllers
│   ├── Core/            # Framework core classes
│   ├── Helpers/         # Helper functions
│   ├── Middleware/      # Request filtering
│   └── Models/          # Business logic
├── config/              # Configuration files
├── database/
│   └── migrations/      # SQL schema files
├── public/              # Web root (index.php)
├── routes/              # Route definitions
└── storage/
    └── logs/            # Application logs
```

---

## 🚀 เริ่มต้นใช้งาน

### ความต้องการ
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Composer
- Apache (mod_rewrite) หรือ Nginx

### การติดตั้งอย่างรวดเร็ว

```bash
# 1. Clone repository
git clone [repository-url]
cd SimpleBiz-MVC-Framework-V2

# 2. ติดตั้ง dependencies
composer install

# 3. ตั้งค่า environment (ไฟล์ .env ถูกสร้างไว้แล้ว)
# แก้ไข .env ด้วยข้อมูลฐานข้อมูลของคุณ

# 4. สร้างฐานข้อมูลและรัน migrations
php console migrate

# 5. สร้างข้อมูลตัวอย่าง (optional)
php console seed

# 6. เริ่มเซิร์ฟเวอร์
php console serve
```

เข้าถึงได้ที่: http://localhost:8000

**บัญชีทดสอบ:**
- Username: `admin`
- Password: `password123`

---

## 🛠️ คำสั่ง CLI ที่มีให้ใช้งาน

Framework มาพร้อมกับระบบ CLI ที่ช่วยในการพัฒนา:

```bash
# รันเซิร์ฟเวอร์พัฒนา
php console serve [host] [port]

# Database
php console migrate          # รัน migrations
php console seed            # รัน seeders

# สร้างไฟล์ใหม่
php console make:controller ControllerName
php console make:model ModelName
php console make:middleware MiddlewareName

# เครื่องมืออื่นๆ
php console cache:clear     # ลบ cache
php console test           # รัน PHPUnit tests
php console help           # แสดงความช่วยเหลือ
```

---

## 🎓 สำหรับใคร?

โปรเจคนี้เหมาะสำหรับ:

✅ **นักพัฒนาที่ต้องการเรียนรู้** - ศึกษาการสร้างเฟรมเวิร์ก MVC จากศูนย์  
✅ **โปรเจคขนาดเล็ก-กลาง-ใหญ่** - มีเครื่องมือครบสำหรับ production  
✅ **ระบบอีคอมเมิร์ซแบบกำหนดเอง** - มีฐานรากพร้อมขยายตามต้องการ  
✅ **การเรียนรู้ Security Best Practices** - ดูตัวอย่างการป้องกันช่องโหว่ต่างๆ  
✅ **Production Ready** - มี Testing, Error Handling, CLI Tools ครบถ้วน

---

## ✨ ฟีเจอร์ที่เพิ่งเพิ่มเข้ามา (v2.0)

### Testing Infrastructure ✅
- PHPUnit configuration
- Unit & Feature tests
- Test base class พร้อม helpers

### CLI Commands ✅
- Console command runner
- Code generators (controller, model, middleware)
- Database tools (migrate, seed)
- Test runner

### Error Handling ✅
- Custom error pages (404, 403, 500, 503)
- Error handler class
- JSON error responses

### Database Seeder ✅
- Seeder base class
- Category, User, Product seeders
- ข้อมูลตัวอย่างพร้อมใช้

### Email Service ✅
- Mail class พร้อม template support
- Welcome, Order Confirmation, Password Reset templates
- SMTP configuration

ดู [docs/CHANGELOG.md](docs/CHANGELOG.md) สำหรับรายละเอียดเพิ่มเติม

---

## 📖 เอกสารเพิ่มเติม

สำหรับข้อมูลโดยละเอียด กรุณาอ่าน:
- [docs/CHANGELOG.md](docs/CHANGELOG.md) - การเปลี่ยนแปลงและฟีเจอร์ใหม่
- [docs/READY.md](docs/READY.md) - สรุปความพร้อมของ Framework
- [docs/SYSTEM_CHECK.md](docs/SYSTEM_CHECK.md) - รายงานการตรวจสอบระบบ
- [docs/DEPLOYMENT_GUIDE.md](docs/DEPLOYMENT_GUIDE.md) - คู่มือการ Deploy
- [docs/SECURITY_HARDENING.md](docs/SECURITY_HARDENING.md) - Checklist ความปลอดภัย Production
- [docs/ENVIRONMENTS.md](docs/ENVIRONMENTS.md) - ตารางค่า Environment
- [docs/CORE_USAGE.md](docs/CORE_USAGE.md) - คู่มือการใช้งาน Core
- [docs/HELPERS_GUIDE.md](docs/HELPERS_GUIDE.md) - คู่มือ Helpers
- [docs/MIDDLEWARE_GUIDE.md](docs/MIDDLEWARE_GUIDE.md) - คู่มือ Middleware
- [docs/MIGRATION_GUIDE.md](docs/MIGRATION_GUIDE.md) - คู่มือ Migration
- [docs/PROJECT_STRUCTURE.md](docs/PROJECT_STRUCTURE.md) - โครงสร้างโปรเจค
- [docs/VIEWS_GUIDE.md](docs/VIEWS_GUIDE.md) - คู่มือ Views
- [docs/API_REFERENCE.md](docs/API_REFERENCE.md) - เอกสาร API
- [docs/CLI_GUIDE.md](docs/CLI_GUIDE.md) - คู่มือ CLI
- [docs/SERVICES_GUIDE.md](docs/SERVICES_GUIDE.md) - คู่มือบริการระบบ (Mail/FileUpload/Cache/Logger)
- **Database Schema** - ดูใน [database/migrations/](database/migrations/)
- **Code Comments** - ทุกไฟล์มีคอมเมนต์ภาษาไทยอธิบายการทำงาน

---

## 🧪 การทดสอบ

รัน tests ด้วยคำสั่ง:
```bash
php console test
# หรือ
./vendor/bin/phpunit
```

---

## 🤝 การมีส่วนร่วม

เป็นโปรเจคแบบเปิด สามารถ fork และปรับแต่งได้ตามต้องการ

---

## 📄 ใบอนุญาต

MIT License - ใช้งานได้อย่างอิสระทั้งโปรเจคส่วนตัวและเชิงพาณิชย์

---

## ⚠️ ข้อจำกัดความรับผิด

โปรเจคนี้เป็น **เฟรมเวิร์กเพื่อการศึกษาและพัฒนา** ไม่ใช่โซลูชันอีคอมเมิร์ซที่สมบูรณ์ ควรเพิ่มฟีเจอร์เพิ่มเติม เช่น:
- ระบบชำระเงิน (Payment Gateway)
- ระบบจัดส่ง
- การจัดการผู้ใช้ขั้นสูง (roles, permissions)
- การแจ้งเตือนทาง email
- การจัดการภาพสินค้า

**ใช้ในสภาพแวดล้อมจริงด้วยความระมัดระวัง และปรับปรุงตามความต้องการด้านความปลอดภัย**
INSERT INTO products (name, description, price, stock, status) VALUES
('แล็ปท็อป', 'แล็ปท็อปประสิทธิภาพสูง', 999.99, 10, 'active'),
('เมาส์', 'เมาส์ไร้สาย', 29.99, 50, 'active'),
('คีย์บอร์ด', 'คีย์บอร์ดเมคานิคัล', 79.99, 30, 'active');
```

---

## ⚙️ การกำหนดค่า

### ตัวแปรสภาพแวดล้อม (.env)

```env
# แอปพลิเคชัน
APP_NAME="SimpleBiz MVC Framework V2"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost

# ฐานข้อมูล
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=simplebiz_mvc
DB_USERNAME=root
DB_PASSWORD=

# API
API_KEY=demo-api-key-12345
```

### ไฟล์การกำหนดค่า

- `config/app.php` - การตั้งค่าแอปพลิเคชัน
- `config/database.php` - การเชื่อมต่อฐานข้อมูล
- `routes/web.php` - เส้นทางเว็บ (การตอบสนอง HTML)
- `routes/api.php` - เส้นทาง API (การตอบสนอง JSON)

---

## 🏗️ สถาปัตยกรรม

### โครงสร้างไดเรกทอรี

```
SimpleBiz-MVC-Framework-V2/
│
├── app/
│   ├── Core/              # คลาสหลักของเฟรมเวิร์ก
│   │   ├── Router.php     # การจัดการเส้นทางคำขอ
│   │   ├── Controller.php # Controller พื้นฐาน
│   │   ├── Database.php   # การเชื่อมต่อฐานข้อมูล
│   │   ├── View.php       # การแสดงผล View
│   │   ├── Middleware.php # Middleware พื้นฐาน
│   │   └── Logger.php     # ระบบบันทึก log
│   │
│   ├── Controllers/       # Controllers ของแอปพลิเคชัน
│   │   ├── HomeController.php
│   │   ├── AuthController.php
│   │   ├── Ecommerce/     # Controllers อีคอมเมิร์ซ
│   │   │   ├── ProductController.php
│   │   │   ├── CartController.php
│   │   │   └── OrderController.php
│   │   └── Api/V1/        # Controllers API
│   │
│   ├── Models/            # ตรรกะทางธุรกิจ
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── Cart.php
│   │   └── Order.php
│   │
│   ├── Middleware/        # ตัวกรองคำขอ
│   │   ├── AuthMiddleware.php
│   │   └── ApiKeyMiddleware.php
│   │
│   └── Helpers/           # คลาสยูทิลิตี้
│       └── Response.php
│
├── config/                # ไฟล์การกำหนดค่า
│   ├── app.php
│   └── database.php
│
├── database/
│   └── migrations/        # ไฟล์ SQL migration
│
├── public/                # Web root สาธารณะ
│   ├── index.php          # จุดเริ่มต้น
│   └── .htaccess          # การกำหนดค่า Apache
│
├── routes/                # คำจำกัดความเส้นทาง
│   ├── web.php
│   └── api.php
│
├── storage/
│   └── logs/              # Application logs
│
├── .env.example           # Template สภาพแวดล้อม
└── README.md
```

### การไหลของ MVC

```
คำขอ → index.php → Router → Middleware → Controller → Model → การตอบสนอง
```

1. **Router** จับคู่ URL กับ controller
2. **Middleware** ตรวจสอบการยืนยันตัวตน/สิทธิ์
3. **Controller** ประสานงานการจัดการคำขอ
4. **Model** ดำเนินการตรรกะทางธุรกิจ
5. **การตอบสนอง** ส่งคืนให้ลูกค้า (HTML หรือ JSON)

---

## 🛒 ฟีเจอร์อีคอมเมิร์ซ (ฐานราก)

### สิ่งที่รวมอยู่

#### การจัดการสินค้า
- แสดงรายการสินค้าที่ใช้งานอยู่
- ดูรายละเอียดสินค้า
- ติดตามสต็อก
- ค้นหาสินค้า (API)

#### ตะกร้าสินค้า
- เพิ่มสินค้าลงตะกร้า
- อัพเดทจำนวน
- ลบสินค้า
- การตรวจสอบสต็อก
- การตรวจสอบความถูกต้องของราคา

#### การประมวลผลคำสั่งซื้อ
- Checkout จากตะกร้า
- การคำนวณราคาใหม่ฝั่งเซิร์ฟเวอร์
- การลดสต็อกแบบ Atomic
- ประวัติคำสั่งซื้อ
- การติดตามสถานะคำสั่งซื้อ

### กฎความปลอดภัย

**สำคัญมาก:** เฟรมเวิร์กนี้บังคับใช้กฎความปลอดภัยที่เข้มงวด:

1. **การคำนวณราคา**
   - ห้ามเชื่อถือราคาที่ส่งมาจากลูกค้า
   - เซิร์ฟเวอร์คำนวณยอดรวมใหม่จากฐานข้อมูล
   - บันทึกความพยายามแก้ไขราคา

2. **การจัดการสต็อก**
   - ตรวจสอบความพร้อมก่อนเพิ่มลงตะกร้า
   - การอัพเดทสต็อกแบบ Atomic ป้องกัน race conditions
   - คืนสต็อกเมื่อยกเลิกคำสั่งซื้อ

3. **การตรวจสอบอินพุต**
   - ทำความสะอาดอินพุตผู้ใช้ทั้งหมด
   - ตรวจสอบ IDs และจำนวนเป็นจำนวนเต็ม
   - ป้องกัน SQL injection ด้วย prepared statements

### สิ่งที่ไม่รวมอยู่

นี่เป็น **ฐานราก** ไม่ใช่ร้านค้าที่สมบูรณ์:

- ❌ การผสานเกตเวย์การชำระเงิน (Stripe, PayPal)
- ❌ การคำนวณค่าจัดส่ง
- ❌ การคำนวณภาษี
- ❌ การแจ้งเตือนทางอีเมล
- ❌ แผงผู้ดูแลระบบ
- ❌ หมวดหมู่สินค้า
- ❌ รูปภาพสินค้า
- ❌ รีวิว/คะแนน

**สิ่งเหล่านี้ไม่รวมโดยเจตนา** เพื่อให้คุณสามารถปรับแต่งตามความต้องการของคุณ

---

## 📡 เอกสาร API

### Base URL

```
http://localhost:8000/api/v1
```

### การยืนยันตัวตน

Endpoints ส่วนใหญ่ต้องการการยืนยันตัวตน:

**แบบ Session:**
```bash
# ล็อกอินก่อนเพื่อสร้าง session
curl -X POST http://localhost:8000/login \
  -d "username=testuser&password=password123"
```

**API Key:**
```bash
# รวมใน header
curl -H "X-API-Key: demo-api-key-12345" \
  http://localhost:8000/api/v1/orders/create
```

### รูปแบบการตอบสนอง

การตอบสนอง API ทั้งหมดเป็นไปตามโครงสร้างนี้:

```json
{
  "success": true,
  "data": {...},
  "message": "ข้อความความสำเร็จ",
  "errors": []
}
```

### Endpoints

#### สินค้า

**GET /api/v1/products**
แสดงรายการสินค้าที่ใช้งานทั้งหมด

```bash
curl http://localhost:8000/api/v1/products
```

การตอบสนอง:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "แล็ปท็อป",
      "price": "999.99",
      "stock": 10,
      "status": "active"
    }
  ],
  "message": "ดึงข้อมูลสินค้าสำเร็จ"
}
```

**GET /api/v1/products/{id}**
ดูรายละเอียดสินค้า

**GET /api/v1/products/search?q=laptop**
ค้นหาสินค้า

#### ตะกร้า

**GET /api/v1/cart** (ต้องยืนยันตัวตน)
ดูเนื้อหาตะกร้า

**POST /api/v1/cart/add** (ต้องยืนยันตัวตน)
เพิ่มสินค้าลงตะกร้า

**PUT /api/v1/cart/update** (ต้องยืนยันตัวตน)
อัพเดทจำนวน

**DELETE /api/v1/cart/remove/{product_id}** (ต้องยืนยันตัวตน)
ลบสินค้า

#### คำสั่งซื้อ

**GET /api/v1/orders** (ต้องยืนยันตัวตน)
แสดงรายการคำสั่งซื้อของผู้ใช้

**POST /api/v1/orders/create** (ต้องยืนยันตัวตน + API Key)
สร้างคำสั่งซื้อจากตะกร้า

---

## 🔒 ความปลอดภัย

### การป้องกันที่มีอยู่

1. **การป้องกัน SQL Injection**
   - คิวรีฐานข้อมูลทั้งหมดใช้ PDO prepared statements
   - พารามิเตอร์ถูกผูกแยกจาก SQL

2. **ความปลอดภัยของรหัสผ่าน**
   - รหัสผ่านแฮชด้วย `password_hash()` (bcrypt)
   - การสร้าง salt อัตโนมัติ
   - ไม่เก็บเป็นข้อความธรรมดา

3. **การป้องกันการแก้ไขราคา**
   - ราคาจากลูกค้าถูกละเลย
   - เซิร์ฟเวอร์คำนวณใหม่จากฐานข้อมูล
   - บันทึกความแตกต่างเป็นเหตุการณ์ความปลอดภัย

4. **การป้องกันการฉ้อโกงสต็อก**
   - การอัพเดทสต็อกแบบ Atomic พร้อม optimistic locking
   - ตรวจสอบความพร้อมก่อน checkout
   - คืนสต็อกเมื่อยกเลิก

5. **การตรวจสอบอินพุต**
   - ทำความสะอาดอินพุตทั้งหมด
   - การตรวจสอบประเภท (integers, floats, strings)
   - SQL injection เป็นไปไม่ได้ด้วย prepared statements

### รายการตรวจสอบความปลอดภัยสำหรับ Production

ก่อนติดตั้ง:

- [ ] ตั้งค่า `APP_ENV=production` ใน `.env`
- [ ] ตั้งค่า `APP_DEBUG=false` ใน `.env`
- [ ] เปลี่ยน API keys เริ่มต้น
- [ ] ใช้ HTTPS (ใบรับรอง SSL)
- [ ] จำกัดสิทธิ์ผู้ใช้ฐานข้อมูล
- [ ] เปิดใช้งาน firewall บนเซิร์ฟเวอร์
- [ ] ตั้งค่าการหมุนเวียน log
- [ ] ตรวจสอบความปลอดภัยเป็นประจำ
- [ ] อัพเดท PHP และ MySQL
- [ ] ใช้การกำหนดค่า session ที่แข็งแกร่ง

---

## 📚 ตัวอย่างการใช้งาน

### สร้างเส้นทางใหม่

**1. กำหนดเส้นทาง** (`routes/web.php`)

```php
$router->get('/about', 'App\Controllers\PageController@about');
```

**2. สร้าง Controller** (`app/Controllers/PageController.php`)

```php
<?php
namespace App\Controllers;

use App\Core\Controller;

class PageController extends Controller
{
    public function about(): void
    {
        echo "<h1>เกี่ยวกับเรา</h1>";
        // หรือใช้ view:
        // $this->view('pages/about', ['title' => 'เกี่ยวกับเรา']);
    }
}
```

### เพิ่มตรรกะทางธุรกิจไปยัง Model

**ตัวอย่าง: เพิ่มส่วนลดสินค้า**

```php
// ใน app/Models/Product.php

public function applyDiscount(int $productId, float $percentage): bool
{
    if ($percentage < 0 || $percentage > 100) {
        return false;
    }
    
    $stmt = $this->db->prepare("
        UPDATE products 
        SET price = price * (1 - ? / 100)
        WHERE id = ?
    ");
    
    $stmt->execute([$percentage, $productId]);
    
    $this->logger->info('product.discount_applied', [
        'product_id' => $productId,
        'discount' => $percentage,
    ]);
    
    return true;
}
```

---

## 🛠️ การพัฒนา

### รันเซิร์ฟเวอร์พัฒนา

```bash
composer serve
# หรือ
php -S localhost:8000 -t public
```

### ดู Logs

```bash
tail -f storage/logs/app.log
```

### ทดสอบ API ด้วย curl

```bash
# ดูสินค้า
curl http://localhost:8000/api/v1/products

# ล็อกอิน
curl -X POST -c cookies.txt \
  -d "username=testuser&password=password123" \
  http://localhost:8000/login

# เพิ่มลงตะกร้า
curl -X POST -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"product_id": 1, "quantity": 1}' \
  http://localhost:8000/api/v1/cart/add
```

---

## 🚀 การติดตั้งใน Production

### การกำหนดค่า Apache

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/simplebiz/public
    
    <Directory /var/www/simplebiz/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/simplebiz_error.log
    CustomLog ${APACHE_LOG_DIR}/simplebiz_access.log combined
</VirtualHost>
```

### การปรับแต่ง

```bash
# ปรับแต่ง Composer autoloader
composer install --optimize-autoloader --no-dev

# ตั้งค่าสิทธิ์ที่เหมาะสม
chmod -R 755 storage
chown -R www-data:www-data storage
```

---

## 📄 ใบอนุญาต

โปรเจกต์นี้ใช้สิทธิ์แบบ MIT License

---

## 🙏 กิตติกรรมประกาศ

สร้างด้วย ❤️ เป็นเฟรมเวิร์กสำหรับสอนเกี่ยวกับ:
- แนวปฏิบัติ PHP สมัยใหม่
- สถาปัตยกรรม MVC
- พื้นฐานอีคอมเมิร์ซ
- การพัฒนา API
- แนวปฏิบัติด้านความปลอดภัย

---

**จำไว้:** นี่เป็นฐานรากสำหรับเริ่มต้นอีคอมเมิร์ซที่ปลอดภัยและขยายได้ — ไม่ใช่ร้านค้าที่สมบูรณ์ ปรับแต่งตามความต้องการของคุณ!

ขอให้เขียนโค้ดสนุก! 🚀
