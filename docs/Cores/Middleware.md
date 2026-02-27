# Middleware Usage Guide

คู่มือนี้อธิบายแนวทางการใช้งานคลาส `Middleware` (namespace `App\\Core`) สำหรับกรองคำขอก่อนถึง Controller และรองรับทั้ง Web และ API

## 1. Middleware คืออะไร

Middleware คือ=โค้ดที่ทำงานก่อน Controller ถูกเรียกใช้ มีหน้าที่ตรวจสอบ เงื่อนไข หรือจัดการกับคำขอ (`Request`) เช่น:

- ตรวจสอบการเข้าสู่ระบบ (Authentication)
- ตรวจสอบสิทธิ์การเข้าถึง (Authorization)
- ตรวจสอบ API Key
- ตรวจสอบ CSRF Token
- Rate limiting
- Logging

ลำดับการทำงานโดยทั่วไป:

1. Router จับคู่เส้นทาง (Route)
2. Router เรียก Middleware ที่กำหนดไว้
3. เรียกเมธอด `handle()` ของ Middleware
   - ถ้า `handle()` คืนค่า `true` → ดำเนินการต่อไปยัง Controller
   - ถ้า `false` → หยุดการประมวลผล
   - ถ้าคืนค่า `Response` → ส่ง Response กลับทันที

## 2. โครงสร้างพื้นฐานของ Middleware

คลาสหลักมักเป็น `abstract` และกำหนดเมธอดสำคัญ:

```php
abstract public function handle(?Request $request = null): bool|Response;
```

ค่าที่ `handle()` อาจคืนค่า:

- `true` — อนุญาตให้ไปต่อ
- `false` — หยุดทันที
- `Response` — ส่ง `Response` กลับทันที (เช่น JSON error หรือ redirect)

## 3. การสร้าง Middleware ใหม่

สร้างไฟล์ในโฟลเดอร์ `App/Middleware` ตัวอย่าง:

```php
namespace App\\Middleware;

use App\\Core\\Middleware;
use App\\Core\\Request;
use App\\Core\\Response;

class AuthMiddleware extends Middleware
{
    public function handle(?Request $request = null): bool|Response
    {
        if (! $this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        return true;
    }
}
```

แนวคิดสำคัญ:

- ใช้ `$this->isAuthenticated()` เพื่อตรวจสอบสถานะผู้ใช้
- ใช้ `$this->redirect()` หรือ `$this->jsonError()` เพื่อส่ง Response ทันที

## 4. เมธอดที่มีให้ใช้ใน Middleware

### 4.1 `isAuthenticated()`
ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่ (อ้างอิงระบบ `Auth` ภายใน)

```php
if ($this->isAuthenticated()) {
    // ผ่าน
}
```

### 4.2 `getUserId()`
ดึง ID ของผู้ใช้ที่ล็อกอินอยู่ (`null` ถ้าไม่ล็อกอิน)

```php
$userId = $this->getUserId();
```

### 4.3 `jsonError()`
สร้าง JSON `Response` สำหรับ API/AJAX/SPA

```php
return $this->jsonError('Unauthorized', 401);
```

### 4.4 `redirect()`
ส่งกลับ `Response` แบบ redirect (เหมาะสำหรับ Web)

```php
return $this->redirect('/login');
```

### 4.5 `getClientIp()`
ดึง IP ของผู้ใช้ โดยรองรับ trusted proxies ตามค่าที่กำหนดใน config

```php
$ip = $this->getClientIp();
```

ระบบจะตรวจสอบ `REMOTE_ADDR` → ถ้าเป็น trusted proxy จะอ่าน `X-Forwarded-For` → fallback เป็น `X-Real-IP` → ถ้าไม่ได้จะคืนค่า `unknown`

ตัวอย่างการตั้งค่าใน `config`:

```php
'app' => [
    'trusted_proxies' => ['127.0.0.1']
]
```

## 5. ตัวอย่าง Middleware แบบต่าง ๆ

### 5.1 API Key Middleware

```php
class ApiKeyMiddleware extends Middleware
{
    public function handle(?Request $request = null): bool|Response
    {
        $apiKey = $request?->header('X-API-KEY');

        if ($apiKey !== 'my-secret-key') {
            return $this->jsonError('Invalid API Key', 403);
        }

        return true;
    }
}
```

### 5.2 Role Middleware

```php
class AdminMiddleware extends Middleware
{
    public function handle(?Request $request = null): bool|Response
    {
        if (! $this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $userId = $this->getUserId();

        if (! $this->isAdmin($userId)) {
            return $this->jsonError('Forbidden', 403);
        }

        return true;
    }

    protected function isAdmin(int $userId): bool
    {
        // ตรวจสอบ role จากฐานข้อมูล
        return true;
    }
}
```

## 6. การผูก Middleware กับ Route

แนวคิดตัวอย่างใน `Router`:

```php
$router->get('/dashboard', [DashboardController::class, 'index'], [
    AuthMiddleware::class
]);
```

ลำดับการทำงาน:

1. สร้าง instance ของ `AuthMiddleware`
2. เรียก `handle()`
3. ถ้า return `true` → เรียก Controller

## 7. แนวปฏิบัติที่ดี (Best Practices)

- Middleware ควรทำหน้าที่เดียว (Single Responsibility)
- หลีกเลี่ยงการใส่ business logic หนัก ๆ ใน Middleware
- ใช้ `Response` แทนการ `exit`
- แยก Web Middleware กับ API Middleware ให้ชัดเจน
- ตรวจสอบค่า input อย่างปลอดภัยเสมอ

## 8. โครงสร้างตัวอย่างโปรเจกต์

```
App/
 ├── Core/
 │    ├── Middleware.php
 │    ├── Request.php
 │    └── Response.php
 ├── Middleware/
 │    ├── AuthMiddleware.php
 │    ├── ApiKeyMiddleware.php
 │    └── AdminMiddleware.php
```

## 9. สรุป

Middleware คือ “ด่านหน้า” ของระบบ: ถ้า Controller คือห้องประชุม → Middleware คือพนักงานต้อนรับที่ตรวจบัตรก่อนเข้า
