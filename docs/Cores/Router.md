# Router Usage Guide

เอกสารนี้อธิบายวิธีใช้งานคลาส Router สำหรับจัดการเส้นทาง (Routing) ของแอปพลิเคชัน โดยรองรับ HTTP หลายเมธอด, พารามิเตอร์แบบไดนามิก และ Middleware

---

## 1) แนวคิดพื้นฐานของ Router

Router ทำหน้าที่จับคู่คำขอ HTTP ที่เข้ามา (เช่น `GET /products/10`) แล้วส่งต่อไปยัง Controller และเมธอดที่กำหนดไว้

### ลำดับการทำงานโดยสรุป

- ลงทะเบียนเส้นทาง (Route Registration)
- รับ HTTP Method และ URI
- จับคู่เส้นทาง
- เรียก Middleware ตามลำดับ
- เรียก Controller พร้อมพารามิเตอร์
- ส่ง Response กลับไปยังผู้ใช้

---

## 2) การสร้างและใช้งาน Router

ตัวอย่างใน Front Controller (เช่น `public/index.php`)

```php
use App\Core\Router;

$router = new Router();

$router->get('/products', 'App\Controllers\ProductController@index');
$router->post('/products', 'App\Controllers\ProductController@store');

$router->dispatch();
```

เมื่อมีคำขอเข้ามา ระบบจะเรียก `dispatch()` เพื่อเริ่มกระบวนการจับคู่เส้นทาง

---

## 3) การลงทะเบียนเส้นทาง (Route Registration)

Router รองรับ 4 HTTP Method หลัก: `GET`, `POST`, `PUT`, `DELETE`

### 3.1 GET

```php
$router->get('/products', 'App\Controllers\ProductController@index');
```

เหมาะสำหรับ:

- แสดงข้อมูล
- หน้าเว็บทั่วไป
- API ดึงข้อมูล

### 3.2 POST

```php
$router->post('/products', 'App\Controllers\ProductController@store');
```

เหมาะสำหรับ:

- ส่งฟอร์ม
- สร้างข้อมูลใหม่

### 3.3 PUT

```php
$router->put('/products/{id}', 'App\Controllers\ProductController@update');
```

เหมาะสำหรับ:

- อัปเดตข้อมูล

รองรับ _method override จากฟอร์ม:

```html
<form method="POST">
    <input type="hidden" name="_method" value="PUT">
</form>
```

### 3.4 DELETE

```php
$router->delete('/products/{id}', 'App\Controllers\ProductController@destroy');
```

เหมาะสำหรับ:

- ลบข้อมูล

---

## 4) เส้นทางแบบพารามิเตอร์ (Dynamic Route)

Router รองรับรูปแบบ `{parameter}`

ตัวอย่าง:

```php
$router->get('/products/{id}', 'App\Controllers\ProductController@show');
```

ถ้าเรียก: `GET /products/15`

Controller จะได้รับค่า:

```php
public function show($id)
{
    echo $id; // 15
}
```

Router จะแปลง `{id}` เป็น regex ภายในโดยอัตโนมัติ

---

## 5) การใช้งาน Middleware กับ Route

สามารถกำหนด middleware เป็นอาร์เรย์ลำดับที่ต้องการให้ทำงาน

```php
use App\Middleware\AuthMiddleware;

$router->get(
    '/dashboard',
    'App\Controllers\DashboardController@index',
    [AuthMiddleware::class]
);
```

ลำดับการทำงาน:

- สร้าง instance ของ Middleware
- เรียก `handle()`
- ถ้า return `true` → ไปต่อ
- ถ้า return `false` → หยุด
- ถ้า return `Response` → ส่งกลับทันที

### 5.1 Middleware พร้อมพารามิเตอร์

รองรับรูปแบบ array:

```php
$router->get(
    '/admin',
    'App\Controllers\AdminController@index',
    [
        [RoleMiddleware::class, 'admin']
    ]
);
```

หรือส่งหลายค่า:

```php
[
    [RoleMiddleware::class, ['admin', 'editor']]
]
```

Router จะส่งพารามิเตอร์เข้า constructor ของ middleware

---

## 6) รูปแบบ Controller

ต้องกำหนดเป็น: `Full\Namespace\Controller@method`

ตัวอย่าง:

```php
$router->get(
    '/users',
    'App\Controllers\UserController@index'
);
```

Controller ตัวอย่าง:

```php
namespace App\Controllers;

class UserController
{
    public function index()
    {
        return "User List";
    }
}
```

---

## 7) Controller ที่รับ Request โดยตรง

Router รองรับการ inject `Request` อัตโนมัติ หากกำหนด type hint

```php
use App\Core\Request;

public function show(Request $request, $id)
{
    $query = $request->query('search');
}
```

ถ้าพารามิเตอร์ตัวแรกเป็น `Request` ระบบจะส่งเข้าไปให้เอง

---

## 8) การจัดการ Response จาก Controller

Controller สามารถ return ได้ 2 แบบหลัก:

### 8.1 Return Response

```php
return Response::json(['message' => 'Success']);
```

Router จะเรียก `send()` ให้อัตโนมัติ

### 8.2 Return String

```php
return "<h1>Hello</h1>";
```

Router จะห่อเป็น HTML Response ให้เอง

---

## 9) การจัดการ 404 และ 405

### 9.1 404 Not Found

เกิดเมื่อไม่พบเส้นทางที่ตรงกัน

Router จะเรียก `notFound()`

### 9.2 405 Method Not Allowed

เกิดเมื่อ:

- URI ถูกต้อง
- แต่ HTTP Method ไม่ตรง

Router จะตอบกลับ `405` พร้อม Header:

```
Allow: GET, POST
```

---

## 10) การทำงานของ URI ภายใน

Router จะ:

- ลบ query string
- ตัด base path (กรณีติดตั้งใน subdirectory)
- ลบ slash หน้าและหลัง
- คืนค่าในรูปแบบ `/path`

ตัวอย่าง:

`/myapp/products/10?search=test` จะถูกแปลงเป็น `/products/10`

---

## 11) โครงสร้างไฟล์ที่แนะนำ

```
App/
 ├── Core/
 │    ├── Router.php
 │    ├── Request.php
 │    └── Response.php
 ├── Controllers/
 │    ├── ProductController.php
 │    └── UserController.php
 └── Middleware/
```

---

## 12) แนวปฏิบัติที่ดี (Best Practices)

- ใช้ RESTful naming:
  - `GET /products`
  - `GET /products/{id}`
  - `POST /products`
  - `PUT /products/{id}`
  - `DELETE /products/{id}`
- แยก Web Route กับ API Route ให้ชัดเจน เช่น `/products` และ `/api/products`
- อย่าใส่ business logic ใน Router
- ใช้ Middleware สำหรับ validation และ security
- Controller ควรมีหน้าที่ประสานงาน ไม่ควรทำงานหนักเกินไป

---

## 13) สรุปแนวคิดสำคัญ

Router คือ “ระบบนำทาง” ของแอปพลิเคชัน — มันรับคำขอ → ตรวจสอบเส้นทาง → ผ่านด่าน middleware → เรียก controller

ถ้าออกแบบเส้นทางดีตั้งแต่ต้น:

- โค้ดจะอ่านง่าย
- ขยายระบบง่าย
- ลดความซับซ้อนระยะยาว
- รองรับ API และ Web ได้ในโครงสร้างเดียวกัน
