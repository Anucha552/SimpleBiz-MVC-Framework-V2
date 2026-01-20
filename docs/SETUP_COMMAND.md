# คำสั่ง setup - ตั้งค่าโปรเจคอัตโนมัติ

คู่มือการใช้งานคำสั่ง `php console setup` สำหรับตั้งค่าโปรเจคใหม่อัตโนมัติ

---

## 🎯 คำสั่ง setup คืออะไร?

คำสั่ง `setup` เป็นเครื่องมือ CLI ที่ช่วยตั้งค่าโปรเจคใหม่ให้เสร็จสมบูรณ์โดยอัตโนมัติ แทนที่การแก้ไขไฟล์ทีละไฟล์ด้วยมือ

**ประโยชน์:**
- ⏱️ **ประหยัดเวลา**: จาก 30 นาที เหลือเพียง 3 นาที
- ✅ **ไม่มีข้อผิดพลาด**: ไม่ต้องกังวลว่าจะแก้ไขไฟล์ผิด
- 🔐 **ปลอดภัย**: สร้าง APP_KEY แบบสุ่มอัตโนมัติ
- 📦 **ครบถ้วน**: ติดตั้ง dependencies พร้อมใช้งาน

---

## 🚀 การใช้งาน

### ขั้นตอนที่ 1: Clone Framework

```bash
git clone https://github.com/Anucha552/SimpleBiz-MVC-Framework-V2.git
```

### ขั้นตอนที่ 2: เปลี่ยนชื่อโฟลเดอร์

```bash
mv SimpleBiz-MVC-Framework-V2 my-project
cd my-project
```

### ขั้นตอนที่ 3: รันคำสั่ง setup

```bash
php console setup
```

---

## 💬 คำถามที่คำสั่ง setup จะถาม

คำสั่งจะถามคำถาม 7 ข้อเพื่อตั้งค่าโปรเจค:

### 1. ชื่อโปรเจค (Project Name)
```
ชื่อโปรเจค (เช่น mybookstore, restaurant-ordering): 
```
- ใช้ตัวพิมพ์เล็ก
- ไม่มีช่องว่าง (ใช้ `-` หรือ `_` แทน)
- ตัวอย่าง: `bookstore-ecommerce`, `restaurant-pos`, `my-blog`

### 2. คำอธิบายโปรเจค (Project Description)
```
คำอธิบายโปรเจค: 
```
- คำอธิบายสั้นๆ ว่าโปรเจคนี้คืออะไร
- ตัวอย่าง: `Online Bookstore Platform`, `Restaurant Management System`
- สามารถเว้นว่างได้ (กด Enter ข้ามไป)

### 3. Vendor/Company Name
```
Vendor/Company name (เช่น mycompany): 
```
- ชื่อบริษัทหรือ vendor สำหรับ composer.json
- ใช้ตัวพิมพ์เล็ก ไม่มีช่องว่าง
- ตัวอย่าง: `mycompany`, `acmecorp`, `devstudio`
- Default: `mycompany` (ถ้าเว้นว่าง)

### 4. ชื่อแอปพลิเคชัน (Application Name)
```
ชื่อแอปพลิเคชัน (สำหรับแสดงผล เช่น My Bookstore): 
```
- ชื่อแอปที่จะแสดงบนเว็บไซต์
- สามารถมีช่องว่างและตัวพิมพ์ใหญ่ได้
- ตัวอย่าง: `My Bookstore`, `ABC Restaurant`, `Company CRM`
- Default: แปลงจากชื่อโปรเจคอัตโนมัติ

### 5. ชื่อ Database
```
ชื่อ Database (ถ้าไม่ระบุจะใช้ชื่อโปรเจค): 
```
- ชื่อฐานข้อมูลที่จะใช้
- ใช้ตัวพิมพ์เล็ก ไม่มีช่องว่าง (ใช้ `_` แทน)
- ตัวอย่าง: `bookstore_db`, `restaurant_pos`, `my_blog`
- Default: แปลงจากชื่อโปรเจคอัตโนมัติ (แทนที่ `-` ด้วย `_`)

