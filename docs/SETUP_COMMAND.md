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

คำสั่งจะถามคำถามเพื่อตั้งค่าโปรเจค แบ่งเป็น 2 ส่วน:

### ส่วนที่ 1: ข้อมูลโปรเจค (7 คำถาม)

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

### ส่วนที่ 2: การจัดการ Git Repository (3 คำถาม - Optional)

### 8. ต้องการจัดการ Git repository หรือไม่?
```
ต้องการจัดการ Git repository หรือไม่? (y/n) [n]:
```
- กด `y` หรือ `yes` ถ้าต้องการให้ระบบจัดการ Git ให้
- กด `n` หรือ `no` หรือ Enter เพื่อข้าม
- Default: `n` (ไม่จัดการ Git)

**ถ้าตอบ `y` จะถามคำถามต่อ:**

### 9. เลือกวิธีการจัดการ Git
```
เลือกวิธีการ:
  1. เปลี่ยน remote URL (เก็บประวัติ commits เดิม)
  2. เริ่ม Git ใหม่ทั้งหมด (ลบประวัติเก่า)
เลือก (1/2) [1]:
```
- **ตัวเลือก 1** (Default): เปลี่ยนแค่ remote URL เก็บประวัติ commits เดิมไว้
  - ใช้สำหรับ: Fork โปรเจค หรือต้องการเก็บประวัติการพัฒนา
  - รัน: `git remote remove origin` และ `git remote add origin <URL>`
  
- **ตัวเลือก 2**: ลบ `.git` และเริ่มใหม่ทั้งหมด
  - ใช้สำหรับ: เริ่มโปรเจคใหม่ ไม่ต้องการประวัติเก่า
  - รัน: ลบโฟลเดอร์ `.git`, `git init`, และตั้ง remote ใหม่

### 10. URL ของ GitHub Repository
```
GitHub Repository URL (เช่น https://github.com/yourusername/yourrepo.git):
```
- ใส่ URL ของ repository ที่สร้างไว้บน GitHub แล้ว
- ตัวอย่าง: `https://github.com/abccompany/bookstore-ecommerce.git`
- **สำคัญ**: ต้องสร้าง repository บน GitHub ก่อน

### 11. Commit และ Push เลยไหม? (ถ้าเลือกจัดการ Git)
```
ต้องการ commit และ push การเปลี่ยนแปลงหรือไม่? (y/n) [y]:
```
- กด `y` หรือ `yes` หรือ Enter เพื่อ commit และ push ทันที
- กด `n` หรือ `no` เพื่อข้าม (จะไม่ commit และ push)
- Default: `y` (commit และ push)

**ถ้าตอบ `y` ระบบจะ:**
- `git add .`
- `git commit -m "Initial setup for {projectName}"`
- `git push -u origin main`

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

ต้องการจัดการ Git repository หรือไม่? (y/n) [n]: y
เลือกวิธีการ:
  1. เปลี่ยน remote URL (เก็บประวัติ commits เดิม)
  2. เริ่ม Git ใหม่ทั้งหมด (ลบประวัติเก่า)
เลือก (1/2) [1]: 1
GitHub Repository URL: https://github.com/abccompany/bookstore-ecommerce.git

กำลังตั้งค่าโปรเจค...

1. กำลังแก้ไข composer.json...
  ✓ อัปเดต composer.json แล้ว
2. กำลังสร้างไฟล์ .env...
  ✓ สร้างไฟล์ .env แล้ว
3. กำลังสร้าง APP_KEY...
  ✓ สร้าง APP_KEY แล้ว
4. กำลังอัปเดต README.md...
  ✓ อัปเดต README.md แล้ว
5. กำลังตรวจสอบ .gitignore...
  ✓ .gitignore พร้อมใช้งาน
6. กำลังจัดการ Git repository...
  ✓ เปลี่ยน remote URL เป็น https://github.com/abccompany/bookstore-ecommerce.git
7. กำลังติดตั้ง Composer dependencies...

ต้องการ commit และ push การเปลี่ยนแปลงหรือไม่? (y/n) [y]: y
8. กำลัง commit และ push...
  ✓ Commit และ push เรียบร้อย

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

