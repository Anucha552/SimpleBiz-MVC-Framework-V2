# SimpleBiz MVC Framework V2

**เฟรมเวิร์ก MVC ขนาดเล็ก-กลาง สำหรับพัฒนาเว็บแอปและ API แบบปลอดภัย และขยายได้ง่าย**

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 🎯 ภาพรวม (สรุปปัจจุบัน)

SimpleBiz MVC Framework V2 เป็นเฟรมเวิร์ก PHP แบบเรียบง่ายที่ให้โครงสร้าง MVC, routing, middleware และบริการหลัก (Core services) เพื่อเร่งการพัฒนาแอปเว็บหรือ API ขนาดเล็กถึงกลาง

จุดเด่นสั้น ๆ:
- โครงสร้าง MVC และ PSR-4 autoloading
- Router ที่รองรับการกำหนด middleware ต่อ route
- Core services เช่น `Database` (PDO), `Session`, `Logger`, `Cache`, `Mail`, `FileUpload`, `View`
- ระบบโมดูล (`modules/`) เพื่อแยกฟีเจอร์เสริมเป็นหน่วย
- คำสั่ง CLI ช่วยงานพัฒนา (migrate, seed, make:*, serve, test)

หมายเหตุ: เอกสารและตัวอย่างฟีเจอร์บางส่วนใน README แบบเดิม (เช่น catalog/cart/orders) ถูกย้ายออกจาก README และถูกยืนยันอยู่ใน `docs/` แทน — README นี้เป็นสรุปและชี้ไปยังเอกสารเชิงลึก

---

## ✅ ใช้เป็นฐานโปรเจกต์
ถ้าจะใช้เป็น “base” สำหรับงาน ให้เริ่มจากเช็คลิสต์ขั้นต่ำที่ [docs/guides/BASELINE_CHECKLIST.md](docs/guides/BASELINE_CHECKLIST.md)

ถ้าต้องการแยกฟีเจอร์เป็นโมดูล ดู [docs/modules/MODULES_GUIDE.md](docs/modules/MODULES_GUIDE.md)

---

## 📋 โครงสร้างโปรเจกต์ (ย่อ)

```
app/           # Controllers, Core classes, Helpers, Middleware, Models
config/        # การตั้งค่า (app, database, modules)
modules/       # โมดูลเสริม (Auth, HelloWorld, ...)
public/        # เว็บรูท (index.php)
routes/        # web.php, api.php
database/      # migrations, seeders
docs/          # เอกสารแยกตามหมวดหมู่ (overview, guides, modules, reference, security)
storage/       # cache, logs, views
vendor/        # Composer dependencies
```

---

## 🚀 เริ่มใช้งาน (ย่อ)

1. Clone และติดตั้ง dependencies

```bash
git clone https://github.com/Anucha552/SimpleBiz-MVC-Framework-V2.git
cd SimpleBiz-MVC-Framework-V2
composer install
```

2. คัดลอก `.env` และปรับค่า

```bash
cp .env.example .env
# แก้ค่า DB, APP_ENV, APP_DEBUG เป็นต้น
```

3. สร้าง `APP_KEY` หรือรัน `php console setup`

```bash
php -r "echo 'APP_KEY=' . bin2hex(random_bytes(16)) . PHP_EOL;" >> .env
php console setup   # (optional) interactive setup
```

4. สร้างฐานข้อมูลและรัน migrations

```bash
mysql -u root -p -e "CREATE DATABASE my_project CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php console migrate
```

5. รันเซิร์ฟเวอร์พัฒนา

```bash
php console serve
# เข้า: http://localhost:8000
```

6. รันชุดทดสอบ (ถ้ามี)

```bash
./vendor/bin/phpunit --colors=always
```

---

## 🛠️ คำสั่ง CLI ที่มี (ย่อ)

```bash
php console serve          # รันเซิร์ฟเวอร์
php console migrate        # รัน migrations
php console seed           # รัน seeders
php console make:controller Name
php console make:model Name
php console make:middleware Name
php console cache:clear
php console test
```

---

## 📖 เอกสารเพิ่มเติม (ดูในโฟลเดอร์ `docs/`)

- Overview: `docs/overview/PROJECT_STRUCTURE.md`, `docs/overview/FRAMEWORK_CAPABILITIES.md`
- Guides: `docs/guides/` (CLI, Helpers, Services, Middleware, Baseline checklist)
- Modules: `docs/modules/` (Auth, HelloWorld)
- Reference: `docs/reference/API_REFERENCE.md`, testing guides
- Security & Deployment: `docs/security/SECURITY_HARDENING.md`, `docs/DEPLOYMENT_GUIDE.md`

---

## 🧪 Testing

รันโดย:

```bash
php console test
```

หรือ

```bash
./vendor/bin/phpunit
```

---

## 🤝 Contributing

Fork, ปรับแก้ และส่ง PR ได้ตามปกติ — ดู `CONTRIBUTING.md` (ถ้ามี)

---

## 📄 ใบอนุญาต

MIT License

---

ต้องการให้ผม: 1) เพิ่มตารางการอ้างอิงคำสั่ง `console` ที่สกัดจากไฟล์ `console` หรือ 2) รันการตรวจสอบลิงก์ในทุก `docs/` ตอนนี้เลย? 
- การจัดการภาพสินค้า

**ใช้ในสภาพแวดล้อมจริงด้วยความระมัดระวัง และปรับปรุงตามความต้องการด้านความปลอดภัย**

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
# ดูไฟล์ล็อกของวันนี้ (แนะนำ)
tail -f storage/logs/$(date +%Y-%m-%d).log

# หรือถ้าต้องการดูไฟล์ทั่วไป (fallback)
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
