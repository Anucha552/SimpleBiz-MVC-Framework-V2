# 📦 Session Class – คู่มือการใช้งาน

คลาส `Session` ใช้สำหรับจัดการ:

- Session lifecycle
- Flash messages (ข้อความแสดงครั้งเดียว)
- Old input (ข้อมูลฟอร์มเดิม)
- CSRF protection
- Regenerate session ป้องกัน Session Fixation

---

## 1️⃣ การเริ่มต้นใช้งาน

```php
use App\Core\Session;

Session::start();
```
ควรเรียกใน index.php หรือ bootstrap ของระบบ

---

## 2️⃣ การจัดการข้อมูลพื้นฐานใน Session

**ตั้งค่า**
```php
Session::set('user_id', 1001);
```
**ดึงค่า**
```php
$userId = Session::get('user_id');
```
**เช็คว่ามีคีย์หรือไม่**
```php
if (Session::has('user_id')) {
    // มี user_id
}
```
**ดึงค่าแล้วลบทิ้ง**
```php
$value = Session::pull('user_id');
```
**ลบคีย์**
```php
Session::remove('user_id');
```
**ดึงข้อมูลทั้งหมด**
```php
$all = Session::all();
```
**ล้างทั้งหมด**
```php
Session::clear();
```

---

## 3️⃣ Flash Messages (ข้อความแสดงครั้งเดียว)

เหมาะกับ: หลัง redirect เช่น บันทึกข้อมูลสำเร็จ

**ตั้งค่า Flash**
```php
Session::flash('success', 'บันทึกข้อมูลสำเร็จ');
```
**ดึงค่า Flash**
```php
$message = Session::getFlash('success');
```
**เช็คว่ามี Flash หรือไม่**
```php
if (Session::hasFlash('success')) {
    echo Session::getFlash('success');
}
```
**ดึง Flash ทั้งหมด**
```php
$messages = Session::getAllFlash();
```
**เก็บ Flash ไว้อีก 1 Request**
```php
Session::keepFlash();            // เก็บทั้งหมด
Session::keepFlash('success');   // เก็บเฉพาะคีย์
```

---

## 4️⃣ Old Input (ข้อมูลฟอร์มเดิม)

ใช้ตอน Validation ไม่ผ่าน แล้ว Redirect กลับฟอร์ม

**Controller**
```php
Session::flashInput($_POST);
```
**View**
```php
<input type="text" 
       name="username" 
       value="<?= htmlspecialchars(Session::old('username', '')) ?>">
```
**เช็คว่ามีค่าเก่าหรือไม่**
```php
if (Session::hasOldInput('username')) {
    // มีข้อมูลเก่า
}
```

---

## 5️⃣ CSRF Protection

**สร้าง Token**
```php
$token = Session::generateCsrfToken();
```
**ใส่ในฟอร์ม**
```php
<form method="POST">
    <?= Session::csrfField(); ?>
</form>
```
หรือ
```php
<head>
    <?= Session::csrfMeta(); ?>
</head>
```
**ตรวจสอบ Token ใน Controller**
```php
if (!Session::verifyCsrfToken($_POST['_csrf_token'] ?? '')) {
    die('CSRF token ไม่ถูกต้อง');
}
```

---

## 6️⃣ Regenerate Session (ป้องกัน Session Fixation)

**แบบปกติ**
```php
Session::regenerate();
```
**แบบมี Context (เหมาะกับ Login/Logout)**
```php
Session::regenerateWithContext('login', $userId);
Session::regenerateWithContext('logout', $userId);
```

---

## 7️⃣ ทำลาย Session (Logout)
```php
Session::destroy();
```

---

## 8️⃣ จัดการ Session ID
**ดึง Session ID**
```php
$id = Session::id();
```
**ตั้งค่า Session ID**
```php
Session::setId('custom_id');
```

---

## 9️⃣ ตั้งชื่อ Session
```php
Session::setName('MY_SESSION');
```

---

## 🔐 แนวปฏิบัติที่แนะนำ (Best Practice)

- ✔ เรียก Session::start() ที่จุดเริ่มต้นระบบ
- ✔ หลัง Login ให้เรียก Session::regenerate()
- ✔ ใช้ flash() หลัง Redirect เสมอ
- ✔ ตรวจสอบ CSRF ทุก POST request
- ✔ อย่าเก็บข้อมูลขนาดใหญ่ใน Session

---

## 🚀 ตัวอย่าง Flow Login แบบสมบูรณ์

```php
Session::start();

// ตรวจสอบ CSRF
if (!Session::verifyCsrfToken($_POST['_csrf_token'] ?? '')) {
    die('Invalid CSRF Token');
}

// ตรวจสอบผู้ใช้ (สมมติผ่านแล้ว)
$userId = 1001;

// ตั้งค่า session
Session::set('user_id', $userId);

// ป้องกัน fixation
Session::regenerateWithContext('login', $userId);

// ตั้ง flash
Session::flash('success', 'เข้าสู่ระบบสำเร็จ');

// redirect
header('Location: /dashboard');
exit;
```

---

## 🎯 สรุป

คลาส Session นี้รองรับครบ:

- Session lifecycle
- Flash messages
- Old input
- CSRF protection
- Secure session regeneration
