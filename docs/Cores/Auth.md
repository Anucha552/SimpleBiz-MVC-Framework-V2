# Auth System

คู่มือนี้อธิบายวิธีการใช้งานระบบ Authentication และ Authorization ที่พัฒนาขึ้นสำหรับระบบ PHP แบบ production-ready

## รองรับ

- Login / Logout
- Remember Me
- Brute-force protection
- Session fixation protection
- Permission caching
- Role + Permission authorization
- Temporary login (development only)

## สารบัญ

- [Auth System](#auth-system)
  - [รองรับ](#รองรับ)
  - [สารบัญ](#สารบัญ)
  - [โครงสร้างระบบ](#โครงสร้างระบบ)
  - [การ Login](#การ-login)
  - [การ Logout](#การ-logout)
  - [Remember Me](#remember-me)
  - [ตรวจสอบสถานะผู้ใช้](#ตรวจสอบสถานะผู้ใช้)
  - [Authorization (Permission System)](#authorization-permission-system)
  - [Temporary Login (Development Only)](#temporary-login-development-only)
  - [Security Features](#security-features)
  - [ตัวอย่างการใช้งานจริง](#ตัวอย่างการใช้งานจริง)
  - [Best Practices](#best-practices)
  - [Architecture Notes](#architecture-notes)
  - [Version](#version)

---

## โครงสร้างระบบ

ระบบประกอบด้วย:

- **Auth** → จัดการ authentication
- **Authorization** → จัดการ permission และ role
- **Session** → จัดการ session
- **Logger** → บันทึก security log
- **Cache** → จัดการ brute-force throttle

## การ Login

การเรียกใช้งาน:

```php
$success = Auth::login($identifier, $password, $remember);
```

พารามิเตอร์:

| พารามิเตอร์ | Type | คำอธิบาย |
|---|---:|---|
| $identifier | string | username หรือ email |
| $password | string | รหัสผ่าน |
| $remember | bool | true หากต้องการ Remember Me |

ตัวอย่าง:

```php
if (Auth::login($_POST['email'], $_POST['password'], true)) {
    header('Location: /dashboard');
    exit;
}
```

## การ Logout

```php
Auth::logout();
```

ระบบจะ:

- ลบ session
- ลบ remember token
- regenerate session id
- บันทึก log ความปลอดภัย

## Remember Me

เมื่อ `$remember = true` ระบบจะ:

- สร้าง random token
- hash เก็บใน database
- สร้าง HMAC signature
- ตั้ง cookie
- rotate token ทุกครั้งที่ auto-login

Cookie ถูกป้องกันด้วย:

- HttpOnly
- Secure (ถ้า HTTPS)
- HMAC verification

## ตรวจสอบสถานะผู้ใช้

เช็คว่า login หรือยัง:

```php
if (Auth::check()) {
    echo "Logged in";
}
```

ดึงข้อมูล user:

```php
$user = Auth::user(); // คืนค่าเป็น array ข้อมูล user
```

## Authorization (Permission System)

ตรวจสอบสิทธิ์:

```php
if (Authorization::can('edit_post')) {
    // อนุญาต
}
```

หลักการทำงาน:

- ตรวจสอบว่าเป็น admin หรือไม่
- ตรวจสอบ permission จาก session cache
- หากไม่มี cache → query database
- ใช้ strict comparison

Permission Cache:

หลัง login ระบบจะ preload permission แล้วเก็บใน `_session['_auth_permissions']` เพื่อลดการ query ซ้ำ

## Temporary Login (Development Only)

```php
Auth::loginTemporary($userId);
```

ใช้สำหรับ development เท่านั้น — จะทำงานได้เฉพาะเมื่อ `Config::get('app.debug') === true` หากอยู่ production จะ throw exception

## Security Features

1. Strict Types: `declare(strict_types=1);`
2. Password Hashing: ใช้ `password_hash` และ `password_needs_rehash`
3. Timing Attack Mitigation: หาก user ไม่พบ ให้เรียก `password_verify` ด้วย fake hash เพื่อป้องกัน timing-based user enumeration
4. Brute-force Protection: จำกัดจำนวนครั้งต่อ identifier + IP, ใช้ cache TTL เท่ากับ window, block เมื่อเกิน limit
5. Session Fixation Protection: regenerate session ทุก login/logout
6. Remember Token Security: เก็บเฉพาะ hashed token ใน DB, ใช้ HMAC signature, rotate token ทุกครั้ง

## ตัวอย่างการใช้งานจริง

Login Controller:

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['email'];
    $password   = $_POST['password'];
    $remember   = isset($_POST['remember']);

    if (Auth::login($identifier, $password, $remember)) {
        header('Location: /dashboard');
        exit;
    }

    echo "Invalid credentials";
}
```

Protect Route:

```php
if (!Auth::check()) {
    header('Location: /login');
    exit;
}
```

Protect by Permission:

```php
if (!Authorization::can('manage_users')) {
    http_response_code(403);
    exit('Forbidden');
}
```

## Best Practices

- ห้ามใช้ `loginTemporary()` ใน production
- ห้าม log token จริงลง log
- ใช้ HTTPS เสมอ
- ตั้งค่า `APP_KEY` ให้ยาวและสุ่มจริง
- ตั้งค่า secure cookie ใน production

## Architecture Notes

ระบบนี้ถูกออกแบบให้:

- ใช้งานง่าย
- ปลอดภัยระดับ production
- รองรับการขยายในอนาคต
- สามารถพัฒนาไปสู่ multi-guard ได้

## Version

- Auth Core Version: 1.0
- Architecture Level: Production Ready
