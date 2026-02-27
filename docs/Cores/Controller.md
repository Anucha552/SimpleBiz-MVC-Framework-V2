# Controller Base Class Guide

คู่มือนี้อธิบายการใช้งานคลาส Controller ซึ่งเป็นคลาสแม่สำหรับตัวควบคุมทั้งหมดในระบบ

Controller ถูกออกแบบตามแนวคิด Thin Controller คือ ทำหน้าที่ควบคุมการไหลของข้อมูลเท่านั้น และมอบหมายตรรกะธุรกิจทั้งหมดให้ Model

## แนวคิดของ Controller

Controller ทำหน้าที่เป็นตัวกลางระหว่าง HTTP Request และ Business Logic

### หน้าที่หลัก

- ตรวจสอบความถูกต้องของคำขอ (Validation)
- เรียกใช้ Model เพื่อประมวลผลตรรกะทางธุรกิจ
- ส่งข้อมูลไปยัง View
- คืนค่า HTTP Response เช่น HTML, JSON หรือ Redirect

### สิ่งที่ Controller ไม่ควรทำ

- เขียนตรรกะธุรกิจที่ซับซ้อน
- เข้าถึงฐานข้อมูลโดยตรง
- ทำการคำนวณหรือประมวลผลหนัก ๆ

หลักคิดง่าย ๆ:

Controller = ควบคุมทิศทาง  
Model = จัดการตรรกะ  
View = แสดงผล

## การใช้งานเมธอดหลัก

### 1. แสดงผล View

ใช้เมื่อคุณต้องการ render หน้า HTML ทันที

```php
$this->view('home', ['name' => 'John']);
```

เหมาะกับกรณีที่ Router ไม่ได้บังคับให้ return Response

### 2. ส่ง View พร้อม Response

ใช้เมื่อ Router รองรับการ return Response object

```php
return $this->responseView(
    'home',
    ['name' => 'John'],
    'main_layout',
    200
);
```

รองรับ: layout, status code, cache control

ตัวอย่างใส่ cache:

```php
return $this->responseView('home', [], null, 200, 60); // แคช 60 วินาที
```

### 3. Redirect

เปลี่ยนเส้นทางผู้ใช้

```php
return $this->redirect('/home', 302);
```

Redirect ถาวร:

```php
return $this->redirect('/home', 301);
```

ย้อนกลับหน้าก่อนหน้า:

```php
return $this->back();
```

### 4. JSON Response (API)

กรณี API สำเร็จ:

```php
return $this->responseJson(
    true,
    ['id' => 1, 'name' => 'John'],
    'User retrieved successfully'
);
```

กรณี Error:

```php
return $this->responseJson(
    false,
    null,
    'Validation failed',
    ['email' => 'Email is required'],
    422
);
```

สร้างข้อมูลใหม่ (201 Created):

```php
return $this->respondCreated(
    ['id' => 1],
    'User created successfully'
);
```

ไม่มีเนื้อหา (204 No Content):

```php
return $this->noContent();
```

### 5. การเข้าถึง Request

ดึงค่าทั้งหมด:

```php
$data = $this->all();
```

ดึงเฉพาะบางค่า:

```php
$data = $this->only(['username', 'email']);
```

ดึงค่าเดียว:

```php
$username = $this->input('username');
```

กำหนดค่า default:

```php
$page = $this->input('page', 1);
```

ดึงไฟล์:

```php
$file = $this->file('avatar');
```

### 6. Authentication

ตรวจสอบว่า login หรือไม่:

```php
if (!$this->isAuthenticated()) {
    return $this->redirect('/login');
}
```

ดึง user id:

```php
$userId = $this->getUserId();
```

ดึง user object:

```php
$user = $this->currentUser();
```

ตรวจสอบสิทธิ์แบบสั้น:

```php
return $this->authorize(
    $this->isAuthenticated(),
    '/login'
);
```

ถ้า allowed = true → return null  
ถ้า false → redirect

### 7. Flash Message และ Old Input

ตั้งค่า flash message:

```php
$this->flash('success', 'Saved successfully');
```

แฟลช input:

```php
$this->flashInput($this->all());
```

เหมาะกับกรณี validation error แล้ว redirect กลับ

### 8. CSRF

สร้าง hidden field สำหรับฟอร์ม:

```php
echo $this->csrfField();
```

สร้าง meta tag:

```php
echo $this->csrfMeta();
```

### 9. URL & Asset

สร้าง asset URL:

```php
$css = $this->asset('css/app.css');
```

สร้าง route URL พร้อม parameter:

```php
$url = $this->route('/users/{id}', ['id' => 1]);
```

### 10. Pagination

แบ่งหน้า array:

```php
$paginated = $this->paginate($items, 1, 15); // page เริ่มต้นที่ 1
```

### 11. Share Data กับทุก View

แชร์ข้อมูล:

```php
$this->share('appName', 'MyApp');
```

อ่านค่าที่แชร์:

```php
$appName = $this->shared('appName');
```

## ตัวอย่าง Controller เต็มรูปแบบ

```php
use App\\Core\\Controller;
use App\\Models\\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();

        return $this->responseView(
            'users.index',
            compact('users')
        );
    }

    public function store()
    {
        $data = $this->only(['name', 'email']);

        // Business logic ควรอยู่ใน Model
        User::create($data);

        $this->flash('success', 'User created successfully');

        return $this->redirect('/users');
    }
}
```

## Best Practice

ทำแบบนี้:

- Controller บาง
- Model จัดการตรรกะ
- แยกความรับผิดชอบชัดเจน
- ใช้ Response object แทน echo/exit

อย่าทำแบบนี้:

- Query DB ตรงใน Controller จำนวนมาก
- เขียน Business Logic ยาว ๆ ใน Controller
- ทำ Validation แบบกระจัดกระจาย

## สรุป

Controller ที่ดีควร:

- รับ Request
- ตรวจสอบข้อมูล
- เรียก Model
- คืน Response

ยิ่ง Controller บาง ระบบยิ่งดูแลง่าย และยิ่งแยกความรับผิดชอบชัดเจน ระบบยิ่งขยายได้ดีในอนาคต