### ตัวอย่างที่ 2: โปรเจค CRM พร้อมจัดการ Git แบบเริ่มใหม่

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

ต้องการจัดการ Git repository หรือไม่? (y/n) [n]: y
เลือกวิธีการ:
  1. เปลี่ยน remote URL (เก็บประวัติ commits เดิม)
  2. เริ่ม Git ใหม่ทั้งหมด (ลบประวัติเก่า)
เลือก (1/2) [1]: 2
GitHub Repository URL: https://github.com/techcorp/company-crm.git

กำลังตั้งค่าโปรเจค...
...
6. กำลังจัดการ Git repository...
  ✓ เริ่มต้น Git repository ใหม่
  ✓ เพิ่ม remote: https://github.com/techcorp/company-crm.git
...
```

### ตัวอย่างที่ 3: ใช้ค่า default และไม่จัดการ Git

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

ต้องการจัดการ Git repository หรือไม่? (y/n) [n]: [กด Enter - ข้าม]

กำลังตั้งค่าโปรเจค...
...
```

---

## 🔧 สิ่งที่คำสั่ง setup ทำให้อัตโนมัติ

คำสั่ง setup จะทำงานตามลำดับดังนี้:

### 1. แก้ไข composer.json
แก้ไขชื่อโปรเจคและคำอธิบายใน `composer.json`:

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
คัดลอก `.env.example` เป็น `.env` และแก้ไขค่าต่อไปนี้:

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

### 4. อัปเดต README.md
แก้ไขไฟล์ `README.md` ให้ตรงกับชื่อโปรเจคใหม่:
- แก้ชื่อโปรเจคในหัวข้อ
- แก้คำอธิบาย (ถ้ามี)
- แก้ชื่อ database ในตัวอย่าง

### 5. ตรวจสอบและอัปเดต .gitignore
ตรวจสอบว่าไฟล์ `.gitignore` มีเนื้อหาที่จำเป็น:
```
.env
vendor/
storage/logs/*.log
storage/cache/*
!storage/logs/.gitkeep
!storage/cache/.gitkeep
```

### 6. จัดการ Git Repository (ถ้าเลือก)

**ตัวเลือก 1: เปลี่ยน remote URL**
```bash
git remote remove origin
git remote add origin <URL>
```
- เก็บประวัติ commits เดิม
- เหมาะสำหรับ Fork

**ตัวเลือก 2: เริ่ม Git ใหม่**
```bash
rm -rf .git
git init
git add .
git commit -m "Initial setup for {projectName}"
git branch -M main
git remote add origin <URL>
```
- ลบประวัติเก่าทั้งหมด
- เริ่มต้นใหม่

### 7. ติดตั้ง Composer Dependencies
รันคำสั่ง:
```bash
composer install --quiet
```
ติดตั้ง packages ทั้งหมดที่จำเป็น จาก `composer.json`:
- PHPUnit (testing framework)
- Doctrine DBAL (database abstraction)
- และอื่นๆ

สร้างโฟลเดอร์ `vendor/` และไฟล์ `vendor/autoload.php`

### 8. Commit และ Push (ถ้าเลือก)
ถ้าเลือกให้ commit และ push:
```bash
git add .
git commit -m "Initial setup for {projectName}"
git push -u origin main
```

### 9. แสดงสรุปและขั้นตอนถัดไป
แสดงข้อมูลโปรเจคที่ตั้งค่าและแนะนำขั้นตอนถัดไป

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

### จัดการ Git ด้วยตัวเอง (ถ้าข้ามในขั้นตอน setup)

ถ้าไม่ได้เลือกจัดการ Git ตอน setup สามารถทำเองภายหลังได้:

**วิธีที่ 1: เปลี่ยน Remote URL**
```bash
# ลบ remote เดิม
git remote remove origin

# เพิ่ม remote ใหม่ (repository ของคุณเอง)
git remote add origin https://github.com/yourusername/your-project.git

# Push
git push -u origin main
```

**วิธีที่ 2: เริ่ม Git ใหม่ทั้งหมด**

