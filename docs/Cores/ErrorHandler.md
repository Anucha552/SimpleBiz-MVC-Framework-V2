# ErrorHandler Class Guide

คลาส `ErrorHandler` ใช้สำหรับจัดการข้อผิดพลาด (HTTP Errors) ทั้งในรูปแบบ **Web (HTML)** และ **API (JSON)** ภายในแอปพลิเคชันของคุณ โดยจะเลือกประเภทการตอบกลับอัตโนมัติตามเส้นทางคำขอ

- ถ้า URL ขึ้นต้นด้วย `/api` → ส่งกลับเป็น JSON
- ถ้าไม่ใช่ → ส่งกลับเป็น HTML View

---

## 1️⃣ แนวคิดการทำงาน

```
Controller → เรียก ErrorHandler → คืนค่า Response → ส่งออกไปยัง Browser / API Client

ตัวอย่างเส้นทาง:

/api/users/99   → JSON Error
/users/99       → HTML Error Page
```

## 2️⃣ วิธีใช้งานหลัก

### ✅ แบบที่ 1: คืนค่า Response (แนะนำใน Controller)

ใช้เมื่อคุณต้องการ return กลับไปยังระบบ Router

```php
use App\Core\ErrorHandler;

return ErrorHandler::response(404, 'ไม่พบหน้า');
```

**เหมาะสำหรับ:**
- ภายใน Controller
- ต้องการให้ Router เป็นผู้ส่ง Response

### ✅ แบบที่ 2: แสดงทันทีและหยุดการทำงาน

ใช้เมื่อคุณต้องการแสดง error แล้วหยุดสคริปต์ทันที

```php
use App\Core\ErrorHandler;

ErrorHandler::show(404, 'ไม่พบหน้า');
```

จะทำงานดังนี้:
- ล้าง output buffer ทั้งหมด
- ส่ง Response
- exit ทันที

## 3️⃣ Shortcut Methods ที่มีให้

เพื่อความสะดวก มีเมธอดสำเร็จรูปดังนี้

- **404 Not Found**
  ```php
  ErrorHandler::notFound('ไม่พบข้อมูล');
  ```
- **403 Forbidden**
  ```php
  ErrorHandler::forbidden('ไม่มีสิทธิ์เข้าถึง');
  ```
- **405 Method Not Allowed**
  ```php
  ErrorHandler::methodNotAllowed('เมธอดไม่ถูกต้อง');
  ```
- **500 Internal Server Error**
  ```php
  ErrorHandler::serverError('เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์');
  ```
- **503 Service Unavailable**
  ```php
  ErrorHandler::maintenance('ปิดปรับปรุงระบบชั่วคราว');
  ```

## 4️⃣ การใช้งานใน Controller (ตัวอย่างจริง)

**ตัวอย่าง Web Controller**

```php
public function show(int $id)
{
    $user = User::find($id);

    if (!$user) {
        return ErrorHandler::response(404, 'ไม่พบผู้ใช้');
    }

    return view('users.show', compact('user'));
}
```

**ตัวอย่าง API Controller**

```php
public function show(int $id)
{
    $user = User::find($id);

    if (!$user) {
        return ErrorHandler::response(404);
    }

    return Response::apiSuccess($user);
}
```

ระบบจะตอบกลับเป็น JSON อัตโนมัติ:

```json
{
  "status": "error",
  "message": "ไม่พบข้อมูลที่ต้องการ"
}
```

## 5️⃣ การสร้างหน้า Error HTML

**ตำแหน่งไฟล์:**

```
app/Views/errors/
```

**ตัวอย่างโครงสร้าง:**

```
404.php
403.php
500.php
503.php
```

**ตัวอย่างไฟล์ 404.php**

```php
<!DOCTYPE html>
<html>
<head>
    <title>404 Not Found</title>
</head>
<body>
    <h1>404</h1>
    <p><?= $error ?? 'ไม่พบหน้า' ?></p>
</body>
</html>
```

**ตัวแปรที่ส่งเข้า View:**
- `$error`

## 6️⃣ รหัส HTTP ที่รองรับ

| Code | ความหมาย                |
|------|-------------------------|
| 404  | ไม่พบข้อมูล             |
| 403  | ไม่มีสิทธิ์             |
| 405  | เมธอดไม่ถูกต้อง         |
| 500  | เซิร์ฟเวอร์ผิดพลาด      |
| 503  | ปิดปรับปรุงระบบ         |

หากไม่มี View ตามรหัสที่เรียก ระบบจะ fallback ไปใช้ 500.php

## 7️⃣ แนวทางที่แนะนำ (Best Practice)

- ✅ ใน Controller ให้ใช้ `return ErrorHandler::response()`
- ✅ ในระดับระบบ (Router / Middleware) ใช้ `ErrorHandler::show()`
- ✅ แยก Web และ API ด้วย prefix `/api`
- ✅ สร้างไฟล์ error view ให้ครบทุกสถานะสำคัญ

## 8️⃣ ตัวอย่าง Flow ทั้งระบบ

```
Request → Router → Controller
                     ↓
               เกิด Error
                     ↓
             ErrorHandler::response()
                     ↓
               Response::html() / Response::apiError()
                     ↓
                  Browser / API
```

## 9️⃣ สรุปแนวคิดสำคัญ

ErrorHandler ทำหน้าที่เป็น “ศูนย์กลางการจัดการ Error” ของ Framework

**ข้อดีของโครงสร้างนี้:**
- แยก Error ออกจาก Controller
- รองรับ Web + API ในคลาสเดียว
- ลดการเขียนโค้ดซ้ำ
- ควบคุมมาตรฐาน Response ได้ทั้งระบบ