### 6. Database Username
```
Database Username [root]: 
```
- Username สำหรับเชื่อมต่อ MySQL/MariaDB
- ตัวอย่าง: `root`, `admin`, `dbuser`
- Default: `root` (กด Enter ถ้าใช้ root)

### 7. Database Password
```
Database Password [เว้นว่าง]: 
```
- Password สำหรับเชื่อมต่อฐานข้อมูล
- ถ้าไม่มี password ให้กด Enter ข้ามไป
- Default: เว้นว่าง (no password)

---

## 📋 ตัวอย่างการใช้งานจริง

### ตัวอย่างที่ 1: โปรเจคร้านหนังสือออนไลน์

```bash
$ php console setup

🚀 SimpleBiz Framework Project Setup

ชื่อโปรเจค (เช่น mybookstore, restaurant-ordering): bookstore-ecommerce
คำอธิบายโปรเจค: Online Bookstore E-commerce Platform
Vendor/Company name (เช่น mycompany): abccompany
ชื่อแอปพลิเคชัน (สำหรับแสดงผล เช่น My Bookstore): ABC Bookstore
ชื่อ Database (ถ้าไม่ระบุจะใช้ชื่อโปรเจค): bookstore_db
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
  Database: bookstore_db
  APP_KEY: a1b2c3d4e5f6789012345678901234567

ขั้นตอนถัดไป:
  1. สร้าง Database: CREATE DATABASE bookstore_db;
  2. รัน Migrations: php console migrate
  3. รัน Seeders (optional): php console seed
  4. เริ่มเซิร์ฟเวอร์: php console serve
```

### ตัวอย่างที่ 2: โปรเจค CRM (ไม่ใช้ค่า default)

```bash
$ php console setup

🚀 SimpleBiz Framework Project Setup

ชื่อโปรเจค: company-crm
คำอธิบายโปรเจค: Customer Relationship Management System
Vendor/Company name: techcorp
ชื่อแอปพลิเคชัน: TechCorp CRM
ชื่อ Database: techcorp_crm
Database Username [root]: crm_admin
Database Password [เว้นว่าง]: secure_password_123

...
```

### ตัวอย่างที่ 3: ใช้ค่า default (กด Enter ทุกอย่าง)

```bash
$ php console setup

🚀 SimpleBiz Framework Project Setup

ชื่อโปรเจค: myblog
คำอธิบายโปรเจค: [กด Enter - เว้นว่าง]
Vendor/Company name: [กด Enter - ใช้ mycompany]
ชื่อแอปพลิเคชัน: [กด Enter - ใช้ Myblog อัตโนมัติ]
ชื่อ Database: [กด Enter - ใช้ myblog อัตโนมัติ]
Database Username [root]: [กด Enter - ใช้ root]
Database Password [เว้นว่าง]: [กด Enter - ไม่มี password]

กำลังตั้งค่าโปรเจค...
...
```

---

## 🔧 สิ่งที่คำสั่ง setup ทำให้อัตโนมัติ

### 1. แก้ไข composer.json
```json
// ก่อน
{
    "name": "simplebiz/mvc-framework-v2",
    "description": "SimpleBiz MVC Framework V2",
    ...
}

// หลัง
{
    "name": "abccompany/bookstore-ecommerce",
    "description": "Online Bookstore E-commerce Platform",
    ...
}
```

### 2. สร้างไฟล์ .env จาก .env.example
คำสั่งจะคัดลอก `.env.example` เป็น `.env` และแก้ไขค่าต่อไปนี้:

```env
# ก่อน (.env.example)
APP_NAME="SimpleBiz MVC Framework V2"
APP_KEY=
DB_DATABASE=simplebiz_mvc
DB_USERNAME=root
DB_PASSWORD=

# หลัง (.env)
APP_NAME="ABC Bookstore"
APP_KEY=a1b2c3d4e5f6789012345678901234567
DB_DATABASE=bookstore_db
DB_USERNAME=root
DB_PASSWORD=
```

