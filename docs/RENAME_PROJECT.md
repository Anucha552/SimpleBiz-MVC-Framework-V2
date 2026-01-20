# เปลี่ยนชื่อโปรเจค Framework

เอกสารนี้อธิบายวิธีการเปลี่ยนชื่อโปรเจคเมื่อ clone Framework มาเพื่อพัฒนาเว็บไซต์ให้กับลูกค้า

---

## 🎯 คำตอบสั้น

**ได้แน่นอน!** Framework นี้ออกแบบมาเพื่อให้เอาไปใช้พัฒนาโปรเจคจริง สามารถเปลี่ยนชื่อโปรเจคได้ตามต้องการ

---

## ⚡ วิธีที่ 1: ใช้คำสั่ง CLI (แนะนำ - เร็วที่สุด!)

Framework มีคำสั่ง `setup` ที่จะทำทุกอย่างอัตโนมัติให้

### ขั้นตอน:

```bash
# 1. Clone framework
git clone https://github.com/Anucha552/SimpleBiz-MVC-Framework-V2.git

# 2. เปลี่ยนชื่อโฟลเดอร์
mv SimpleBiz-MVC-Framework-V2 my-client-project
cd my-client-project

# 3. รันคำสั่ง setup
php console setup
```

### คำสั่ง `setup` จะถามคำถามต่อไปนี้:

1. **ชื่อโปรเจค** (เช่น mybookstore, restaurant-ordering)
2. **คำอธิบายโปรเจค**
3. **Vendor/Company name** (เช่น mycompany)
4. **ชื่อแอปพลิเคชัน** (เช่น My Bookstore)
5. **ชื่อ Database** (ถ้าไม่ระบุจะใช้ชื่อโปรเจค)
6. **Database Username** (default: root)
7. **Database Password**

### คำสั่ง `setup` จะทำอะไรให้อัตโนมัติ:

- ✅ แก้ไข `composer.json` (name, description)
- ✅ สร้างไฟล์ `.env` จาก `.env.example`
- ✅ สร้าง `APP_KEY` อัตโนมัติ (32 characters)
- ✅ แก้ไข `.env` ตามข้อมูลที่ระบุ (APP_NAME, DB_DATABASE, DB_USERNAME, DB_PASSWORD)
- ✅ รัน `composer install`
- ✅ แสดงสรุปข้อมูลโปรเจค
- ✅ แสดงขั้นตอนถัดไปที่ต้องทำ

### ตัวอย่างการใช้งาน:

```bash
$ php console setup

🚀 SimpleBiz Framework Project Setup

ชื่อโปรเจค (เช่น mybookstore, restaurant-ordering): bookstore-ecommerce
คำอธิบายโปรเจค: Online Bookstore Platform for ABC Company
Vendor/Company name (เช่น mycompany): abccompany
ชื่อแอปพลิเคชัน (สำหรับแสดงผล เช่น My Bookstore): ABC Bookstore
ชื่อ Database (ถ้าไม่ระบุจะใช้ชื่อโปรเจค): abc_bookstore
Database Username [root]: root
Database Password [เว้นว่าง]: 

กำลังตั้งค่าโปรเจค...

1. กำลังแก้ไข composer.json...
  ✓ อัปเดต composer.json แล้ว
2. กำลังสร้างไฟล์ .env...
  ✓ สร้างไฟล์ .env แล้ว
3. กำลังสร้าง APP_KEY...
  ✓ สร้าง APP_KEY แล้ว
4. กำลังติดตั้ง Composer dependencies...

✓ ตั้งค่าโปรเจคเสร็จสมบูรณ์!

สรุปข้อมูลโปรเจค:
  ชื่อโปรเจค: bookstore-ecommerce
  Composer name: abccompany/bookstore-ecommerce
  ชื่อแอป: ABC Bookstore
  Database: abc_bookstore
  APP_KEY: abc123def456...

ขั้นตอนถัดไป:
  1. สร้าง Database: CREATE DATABASE abc_bookstore;
  2. รัน Migrations: php console migrate
  3. รัน Seeders (optional): php console seed
  4. เริ่มเซิร์ฟเวอร์: php console serve
```

