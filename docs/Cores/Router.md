# Router Usage Guide

เอกสารนี้อธิบายการลงทะเบียนและจัดการ routes ด้วย `App\Core\Router` ตามความสามารถที่มีอยู่ในโปรเจกต์ปัจจุบัน

## 1. ภาพรวม

Router จับคู่ HTTP method และ URL กับ Controller method จากนั้นเรียก middleware ก่อนส่งพารามิเตอร์ให้ Controller

ลำดับโดยย่อ:

1. ลงทะเบียน routes
2. รับ HTTP method และ URI
3. หา route ที่ตรงกัน
4. เรียก middleware ตามลำดับ
5. เรียก Controller พร้อม route parameters
6. ส่ง `Response` กลับ หาก Controller คืน `Response` หรือ string

## 2. เริ่มใช้งาน

ใน front controller สร้าง Router, โหลดไฟล์ routes แล้วเรียก `dispatch()`:

```php
use App\Core\Router;

$router = new Router();
require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../routes/api.php';

$router->dispatch();
```

## 3. ลงทะเบียน routes

Router รองรับ `GET`, `POST`, `PUT` และ `DELETE`:

```php
$router->get('/products', 'App\\Controllers\\ProductController@index');
$router->post('/products', 'App\\Controllers\\ProductController@store');
$router->put('/products/{id}', 'App\\Controllers\\ProductController@update');
$router->delete('/products/{id}', 'App\\Controllers\\ProductController@destroy');
```

Controller ระบุด้วยรูปแบบ `Full\\Namespace\\Controller@method` ส่วน method ของ Router คืน `RouteDefinition` เพื่อให้ต่อ chain constraints ได้

### Method override

ฟอร์ม HTML ใช้ `POST` และส่ง `_method` เพื่อเรียก route แบบ `PUT` หรือ `DELETE` ได้:

```html
<form method="POST" action="/products/15">
    <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="_csrf_token" value="...">
</form>
```

Router จะใช้ค่า `_method` เมื่อ request method จริงเป็น `POST` จากนั้นจับคู่ route ด้วย method ที่ override แล้ว ควรใช้ CSRF middleware กับคำขอที่เปลี่ยนข้อมูล

## 4. Route parameters

ใช้ `{name}` เพื่อระบุ segment ที่เปลี่ยนแปลงได้:

```php
$router->get('/products/{id}', 'App\\Controllers\\ProductController@show')
    ->whereNumber('id');
```

สำหรับ URL `/products/15` Controller จะได้รับค่า `15` เป็น route parameter ตำแหน่งแรก โดยค่าที่ Router จับจาก URL เป็น string; หาก Controller ระบุ type เช่น `int` PHP อาจแปลงค่าตามกฎ type coercion ของ PHP แต่ constraint มีหน้าที่กรองรูปแบบ URL ไม่ใช่แปลงชนิดหรือยืนยันว่าข้อมูลมีอยู่จริง

Route ที่มีหลาย parameters จะส่งค่าให้ Controller ตามลำดับที่ปรากฏใน path:

```php
$router->get(
    '/users/{userId}/files/{fileId}',
    'App\\Controllers\\FileController@show'
)->whereNumber('userId')->whereUuid('fileId');
```

```php
use App\Core\Response;

public function show(int $userId, string $fileId): Response
{
    // ใช้ $userId และ $fileId
}
```

หาก Controller รับ `App\Core\Request` เป็นพารามิเตอร์แรก Router จะ inject Request ให้อัตโนมัติ จากนั้นจึงส่ง route parameters ตามลำดับ:

```php
use App\Core\Request;
use App\Core\Response;

public function show(Request $request, string $id): Response
{
    $search = $request->get('search');
}
```

## 5. Parameter constraints

Constraint ใช้กำหนดว่าค่าใดจับคู่กับ route ได้:

```php
$router->get('/posts/{postId}/{slug}', 'App\\Controllers\\PostController@show')
    ->whereNumber('postId')
    ->where('slug', '[a-z0-9-]+');
```