### 3. สร้าง APP_KEY อัตโนมัติ
- สร้าง random key ความยาว 32 characters
- ใช้ `bin2hex(random_bytes(16))`
- ปลอดภัยและไม่ซ้ำกัน
- ตัวอย่าง: `a1b2c3d4e5f6789012345678901234567`

### 4. ติดตั้ง Composer Dependencies
รันคำสั่ง:
```bash
composer install --quiet
```
ติดตั้ง packages ทั้งหมดที่จำเป็น (PHPUnit, Doctrine, etc.)

### 5. แสดงสรุปและขั้นตอนถัดไป
แสดงข้อมูลที่ตั้งค่าและแนะนำขั้นตอนถัดไป

---

## ✅ ขั้นตอนหลังรันคำสั่ง setup

หลังจากรัน `php console setup` เสร็จแล้ว ให้ทำขั้นตอนต่อไปนี้:

### 1. สร้าง Database

```bash
# เข้า MySQL
mysql -u root -p

# สร้าง Database (ใช้ชื่อที่ระบุในขั้นตอน setup)
CREATE DATABASE bookstore_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# ตรวจสอบ
SHOW DATABASES;

# ออกจาก MySQL
exit;
```

### 2. รัน Migrations

```bash
php console migrate
```

คำสั่งนี้จะสร้างตารางทั้งหมดในฐานข้อมูล

### 3. รัน Seeders (Optional)

```bash
php console seed
```

คำสั่งนี้จะเพิ่มข้อมูลตัวอย่างลงในฐานข้อมูล (ถ้าต้องการ)

### 4. เริ่มเซิร์ฟเวอร์

```bash
php console serve
```

เปิดเบราว์เซอร์ไปที่: `http://localhost:8000`

---

## 🎨 การปรับแต่งเพิ่มเติม

### เปลี่ยน Git Remote (ถ้าต้องการ)

```bash
# ลบ remote เดิม
git remote remove origin

# เพิ่ม remote ใหม่ (repository ของคุณเอง)
git remote add origin https://github.com/yourusername/your-project.git

# หรือเริ่ม Git ใหม่ทั้งหมด
rm -rf .git
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/yourusername/your-project.git
git push -u origin main
```

### เปลี่ยนค่า .env เพิ่มเติม

แก้ไขไฟล์ `.env` ตามต้องการ:

```env
# Environment
APP_ENV=production  # เปลี่ยนเป็น production เมื่อ deploy จริง
APP_DEBUG=false     # ปิด debug mode ใน production

# URL
APP_URL=https://yourdomain.com

# Email
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
```

---

## ❓ FAQ - คำถามที่พบบ่อย

### Q1: ต้องใช้คำสั่ง setup ทุกครั้งไหม?
**A:** ไม่ ใช้แค่ครั้งแรกหลัง clone Framework เท่านั้น

### Q2: ถ้าต้องการเปลี่ยนชื่อโปรเจคภายหลังจะทำยังไง?
**A:** แก้ไขไฟล์ `composer.json` และ `.env` ด้วยมือ

### Q3: รันคำสั่ง setup ซ้ำได้ไหม?
**A:** ได้ แต่จะเขียนทับไฟล์ `.env` และ `composer.json` เดิม ควรสำรองก่อน

### Q4: ถ้าไม่มี MySQL จะใช้คำสั่ง setup ได้ไหม?
**A:** ได้ แต่ต้องแก้ไข `.env` เพื่อเปลี่ยนเป็น SQLite หรือ PostgreSQL ภายหลัง

### Q5: APP_KEY คืออะไร?
**A:** เป็น encryption key สำหรับเข้ารหัสข้อมูล sessions และ cookies

### Q6: ถ้าลืม APP_KEY จะเกิดอะไรขึ้น?
**A:** Sessions ทั้งหมดจะ invalid ผู้ใช้ต้อง login ใหม่ ไม่ควรเปลี่ยน APP_KEY หลังจาก deploy

