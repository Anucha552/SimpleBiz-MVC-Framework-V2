# Request Class – คู่มือการใช้งาน

คลาส `Request` ทำหน้าที่เป็นตัวกลางจัดการ HTTP Request ทั้งหมดในระบบของคุณ โดยห่อหุ้มตัวแปร global ของ PHP เช่น `$_GET`, `$_POST`, `$_SERVER`, `$_FILES`, `$_COOKIE` ให้อยู่ในรูปแบบที่เรียกใช้งานง่าย อ่านง่าย และควบคุมได้มากขึ้น

เหมาะสำหรับใช้ใน Controller, Middleware และ Service ต่าง ๆ ใน Framework ของคุณ

## 1️⃣ การสร้างอินสแตนซ์
```php
use App\Core\Request;

$request = new Request();
```
โดยปกติคุณควรสร้าง Request ที่หน้า `index.php` แล้วส่งต่อเข้า Router หรือ Controller

## 2️⃣ การดึงข้อมูลจาก Query String (GET)
**URL:**
```
/users?id=10
```
```php
$id = $request->get('id');        // 10
$all = $request->get();           // คืนค่า array ของ GET ทั้งหมด

// กำหนดค่า default:
$id = $request->get('id', 0);
```

## 3️⃣ การดึงข้อมูลจาก POST
```php
$username = $request->post('username');
$password = $request->post('password');

$allPost = $request->post(); // POST ทั้งหมด
```

## 4️⃣ การดึงข้อมูลแบบไม่สนใจชนิด (POST / PUT / DELETE / JSON)
ใช้ `input()` เมื่อต้องรองรับทั้ง Form และ JSON
```php
$email = $request->input('email');
$data  = $request->input(); // ทั้งหมด
```

## 5️⃣ ดึงข้อมูลทั้งหมดจากคำขอ
รวม GET + POST + JSON
```php
$allData = $request->all();
```

## 6️⃣ ตรวจสอบว่ามีค่าอยู่หรือไม่
```php
$request->has('email');            // true/false
$request->hasAll(['email','name']); 
```

## 7️⃣ ดึงเฉพาะบางคีย์
```php
$data = $request->only(['name', 'email']);
```

## 8️⃣ ดึงทุกอย่างยกเว้นบางคีย์
```php
$data = $request->except(['password']);
```

## 9️⃣ การจัดการไฟล์อัปโหลด
```php
if ($request->hasFile('avatar')) {
    $file = $request->file('avatar');
}

// ดึงไฟล์ทั้งหมด:
$files = $request->file();
```

## 🔟 ตรวจสอบ HTTP Method
```php
$request->method();     // GET, POST, PUT, DELETE
$request->isGet();
$request->isPost();
$request->isPut();
$request->isDelete();
```

ตัวอย่างใน Controller:
```php
if ($request->isPost()) {
    // ประมวลผลฟอร์ม
}
```

## 11️⃣ ตรวจสอบ AJAX
```php
$request->isAjax();
```

## 12️⃣ ตรวจสอบ JSON Request
```php
if ($request->isJson()) {
    $data = $request->json();
}
```

## 13️⃣ ดึง Header
```php
$auth = $request->header('Authorization');
$contentType = $request->header('Content-Type');
```

## 14️⃣ ดึง Bearer Token
```php
$token = $request->bearerToken();
```
เหมาะสำหรับ API ที่ใช้ JWT

## 15️⃣ URI และ URL
```php
$request->uri(); // /users
$request->url(); // http://example.com/users?id=10
```

## 16️⃣ ตรวจสอบ HTTPS
```php
$request->isSecure();
```

## 17️⃣ IP Address
```php
$ip = $request->ip();
```

## 18️⃣ User Agent
```php
$ua = $request->userAgent();
```

## 19️⃣ Cookies
```php
$token = $request->cookie('remember_token');
$allCookies = $request->cookie();
```

## 20️⃣ JSON และ Raw Body
```php
$jsonData = $request->json();
$rawBody  = $request->raw();
```

## 21️⃣ Request ID (Correlation ID)
ทุก Request จะมี X-Request-Id อัตโนมัติ
```php
$requestId = $request->getRequestId();
```
เหมาะสำหรับ:
- Logging
- Debugging
- Trace ระบบแบบ Microservices

## 22️⃣ ตั้งค่า Response Header ผ่าน Request
Middleware สามารถเพิ่ม header ที่จะส่งกลับไปยัง client ได้
```php
$request->setResponseHeader('X-Custom-Header', 'Value');

// เพิ่มหลายตัว:
$request->addResponseHeaders([
    'X-App-Version' => '1.0',
    'X-Env' => 'production',
]);

// ดึงออกไปใช้ตอนส่ง Response:
$headers = $request->getResponseHeaders();
```

## 🔥 ตัวอย่างการใช้งานจริงใน Controller
```php
public function store(Request $request)
{
    if (!$request->hasAll(['name', 'email'])) {
        return response()->json(['error' => 'Missing fields'], 400);
    }

    $data = $request->only(['name', 'email']);

    // บันทึกข้อมูลลงฐานข้อมูล

    return response()->json([
        'message' => 'User created',
        'request_id' => $request->getRequestId()
    ]);
}
```

## 🎯 แนวทางใช้งานที่แนะนำ
- ใช้ `get()` เฉพาะ query string
- ใช้ `input()` เมื่อต้องรองรับทั้ง Form และ JSON
- ใช้ `only()` ก่อนส่งข้อมูลเข้า Model เพื่อป้องกัน Mass Assignment
- ใช้ `getRequestId()` ร่วมกับ Logger