**สำหรับ Windows:**
```powershell
Remove-Item -Recurse -Force .git
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/yourusername/your-project.git
git push -u origin main
```

**สำหรับ Linux/Mac:**
```bash
rm -rf .git
git init
git add .
git commit -m "Initial commit"
git branch -M main
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

### Q9: ต้องสร้าง GitHub repository ก่อนหรือไม่?
**A:** ถ้าเลือกจัดการ Git และต้องการ push ต้องสร้าง repository บน GitHub ก่อน ถ้าไม่ push ทันทีไม่จำเป็นต้องสร้างก่อน

### Q10: ควรเลือก "เปลี่ยน remote" หรือ "เริ่มใหม่"?
**A:** 
- **เปลี่ยน remote**: เหมาะสำหรับ Fork โปรเจค หรือต้องการเก็บประวัติการพัฒนา framework
- **เริ่มใหม่**: เหมาะสำหรับโปรเจคใหม่ทั้งหมด ไม่ต้องการประวัติเก่า (แนะนำสำหรับโปรเจคส่วนตัว)

### Q11: Git push ล้มเหลว ทำไง?
**A:** ตรวจสอบว่า:
1. สร้าง repository บน GitHub แล้ว
2. มีสิทธิ์ push (ใช้ SSH key หรือ Personal Access Token)
3. Branch ชื่อถูกต้อง (main หรือ master)

---

## ⚠️ ข้อควรระวัง

### 1. สร้าง GitHub Repository ก่อน (ถ้าต้องการ push)
ถ้าเลือกให้ระบบจัดการ Git และต้องการ push ทันที:
```bash
# สร้าง repository บน GitHub ก่อน
# ตั้งชื่อให้ตรงกับชื่อโปรเจค
# เลือก Public หรือ Private
# **อย่าเลือก** "Initialize with README" (เพราะมีของเก่าอยู่แล้ว)
```

### 2. อย่ารันคำสั่ง setup ซ้ำหลังจากเริ่มใช้งาน
หากรันซ้ำจะสร้าง APP_KEY ใหม่ และทำให้ sessions เดิมไม่สามารถใช้ได้

### 3. สำรอง .env ก่อนรันคำสั่ง setup ซ้ำ
```bash
cp .env .env.backup
```

### 4. อย่า commit ไฟล์ .env
ไฟล์ `.env` มีข้อมูลสำคัญ (passwords, keys) อย่า commit ขึ้น Git
ตรวจสอบว่า `.env` อยู่ใน `.gitignore`

### 5. ตรวจสอบ composer.json หลังรัน setup
ตรวจสอบว่าชื่อและคำอธิบายถูกต้อง:
```bash
cat composer.json
```

### 6. เก็บ APP_KEY ไว้ในที่ปลอดภัย
หากสูญหาย APP_KEY จะไม่สามารถ decrypt ข้อมูลเก่าได้

### 7. Git Mode "เริ่มใหม่" จะลบประวัติทั้งหมด
ถ้าเลือกตัวเลือก 2 (เริ่ม Git ใหม่) จะลบโฟลเดอร์ `.git` ทั้งหมด
ประวัติ commits เดิมจะหายไป **ไม่สามารถกู้คืนได้**

---

## 🔍 การตรวจสอบว่าคำสั่ง setup ทำงานถูกต้อง

### ตรวจสอบไฟล์ที่สร้าง/แก้ไข:

**Windows PowerShell:**
```powershell
# 1. ตรวจสอบว่ามีไฟล์ .env
Test-Path .env

# 2. ตรวจสอบเนื้อหา .env
Get-Content .env | Select-String "APP_NAME|APP_KEY|DB_DATABASE"

# 3. ตรวจสอบ composer.json
Get-Content composer.json | Select-String "name|description"

# 4. ตรวจสอบว่า vendor/ ถูกสร้าง
Test-Path vendor\

# 5. ตรวจสอบ Git remote (ถ้าเลือกจัดการ Git)
git remote -v
```

**Linux/Mac:**
```bash
# 1. ตรวจสอบว่ามีไฟล์ .env
ls -la .env

# 2. ตรวจสอบเนื้อหา .env
cat .env | grep -E "(APP_NAME|APP_KEY|DB_DATABASE)"