Methods ที่มีใน `RouteDefinition`:

| Method | เงื่อนไข |
| --- | --- |
| `where($parameter, $regex)` | กำหนด regex เอง |
| `where([...])` | กำหนด regex หลาย parameters ด้วย array `[ชื่อ => regex]` |
| `whereNumber($parameter)` | ตัวเลข 0-9 อย่างน้อยหนึ่งหลัก |
| `whereAlpha($parameter)` | ตัวอักษรภาษาอังกฤษอย่างน้อยหนึ่งตัว |
| `whereAlphaNumeric($parameter)` | ตัวอักษรภาษาอังกฤษหรือตัวเลขอย่างน้อยหนึ่งตัว |
| `whereUuid($parameter)` | รูปแบบ UUID 8-4-4-4-12 โดยรับเลขฐานสิบหกทั้งตัวพิมพ์เล็กและใหญ่ |
| `whereUlid($parameter)` | รูปแบบ ULID 26 ตัวอักษร |
| `whereIn($parameter, $values)` | จำกัดให้ตรงกับค่าใดค่าหนึ่งในรายการ |

กำหนดเงื่อนไขหลาย parameters ด้วย `where()` หรือ chain methods ได้:

```php
$router->get('/posts/{id}/{status}', 'App\\Controllers\\PostController@show')
    ->where([
        'id' => '[0-9]+',
        'status' => '[a-z]+',
    ]);

$router->get('/posts/{status}', 'App\\Controllers\\PostController@byStatus')
    ->whereIn('status', ['draft', 'published']);
```

ถ้า URL ไม่ตรง constraint route นั้นจะไม่ match และ Router จะจัดการเป็น 404 เว้นแต่มี route อื่นที่ตรงกัน

## 6. Route groups

`group($prefix, $middleware, $callback)` รวม prefix และ middleware ให้ routes ภายใน รองรับ groups ซ้อนกันได้ Middleware ของกลุ่มชั้นนอกจะทำงานก่อน middleware ของกลุ่มชั้นในและ middleware ที่ระบุบน route:

```php
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;

$router->group('/admin', [AuthMiddleware::class], function (Router $router): void {
    $router->get('/dashboard', 'App\\Controllers\\AdminController@index');

    $router->group('/users', [CsrfMiddleware::class], function (Router $router): void {
        $router->post('/', 'App\\Controllers\\AdminUserController@store');
        $router->get('/{id}', 'App\\Controllers\\AdminUserController@show')
            ->whereNumber('id');
    });
});
```

ตัวอย่างนี้ลงทะเบียนเป็น `/admin/dashboard`, `/admin/users` และ `/admin/users/{id}` โดย route ภายในกลุ่มซ้อนจะได้รับ middleware ทั้งจากกลุ่มนอกและกลุ่มใน เครื่องหมาย slash รอบ path จะถูกตัดและประกอบใหม่

กลุ่มปัจจุบันรองรับเฉพาะ prefix และ middleware; ยังไม่มี group options สำหรับ name, domain หรือ constraints ที่สืบทอดทั้งกลุ่ม

## 7. Middleware

กำหนด middleware เป็นอาร์เรย์ใน argument ที่สามของ route:

```php
use App\Middleware\AuthMiddleware;

$router->get(
    '/dashboard',
    'App\\Controllers\\DashboardController@index',
    [AuthMiddleware::class]
);
```

Middleware ทำงานตามลำดับที่กำหนด:

- `handle()` คืน `true` เพื่อไป middleware หรือ Controller ถัดไป
- คืน `false` เพื่อหยุดการทำงาน
- คืน `Response` เพื่อส่ง response และหยุดการทำงาน
- หลัง Controller ทำงานสำเร็จ Router เรียก `after()` ย้อนลำดับ สำหรับ middleware ที่มี method นี้