### เสร็จแล้ว! ใช้เวลาแค่ 2-3 นาที 🚀

---

## 🔧 วิธีที่ 2: แก้ไขด้วยมือ (Manual)

ถ้าต้องการควบคุมทุกอย่างเองหรือไม่สามารถใช้คำสั่ง `setup` ได้

---

## 📋 ขั้นตอนการเปลี่ยนชื่อโปรเจค (Manual)

### ขั้นตอนที่ 1: Clone Framework

```bash
# Clone framework
git clone https://github.com/Anucha552/SimpleBiz-MVC-Framework-V2.git

# เปลี่ยนชื่อโฟลเดอร์ทันทีหลัง clone
mv SimpleBiz-MVC-Framework-V2 nama-client-project
cd nama-client-project
```

**ตัวอย่างชื่อโปรเจค:**
- `bookstore-ecommerce`
- `restaurant-ordering`
- `hotel-booking-system`
- `company-crm`
- `my-blog-platform`

---

### ขั้นตอนที่ 2: แก้ไขไฟล์ composer.json

```bash
# แก้ไขชื่อโปรเจคใน composer.json
```

**ก่อนแก้:**
```json
{
    "name": "simplebiz/mvc-framework",
    "description": "SimpleBiz MVC Framework V2",
    "type": "project",
    ...
}
```

**หลังแก้:**
```json
{
    "name": "yourcompany/client-project-name",
    "description": "Client Project Description",
    "type": "project",
    ...
}
```

**ตัวอย่างจริง:**
```json
{
    "name": "mycompany/bookstore-ecommerce",
    "description": "Online Bookstore E-commerce Platform",
    "type": "project",
    "keywords": ["bookstore", "ecommerce", "online-shop"],
    "homepage": "https://bookstore.example.com",
    ...
}
```

---

### ขั้นตอนที่ 3: แก้ไขไฟล์ .env

```bash
# สร้างไฟล์ .env จาก .env.example
cp .env.example .env

# แก้ไขค่าต่างๆ ให้เหมาะสมกับโปรเจค
```

**ค่าที่ต้องแก้:**
```env
# ชื่อแอปพลิเคชัน
APP_NAME="Client Project Name"
APP_URL=http://localhost

# Database - ตั้งชื่อใหม่ตามโปรเจค
DB_DATABASE=client_project_db
DB_USERNAME=your_username
DB_PASSWORD=your_password

# สร้าง APP_KEY ใหม่
APP_KEY=your-unique-32-character-key
```

**ตัวอย่างจริง:**
```env
APP_NAME="ABC Bookstore"
APP_URL=https://bookstore.abccompany.com

DB_DATABASE=abc_bookstore
DB_USERNAME=bookstore_user
DB_PASSWORD=secure_password_here

APP_KEY=abcd1234efgh5678ijkl9012mnop3456
```

---

### ขั้นตอนที่ 4: แก้ไขไฟล์ README.md

แก้ไขไฟล์ `README.md` ให้เหมาะสมกับโปรเจคของคุณ

**ตัวเลือก 1: แก้ไขทั้งหมด**
```markdown
# Client Project Name

Description of your client project

## Features
- Feature 1
- Feature 2

## Installation
...
```

**ตัวเลือก 2: เก็บข้อมูล Framework ไว้**
```markdown
# Client Project Name

Description of your client project

Built with SimpleBiz MVC Framework V2

## Features
- Your custom features
...
```

**แนะนำ:** เก็บข้อมูลว่าใช้ SimpleBiz Framework เพื่อความชัดเจนในการ maintenance

---

### ขั้นตอนที่ 5: ตั้งค่า Git ใหม่

```bash
# ลบ Git remote เดิม (optional)
git remote remove origin

# เพิ่ม Git remote ใหม่ (repository ของลูกค้า)
git remote add origin https://github.com/client/client-project.git

# หรือ
git remote add origin https://gitlab.com/company/client-project.git

# ตรวจสอบ
git remote -v

# Push ไปยัง repository ใหม่
git push -u origin main
```