# 3. ตรวจสอบ composer.json
cat composer.json | grep -E "(name|description)"

# 4. ตรวจสอบว่า vendor/ ถูกสร้าง
ls -la vendor/

# 5. ตรวจสอบ Git remote (ถ้าเลือกจัดการ Git)
git remote -v
```

### ตัวอย่างผลลัพธ์ที่ถูกต้อง:

**Windows:**
```powershell
PS> Get-Content .env | Select-String "APP_NAME|APP_KEY|DB_DATABASE"
APP_NAME="ABC Bookstore"
APP_KEY=a1b2c3d4e5f6789012345678901234567
DB_DATABASE=bookstore_db

PS> Get-Content composer.json | Select-String "name|description"
    "name": "abccompany/bookstore-ecommerce",
    "description": "Online Bookstore E-commerce Platform",

PS> git remote -v
origin  https://github.com/abccompany/bookstore-ecommerce.git (fetch)
origin  https://github.com/abccompany/bookstore-ecommerce.git (push)
```

**Linux/Mac:**
```bash
$ cat .env | grep -E "(APP_NAME|APP_KEY|DB_DATABASE)"
APP_NAME="ABC Bookstore"
APP_KEY=a1b2c3d4e5f6789012345678901234567
DB_DATABASE=bookstore_db

$ cat composer.json | grep -E "(name|description)"
    "name": "abccompany/bookstore-ecommerce",
    "description": "Online Bookstore E-commerce Platform",

$ git remote -v
origin  https://github.com/abccompany/bookstore-ecommerce.git (fetch)
origin  https://github.com/abccompany/bookstore-ecommerce.git (push)
```

---

## 🚀 สรุป

### ขั้นตอนสั้นๆ (ไม่จัดการ Git):
```bash
git clone https://github.com/Anucha552/SimpleBiz-MVC-Framework-V2.git
mv SimpleBiz-MVC-Framework-V2 my-project
cd my-project
php console setup
# ตอบคำถาม 7 ข้อ
# ข้าม Git management (กด Enter)
mysql -u root -p
CREATE DATABASE my_database;
exit;
php console migrate
php console serve
```

### ขั้นตอนเต็ม (พร้อมจัดการ Git):
```bash
# 1. Clone และเข้าโฟลเดอร์
git clone https://github.com/Anucha552/SimpleBiz-MVC-Framework-V2.git
mv SimpleBiz-MVC-Framework-V2 my-project
cd my-project

# 2. สร้าง GitHub repository ใหม่ (บนเว็บ GitHub)
# ตั้งชื่อให้ตรงกับโปรเจค เช่น my-project
# อย่าเลือก "Initialize with README"

# 3. รัน setup พร้อมจัดการ Git
php console setup
# ตอบคำถาม 7 ข้อเกี่ยวกับโปรเจค
# เลือก y สำหรับจัดการ Git
# เลือกโหมด (1=เปลี่ยน remote, 2=เริ่มใหม่)
# ใส่ URL repository
# เลือก y เพื่อ commit และ push

# 4. สร้าง Database และรัน Migrations
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
- 🔧 จัดการ Git อัตโนมัติ (optional)
- 📝 อัปเดต README.md และ composer.json
- ✨ พร้อมเริ่มพัฒนาใน 3 นาที!

**คำสั่ง setup ทำให้การเริ่มต้นโปรเจคใหม่เป็นเรื่องง่าย เพียง 3 นาทีก็พร้อมพัฒนา! 🎉**

---

## 📚 เอกสารที่เกี่ยวข้อง

- [QUICK_START.md](QUICK_START.md) - คู่มือเริ่มต้นใช้งานแบบรวดเร็ว
- [RENAME_PROJECT.md](RENAME_PROJECT.md) - การเปลี่ยนชื่อโปรเจค
- [CLI_GUIDE.md](CLI_GUIDE.md) - คู่มือคำสั่ง CLI ทั้งหมด
- [ENVIRONMENTS.md](ENVIRONMENTS.md) - การตั้งค่า Environment Variables
- [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) - การ Deploy Production