ส่ง constructor arguments ให้ middleware ได้ เช่น:

```php
use App\Middleware\RoleMiddleware;

$router->get('/admin', 'App\\Controllers\\AdminController@index', [
    [RoleMiddleware::class, 'admin'],
]);

$router->get('/reports', 'App\\Controllers\\ReportController@index', [
    [RoleMiddleware::class, [['admin', 'manager']]],
]);
```

รูปแบบแรกส่ง `'admin'` เป็น argument เดียว ส่วนรูปแบบที่สองส่ง array `['admin', 'manager']` เป็น argument เดียวให้ constructor โดยต้องใส่ array ซ้อนตามตัวอย่าง เพราะ Router กระจายค่าจากรายการ middleware เข้า constructor

## 8. ลำดับการจับคู่และ HTTP errors

Router ตรวจ routes ตามลำดับที่ลงทะเบียนและใช้ route แรกที่ match ดังนั้นควรลงทะเบียน static routes ก่อน dynamic routes ที่อาจครอบคลุม path เดียวกัน:

```php
$router->get('/users/create', 'App\\Controllers\\UserController@create');
$router->get('/users/{id}', 'App\\Controllers\\UserController@show')->whereNumber('id');
```

- `404 Not Found`: ไม่มี route ที่ตรงกับ method และ URI
- `405 Method Not Allowed`: URI ตรงกับ route แต่ method ไม่ตรง Router ส่ง `Allow` header พร้อม methods ที่รองรับ

## 9. Controller และ Response

Controller method ต้องตรงกับชื่อหลัง `@`:

```php
namespace App\Controllers;

use App\Core\Response;

class ProductController
{
    public function index(): Response
    {
        return Response::json(['items' => []]);
    }

    public function show(string $id): string
    {
        return '<h1>Product ' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '</h1>';
    }
}
```

Router ส่ง `Response` ที่ Controller คืนกลับไปยังผู้ใช้ และห่อ string ที่ไม่ว่างเป็น HTML response ค่า return แบบอื่นหรือ string ว่างจะไม่สร้าง response จาก Controller

หากเกิด `Throwable` ขณะเรียก Controller Router จะบันทึก exception; คำขอ API ได้ response 500 ส่วนคำขอ Web จะเก็บข้อความ error ใน session และ redirect กลับ

## 10. การอ่าน URI

ก่อนจับคู่ Router จะตัด query string, base path ของการติดตั้งใน subdirectory และ slash ด้านหน้า/ด้านหลัง ตัวอย่าง:

```text
/myapp/products/10?search=book  ->  /products/10
```

Query parameters ไม่ใช่ route parameters; ให้อ่านผ่าน `Request` เช่น `$request->get('search')` หรือ `$request->input('search')`

## 11. แนวทางจัดระเบียบ routes

- แยกไฟล์ Web และ API ตามที่โปรเจกต์ใช้อยู่ เช่น `routes/web.php` และ `routes/api.php`
- แบ่งกลุ่ม routes ตาม feature หรือสิทธิ์การเข้าถึง
- ใช้ group เมื่อหลาย routes ใช้ prefix หรือ middleware ชุดเดียวกัน
- วาง static routes ก่อน dynamic routes และกำหนด constraints ให้ parameters
- ใช้ HTTP method ให้ตรงกับการกระทำ และใช้ CSRF middleware สำหรับ Web requests ที่เปลี่ยนข้อมูล
- เก็บ business logic ไว้ใน Controller หรือ service ไม่ใส่ใน route definition

## 12. ความสามารถที่ยังไม่มี

Router ปัจจุบันยังไม่มี `PATCH`, `HEAD`, `OPTIONS`, optional parameters เช่น `{id?}`, named routes/URL generation, resource routes, model binding หรือ fallback routes กลุ่ม route ยังไม่มี name/domain/group-wide constraints ด้วย หากต้องใช้ความสามารถเหล่านี้ต้องเพิ่มใน Router ก่อน