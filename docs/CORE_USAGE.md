# คู่มือการใช้งาน App Core Classes

เอกสารนี้อธิบายการใช้งานคลาสหลักทั้งหมดใน `app/Core` พร้อมตัวอย่างโค้ดที่ละเอียด

## สารบัญ

1. [Request](#1-request) - จัดการ HTTP Request
2. [Validator](#2-validator) - ตรวจสอบข้อมูล
3. [Session](#3-session) - จัดการ Session
4. [Auth](#4-auth) - ระบบยืนยันตัวตน
5. [Model](#5-model) - Base Model สำหรับฐานข้อมูล
6. [FileUpload](#6-fileupload) - อัปโหลดไฟล์
7. [Pagination](#7-pagination) - แบ่งหน้าข้อมูล
8. [Cache](#8-cache) - ระบบ Cache
9. [Controller](#9-controller) - Base Controller
10. [Router](#10-router) - ระบบ Routing
11. [View](#11-view) - Template Engine
12. [Database](#12-database) - การเชื่อมต่อฐานข้อมูล
13. [Logger](#13-logger) - ระบบบันทึกข้อมูล
14. [ErrorHandler](#14-errorhandler) - จัดการข้อผิดพลาด
15. [Mail](#15-mail) - ส่งอีเมล
16. [Migration](#16-migration) - Migration Base Class
17. [MigrationRunner](#17-migrationrunner) - รันระบบ Migration
18. [Seeder](#18-seeder) - สร้างข้อมูลตัวอย่าง
19. [Middleware](#19-middleware) - Base Middleware

---

## 1. Request

คลาส `Request` ใช้สำหรับจัดการคำขอ HTTP ทั้งหมด

### การใช้งานพื้นฐาน

```php
use App\Core\Request;

// สร้าง Request instance
$request = new Request();

// รับข้อมูล GET
$id = $request->get('id');
$page = $request->get('page', 1); // ค่าเริ่มต้นเป็น 1

// รับข้อมูล POST
$username = $request->post('username');
$password = $request->post('password');

// รับข้อมูล (POST, JSON, หรือ PUT/DELETE)
$email = $request->input('email');
$data = $request->input(); // รับทั้งหมด

// รับข้อมูลทั้งหมด (GET + POST + JSON)
$allData = $request->all();
```

### ตรวจสอบข้อมูล

```php
// ตรวจสอบว่ามีคีย์หรือไม่
if ($request->has('email')) {
    // มี email
}

// ตรวจสอบหลายคีย์
if ($request->hasAll(['username', 'password', 'email'])) {
    // มีครบทั้งหมด
}
```

### รับข้อมูลเฉพาะที่ต้องการ

```php
// รับเฉพาะคีย์ที่ระบุ
$credentials = $request->only(['username', 'password']);

// รับทุกอย่างยกเว้นคีย์ที่ระบุ
$userData = $request->except(['password', 'password_confirm']);
```

### จัดการไฟล์

```php
// ตรวจสอบว่ามีไฟล์หรือไม่
if ($request->hasFile('avatar')) {
    $file = $request->file('avatar');
    // $file เป็น array ที่มี name, type, tmp_name, error, size
}

// รับไฟล์ทั้งหมด
$files = $request->file();
```

### HTTP Method

```php
// รับ HTTP method
$method = $request->method(); // GET, POST, PUT, DELETE

// ตรวจสอบ method
if ($request->isPost()) {
    // เป็น POST request
}

if ($request->isGet()) {
    // เป็น GET request
}

// ตรวจสอบ AJAX
if ($request->isAjax()) {
    // เป็น AJAX request
}

// ตรวจสอบ JSON
if ($request->isJson()) {
    // Content-Type เป็น application/json
}
```

### Headers

```php
// รับ header
$userAgent = $request->header('User-Agent');
$contentType = $request->header('Content-Type');

// รับ Bearer Token
$token = $request->bearerToken();
```

### URL & IP

```php
// รับ URI
$uri = $request->uri(); // /products/123

// รับ URL เต็ม
$url = $request->url(); // http://example.com/products/123

// ตรวจสอบ HTTPS
if ($request->isSecure()) {
    // เป็น HTTPS
}

// รับ IP address
$ip = $request->ip();

// รับ User Agent
$userAgent = $request->userAgent();
```

### ตัวอย่างในตัวควบคุม

```php
class ProductController extends Controller
{
    public function store()
    {
        $request = new Request();
        
        // ตรวจสอบว่าเป็น POST request
        if (!$request->isPost()) {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        // รับข้อมูลสินค้า
        $productData = $request->only(['name', 'price', 'description']);
        
        // ตรวจสอบไฟล์รูปภาพ
        if ($request->hasFile('image')) {
            // จัดการอัปโหลดรูปภาพ
        }
        
        // บันทึกข้อมูล
        Product::create($productData);
    }
}
```

---

## 2. Validator

คลาส `Validator` ใช้สำหรับตรวจสอบความถูกต้องของข้อมูล

### การใช้งานพื้นฐาน

```php
use App\Core\Validator;

// ข้อมูลที่ต้องการตรวจสอบ
$data = [
    'username' => 'john_doe',
    'email' => 'john@example.com',
    'password' => 'secret123',
    'age' => 25
];

// กฎการตรวจสอบ
$rules = [
    'username' => 'required|alphanumeric|min:3|max:20',
    'email' => 'required|email',
    'password' => 'required|min:8',
    'age' => 'required|numeric|between:18,100'
];

// สร้าง validator
$validator = new Validator($data, $rules);

// ตรวจสอบ
if ($validator->fails()) {
    $errors = $validator->errors();
    // แสดงข้อความแสดงข้อผิดพลาด
    foreach ($errors as $field => $messages) {
        echo "$field: " . implode(', ', $messages) . "<br>";
    }
} else {
    // ข้อมูลถูกต้อง
    echo "ข้อมูลถูกต้องทั้งหมด!";
}
```

### กฎการตรวจสอบที่มีให้ใช้

```php
// required - ต้องมีค่า
'field' => 'required'

// email - รูปแบบอีเมล
'email' => 'email'

// min:n - ความยาวขั้นต่ำ
'password' => 'min:8'

// max:n - ความยาวสูงสุด
'username' => 'max:20'

// numeric - ต้องเป็นตัวเลข
'age' => 'numeric'

// integer - ต้องเป็นจำนวนเต็ม
'quantity' => 'integer'

// alpha - เฉพาะตัวอักษร
'name' => 'alpha'

// alphanumeric - ตัวอักษรและตัวเลข
'username' => 'alphanumeric'

// url - รูปแบบ URL
'website' => 'url'

// match:field - ต้องตรงกับฟิลด์อื่น
'password_confirm' => 'match:password'

// in:value1,value2 - ต้องอยู่ในรายการ
'status' => 'in:active,inactive,pending'

// regex:pattern - ตรงกับ pattern
'code' => 'regex:/^[A-Z]{3}[0-9]{3}$/'

// date - วันที่ที่ถูกต้อง
'birthdate' => 'date'

// phone - เบอร์โทรศัพท์ไทย
'phone' => 'phone'

// between:min,max - อยู่ระหว่างค่า
'price' => 'between:100,10000'

// unique:table,column,except_id - ไม่ซ้ำในฐานข้อมูล
'email' => 'unique:users,email'
'email' => 'unique:users,email,5' // ยกเว้น id=5

// exists:table,column - ต้องมีในฐานข้อมูล
'category_id' => 'exists:categories,id'
```

### ตัวอย่างการใช้งานจริง

```php
// ฟอร์มลงทะเบียน
class AuthController extends Controller
{
    public function register()
    {
        $request = new Request();
        
        if ($request->isPost()) {
            $data = $request->input();
            
            $validator = new Validator($data, [
                'username' => 'required|alphanumeric|min:3|max:20|unique:users,username',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8',
                'password_confirm' => 'required|match:password',
                'phone' => 'required|phone',
                'age' => 'required|integer|between:18,100'
            ]);
            
            if ($validator->fails()) {
                // บันทึก errors ใน session
                Session::flash('errors', $validator->errors());
                Session::flashInput($data);
                
                // redirect กลับ
                header('Location: /register');
                exit;
            }
            
            // สร้างบัญชีผู้ใช้
            User::create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Auth::hash($data['password'])
            ]);
            
            Session::flash('success', 'ลงทะเบียนสำเร็จ');
            header('Location: /login');
            exit;
        }
    }
}
```

### ข้อความแสดงข้อผิดพลาดแบบกำหนดเอง

```php
$customMessages = [
    'username.required' => 'กรุณากรอกชื่อผู้ใช้',
    'username.min' => 'ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร',
    'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
    'password.min' => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร'
];

$validator = new Validator($data, $rules, $customMessages);
```

---

## 3. Session

คลาส `Session` ใช้สำหรับจัดการ session และ flash messages

### การใช้งานพื้นฐาน

```php
use App\Core\Session;

// เริ่ม session
Session::start();

// ตั้งค่าข้อมูล
Session::set('user_id', 123);
Session::set('username', 'john_doe');

// รับข้อมูล
$userId = Session::get('user_id');
$username = Session::get('username', 'Guest'); // ค่าเริ่มต้น

// ตรวจสอบว่ามีหรือไม่
if (Session::has('user_id')) {
    // มี user_id ใน session
}

// ลบข้อมูล
Session::remove('user_id');

// รับและลบ
$value = Session::pull('temp_data');

// รับทั้งหมด
$all = Session::all();

// ล้างทั้งหมด
Session::clear();

// ทำลาย session
Session::destroy();
```

### Flash Messages

Flash messages คือข้อความที่แสดงครั้งเดียวแล้วหายไป เหมาะสำหรับแสดงข้อความ success/error หลัง redirect

```php
// ตั้งค่า flash message
Session::flash('success', 'บันทึกข้อมูลสำเร็จ');
Session::flash('error', 'เกิดข้อผิดพลาด');
Session::flash('info', 'กรุณาตรวจสอบข้อมูล');

// รับ flash message (แสดงครั้งเดียว)
$success = Session::getFlash('success');
$error = Session::getFlash('error');

// ตรวจสอบว่ามี flash หรือไม่
if (Session::hasFlash('success')) {
    // มีข้อความ success
}

// รับทั้งหมด
$allFlash = Session::getAllFlash();

// เก็บ flash ไว้อีก 1 request
Session::keepFlash(['success', 'error']);
Session::keepFlash(); // เก็บทั้งหมด
```

### Old Input (สำหรับฟอร์ม)

```php
// บันทึก input เก่า (หลังจาก validation ไม่ผ่าน)
Session::flashInput($_POST);

// รับ old input
$username = Session::old('username');
$email = Session::old('email', 'default@example.com');

// ใช้ใน view
<input type="text" name="username" value="<?= Session::old('username') ?>">
```

### CSRF Protection

```php
// สร้าง CSRF token
$token = Session::generateCsrfToken();

// รับ token ปัจจุบัน
$token = Session::getCsrfToken();

// ตรวจสอบ token
$isValid = Session::verifyCsrfToken($_POST['_csrf_token']);

// สร้าง hidden input สำหรับฟอร์ม
echo Session::csrfField();
// Output: <input type="hidden" name="_csrf_token" value="...">

// สร้าง meta tag สำหรับ AJAX
echo Session::csrfMeta();
// Output: <meta name="csrf-token" content="...">
```

### ตัวอย่างการใช้งานจริง

```php
// ในตัวควบคุม
class ProductController extends Controller
{
    public function store()
    {
        Session::start();
        
        // ตรวจสอบ CSRF token
        $request = new Request();
        if (!Session::verifyCsrfToken($request->post('_csrf_token'))) {
            Session::flash('error', 'Invalid CSRF token');
            header('Location: /products/create');
            exit;
        }
        
        // ตรวจสอบข้อมูล
        $validator = new Validator($request->input(), [
            'name' => 'required|min:3',
            'price' => 'required|numeric'
        ]);
        
        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            Session::flashInput($request->input());
            header('Location: /products/create');
            exit;
        }
        
        // บันทึกสินค้า
        Product::create($request->input());
        
        Session::flash('success', 'สร้างสินค้าสำเร็จ');
        header('Location: /products');
        exit;
    }
}

// ในฟอร์ม (view)
?>
<form method="POST" action="/products/store">
    <?= Session::csrfField() ?>
    
    <?php if (Session::hasFlash('success')): ?>
        <div class="alert alert-success">
            <?= Session::getFlash('success') ?>
        </div>
    <?php endif; ?>
    
    <?php if (Session::hasFlash('errors')): ?>
        <div class="alert alert-danger">
            <?php foreach (Session::getFlash('errors') as $field => $messages): ?>
                <?= implode('<br>', $messages) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <input type="text" name="name" value="<?= Session::old('name') ?>">
    <input type="number" name="price" value="<?= Session::old('price') ?>">
    
    <button type="submit">บันทึก</button>
</form>
```

---

## 4. Auth

คลาส `Auth` ใช้สำหรับจัดการการยืนยันตัวตนและการเข้าสู่ระบบ

### การใช้งานพื้นฐาน

```php
use App\Core\Auth;

// เข้าสู่ระบบ
$credentials = [
    'username' => 'john_doe',
    'password' => 'secret123'
];

if (Auth::attempt($credentials)) {
    // เข้าสู่ระบบสำเร็จ
    echo "ยินดีต้อนรับ!";
} else {
    // เข้าสู่ระบบไม่สำเร็จ
    echo "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
}

// เข้าสู่ระบบพร้อม remember me
Auth::attempt($credentials, true);
```

### ตรวจสอบสถานะการเข้าสู่ระบบ

```php
// ตรวจสอบว่าเข้าสู่ระบบหรือไม่
if (Auth::check()) {
    // เข้าสู่ระบบแล้ว
    echo "คุณเข้าสู่ระบบแล้ว";
}

// ตรวจสอบว่าเป็นแขกหรือไม่
if (Auth::guest()) {
    // ยังไม่ได้เข้าสู่ระบบ
    echo "กรุณาเข้าสู่ระบบ";
}
```

### รับข้อมูลผู้ใช้

```php
// รับข้อมูลผู้ใช้ที่เข้าสู่ระบบ
$user = Auth::user();

if ($user) {
    echo "สวัสดี " . $user->username;
    echo "อีเมล: " . $user->email;
}

// รับเฉพาะ ID
$userId = Auth::id();
```

### ออกจากระบบ

```php
// ออกจากระบบ
Auth::logout();

// Redirect ไปหน้า login
header('Location: /login');
exit;
```

### เข้าสู่ระบบด้วย ID

```php
// เข้าสู่ระบบด้วย user ID
Auth::loginById(123);

// พร้อม remember me
Auth::loginById(123, true);
```

### Password Hashing

```php
// Hash รหัสผ่าน
$hashedPassword = Auth::hash('secret123');

// ตรวจสอบรหัสผ่าน
if (Auth::verifyPassword('secret123', $hashedPassword)) {
    // รหัสผ่านถูกต้อง
}

// ตรวจสอบว่า hash ต้องทำใหม่หรือไม่
if (Auth::needsRehash($hashedPassword)) {
    // ควร hash ใหม่
}
```

### ตัวอย่างการใช้งานจริง

```php
// Login Controller
class AuthController extends Controller
{
    public function showLogin()
    {
        // ถ้าเข้าสู่ระบบแล้ว redirect ไปหน้าหลัก
        if (Auth::check()) {
            header('Location: /dashboard');
            exit;
        }
        
        $this->view('auth/login');
    }
    
    public function login()
    {
        Session::start();
        $request = new Request();
        
        if (!$request->isPost()) {
            header('Location: /login');
            exit;
        }
        
        // ตรวจสอบข้อมูล
        $validator = new Validator($request->input(), [
            'username' => 'required',
            'password' => 'required'
        ]);
        
        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            Session::flashInput($request->input());
            header('Location: /login');
            exit;
        }
        
        // พยายามเข้าสู่ระบบ
        $credentials = $request->only(['username', 'password']);
        $remember = $request->input('remember') === '1';
        
        if (Auth::attempt($credentials, $remember)) {
            Session::regenerate();
            Session::flash('success', 'เข้าสู่ระบบสำเร็จ');
            header('Location: /dashboard');
            exit;
        }
        
        Session::flash('error', 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
        Session::flashInput($request->except(['password']));
        header('Location: /login');
        exit;
    }
    
    public function logout()
    {
        Auth::logout();
        Session::flash('success', 'ออกจากระบบสำเร็จ');
        header('Location: /login');
        exit;
    }
    
    public function register()
    {
        $request = new Request();
        
        if ($request->isPost()) {
            $data = $request->input();
            
            $validator = new Validator($data, [
                'username' => 'required|alphanumeric|min:3|unique:users,username',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8',
                'password_confirm' => 'required|match:password'
            ]);
            
            if ($validator->fails()) {
                Session::flash('errors', $validator->errors());
                Session::flashInput($data);
                header('Location: /register');
                exit;
            }
            
            // สร้างผู้ใช้ใหม่
            $user = User::create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Auth::hash($data['password'])
            ]);
            
            // เข้าสู่ระบบอัตโนมัติ
            Auth::loginById($user->id);
            
            Session::flash('success', 'ลงทะเบียนสำเร็จ');
            header('Location: /dashboard');
            exit;
        }
        
        $this->view('auth/register');
    }
}

// Middleware สำหรับตรวจสอบการเข้าสู่ระบบ
class AuthMiddleware extends Middleware
{
    public function handle()
    {
        if (Auth::guest()) {
            Session::flash('error', 'กรุณาเข้าสู่ระบบก่อน');
            header('Location: /login');
            exit;
        }
    }
}
```

---

## 5. Model

คลาส `Model` เป็น base class สำหรับโมเดลทั้งหมด รองรับ CRUD operations และ Query Builder

### สร้าง Model

```php
namespace App\Models;

use App\Core\Model;

class Product extends Model
{
    // ชื่อตาราง (ถ้าไม่ระบุจะใช้ชื่อคลาสพหูพจน์)
    protected string $table = 'products';
    
    // Primary key (default: 'id')
    protected string $primaryKey = 'id';
    
    // ฟิลด์ที่สามารถ mass assign ได้
    protected array $fillable = ['name', 'price', 'description', 'category_id'];
    
    // ฟิลด์ที่ห้าม mass assign
    protected array $guarded = ['id'];
    
    // เปิดใช้ timestamps (created_at, updated_at)
    protected bool $timestamps = true;
    
    // เปิดใช้ soft deletes (deleted_at)
    protected bool $softDeletes = false;
}
```

### CRUD Operations

```php
// Create - สร้างข้อมูลใหม่
$product = Product::create([
    'name' => 'iPhone 15',
    'price' => 30000,
    'description' => 'สมาร์ทโฟนรุ่นล่าสุด'
]);

// หรือ
$product = new Product();
$product->name = 'iPhone 15';
$product->price = 30000;
$product->save();

// Read - ดึงข้อมูล
$product = Product::find(1); // ค้นหาด้วย ID
$products = Product::all(); // ดึงทั้งหมด

// Update - แก้ไขข้อมูล
$product = Product::find(1);
$product->price = 25000;
$product->save();

// Delete - ลบข้อมูล
$product = Product::find(1);
$product->delete();
```

### Query Builder

```php
// WHERE
$products = Product::where('price', '>', 10000)->get();
$products = Product::where('category_id', 1)->get();

// ORDER BY
$products = Product::orderBy('price', 'DESC')->get();
$products = Product::orderBy('name', 'ASC')->get();

// LIMIT
$products = Product::limit(10)->get();

// OFFSET
$products = Product::offset(20)->limit(10)->get();

// รวมกัน
$products = Product::where('price', '>', 5000)
                   ->where('category_id', 1)
                   ->orderBy('price', 'DESC')
                   ->limit(10)
                   ->get();

// รับชุดแรก
$product = Product::where('name', 'iPhone 15')->first();

// นับจำนวน
$count = Product::where('price', '>', 10000)->count();
```

### ตัวอย่างการใช้งานจริง

```php
class ProductController extends Controller
{
    // แสดงรายการสินค้า
    public function index()
    {
        $request = new Request();
        $page = $request->get('page', 1);
        $perPage = 20;
        
        // นับจำนวนทั้งหมด
        $total = Product::where('status', 'active')->count();
        
        // ดึงข้อมูล
        $products = Product::where('status', 'active')
                          ->orderBy('created_at', 'DESC')
                          ->limit($perPage)
                          ->offset(($page - 1) * $perPage)
                          ->get();
        
        // สร้าง pagination
        $pagination = new Pagination($total, $perPage, $page);
        
        $this->view('products/index', [
            'products' => $products,
            'pagination' => $pagination
        ]);
    }
    
    // แสดงฟอร์มสร้างสินค้า
    public function create()
    {
        $this->view('products/create');
    }
    
    // บันทึกสินค้าใหม่
    public function store()
    {
        $request = new Request();
        
        $validator = new Validator($request->input(), [
            'name' => 'required|min:3|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'required',
            'category_id' => 'required|exists:categories,id'
        ]);
        
        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            Session::flashInput($request->input());
            header('Location: /products/create');
            exit;
        }
        
        // สร้างสินค้า
        $product = Product::create($request->only([
            'name', 'price', 'description', 'category_id'
        ]));
        
        Session::flash('success', 'สร้างสินค้าสำเร็จ');
        header("Location: /products/{$product->id}");
        exit;
    }
    
    // แสดงรายละเอียดสินค้า
    public function show($id)
    {
        $product = Product::find($id);
        
        if (!$product) {
            Session::flash('error', 'ไม่พบสินค้า');
            header('Location: /products');
            exit;
        }
        
        $this->view('products/show', ['product' => $product]);
    }
    
    // แก้ไขสินค้า
    public function update($id)
    {
        $product = Product::find($id);
        
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'ไม่พบสินค้า']);
            return;
        }
        
        $request = new Request();
        
        $validator = new Validator($request->input(), [
            'name' => 'required|min:3|max:255',
            'price' => 'required|numeric|min:0'
        ]);
        
        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            header("Location: /products/{$id}/edit");
            exit;
        }
        
        // อัปเดต
        $product->name = $request->input('name');
        $product->price = $request->input('price');
        $product->description = $request->input('description');
        $product->save();
        
        Session::flash('success', 'อัปเดตสินค้าสำเร็จ');
        header("Location: /products/{$id}");
        exit;
    }
    
    // ลบสินค้า
    public function destroy($id)
    {
        $product = Product::find($id);
        
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'ไม่พบสินค้า']);
            return;
        }
        
        $product->delete();
        
        Session::flash('success', 'ลบสินค้าสำเร็จ');
        header('Location: /products');
        exit;
    }
}
```

### Helper Methods

```php
// แปลงเป็น array
$array = $product->toArray();

// แปลงเป็น JSON
$json = $product->toJson();

// Refresh จากฐานข้อมูล
$product->refresh();

// ตรวจสอบว่ามีการเปลี่ยนแปลงหรือไม่
if ($product->isDirty()) {
    // มีการเปลี่ยนแปลง
}
```

---

## 6. FileUpload

คลาส `FileUpload` ใช้สำหรับจัดการการอัปโหลดไฟล์อย่างปลอดภัย

### การใช้งานพื้นฐาน

```php
use App\Core\FileUpload;

$uploader = new FileUpload();

// ตั้งค่า
$uploader->setAllowedTypes(['jpg', 'png', 'gif'])
         ->setMaxSize(5 * 1024 * 1024) // 5MB
         ->setUploadPath('uploads/images');

// อัปโหลด
if ($uploader->upload('image_field')) {
    $filename = $uploader->getUploadedFileName();
    echo "อัปโหลดสำเร็จ: {$filename}";
} else {
    echo "Error: " . $uploader->getError();
}
```

### ตั้งค่าต่างๆ

```php
// ชนิดไฟล์ที่อนุญาต
$uploader->setAllowedTypes(['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// ขนาดไฟล์สูงสุด (bytes)
$uploader->setMaxSize(10 * 1024 * 1024); // 10MB

// โฟลเดอร์อัปโหลด
$uploader->setUploadPath('uploads/documents');
```

### อัปโหลดหลายไฟล์

```php
$uploader = new FileUpload();
$uploader->setAllowedTypes(['jpg', 'png'])
         ->setMaxSize(5 * 1024 * 1024)
         ->setUploadPath('uploads/gallery');

$result = $uploader->uploadMultiple('images');

// ตรวจสอบผลลัพธ์
foreach ($result['success'] as $filename) {
    echo "อัปโหลดสำเร็จ: {$filename}<br>";
}

foreach ($result['failed'] as $failed) {
    echo "อัปโหลดไม่สำเร็จ: {$failed['file']} - {$failed['error']}<br>";
}
```

### ตัวอย่างการใช้งานจริง

```php
class ProductController extends Controller
{
    public function store()
    {
        $request = new Request();
        
        // ตรวจสอบข้อมูลสินค้า
        $validator = new Validator($request->input(), [
            'name' => 'required|min:3',
            'price' => 'required|numeric'
        ]);
        
        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            header('Location: /products/create');
            exit;
        }
        
        // อัปโหลดรูปภาพ
        $imagePath = null;
        if ($request->hasFile('image')) {
            $uploader = new FileUpload();
            $uploader->setAllowedTypes(['jpg', 'jpeg', 'png'])
                     ->setMaxSize(5 * 1024 * 1024)
                     ->setUploadPath('uploads/products');
            
            if ($uploader->upload('image')) {
                $imagePath = $uploader->getUploadedFilePath();
            } else {
                Session::flash('error', $uploader->getError());
                header('Location: /products/create');
                exit;
            }
        }
        
        // สร้างสินค้า
        $product = Product::create([
            'name' => $request->input('name'),
            'price' => $request->input('price'),
            'image' => $imagePath
        ]);
        
        Session::flash('success', 'สร้างสินค้าสำเร็จ');
        header("Location: /products/{$product->id}");
        exit;
    }
    
    // อัปโหลดหลายรูป
    public function uploadGallery($id)
    {
        $request = new Request();
        
        if (!$request->hasFile('images')) {
            echo json_encode(['error' => 'ไม่มีไฟล์']);
            return;
        }
        
        $uploader = new FileUpload();
        $uploader->setAllowedTypes(['jpg', 'jpeg', 'png'])
                 ->setMaxSize(5 * 1024 * 1024)
                 ->setUploadPath("uploads/products/{$id}/gallery");
        
        $result = $uploader->uploadMultiple('images');
        
        // บันทึกข้อมูลรูปภาพลงฐานข้อมูล
        foreach ($result['success'] as $filename) {
            ProductImage::create([
                'product_id' => $id,
                'filename' => $filename,
                'path' => "uploads/products/{$id}/gallery/{$filename}"
            ]);
        }
        
        echo json_encode([
            'success' => count($result['success']),
            'failed' => count($result['failed']),
            'errors' => $result['failed']
        ]);
    }
}
```

### Static Methods

```php
// ลบไฟล์
FileUpload::deleteFile('uploads/products/image.jpg');

// ตรวจสอบว่าเป็นรูปภาพหรือไม่
if (FileUpload::isImage('uploads/image.jpg')) {
    // เป็นรูปภาพ
}

// แปลงขนาดไฟล์เป็นรูปแบบที่อ่านง่าย
echo FileUpload::formatFileSize(1048576); // 1 MB
```

---

## 7. Pagination

คลาส `Pagination` ใช้สำหรับแบ่งหน้าข้อมูล

### การใช้งานพื้นฐาน

```php
use App\Core\Pagination;

// สมมติว่ามีสินค้า 100 รายการ
$totalItems = 100;
$perPage = 20;
$currentPage = $_GET['page'] ?? 1;

// สร้าง pagination
$pagination = new Pagination($totalItems, $perPage, $currentPage);

// รับ offset สำหรับ SQL
$offset = $pagination->getOffset();
$limit = $pagination->getPerPage();

// Query ข้อมูล
$sql = "SELECT * FROM products LIMIT {$limit} OFFSET {$offset}";

// แสดง pagination HTML
echo $pagination->render();
```

### การใช้งานกับ Model

```php
class ProductController extends Controller
{
    public function index()
    {
        $request = new Request();
        $page = $request->get('page', 1);
        $perPage = 20;
        
        // นับจำนวนทั้งหมด
        $total = Product::count();
        
        // สร้าง pagination
        $pagination = new Pagination($total, $perPage, $page);
        
        // ดึงข้อมูล
        $products = Product::orderBy('created_at', 'DESC')
                          ->limit($pagination->getPerPage())
                          ->offset($pagination->getOffset())
                          ->get();
        
        $this->view('products/index', [
            'products' => $products,
            'pagination' => $pagination
        ]);
    }
}
```

### Customize การแสดงผล

```php
// ตั้งค่าจำนวน page links
$pagination->setLinksCount(7); // แสดง 7 ลิงก์

// ตั้งค่า CSS classes
$pagination->setClasses(
    'pagination justify-content-center', // container class
    'active',  // active class
    'disabled' // disabled class
);

// ตั้งค่าข้อความปุ่ม
$pagination->setButtonTexts('← ก่อนหน้า', 'ถัดไป →');
```

### แสดงแบบง่าย (เฉพาะ Previous/Next)

```php
echo $pagination->renderSimple();
```

### แสดงข้อมูลสรุป

```php
echo $pagination->summary();
// Output: แสดง 21 ถึง 40 จากทั้งหมด 100 รายการ
```

### ตัวอย่างใน View

```php
<!-- products/index.php -->
<div class="container">
    <h1>รายการสินค้า</h1>
    
    <!-- แสดงข้อมูลสรุป -->
    <p><?= $pagination->summary() ?></p>
    
    <!-- แสดงสินค้า -->
    <div class="row">
        <?php foreach ($products as $product): ?>
            <div class="col-md-4">
                <h3><?= htmlspecialchars($product->name) ?></h3>
                <p>ราคา: <?= number_format($product->price) ?> บาท</p>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?= $pagination->render() ?>
</div>
```

### รับข้อมูล Pagination เป็น Array/JSON

```php
// สำหรับ API
$paginationData = $pagination->toArray();
// [
//     'current_page' => 2,
//     'per_page' => 20,
//     'total_pages' => 5,
//     'total_items' => 100,
//     'has_previous' => true,
//     'has_next' => true,
//     'previous_page' => 1,
//     'next_page' => 3,
//     'offset' => 20
// ]

// JSON
echo $pagination->toJson();
```

---

## 8. Cache

คลาส `Cache` ใช้สำหรับจัดเก็บข้อมูลชั่วคราวเพื่อเพิ่มความเร็ว

### การใช้งานพื้นฐาน

```php
use App\Core\Cache;

// บันทึกข้อมูล (cache 1 ชั่วโมง)
Cache::set('products', $products, 3600);

// ดึงข้อมูล
$products = Cache::get('products');

// ดึงข้อมูล พร้อมค่าเริ่มต้น
$products = Cache::get('products', []);

// ตรวจสอบว่ามี cache หรือไม่
if (Cache::has('products')) {
    // มี cache
}

// ลบ cache
Cache::forget('products');
```

### Remember Pattern

```php
// ดึงข้อมูล ถ้าไม่มีให้สร้างและ cache
$products = Cache::remember('products', 3600, function() {
    return Product::all();
});

// Cache แบบไม่หมดอายุ
$settings = Cache::rememberForever('settings', function() {
    return Setting::all();
});
```

### Cache ไม่มีกำหนด

```php
// บันทึกแบบไม่หมดอายุ
Cache::forever('app_name', 'My Application');

// ดึงและลบ
$value = Cache::pull('temp_data');
```

### จัดการ Numeric Cache

```php
// เพิ่มค่า
Cache::increment('page_views');
Cache::increment('counter', 5); // เพิ่ม 5

// ลดค่า
Cache::decrement('stock');
Cache::decrement('stock', 3); // ลด 3
```

### ล้าง Cache

```php
// ลบทั้งหมด
Cache::flush();

// ลบที่หมดอายุแล้ว
$deleted = Cache::clearExpired();

// ลบตาม pattern
Cache::forgetPattern('product_*');

// ลบที่เก่ากว่า X วินาที
Cache::clearOlderThan(86400); // 1 วัน
```

### ตัวอย่างการใช้งานจริง

```php
class ProductController extends Controller
{
    public function index()
    {
        // ใช้ cache สำหรับรายการสินค้า
        $products = Cache::remember('products_list', 3600, function() {
            return Product::where('status', 'active')
                         ->orderBy('created_at', 'DESC')
                         ->get();
        });
        
        $this->view('products/index', ['products' => $products]);
    }
    
    public function store()
    {
        $request = new Request();
        
        // สร้างสินค้าใหม่
        $product = Product::create($request->input());
        
        // ลบ cache เก่า
        Cache::forget('products_list');
        
        Session::flash('success', 'สร้างสินค้าสำเร็จ');
        header("Location: /products/{$product->id}");
        exit;
    }
    
    public function show($id)
    {
        // Cache ข้อมูลสินค้าแต่ละรายการ
        $product = Cache::remember("product_{$id}", 3600, function() use ($id) {
            return Product::find($id);
        });
        
        if (!$product) {
            http_response_code(404);
            echo "ไม่พบสินค้า";
            return;
        }
        
        // นับจำนวนการเข้าชม
        Cache::increment("product_{$id}_views");
        
        $this->view('products/show', ['product' => $product]);
    }
    
    public function update($id)
    {
        $request = new Request();
        $product = Product::find($id);
        
        if (!$product) {
            http_response_code(404);
            return;
        }
        
        // อัปเดตข้อมูล
        $product->fill($request->input());
        $product->save();
        
        // ลบ cache ที่เกี่ยวข้อง
        Cache::forget("product_{$id}");
        Cache::forget('products_list');
        
        Session::flash('success', 'อัปเดตสำเร็จ');
        header("Location: /products/{$id}");
        exit;
    }
}

// Cache สำหรับ Settings
class SettingService
{
    public static function get($key, $default = null)
    {
        $settings = Cache::rememberForever('app_settings', function() {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT * FROM settings");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = [];
            foreach ($rows as $row) {
                $result[$row['key']] = $row['value'];
            }
            return $result;
        });
        
        return $settings[$key] ?? $default;
    }
    
    public static function set($key, $value)
    {
        // บันทึกในฐานข้อมูล
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO settings (`key`, `value`) 
            VALUES (:key, :value)
            ON DUPLICATE KEY UPDATE `value` = :value
        ");
        $stmt->execute(['key' => $key, 'value' => $value]);
        
        // ลบ cache
        Cache::forget('app_settings');
    }
}
```

### ดูสถิติ Cache

```php
$stats = Cache::stats();
// [
//     'total_files' => 50,
//     'total_size' => 1048576,
//     'total_size_formatted' => '1 MB',
//     'expired_files' => 5
// ]

// ดูข้อมูล cache ทั้งหมด (สำหรับ debug)
$allCache = Cache::all();
foreach ($allCache as $key => $info) {
    echo "Key: {$key}, Expires: {$info['expires_at']}<br>";
}
```

---

## 9. Controller

คลาส `Controller` เป็น base class สำหรับตัวควบคุมทั้งหมด

### สร้าง Controller

```php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::all();
        
        // แสดง view
        $this->view('products/index', [
            'products' => $products,
            'title' => 'รายการสินค้า'
        ]);
    }
    
    public function show($id)
    {
        $product = Product::find($id);
        
        if (!$product) {
            // Redirect กลับไปหน้าแรก
            $this->redirect('/products');
        }
        
        $this->view('products/show', ['product' => $product]);
    }
    
    public function store()
    {
        $request = new Request();
        
        // ตรวจสอบข้อมูล
        // บันทึกข้อมูล
        // Redirect พร้อม flash message
        
        Session::flash('success', 'สร้างสินค้าสำเร็จ');
        $this->redirect('/products');
    }
}
```

### Response Methods

```php
// แสดง view
$this->view('products/index', ['products' => $products]);

// Redirect
$this->redirect('/products');
$this->redirect('/products', 301); // permanent redirect

// JSON response
$this->json(['success' => true, 'data' => $products]);

// JSON response พร้อม status code
$this->json(['error' => 'Not found'], 404);
```

---

## 10. Router

คลาส `Router` ใช้สำหรับจัดการ routing

### ลงทะเบียน Routes

```php
// routes/web.php
use App\Core\Router;

$router = new Router();

// GET route
$router->get('/', 'HomeController@index');
$router->get('/products', 'ProductController@index');
$router->get('/products/{id}', 'ProductController@show');

// POST route
$router->post('/products', 'ProductController@store');

// PUT route
$router->put('/products/{id}', 'ProductController@update');

// DELETE route
$router->delete('/products/{id}', 'ProductController@destroy');

// Dispatch (public/index.php)
$router->dispatch();
```

### Middleware

```php
use App\Middleware\AuthMiddleware;

// Route พร้อม middleware
$router->get('/dashboard', 'DashboardController@index', [AuthMiddleware::class]);

// หลาย middleware
$router->post('/admin/products', 'AdminProductController@store', [
    AuthMiddleware::class,
    AdminMiddleware::class
]);
```

### Route Parameters

```php
// Dynamic parameter
$router->get('/products/{id}', 'ProductController@show');
$router->get('/products/{id}/edit', 'ProductController@edit');
$router->get('/categories/{category}/products/{id}', 'ProductController@show');

// ใน Controller
class ProductController extends Controller
{
    public function show($id)
    {
        // $id จะถูกส่งมาจาก route parameter
        $product = Product::find($id);
        // ...
    }
}
```

---

## 11. View

คลาส `View` ใช้สำหรับจัดการ template และ layout

### การใช้งานพื้นฐาน

```php
// ใน Controller
$this->view('products/index', [
    'products' => $products,
    'title' => 'รายการสินค้า'
]);
```

### สร้าง View File

```php
<!-- app/Views/products/index.php -->
<div class="container">
    <h1><?= htmlspecialchars($title) ?></h1>
    
    <div class="row">
        <?php foreach ($products as $product): ?>
            <div class="col-md-4">
                <h3><?= htmlspecialchars($product->name) ?></h3>
                <p>ราคา: <?= number_format($product->price) ?> บาท</p>
                <a href="/products/<?= $product->id ?>">ดูรายละเอียด</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
```

### ใช้ Layout

```php
use App\Core\View;

// สร้าง view พร้อม layout
$view = new View('products/index', ['products' => $products]);
$view->setLayout('layouts/main');
$view->render();
```

---

## 12. Database

คลาส `Database` ใช้ Singleton pattern สำหรับการเชื่อมต่อฐานข้อมูล

### การใช้งาน

```php
use App\Core\Database;

// รับ connection
$db = Database::getInstance()->getConnection();

// Prepared statement
$stmt = $db->prepare("SELECT * FROM products WHERE price > :price");
$stmt->bindValue(':price', 1000);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Simple query
$stmt = $db->query("SELECT * FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

### Transaction

```php
$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();
    
    // ทำงานหลายอย่าง
    $db->exec("UPDATE products SET stock = stock - 1 WHERE id = 1");
    $db->exec("INSERT INTO orders (product_id, quantity) VALUES (1, 1)");
    
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    throw $e;
}
```

---

## 13. Logger

คลาส `Logger` ใช้สำหรับบันทึกข้อมูลเหตุการณ์ต่างๆ

### การใช้งานพื้นฐาน

```php
use App\Core\Logger;

$logger = new Logger();

// บันทึก info
$logger->info('User logged in', ['user_id' => 123]);

// บันทึก error
$logger->error('Database connection failed', ['error' => $e->getMessage()]);

// บันทึก warning
$logger->warning('Low disk space', ['available' => '10GB']);

// บันทึก debug
$logger->debug('Processing data', ['count' => 100]);
```

ไฟล์ log จะถูกเก็บใน `storage/logs/` โดยแยกตามวันที่

---

## 14. ErrorHandler

คลาส `ErrorHandler` ใช้สำหรับจัดการข้อผิดพลาดแบบ centralized

### การใช้งานพื้นฐาน

```php
use App\Core\ErrorHandler;

// แสดงหน้า 404
ErrorHandler::show(404);

// แสดงหน้า 403 พร้อมข้อความ
ErrorHandler::show(403, 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');

// แสดงหน้า 500
ErrorHandler::show(500, 'เกิดข้อผิดพลาดภายในระบบ');

// แสดงหน้า 503 (Maintenance Mode)
ErrorHandler::show(503, 'ระบบอยู่ในระหว่างปรับปรุง');
```

ErrorHandler จะตรวจสอบว่าเป็น API request หรือไม่ และส่งคืน JSON หรือ HTML ตามความเหมาะสม

---

## 15. Mail

คลาส `Mail` ใช้สำหรับส่งอีเมล

### การตั้งค่า

ตั้งค่าใน `.env`:
```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Your App Name"
```

### การใช้งานพื้นฐาน

```php
use App\Core\Mail;

$mail = new Mail();

// ส่งอีเมลแบบ HTML
$mail->to('user@example.com', 'John Doe')
     ->subject('Welcome to Our Platform')
     ->html('<h1>Welcome!</h1><p>Thank you for joining us.</p>')
     ->send();

// ส่งอีเมลด้วย template
$mail->to('user@example.com', 'Jane Doe')
     ->subject('Order Confirmation')
     ->template('emails/order-confirmation', [
         'name' => 'Jane Doe',
         'order_id' => 'ORD-12345',
         'total' => 1500.00
     ])
     ->send();

// ส่งอีเมลหลายคน
$mail->to(['user1@example.com', 'user2@example.com'])
     ->subject('Newsletter')
     ->template('emails/newsletter', ['month' => 'January'])
     ->send();

// แนบไฟล์
$mail->to('user@example.com')
     ->subject('Invoice')
     ->attach('/path/to/invoice.pdf')
     ->html('<p>Please find attached invoice.</p>')
     ->send();
```

---

## 16. Migration

คลาส `Migration` เป็น base class สำหรับสร้าง database migrations

### การสร้าง Migration

```php
namespace Database\Migrations;

use App\Core\Migration;

class CreateUsersTable extends Migration
{
    /**
     * รัน migration (สร้างตาราง)
     */
    public function up(): void
    {
        $sql = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $this->execute($sql);
    }

    /**
     * Rollback migration (ลบตาราง)
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS users");
    }
}
```

---

## 17. MigrationRunner

ใช้สำหรับรัน migrations ผ่านคำสั่ง CLI

```bash
# รัน migrations ทั้งหมด
php console migrate

# หรือใช้ migrate.php โดยตรง
php migrate.php up

# Rollback
php migrate.php down

# Fresh migration (ลบทุกอย่างและรันใหม่)
php migrate.php fresh

# ดูสถานะ
php migrate.php status
```

---

## 18. Seeder

คลาส `Seeder` เป็น base class สำหรับสร้างข้อมูลตัวอย่าง

### การสร้าง Seeder

```php
namespace Database\Seeders;

use App\Core\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => password_hash('password123', PASSWORD_BCRYPT),
                'role' => 'admin'
            ],
            [
                'name' => 'Test User',
                'email' => 'user@example.com',
                'password' => password_hash('password123', PASSWORD_BCRYPT),
                'role' => 'user'
            ]
        ];

        $this->insert('users', $users);
    }
}
```

### รัน Seeders

```bash
# รัน seeders ทั้งหมด
php console seed

# หรือใช้ seed.php โดยตรง
php seed.php
```

---

## 19. Middleware

คลาส `Middleware` เป็น base class สำหรับสร้าง middleware

### การสร้าง Middleware

```php
namespace App\Middleware;

use App\Core\Middleware;

class CheckAgeMiddleware extends Middleware
{
    public function handle(): bool
    {
        $age = $_GET['age'] ?? 0;
        
        if ($age < 18) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'คุณต้องมีอายุ 18 ปีขึ้นไป'
            ]);
            return false;
        }
        
        return true;
    }
}
```

### การใช้งาน Middleware

```php
// ใน routes
$router->get('/adult-content', 'ContentController@show', [
    new CheckAgeMiddleware()
]);
```

---

## สรุป

คลาสทั้งหมดใน `app/Core` ถูกออกแบบมาให้ทำงานร่วมกันได้อย่างลงตัว:

1. **Request** - รับข้อมูลจากผู้ใช้
2. **Validator** - ตรวจสอบข้อมูล
3. **Session** - จัดเก็บข้อมูลชั่วคราว
4. **Auth** - ยืนยันตัวตน
5. **Model** - จัดการฐานข้อมูล
6. **FileUpload** - อัปโหลดไฟล์
7. **Pagination** - แบ่งหน้า
8. **Cache** - เพิ่มความเร็ว
9. **Controller** - Base controller สำหรับ HTTP handling
10. **Router** - จัดการ routing และ URL
11. **View** - แสดงผล templates
12. **Database** - เชื่อมต่อฐานข้อมูล
13. **Logger** - บันทึกเหตุการณ์
14. **ErrorHandler** - จัดการข้อผิดพลาด
15. **Mail** - ส่งอีเมล
16. **Migration** - จัดการ database schema
17. **MigrationRunner** - รัน migrations
18. **Seeder** - สร้างข้อมูลตัวอย่าง
19. **Middleware** - กรองและตรวจสอบ requests
9. **Controller** - ควบคุมการทำงาน
10. **Router** - จัดการเส้นทาง
11. **View** - แสดงผล
12. **Database** - เชื่อมต่อฐานข้อมูล

ใช้งานร่วมกันได้อย่างมีประสิทธิภาพสำหรับการพัฒนาเว็บแอปพลิเคชันที่สมบูรณ์!