**หรือเริ่มต้น Git ใหม่ทั้งหมด:**
```bash
# ลบ .git เดิม
rm -rf .git

# สร้าง Git repository ใหม่
git init
git add .
git commit -m "Initial commit: Client Project"
git branch -M main
git remote add origin https://github.com/client/client-project.git
git push -u origin main
```

---

### ขั้นตอนที่ 6: ติดตั้ง Dependencies

```bash
# ติดตั้ง Composer dependencies
composer install

# อัปเดต autoload
composer dump-autoload
```

---

### ขั้นตอนที่ 7: สร้าง Database

```bash
# สร้าง Database ใหม่
mysql -u root -p
CREATE DATABASE client_project_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# รัน Migrations
php console migrate
```

---

### ขั้นตอนที่ 8: ทดสอบระบบ

```bash
# รันเซิร์ฟเวอร์ทดสอบ
php console serve

# หรือ
php -S localhost:8000 -t public
```

เปิดเบราว์เซอร์ไปที่: `http://localhost:8000`

---

## 📝 Checklist: สิ่งที่ต้องเปลี่ยน

### ✅ ไฟล์ที่ต้องแก้ไข

- [ ] **ชื่อโฟลเดอร์** - เปลี่ยนจาก `SimpleBiz-MVC-Framework-V2` เป็นชื่อโปรเจค
- [ ] **composer.json** - แก้ `name`, `description`, `keywords`, `homepage`
- [ ] **.env** - แก้ `APP_NAME`, `APP_URL`, `DB_DATABASE`, `APP_KEY`
- [ ] **README.md** - เขียนเอกสารใหม่เฉพาะโปรเจค
- [ ] **Git remote** - เปลี่ยนเป็น repository ของลูกค้า
- [ ] **config/app.php** - อาจต้องแก้ค่า config เพิ่มเติม

### ✅ ไฟล์ที่ไม่ต้องแก้

- ✅ **app/Core/** - ใช้ได้เลย ไม่ต้องแก้
- ✅ **app/Helpers/** - ใช้ได้เลย
- ✅ **app/Middleware/** - ใช้ได้เลย
- ✅ **routes/** - อาจต้องแก้ routes ตามโปรเจค
- ✅ **public/index.php** - ไม่ต้องแก้
- ✅ **migrate.php** - ไม่ต้องแก้
- ✅ **console.php** - ไม่ต้องแก้

---

## 🗂️ การจัดการ Controllers, Models, Views

### ลบ/แก้ไข Examples

ถ้าโปรเจคของคุณไม่ใช่อีคอมเมิร์ซ ให้ลบหรือแก้ไข:

```bash
# ลบ Controllers ตัวอย่าง (ถ้าไม่ต้องการ)
rm -rf app/Controllers/Ecommerce

# หรือเก็บไว้เป็นตัวอย่างการเขียนโค้ด
mkdir examples
mv app/Controllers/Ecommerce examples/
```

### สร้าง Controllers ใหม่

```bash
# สร้าง Controller สำหรับโปรเจคของคุณ
php console make:controller ClientFeatureController
php console make:controller ClientApiController

# สร้าง Model
php console make:model ClientModel
```

---

## 🎨 การปรับแต่ง Branding

### เปลี่ยน Title และ Meta Tags

แก้ไขใน `app/Views/layouts/main.php`:

```php
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?? 'Your Client Brand Name' ?></title>
    <meta name="description" content="Client project description">
    <meta name="keywords" content="client, keywords">
    ...
</head>
```

### เปลี่ยน Logo และสี

แก้ไขใน `public/assets/css/`:
```css
/* แก้สีหลักของโปรเจค */
:root {
    --primary-color: #YOUR_COLOR;
    --secondary-color: #YOUR_COLOR;
}
```

---

## 🔐 ความปลอดภัย

### สร้าง APP_KEY ใหม่

**สำคัญมาก!** ต้องสร้าง APP_KEY ใหม่สำหรับทุกโปรเจค

```bash
# วิธีที่ 1: ใช้ openssl
openssl rand -hex 16