### Q7: ถ้าไม่ต้องการใช้คำสั่ง setup?
**A:** สามารถตั้งค่าด้วยมือได้ ดูเอกสาร [RENAME_PROJECT.md](RENAME_PROJECT.md) หัวข้อ "วิธีที่ 2: แก้ไขด้วยมือ"

### Q8: คำสั่ง setup ปลอดภัยไหม?
**A:** ปลอดภัย สร้าง APP_KEY แบบสุ่มและไม่ส่งข้อมูลออกนอกเครื่อง

---

## ⚠️ ข้อควรระวัง

### 1. อย่ารันคำสั่ง setup ซ้ำหลังจากเริ่มใช้งาน
หากรันซ้ำจะสร้าง APP_KEY ใหม่ และทำให้ sessions เดิมไม่สามารถใช้ได้

### 2. สำรอง .env ก่อนรันคำสั่ง setup ซ้ำ
```bash
cp .env .env.backup
```

### 3. อย่า commit ไฟล์ .env
ไฟล์ `.env` มีข้อมูลสำคัญ (passwords, keys) อย่า commit ขึ้น Git
ตรวจสอบว่า `.env` อยู่ใน `.gitignore`

### 4. ตรวจสอบ composer.json หลังรัน setup
ตรวจสอบว่าชื่อและคำอธิบายถูกต้อง:
```bash
cat composer.json
```

### 5. เก็บ APP_KEY ไว้ในที่ปลอดภัย
หากสูญหาย APP_KEY จะไม่สามารถ decrypt ข้อมูลเก่าได้

---

## 🔍 การตรวจสอบว่าคำสั่ง setup ทำงานถูกต้อง

### ตรวจสอบไฟล์ที่สร้าง/แก้ไข:

```bash
# 1. ตรวจสอบว่ามีไฟล์ .env
ls -la .env

# 2. ตรวจสอบเนื้อหา .env
cat .env | grep -E "(APP_NAME|APP_KEY|DB_DATABASE)"

# 3. ตรวจสอบ composer.json
cat composer.json | grep -E "(name|description)"

# 4. ตรวจสอบว่า vendor/ ถูกสร้าง
ls -la vendor/
```

### ตัวอย่างผลลัพธ์ที่ถูกต้อง:

```bash
$ cat .env | grep -E "(APP_NAME|APP_KEY|DB_DATABASE)"
APP_NAME="ABC Bookstore"
APP_KEY=a1b2c3d4e5f6789012345678901234567
DB_DATABASE=bookstore_db

$ cat composer.json | grep -E "(name|description)"
    "name": "abccompany/bookstore-ecommerce",
    "description": "Online Bookstore E-commerce Platform",
```

---

## 🚀 สรุป

### ขั้นตอนสั้นๆ:
```bash
git clone https://github.com/Anucha552/SimpleBiz-MVC-Framework-V2.git
mv SimpleBiz-MVC-Framework-V2 my-project
cd my-project
php console setup
# ตอบคำถาม 7 ข้อ
mysql -u root -p
CREATE DATABASE my_database;
exit;
php console migrate
php console serve
```

### ประโยชน์ของคำสั่ง setup:
- ⏱️ ประหยัดเวลา 90%
- ✅ ไม่มีข้อผิดพลาด
- 🔐 APP_KEY ปลอดภัย
- 📦 ติดตั้ง dependencies พร้อมใช้

**คำสั่ง setup ทำให้การเริ่มต้นโปรเจคใหม่เป็นเรื่องง่าย เพียง 3 นาทีก็พร้อมพัฒนา! 🎉**

---

## 📚 เอกสารที่เกี่ยวข้อง

- [QUICK_START.md](QUICK_START.md) - คู่มือเริ่มต้นใช้งานแบบรวดเร็ว
- [RENAME_PROJECT.md](RENAME_PROJECT.md) - การเปลี่ยนชื่อโปรเจค
- [CLI_GUIDE.md](CLI_GUIDE.md) - คู่มือคำสั่ง CLI ทั้งหมด
- [ENVIRONMENTS.md](ENVIRONMENTS.md) - การตั้งค่า Environment Variables
- [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) - การ Deploy Production
