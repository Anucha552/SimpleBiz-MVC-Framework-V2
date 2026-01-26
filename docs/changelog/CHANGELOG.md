````markdown
# SimpleBiz MVC Framework V2 - CHANGELOG

## [Unreleased] - 2026-01-19

### Added - ระบบใหม่ที่เพิ่มเข้ามา ✨

#### 1. Testing Infrastructure
- ✅ เพิ่ม `phpunit.xml` สำหรับการตั้งค่า PHPUnit
- ✅ สร้างโครงสร้าง `tests/` พร้อม TestCase base class
- ✅ เพิ่ม Unit Tests สำหรับ Validator และ Model
- ✅ เพิ่ม Feature Tests สำหรับ Authentication

#### 2. Custom Error Pages
- ✅ สร้าง error views สวยงามสำหรับ 404, 403, 500, 503
- ✅ เพิ่ม ErrorHandler class สำหรับจัดการ errors แบบรวมศูนย์
- ✅ รองรับ error response ทั้ง HTML และ JSON
- ✅ แยกแสดง error details ในโหมด development

#### 3. Database Seeder System
- ✅ สร้าง Seeder base class
- ✅ เพิ่ม CategorySeeder พร้อมข้อมูล 6 หมวดหมู่
- ✅ เพิ่ม UserSeeder พร้อมข้อมูล admin และ customers
- ✅ เพิ่ม ProductSeeder พร้อมข้อมูล 8 สินค้า
- ✅ สร้างไฟล์ `seed.php` สำหรับรัน seeders

#### 4. CLI Commands System
- ✅ สร้างไฟล์ `console` สำหรับรันคำสั่ง CLI
- ✅ เพิ่มคำสั่ง `serve` - รันเซิร์ฟเวอร์พัฒนา
- ✅ เพิ่มคำสั่ง `migrate` - รัน migrations
- ✅ เพิ่มคำสั่ง `seed` - รัน seeders
- ✅ เพิ่มคำสั่ง `cache:clear` - ลบ cache
- ✅ เพิ่มคำสั่ง `make:controller` - สร้าง controller
- ✅ เพิ่มคำสั่ง `make:model` - สร้าง model
- ✅ เพิ่มคำสั่ง `make:middleware` - สร้าง middleware
- ✅ เพิ่มคำสั่ง `test` - รัน PHPUnit tests
- ✅ รองรับสี ANSI สำหรับ terminal output

#### 5. Email Service
﻿# CHANGELOG

อัปเดตล่าสุด: 2026-01-26

## [Unreleased] - 2026-01-26

### เพิ่ม
- ตัวรันคำสั่ง CLI (`console`) พร้อมคำสั่ง: `serve`, `migrate`, `seed`, `cache:clear`, `make:controller`, `make:model`, `make:middleware`, `test`.
- ระบบ Migrations และ Seeders (ไฟล์อยู่ใน `database/` และ `seeders/`).
- โครงสร้างการทดสอบพื้นฐาน: `phpunit.xml` และ `tests/` พร้อม `TestCase` พื้นฐาน.
- บริการอีเมล (`App/Core/Mail`) และเทมเพลตอีเมลใน `app/Views/emails/`.
- บริการหลัก: `Cache`, `Logger`, `FileUpload`.
- `ErrorHandler` กลางสำหรับตอบข้อผิดพลาดทั้ง HTML และ JSON.

### ปรับปรุง
- Router ผสานกับ `ErrorHandler` เพื่อจัดการข้อผิดพลาดแบบรวมศูนย์.
- ปรับโครงสร้างและ namespace ของ seeders และไฟล์ migration ให้เป็นมาตรฐาน.

### แก้ไข
- ปรับปรุงการจัดการ 404 และการแสดงผลหน้า error ให้เหมาะสมยิ่งขึ้น.

---

## คำสั่งที่มีประโยชน์

Development
```bash
php console serve
```

Generate code
```bash
php console make:controller ProductController
php console make:model Product
php console make:middleware CustomMiddleware
```

Database
```bash
php console migrate
php console seed
```

Testing
```bash
php console test
# หรือ
./vendor/bin/phpunit
```

Cache
```bash
php console cache:clear
```

---

## หมายเหตุ
- changelog นี้ถูกย่อและปรับให้ตรงกับสถานะปัจจุบันของ repository โดยตัดส่วนที่ไม่เกี่ยวข้องหรือล้าสมัยออกแล้ว
- ห้ามเก็บรหัสผ่านหรือความลับในซอร์สโค้ด ให้ตั้งค่าคอนฟิกที่ละเอียดอ่อนไว้ในไฟล์ `.env`