# วิธีที่ 2: ใช้ PHP
php -r "echo bin2hex(random_bytes(16)) . PHP_EOL;"

# วิธีที่ 3: ออนไลน์
# https://generate-random.org/api-key-generator
```

คัดลอกค่าที่ได้ไปใส่ใน `.env`:
```env
APP_KEY=abc123def456ghi789jkl012mno345pq
```

### เปลี่ยน Secret Keys อื่นๆ

```env
# Database password ใหม่
DB_PASSWORD=secure_password_for_this_project

# Session name เฉพาะโปรเจค
SESSION_NAME=client_project_session

# CSRF Token key
CSRF_TOKEN_NAME=client_csrf_token
```

---

## 📦 การจัดการ Dependencies

### เพิ่ม Package เฉพาะโปรเจค

```bash
# ติดตั้ง package ที่ต้องการ
composer require vendor/package

# ตัวอย่าง: Payment Gateway
composer require stripe/stripe-php
composer require omnipay/omnipay

# ตัวอย่าง: Image Processing
composer require intervention/image

# ตัวอย่าง: PDF Generation
composer require dompdf/dompdf
```

### อัปเดต composer.json

```json
{
    "require": {
        "php": ">=8.0",
        "your/client-specific-package": "^1.0"
    }
}
```

---

## 🚀 การ Deploy

### ตั้งค่าสำหรับ Production

1. **แก้ไข .env สำหรับ production:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://client-project.com

DB_HOST=production-db-server
DB_DATABASE=production_db
```

2. **Optimize autoload:**
```bash
composer install --no-dev --optimize-autoloader
```

3. **ตั้งค่า permissions:**
```bash
chmod -R 755 storage
chmod -R 755 public/assets
```

---

## 📋 ตัวอย่างการเปลี่ยนชื่อโปรเจคจริง

### ตัวอย่างที่ 1: Bookstore E-commerce

```bash
# 1. Clone และเปลี่ยนชื่อ
git clone https://github.com/Anucha552/SimpleBiz-MVC-Framework-V2.git
mv SimpleBiz-MVC-Framework-V2 abc-bookstore
cd abc-bookstore

# 2. แก้ composer.json
# name: "abccompany/bookstore-ecommerce"
# description: "Online Bookstore Platform for ABC Company"

# 3. แก้ .env
# APP_NAME="ABC Bookstore"
# DB_DATABASE=abc_bookstore

# 4. ติดตั้ง
composer install
php console migrate

# 5. ลบ feature ที่ไม่ใช้
# เก็บ Product, Cart, Order (เหมาะกับร้านหนังสือ)
# อาจไม่ต้องใช้ Review ถ้าไม่มีระบบรีวิว

# 6. สร้าง Models เพิ่ม
php console make:model Book
php console make:model Author
php console make:model Publisher

# 7. Git ใหม่
git remote remove origin
git remote add origin https://github.com/abccompany/bookstore.git
git push -u origin main
```

---

### ตัวอย่างที่ 2: Restaurant Ordering System

```bash
# 1. Clone และเปลี่ยนชื่อ
git clone https://github.com/Anucha552/SimpleBiz-MVC-Framework-V2.git
mv SimpleBiz-MVC-Framework-V2 xyz-restaurant
cd xyz-restaurant

# 2. แก้ composer.json
# name: "xyzrestaurant/ordering-system"

# 3. แก้ .env
# APP_NAME="XYZ Restaurant"
# DB_DATABASE=xyz_restaurant

# 4. ปรับ Models
# Product → Menu
# Category → MenuCategory
# Cart → Order Cart
# เก็บ Order

# 5. สร้าง Models เพิ่ม
php console make:model Table
php console make:model Reservation
php console make:model Kitchen

# 6. ลบที่ไม่ใช้
rm -rf app/Controllers/Ecommerce/ProductController.php
# สร้างใหม่
php console make:controller MenuController
php console make:controller ReservationController
```

