````markdown
# คู่มือการใช้งาน App Core Classes

เอกสารนี้อธิบายการใช้งานคลาสหลักทั้งหมดใน `app/Core` พร้อมตัวอย่างโค้ดที่ละเอียด

## สารบัญ

1. [Request](#1-request) - จัดการ HTTP Request
2. [Validator](#2-validator) - ตรวจสอบข้อมูล
3. [Session](#3-session) - จัดการ Session
4. [Auth](#4-auth) - ระบบยืนยันตัวตน
5. [Model](#5-model) - Base Model สำหรับฐานข้อมูล
6. [FileUpload](#6-fileupload) - อัปโหลดไฟล์
7. [Pagination](#7-pagination) - แบ่งหน้าข้อมูล
8. [Cache](#8-cache) - ระบบ Cache
9. [Controller](#9-controller) - Base Controller
10. [Router](#10-router) - ระบบ Routing
11. [View](#11-view) - Template Engine
12. [Database](#12-database) - การเชื่อมต่อฐานข้อมูล
13. [Logger](#13-logger) - ระบบบันทึกข้อมูล
14. [ErrorHandler](#14-errorhandler) - จัดการข้อผิดพลาด
15. [Mail](#15-mail) - ส่งอีเมล
16. [Migration](#16-migration) - Migration Base Class
17. [MigrationRunner](#17-migrationrunner) - รันระบบ Migration
18. [Seeder](#18-seeder) - สร้างข้อมูลตัวอย่าง
19. [Middleware](#19-middleware) - Base Middleware

---

## 1. Request

คลาส `Request` ใช้สำหรับจัดการคำขอ HTTP ทั้งหมด

### การใช้งานพื้นฐาน

```php
use App\Core\Request;

// สร้าง Request instance
$request = new Request();

// รับข้อมูล GET
$id = $request->get('id');
$page = $request->get('page', 1); // ค่าเริ่มต้นเป็น 1

// รับข้อมูล POST
$username = $request->post('username');
$password = $request->post('password');

// รับข้อมูล (POST, JSON, หรือ PUT/DELETE)
$email = $request->input('email');
$data = $request->input(); // รับทั้งหมด

// รับข้อมูลทั้งหมด (GET + POST + JSON)
$allData = $request->all();
```

---

(เอกสารยาว ถูกย้ายไปยัง `docs/development/CORE_USAGE.md`)
﻿# คู่มือการใช้งาน Core (สรุป ภาษาไทย)

เอกสารฉบับย่อสำหรับการใช้งานคลาสหลักใน `app/Core` ที่ใช้บ่อยในโปรเจค

---

## คำแนะนำทั่วไป
- โค้ดตัวอย่างด้านล่างใช้ namespace `App\Core` และสมมติว่า autoloading ถูกตั้งค่าเรียบร้อย

---

## Request
คลาส `Request` ช่วยอ่านข้อมูลจาก GET, POST, JSON และไฟล์ที่อัพโหลด

ตัวอย่างการใช้งานพื้นฐาน:

```php
use App\Core\Request;

$request = new Request();
$id = $request->get('id');
$name = $request->post('name');
$json = $request->input('data');
$all = $request->all();
```

---

## Validator
ใช้ตรวจสอบข้อมูลก่อนบันทึกหรือประมวลผล เช่น `required`, `email`, `min`, `max` เป็นต้น

```php
use App\Core\Validator;

$v = new Validator();
$rules = ['email' => 'required|email', 'password' => 'required|min:6'];
if (!$v->validate($_POST, $rules)) {
	$errors = $v->errors();
}
```

---

## Model (ฐานข้อมูล)
`Model` เป็น base class สำหรับการทำ CRUD แบบง่าย ๆ ผ่าน PDO

ตัวอย่างสร้างข้อมูล:

```php
$user = new \App\Models\User();
$user->name = 'John';
$user->email = 'john@example.com';
$user->save();
```

ค้นหา/อัปเดต/ลบ ใช้เมธอดมาตรฐานของ Model (ตัวอย่างแตกต่างกันตาม implementation ของโปรเจค)

---

## Mail
ส่งอีเมลโดยใช้ `App\Core\Mail` และเทมเพลตใน `app/Views/emails/`

```php
use App\Core\Mail;

$mail = new Mail();
$mail->to('user@example.com')->subject('ทดสอบ')->html('เนื้อหา')->send();
```

---

## Cache, Logger, FileUpload
- `Cache` สำหรับเก็บข้อมูลชั่วคราว (storage/cache)
- `Logger` บันทึกเหตุการณ์ไปที่ `storage/logs/`
- `FileUpload` จัดการการอัปโหลดไฟล์และการตั้งค่าความปลอดภัย

---

## Migration & Seeder
- สร้าง/รัน migration และ seeder ผ่านคำสั่ง CLI เช่น `php console migrate` และ `php console seed`

---

## ErrorHandler & Middleware
- `ErrorHandler` รวมการจัดการข้อผิดพลาดทั้ง HTML/JSON
- Middleware อยู่ใน `app/Middleware/` และสามารถลงทะเบียนใน Router หรือ Kernel ของโปรเจค

---

ถ้าต้องการ ผมจะแปลงส่วนที่ละเอียด (เช่น รายละเอียดเมธอดทั้งหมด) เป็นเอกสารภาษาไทยฉบับเต็มให้ต่อ — ต้องการให้ผมขยายหัวข้อไหนก่อนบอกได้ครับ
