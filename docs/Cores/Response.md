# คู่มือการใช้งานคลาส Response

คลาส Response มีหน้าที่จัดการ HTTP Response ทั้งหมดในระบบของคุณ ไม่ว่าจะเป็น HTML, JSON, API, Redirect, Cookie หรือการกำหนด Header ต่าง ๆ

แนวคิดสำคัญของคลาสนี้คือ **Immutable Pattern** — ทุกเมธอดประเภท `with...()` จะคืนค่า object ใหม่เสมอ ดังนั้นควรเขียนแบบ chain หรือรับค่ากลับทุกครั้ง

## 1️⃣ การสร้าง Response พื้นฐาน
```php
use App\Core\Response;

$response = new Response(
    'Hello World',
    200,
    ['Content-Type' => 'text/plain']
);

$response->send();
```
เหมาะสำหรับกรณีทั่วไปที่ต้องการควบคุม body, status และ header เอง

## 2️⃣ ส่ง HTML Response
ใช้เมื่อคุณต้องการส่งหน้าเว็บ HTML

```php
return Response::html('<h1>Hello</h1>');
```

กำหนดสถานะเพิ่มเติมได้:

```php
return Response::html('<h1>Not Found</h1>', 404);
```

## 3️⃣ ส่ง JSON Response
ใช้เมื่อทำ API หรือ AJAX

```php
return Response::json([
    'name' => 'Golf',
    'role' => 'Developer'
]);
```

กำหนด status code:

```php
return Response::json($data, 201);
```

## 4️⃣ ส่ง API Success (รูปแบบมาตรฐานของ Framework)
โครงสร้าง JSON ที่ได้:

```json
{
  "success": true,
  "data": ...,
  "message": "...",
  "errors": [],
  "meta": {}
}
```

ตัวอย่างใช้งาน:

```php
return Response::apiSuccess(
    $userData,
    'User created successfully',
    ['page' => 1],
    201
);
```

## 5️⃣ ส่ง API Error
โครงสร้าง JSON:

```json
{
  "success": false,
  "data": null,
  "message": "...",
  "errors": {...}
}
```

ตัวอย่าง:

```php
return Response::apiError(
    'Validation failed',
    ['email' => 'Email is required'],
    422
);
```

## 6️⃣ Redirect ไปยังหน้าอื่น
```php
return Response::redirect('/login');
```

กำหนด status code ได้:

```php
return Response::redirect('/dashboard', 301);
```

## 7️⃣ No Content (204)
เหมาะกับ API ลบข้อมูลสำเร็จ

```php
return Response::noContent();
```

## 8️⃣ การเพิ่ม Header
```php
$response = Response::json($data)
    ->withHeader('X-App-Version', '1.0');

return $response;
```

เพิ่มหลาย header:

```php
$response = Response::html('<h1>Hello</h1>')
    ->withHeaders([
        'Cache-Control' => 'no-cache',
        'X-Test' => 'Example'
    ]);

return $response;
```

## 9️⃣ การตั้งค่า Status Code
```php
$response = Response::html('<h1>Error</h1>')
    ->withStatus(500);

return $response;
```

## 🔟 การตั้งค่า Cookie
```php
$response = Response::html('Login Success')
    ->withCookie('session_id', 'abc123', [
        'expires' => time() + 3600,
        'path' => '/',
        'httponly' => true,
        'secure' => true
    ]);

return $response;
```

## 1️⃣1️⃣ เขียน Body เพิ่มเติม (Append)
```php
$response = Response::html('<h1>Hello</h1>')
    ->write('<p>Welcome</p>');

return $response;
```

## 1️⃣2️⃣ การเรียกดูข้อมูล Response (ใช้ใน Test)
```php
$status = $response->getStatusCode();
$headers = $response->getHeaders();
$body = $response->getBody();
$cookies = $response->getCookies();
```

สำหรับตรวจสอบ header ที่ถูกส่ง:

```php
Response::getLastSentHeaders();
```

## 🔥 ตัวอย่างการใช้งานใน Controller
```php
class UserController
{
    public function store()
    {
        $data = ['name' => 'Golf'];

        return Response::apiSuccess($data, 'Created', [], 201);
    }
}

// ใน Front Controller:
$response = $controller->store();
$response->send();
```

## 🧠 แนวคิดการออกแบบที่คุณทำไว้ (ถือว่าดีมาก)
- Immutable object ✔
- API Response มาตรฐาน ✔
- JSON encode fallback ✔
- รองรับ Cookie options ✔
- Testable headers ✔