---

### ตัวอย่างที่ 3: CRM System (ไม่ใช่ E-commerce)

```bash
# 1. Clone และเปลี่ยนชื่อ
git clone https://github.com/Anucha552/SimpleBiz-MVC-Framework-V2.git
mv SimpleBiz-MVC-Framework-V2 company-crm
cd company-crm

# 2. แก้ .env
# APP_NAME="Company CRM"
# DB_DATABASE=company_crm

# 3. ลบ E-commerce features ทั้งหมด
rm -rf app/Controllers/Ecommerce
rm -rf app/Models/Product.php
rm -rf app/Models/Cart.php
rm -rf app/Models/Order.php
rm -rf app/Models/Category.php
rm -rf app/Views/products
rm -rf app/Views/cart
rm -rf app/Views/orders

# 4. ลบ Migrations ที่ไม่ใช้
rm database/migrations/ecommerce/*

# 5. เก็บเฉพาะส่วนที่ใช้
# - User Management ✅
# - Role/Permission ✅
# - Notification ✅
# - Activity Log ✅

# 6. สร้าง Models ใหม่
php console make:model Customer
php console make:model Lead
php console make:model Deal
php console make:model Task
php console make:model Contact
```

---

## 🔄 การอัปเดต Framework

ถ้า SimpleBiz Framework มีเวอร์ชันใหม่:

### วิธีที่ 1: Manual Update

```bash
# เพิ่ม upstream remote
git remote add upstream https://github.com/Anucha552/SimpleBiz-MVC-Framework-V2.git

# Fetch updates
git fetch upstream

# Merge เฉพาะส่วนที่ต้องการ (ระวัง conflict)
git cherry-pick <commit-hash>
```

### วิธีที่ 2: แยก Core ออกเป็น Package (แนะนำสำหรับโปรเจคใหญ่)

สร้าง `composer.json` เพื่อดึง Core เป็น dependency:
```json
{
    "require": {
        "simplebiz/core": "^2.0"
    }
}
```

---

## ⚠️ ข้อควรระวัง

### 1. **อย่าเปลี่ยน Namespace**
ถ้าไม่จำเป็น อย่าเปลี่ยน `namespace App\...` เพราะจะต้องแก้ทุกไฟล์

### 2. **สำรองก่อนแก้**
```bash
# สำรองก่อนแก้ไขครั้งแรก
cp -r SimpleBiz-MVC-Framework-V2 SimpleBiz-MVC-Framework-V2-backup
```

### 3. **ทดสอบก่อน Deploy**
```bash
# รัน tests
./vendor/bin/phpunit

# ตรวจสอบ syntax errors
find app -name "*.php" -exec php -l {} \;
```

### 4. **เก็บ License**
เก็บไฟล์ `LICENSE` ของ SimpleBiz Framework ไว้ (ถ้ามี)

### 5. **เอกสาร**
เก็บเอกสารใน `docs/` ไว้เป็น reference

---

## 📚 ไฟล์สำคัญที่ควรเก็บไว้

### เก็บไว้แน่นอน:
- ✅ `app/Core/` - Core framework (อย่าลบ!)
- ✅ `app/Helpers/` - Helper functions
- ✅ `app/Middleware/` - Middleware classes
- ✅ `config/` - Configuration files
- ✅ `docs/` - เอกสารอ้างอิง
- ✅ `public/index.php` - Entry point
- ✅ `routes/` - Router files
- ✅ `console.php` - CLI tool
- ✅ `migrate.php` - Migration tool

### พิจารณาเก็บหรือลบ:
- ⚠️ `app/Controllers/` - ดูว่าต้องการใช้ตัวอย่างไหม
- ⚠️ `app/Models/` - ดูว่าต้องการใช้ตัวอย่างไหม
- ⚠️ `app/Views/` - ดูว่าต้องการใช้ตัวอย่างไหม
- ⚠️ `database/migrations/ecommerce/` - ถ้าไม่ทำอีคอมเมิร์ซให้ลบ

