# คู่มือการใช้งาน Middleware

## สารบัญ
- [Middleware คืออะไร](#middleware-คืออะไร)
- [รายการ Middleware ที่มี](#รายการ-middleware-ที่มี)
- [การใช้งาน Middleware](#การใช้งาน-middleware)
- [Middleware แต่ละตัว](#middleware-แต่ละตัว)
  - [AuthMiddleware](#1-authmiddleware)
  - [ApiKeyMiddleware](#2-apikeymiddleware)
  - [CsrfMiddleware](#3-csrfmiddleware)
  - [RateLimitMiddleware](#4-ratelimitmiddleware)
  - [CorsMiddleware](#5-corsmiddleware)
  - [GuestMiddleware](#6-guestmiddleware)
  - [RoleMiddleware](#7-rolemiddleware)
  - [MaintenanceMiddleware](#8-maintenancemiddleware)
  - [LoggingMiddleware](#9-loggingmiddleware)
  - [ValidationMiddleware](#10-validationmiddleware)

---

## Middleware คืออะไร

**Middleware** คือชั้นกลางที่ทำงานระหว่างที่คำขอ (Request) เข้ามาและก่อนที่จะไปถึง Controller 

### หน้าที่หลัก:
- 🔒 **ความปลอดภัย**: ตรวจสอบการเข้าสู่ระบบ, API keys, CSRF tokens
- 🚦 **การควบคุม**: จำกัดอัตราคำขอ, ตรวจสอบสิทธิ์
- ✅ **การตรวจสอบ**: ตรวจสอบความถูกต้องของข้อมูล
- 📝 **การบันทึก**: บันทึก logs, tracking
- 🌐 **การจัดการ**: CORS, Maintenance mode

### ขั้นตอนการทำงาน:
```
Request → Middleware → Controller → Response
```

ถ้า Middleware คืนค่า `false` การประมวลผลจะหยุดทันที และไม่ไปถึง Controller

---

## รายการ Middleware ที่มี

| Middleware | จุดประสงค์ | ระดับความสำคัญ |
|-----------|----------|----------------|
| AuthMiddleware | ตรวจสอบการเข้าสู่ระบบ | 🔴 สูง |
| ApiKeyMiddleware | ตรวจสอบ API key | 🔴 สูง |
| CsrfMiddleware | ป้องกัน CSRF attacks | 🔴 สูง |
| RateLimitMiddleware | จำกัดจำนวนคำขอ | 🟡 กลาง |
| CorsMiddleware | จัดการ CORS | 🟡 กลาง |
| GuestMiddleware | จำกัดผู้ที่ล็อกอินแล้ว | 🟢 ปานกลาง |
| RoleMiddleware | ตรวจสอบสิทธิ์ตามบทบาท | 🔴 สูง |
| MaintenanceMiddleware | โหมดปิดปรุงระบบ | 🟢 ปานกลาง |
| LoggingMiddleware | บันทึก request logs | 🟡 กลาง |
| ValidationMiddleware | ตรวจสอบข้อมูล input | 🔴 สูง |

---

## การใช้งาน Middleware

### วิธีที่ 1: ใน Router (แนะนำ)

```php
// routes/web.php

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;

// ใช้ middleware กับเส้นทางเดียว
$router->get('/dashboard', 'DashboardController@index', [
    new AuthMiddleware()
]);

// ใช้หลาย middleware
$router->post('/products', 'ProductController@store', [
    new AuthMiddleware(),
    new CsrfMiddleware(),
    new ValidationMiddleware([
        'name' => 'required|min:3',
        'price' => 'required|numeric'
    ])
]);

// Group routes พร้อม middleware
$router->group(['middleware' => [new AuthMiddleware()]], function($router) {
    $router->get('/profile', 'UserController@profile');
    $router->get('/settings', 'UserController@settings');
});
```

### วิธีที่ 2: ใน Controller

```php
class ProductController extends Controller
{
    public function __construct()
    {
        // เรียกใช้ middleware ใน constructor
        $authMiddleware = new AuthMiddleware();
        if (!$authMiddleware->handle()) {
            exit; // หยุดถ้า middleware ล้มเหลว
        }
    }
}
```

### วิธีที่ 3: Global Middleware

```php
// public/index.php

// Middleware ที่ทำงานกับทุก request
$maintenanceMiddleware = new MaintenanceMiddleware();
if (!$maintenanceMiddleware->handle()) {
    exit;
}

$loggingMiddleware = new LoggingMiddleware();
$loggingMiddleware->handle();

// ดำเนินการ routing ต่อ...
```

---

## Middleware แต่ละตัว

### 1. AuthMiddleware

**จุดประสงค์**: ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่

#### การใช้งาน:
```php
use App\Middleware\AuthMiddleware;

// ป้องกันเส้นทางที่ต้องเข้าสู่ระบบ
$router->get('/cart', 'CartController@index', [new AuthMiddleware()]);
$router->get('/orders', 'OrderController@index', [new AuthMiddleware()]);
$router->get('/checkout', 'CheckoutController@index', [new AuthMiddleware()]);
```

#### พฤติกรรม:
- ✅ **ผ่าน**: ถ้ามี `$_SESSION['user_id']`
- ❌ **ไม่ผ่าน**: 
  - Web → Redirect ไป `/login`
  - API → คืนค่า JSON 401 Unauthorized

#### ไฟล์: `app/Middleware/AuthMiddleware.php`

---

### 2. ApiKeyMiddleware

**จุดประสงค์**: ตรวจสอบ API key สำหรับ API endpoints

#### การใช้งาน:
```php
use App\Middleware\ApiKeyMiddleware;

// ป้องกัน API endpoints
$router->post('/api/v1/orders', 'Api\V1\OrderApiController@store', [
    new ApiKeyMiddleware()
]);
```

#### การส่ง API Key:

**Header (แนะนำ):**
```bash
curl -H "X-API-Key: demo-api-key-12345" https://example.com/api/v1/products
```

**Query String:**
```bash
curl https://example.com/api/v1/products?api_key=demo-api-key-12345
```

#### การกำหนด API Keys:

**แบบที่ 1: ใน Code**
```php
// แก้ไขใน app/Middleware/ApiKeyMiddleware.php
private array $validKeys = [
    'your-production-key-here',
    'another-key-here',
];
```

**แบบที่ 2: Environment Variable**
```bash
# .env file
API_KEY=your-production-api-key-here
```

#### ไฟล์: `app/Middleware/ApiKeyMiddleware.php`

---

### 3. CsrfMiddleware

**จุดประสงค์**: ป้องกัน Cross-Site Request Forgery (CSRF) attacks

#### การใช้งาน:

**1. ใน Router:**
```php
use App\Middleware\CsrfMiddleware;

// ใช้กับ POST, PUT, DELETE requests
$router->post('/products', 'ProductController@store', [
    new CsrfMiddleware()
]);
```

**2. ใน HTML Form:**
```php
<form method="POST" action="/products">
    <?php
    // วิธีที่ 1: ใช้ static method
    echo \App\Middleware\CsrfMiddleware::field();
    ?>
    
    <!-- วิธีที่ 2: เพิ่ม input ด้วยตัวเอง -->
    <input type="hidden" name="csrf_token" 
           value="<?= $_SESSION['csrf_token'] ?>">
    
    <input type="text" name="name">
    <button type="submit">Submit</button>
</form>
```

**3. สำหรับ AJAX:**
```javascript
// ส่ง token ผ่าน header
fetch('/api/products', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': document.querySelector('[name="csrf_token"]').value
    },
    body: JSON.stringify(data)
});
```

#### คุณสมบัติ:
- Token มีอายุ 1 ชั่วโมง (3600 วินาที)
- Token สร้างใหม่เมื่อหมดอายุ
- ข้าม API endpoints อัตโนมัติ (ใช้ API key แทน)

#### ไฟล์: `app/Middleware/CsrfMiddleware.php`

---

### 4. RateLimitMiddleware

**จุดประสงค์**: จำกัดจำนวนคำขอเพื่อป้องกัน abuse และ DDoS

#### การใช้งาน:

**แบบพื้นฐาน (ค่าเริ่มต้น: 60 requests/นาที):**
```php
use App\Middleware\RateLimitMiddleware;

$router->post('/api/orders', 'OrderController@store', [
    new RateLimitMiddleware()
]);
```

**กำหนดค่าเอง:**
```php
// 100 requests ต่อ 60 วินาที
$router->post('/api/search', 'SearchController@index', [
    new RateLimitMiddleware(100, 60)
]);

// 10 requests ต่อ 300 วินาที (5 นาที) - สำหรับ login
$router->post('/login', 'AuthController@login', [
    new RateLimitMiddleware(10, 300)
]);
```

#### การตรวจสอบสถานะ:
```bash
# Response Headers
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1640123456
```

#### การกำหนดค่าผ่าน Environment:
```bash
# .env
RATE_LIMIT_MAX=100
RATE_LIMIT_WINDOW=60
```

#### พฤติกรรม:
- ติดตามตาม IP address หรือ user_id (ถ้าล็อกอิน)
- คืนค่า 429 Too Many Requests เมื่อเกินขอบเขต
- ใช้ Cache (ถ้ามี) หรือ Session

#### ไฟล์: `app/Middleware/RateLimitMiddleware.php`

---

### 5. CorsMiddleware

**จุดประสงค์**: จัดการ CORS headers สำหรับ API ข้าม domain

#### การใช้งาน:

**แบบพื้นฐาน:**
```php
use App\Middleware\CorsMiddleware;

// ใช้กับ API routes ทั้งหมด
$router->group(['prefix' => '/api', 'middleware' => [new CorsMiddleware()]], 
    function($router) {
        $router->get('/products', 'Api\ProductController@index');
        $router->post('/orders', 'Api\OrderController@store');
    }
);
```

**กำหนดค่าเอง:**
```php
$cors = new CorsMiddleware();

// เพิ่ม allowed origins
$cors->addAllowedOrigins([
    'https://myapp.com',
    'https://admin.myapp.com'
]);

// กำหนด methods
$cors->setAllowedMethods(['GET', 'POST', 'PUT', 'DELETE']);

// กำหนด headers
$cors->setAllowedHeaders(['Content-Type', 'Authorization', 'X-API-Key']);

// เปิด/ปิด credentials
$cors->setAllowCredentials(true);

$router->group(['middleware' => [$cors]], function($router) {
    // routes...
});
```

#### การกำหนด Allowed Origins:

**แบบที่ 1: ใน Code**
```php
// แก้ไขใน app/Middleware/CorsMiddleware.php
private array $allowedOrigins = [
    'https://myapp.com',
    'https://admin.myapp.com',
];
```

**แบบที่ 2: Environment Variable**
```bash
# .env
CORS_ALLOWED_ORIGINS=https://myapp.com,https://admin.myapp.com
```

#### Preflight Requests:
Middleware จัดการ OPTIONS requests อัตโนมัติ

#### ไฟล์: `app/Middleware/CorsMiddleware.php`

---

### 6. GuestMiddleware

**จุดประสงค์**: ป้องกันผู้ที่เข้าสู่ระบบแล้วเข้าถึงหน้าสำหรับแขก

#### การใช้งาน:
```php
use App\Middleware\GuestMiddleware;

// หน้าที่ผู้ล็อกอินแล้วไม่ควรเข้าถึง
$router->get('/login', 'AuthController@showLogin', [
    new GuestMiddleware()
]);

$router->get('/register', 'AuthController@showRegister', [
    new GuestMiddleware()
]);

$router->get('/forgot-password', 'AuthController@showForgot', [
    new GuestMiddleware()
]);
```

**กำหนดหน้าที่จะ redirect:**
```php
// Redirect ไปหน้า dashboard แทน home
$router->get('/login', 'AuthController@showLogin', [
    new GuestMiddleware('/dashboard')
]);
```

#### พฤติกรรม:
- ✅ **ผู้ไม่ได้ล็อกอิน**: เข้าถึงได้ปกติ
- ❌ **ผู้ล็อกอินแล้ว**: 
  - Web → Redirect ไป `/` (หรือหน้าที่กำหนด)
  - API → คืนค่า JSON 403 Already authenticated

#### ไฟล์: `app/Middleware/GuestMiddleware.php`

---

### 7. RoleMiddleware

**จุดประสงค์**: ตรวจสอบบทบาทและสิทธิ์ของผู้ใช้

#### การใช้งาน:

**บทบาทเดียว:**
```php
use App\Middleware\RoleMiddleware;

// เฉพาะ admin
$router->get('/admin/dashboard', 'Admin\DashboardController@index', [
    new RoleMiddleware('admin')
]);
```

**หลายบทบาท:**
```php
// admin หรือ manager สามารถเข้าถึงได้
$router->post('/products', 'ProductController@store', [
    new RoleMiddleware(['admin', 'manager'])
]);
```

**ไม่กำหนดบทบาท (เฉพาะ authenticated):**
```php
$router->get('/dashboard', 'DashboardController@index', [
    new RoleMiddleware() // ผ่านทุกคนที่ล็อกอินแล้ว
]);
```

#### ลำดับชั้นบทบาท:
```
guest (0) → user (1) → manager (2) → admin (3)
```

ถ้าต้องการ `manager` ผู้ที่เป็น `admin` ก็เข้าถึงได้เช่นกัน (ระดับสูงกว่า)

#### การตรวจสอบบทบาทใน View:
```php
// ตรวจสอบบทบาทเฉพาะ
if (\App\Middleware\RoleMiddleware::userHasRole('admin')) {
    echo '<a href="/admin">Admin Panel</a>';
}

// ตรวจสอบว่าเป็น admin
if (\App\Middleware\RoleMiddleware::isAdmin()) {
    echo '<a href="/admin">Admin Panel</a>';
}
```

#### การตั้งค่าในฐานข้อมูล:
```sql
-- ตารางผู้ใช้ต้องมี column 'role'
ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user';

-- ตัวอย่างข้อมูล
UPDATE users SET role = 'admin' WHERE id = 1;
UPDATE users SET role = 'manager' WHERE id = 2;
```

#### กำหนดลำดับชั้นเอง:
```php
$roleMiddleware = new RoleMiddleware(['admin']);
$roleMiddleware->setRoleHierarchy([
    'guest' => 0,
    'user' => 1,
    'premium' => 2,
    'manager' => 3,
    'admin' => 4,
    'superadmin' => 5,
]);
```

#### ไฟล์: `app/Middleware/RoleMiddleware.php`

---

### 8. MaintenanceMiddleware

**จุดประสงค์**: ปิดเว็บไซต์ชั่วคราวระหว่างบำรุงรักษา

#### การใช้งาน:

**Global Middleware (แนะนำ):**
```php
// public/index.php
use App\Middleware\MaintenanceMiddleware;

$maintenanceMiddleware = new MaintenanceMiddleware();
if (!$maintenanceMiddleware->handle()) {
    exit; // หยุดถ้าอยู่ใน maintenance mode
}

// ดำเนินการ routing ต่อ...
```

#### การเปิด Maintenance Mode:

**วิธีที่ 1: สร้างไฟล์ JSON**
```php
// สร้างไฟล์ storage/maintenance.json
\App\Middleware\MaintenanceMiddleware::enable(
    'ขออภัย เว็บไซต์อยู่ระหว่างการปรับปรุง เราจะกลับมาเร็วๆ นี้',
    '26 ธันวาคม 2025 เวลา 18:00 น.'
);
```

**วิธีที่ 2: Environment Variable**
```bash
# .env
MAINTENANCE_MODE=true
```

**วิธีที่ 3: สร้างไฟล์ด้วยตัวเอง**
```json
// storage/maintenance.json
{
    "message": "ระบบอยู่ระหว่างการปรับปรุง",
    "retry_after": "26 ธันวาคม 2025 เวลา 18:00 น.",
    "enabled_at": "2025-12-26 10:00:00"
}
```

#### การปิด Maintenance Mode:
```php
\App\Middleware\MaintenanceMiddleware::disable();
```

หรือลบไฟล์ `storage/maintenance.json`

#### IP Whitelist (ยกเว้นสำหรับ Admin):

**แบบที่ 1: ใน Code**
```php
// แก้ไขใน app/Middleware/MaintenanceMiddleware.php
private array $allowedIPs = [
    '127.0.0.1',
    '192.168.1.100', // IP admin
];
```

**แบบที่ 2: Environment Variable**
```bash
# .env
MAINTENANCE_ALLOWED_IPS=127.0.0.1,192.168.1.100,203.0.113.1
```

#### Except Routes (เส้นทางที่ยกเว้น):
```php
// แก้ไขใน app/Middleware/MaintenanceMiddleware.php
private array $exceptRoutes = [
    '/api/health',
    '/api/status',
    '/admin/login',
];
```

#### หน้า Maintenance:
- **Web**: แสดงหน้า HTML พร้อม spinner
- **API**: คืนค่า JSON 503 Service Unavailable

#### ไฟล์: `app/Middleware/MaintenanceMiddleware.php`

---

### 9. LoggingMiddleware

**จุดประสงค์**: บันทึกคำขอ HTTP ทั้งหมดเพื่อการวิเคราะห์และดีบัก

#### การใช้งาน:

**Global Logging:**
```php
// public/index.php
use App\Middleware\LoggingMiddleware;

$loggingMiddleware = new LoggingMiddleware();
$loggingMiddleware->handle();

// ดำเนินการ routing ต่อ...
```

**Specific Routes:**
```php
use App\Middleware\LoggingMiddleware;

// แบบปกติ
$router->post('/api/orders', 'OrderController@store', [
    new LoggingMiddleware()
]);

// แบบ detailed (บันทึกทุกอย่าง)
$router->post('/api/orders', 'OrderController@store', [
    new LoggingMiddleware(true, false) // (detailed, logBody)
]);

// บันทึกทั้ง headers และ request body
$router->post('/api/webhook', 'WebhookController@handle', [
    new LoggingMiddleware(true, true)
]);
```

#### การกำหนดค่า:
```php
$logger = new LoggingMiddleware();

// เปิด detailed mode
$logger->setDetailed(true);

// เปิดการบันทึก request body
$logger->setLogBody(true);

// เพิ่มเส้นทางที่ไม่ต้องบันทึก
$logger->addExceptRoutes(['/health', '/ping', '/favicon.ico']);
```

#### ข้อมูลที่บันทึก:

**พื้นฐาน:**
- Timestamp
- HTTP Method (GET, POST, etc.)
- URI/Route
- IP Address
- User Agent
- User ID (ถ้าล็อกอิน)

**Detailed Mode:**
- Referer
- Protocol
- Request Headers
- Execution Time
- Memory Usage
- Status Code

**Body Logging:**
- Request Body (ซ่อนข้อมูลละเอียดอ่อนอัตโนมัติ)

#### ฟิลด์ที่ถูกซ่อน:
- password
- password_confirmation
- token
- api_key
- secret
- credit_card
- cvv

#### ไฟล์ Log:
```
storage/logs/app.log
```

#### ตัวอย่าง Log:
```
[2025-12-26 10:30:45] [INFO] request.incoming {"method":"POST","uri":"/api/orders"} user_id=12 ip=192.168.1.1 method=POST route=/api/orders
[2025-12-26 10:30:46] [INFO] request.completed {"status_code":200,"execution_time":0.1234} user_id=12 ip=192.168.1.1 method=POST route=/api/orders
```

#### การใช้งานผ่าน Environment:
```bash
# .env
LOG_DETAILED=true
LOG_REQUEST_BODY=false
```

#### ไฟล์: `app/Middleware/LoggingMiddleware.php`

---

### 10. ValidationMiddleware

**จุดประสงค์**: ตรวจสอบความถูกต้องของข้อมูล input ก่อนส่งไปยัง Controller

#### การใช้งาน:

**แบบกำหนดกฎเอง:**
```php
use App\Middleware\ValidationMiddleware;

$router->post('/products', 'ProductController@store', [
    new ValidationMiddleware([
        'name' => 'required|min:3|max:100',
        'price' => 'required|numeric',
        'description' => 'required|min:10',
        'category' => 'required|in:electronics,clothing,food'
    ])
]);
```

**ใช้ preset (Login):**
```php
$router->post('/login', 'AuthController@login', [
    ValidationMiddleware::login() // email + password
]);
```

**ใช้ preset (Register):**
```php
$router->post('/register', 'AuthController@register', [
    ValidationMiddleware::register() // name, email, password, password_confirmation
]);
```

**เพิ่มกฎเข้าไปใน preset:**
```php
$router->post('/register', 'AuthController@register', [
    ValidationMiddleware::register([
        'phone' => 'required|phone',
        'age' => 'required|numeric|min:18'
    ])
]);
```

#### กฎการตรวจสอบที่รองรับ:

| กฎ | คำอธิบาย | ตัวอย่าง |
|----|---------|---------|
| `required` | ต้องมีค่า | `required` |
| `email` | รูปแบบอีเมล | `email` |
| `min:n` | ความยาวขั้นต่ำ | `min:8` |
| `max:n` | ความยาวสูงสุด | `max:100` |
| `numeric` | ต้องเป็นตัวเลข | `numeric` |
| `integer` | ต้องเป็นจำนวนเต็ม | `integer` |
| `alpha` | เฉพาะตัวอักษร | `alpha` |
| `alphanumeric` | ตัวอักษรและตัวเลข | `alphanumeric` |
| `url` | รูปแบบ URL | `url` |
| `same:field` | ต้องตรงกับฟิลด์อื่น | `same:password` |
| `in:val1,val2` | อยู่ในรายการ | `in:admin,user,guest` |
| `regex:pattern` | ตรงกับ pattern | `regex:/^[0-9]{10}$/` |
| `unique:table,col` | ไม่ซ้ำในฐานข้อมูล | `unique:users,email` |
| `exists:table,col` | ต้องมีในฐานข้อมูล | `exists:categories,id` |

#### การแสดง Errors ในฟอร์ม:

**แสดงข้อผิดพลาดทั้งหมด:**
```php
<?php
$errors = \App\Middleware\ValidationMiddleware::getErrors();
if (!empty($errors)) {
    echo '<ul class="errors">';
    foreach ($errors as $field => $error) {
        echo "<li>{$error}</li>";
    }
    echo '</ul>';
}
?>
```

**แสดงข้อผิดพลาดของฟิลด์เฉพาะ:**
```php
<input type="text" name="email" 
       value="<?= ValidationMiddleware::getOldInput('email') ?>">
<?php if (ValidationMiddleware::hasError('email')): ?>
    <span class="error">
        <?= ValidationMiddleware::getError('email') ?>
    </span>
<?php endif; ?>
```

#### เติมข้อมูลเดิม (Old Input):
```php
<input type="text" name="name" 
       value="<?= \App\Middleware\ValidationMiddleware::getOldInput('name', '') ?>">
```

#### Custom Messages:
```php
$router->post('/products', 'ProductController@store', [
    new ValidationMiddleware(
        // Rules
        [
            'name' => 'required|min:3',
            'price' => 'required|numeric'
        ],
        // Custom messages
        [
            'name.required' => 'กรุณากรอกชื่อสินค้า',
            'name.min' => 'ชื่อสินค้าต้องมีอย่างน้อย 3 ตัวอักษร',
            'price.required' => 'กรุณากรอกราคา',
            'price.numeric' => 'ราคาต้องเป็นตัวเลข'
        ]
    )
]);
```

#### Response สำหรับ API:
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "name": "name จำเป็นต้องกรอก",
        "price": "price ต้องเป็นตัวเลข"
    }
}
```

#### Response สำหรับ Web:
- เก็บ errors ใน `$_SESSION['validation_errors']`
- เก็บ old input ใน `$_SESSION['old_input']`
- Redirect กลับหน้าเดิม

#### ไฟล์: `app/Middleware/ValidationMiddleware.php`

---

## การรวม Middleware หลายตัว

### ตัวอย่างที่ 1: Form สมบูรณ์
```php
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\ValidationMiddleware;
use App\Middleware\RateLimitMiddleware;

$router->post('/products', 'ProductController@store', [
    new RateLimitMiddleware(10, 60),        // จำกัด 10 requests/นาที
    new AuthMiddleware(),                    // ต้องล็อกอิน
    new CsrfMiddleware(),                    // ป้องกัน CSRF
    new ValidationMiddleware([               // ตรวจสอบข้อมูล
        'name' => 'required|min:3|max:100',
        'price' => 'required|numeric'
    ])
]);
```

### ตัวอย่างที่ 2: API Endpoint
```php
use App\Middleware\ApiKeyMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\LoggingMiddleware;

$router->post('/api/v1/orders', 'Api\V1\OrderController@store', [
    new CorsMiddleware(),                    // จัดการ CORS
    new LoggingMiddleware(true),             // บันทึก detailed logs
    new RateLimitMiddleware(100, 60),        // จำกัด 100 requests/นาที
    new ApiKeyMiddleware(),                  // ตรวจสอบ API key
]);
```

### ตัวอย่างที่ 3: Admin Route
```php
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;

$router->group(['prefix' => '/admin', 'middleware' => [
    new AuthMiddleware(),
    new RoleMiddleware('admin')
]], function($router) {
    $router->get('/dashboard', 'Admin\DashboardController@index');
    
    $router->post('/users', 'Admin\UserController@store', [
        new CsrfMiddleware(),
        new ValidationMiddleware([
            'username' => 'required|alphanumeric|min:3',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:user,manager,admin'
        ])
    ]);
});
```

---

## Best Practices

### 1. ลำดับ Middleware
```
1. MaintenanceMiddleware    (ตรวจสอบก่อนทุกอย่าง)
2. CorsMiddleware           (สำหรับ API)
3. LoggingMiddleware        (บันทึกก่อนดำเนินการอื่น)
4. RateLimitMiddleware      (จำกัดอัตราก่อนประมวลผล)
5. AuthMiddleware           (ตรวจสอบการเข้าสู่ระบบ)
6. RoleMiddleware           (ตรวจสอบสิทธิ์)
7. CsrfMiddleware           (ป้องกัน CSRF)
8. ValidationMiddleware     (ตรวจสอบข้อมูลสุดท้าย)
```

### 2. ความปลอดภัย
```php
// ✅ ดี - ใช้หลาย middleware
$router->post('/orders', 'OrderController@store', [
    new AuthMiddleware(),
    new CsrfMiddleware(),
    new ValidationMiddleware([...])
]);

// ❌ ไม่ดี - ขาด CSRF protection
$router->post('/orders', 'OrderController@store', [
    new AuthMiddleware()
]);
```

### 3. API vs Web Routes
```php
// API Routes - ใช้ API key และ CORS
$router->group(['prefix' => '/api', 'middleware' => [
    new CorsMiddleware(),
    new ApiKeyMiddleware()
]], function($router) {
    // API routes...
});

// Web Routes - ใช้ Auth และ CSRF
$router->group(['middleware' => [
    new AuthMiddleware(),
    new CsrfMiddleware()
]], function($router) {
    // Web routes...
});
```

### 4. Performance
```php
// ✅ ดี - จำกัด logging เฉพาะที่จำเป็น
$logger = new LoggingMiddleware(false, false);
$logger->addExceptRoutes(['/health', '/ping', '/assets/*']);

// ✅ ดี - ใช้ Cache สำหรับ rate limiting
$rateLimiter = new RateLimitMiddleware(100, 60); // จะใช้ Cache ถ้ามี

// ❌ ระวัง - logging ทุก request อาจช้า
$router->group(['middleware' => [new LoggingMiddleware(true, true)]], ...);
```

### 5. Environment Configuration
```bash
# .env
API_KEY=your-production-api-key
CORS_ALLOWED_ORIGINS=https://myapp.com,https://admin.myapp.com
MAINTENANCE_MODE=false
MAINTENANCE_ALLOWED_IPS=127.0.0.1
LOG_DETAILED=false
LOG_REQUEST_BODY=false
RATE_LIMIT_MAX=60
RATE_LIMIT_WINDOW=60
```

---

## Troubleshooting

### ปัญหา: Middleware ไม่ทำงาน
```php
// ตรวจสอบว่า middleware คืนค่า true/false
public function handle(): bool
{
    // ต้องคืนค่า true เพื่อดำเนินการต่อ
    return true;
    
    // หรือ false เพื่อหยุด
    return false;
}
```

### ปัญหา: CSRF Token Invalid
```php
// ตรวจสอบว่า session_start() ถูกเรียก
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบว่ามี token ในฟอร์ม
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
```

### ปัญหา: Rate Limit ไม่ทำงาน
```php
// ตรวจสอบว่ามี Cache class
// ถ้าไม่มี จะใช้ Session แทน (อาจไม่แม่นยำ)

// ลอง clear cache
rm -rf storage/cache/*
```

### ปัญหา: Validation Errors ไม่แสดง
```php
// ตรวจสอบว่า session_start() ถูกเรียก
session_start();

// ใช้ static methods เพื่อดึง errors
$errors = ValidationMiddleware::getErrors();
var_dump($errors);
```

---

## สรุป

Middleware เป็นเครื่องมือที่ทรงพลังสำหรับ:
- 🔒 **ความปลอดภัย**: Auth, CSRF, API Keys, Rate Limiting
- ✅ **การตรวจสอบ**: Validation, Roles, Permissions
- 📝 **การจัดการ**: Logging, CORS, Maintenance Mode

ใช้ Middleware อย่างเหมาะสมเพื่อสร้างแอปพลิเคชันที่ปลอดภัยและมีประสิทธิภาพ! 🚀

---

## อ้างอิง

- [CORE_USAGE.md](CORE_USAGE.md) - การใช้งาน Core classes
- [PROJECT_STRUCTURE.md](PROJECT_STRUCTURE.md) - โครงสร้างโปรเจค
- [app/Core/Middleware.php](../app/Core/Middleware.php) - Base Middleware class
- [app/Middleware/](../app/Middleware/) - Middleware ทั้งหมด