### อาจลบได้:
- 🗑️ `docs/` - ถ้าไม่ต้องการเอกสาร (แต่แนะนำให้เก็บ)
- 🗑️ `README.md` เดิม - เขียนใหม่เฉพาะโปรเจค
- 🗑️ `CHANGELOG.md` - ไม่เกี่ยวกับโปรเจคลูกค้า

---

## ✅ สรุป

### คำตอบ: **ได้แน่นอน!**

SimpleBiz MVC Framework V2 ออกแบบมาเพื่อให้:
1. ✅ **เปลี่ยนชื่อโปรเจคได้**
2. ✅ **ปรับแต่งได้ตามต้องการ**
3. ✅ **ลบ/เพิ่ม features ได้**
4. ✅ **ใช้กับหลายโปรเจคพร้อมกันได้**
5. ✅ **เปลี่ยน Git repository ได้**

---

## ⚡ Quick Start (แนะนำ)

### วิธีที่เร็วที่สุด - ใช้คำสั่ง `setup`:

```bash
# 1. Clone และเปลี่ยนชื่อโฟลเดอร์
git clone https://github.com/Anucha552/SimpleBiz-MVC-Framework-V2.git
mv SimpleBiz-MVC-Framework-V2 client-project
cd client-project

# 2. รันคำสั่ง setup (ทำทุกอย่างอัตโนมัติ!)
php console setup

# 3. สร้าง Database
mysql -u root -p
CREATE DATABASE your_database_name;
exit;

# 4. รัน Migrations
php console migrate

# 5. เริ่มพัฒนา!
php console serve
```

**ใช้เวลาแค่ 2-3 นาที! เสร็จแล้ว! 🎉**

---

### ขั้นตอนสั้นๆ (Manual):

```bash
# 1. Clone
git clone https://github.com/Anucha552/SimpleBiz-MVC-Framework-V2.git
mv SimpleBiz-MVC-Framework-V2 client-project

# 2. แก้ไฟล์สำคัญ
# - composer.json
# - .env
# - README.md

# 3. ติดตั้ง
cd client-project
composer install
php console migrate

# 4. เปลี่ยน Git
git remote remove origin
git remote add origin <client-repo-url>
git push -u origin main
```

**เสร็จแล้ว! พร้อมพัฒนาโปรเจคให้ลูกค้า 🚀**

---

## 📞 คำถามที่พบบ่อย

### Q: ต้องเปลี่ยน namespace ไหม?
A: ไม่แนะนำ เพราะจะต้องแก้ทุกไฟล์ ใช้ `namespace App\...` ต่อได้เลย

### Q: ต้องเก็บชื่อ SimpleBiz ไว้ไหม?
A: ไม่จำเป็น แต่ควรระบุใน README หรือ composer.json ว่าใช้ SimpleBiz Framework

### Q: สามารถใช้กับหลายโปรเจคได้ไหม?
A: ได้! Clone ใหม่ทุกครั้งสำหรับแต่ละโปรเจค

### Q: ต้อง credit SimpleBiz ไหม?
A: ตรวจสอบไฟล์ LICENSE (ถ้ามี) แต่แนะนำให้ระบุเพื่อความชัดเจน

### Q: อัปเดต Framework ยังไง?
A: ดูหัวข้อ "การอัปเดต Framework" ด้านบน

---

## 📖 เอกสารที่เกี่ยวข้อง

- [Framework vs Examples](FRAMEWORK_VS_EXAMPLES.md) - แยกส่วนระบบและตัวอย่าง
- [Use Cases](USE_CASES.md) - Framework ทำเว็บอะไรได้บ้าง
- [Project Structure](PROJECT_STRUCTURE.md) - โครงสร้างโปรเจค
- [Deployment Guide](DEPLOYMENT_GUIDE.md) - การ Deploy Production

---

**สรุป:** เปลี่ยนชื่อโปรเจคได้เลย! Framework นี้สร้างมาเพื่อให้เอาไปใช้พัฒนาโปรเจคจริงๆ 🎉
