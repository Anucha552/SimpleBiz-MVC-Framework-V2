# SimpleBiz MVC Framework V2

**เฟรมเวิร์ก MVC ขนาดเล็ก-กลาง สำหรับพัฒนาเว็บแอปและ API แบบปลอดภัย และขยายได้ง่าย**


<div align="center">

![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.0-blue)
![License](https://img.shields.io/badge/License-MIT-green)
![Framework](https://img.shields.io/badge/Framework-MVC-orange)
![Language](https://img.shields.io/badge/Language-Thai%20%7C%20English-red)

*Modern PHP Framework for Web Applications and RESTful APIs*

[📖 Documentation](#-เอกสาร) •
[🚀 Quick Start](#-การติดตั้งใช้งาน) •
[✨ Features](#-คุณสมบัติหลัก) •
[🏗️ Architecture](#-โครงสร้าง) •
[🔐 Security](#-ความปลอดภัย)

</div>

---

## 🌟 ภาพรวม

SimpleBiz MVC Framework V2 เป็น PHP Framework ที่ออกแบบมาเพื่อการพัฒนาเว็บแอปพลิเคชันและ RESTful API อย่างรวดเร็วและมีประสิทธิภาพ โดยเน้นความง่ายในการใช้งาน ความปลอดภัย และความยืดหยุ่นในการขยายระบบ

### 🎯 เหมาะสำหรับ
- **เว็บไซต์องค์กร** และ **ระบบอีคอมเมิร์ซ**
- **RESTful API** สำหรับ Mobile และ SPA Applications  
- **ระบบจัดการองค์กร** (HR, CRM, ERP)
- **ระบบราชการ** และ **หน่วยงานสาธารณะ**
- **Rapid Prototyping** และ **MVP Development**

---

## ✨ คุณสมบัติหลัก

<table>
<tr>
<td width="50%">

### 🏗️ **Architecture & Development**
- ✅ **MVC Pattern** - แยกส่วนการทำงานชัดเจน
- ✅ **PSR-4 Autoloading** - โครงสร้างมาตรฐาน
- ✅ **Modular Architecture** - ขยายด้วย Modules
- ✅ **Composer Integration** - จัดการ dependencies
- ✅ **PHP 8.0+** - ใช้ฟีเจอร์ใหม่ของ PHP

### 🌐 **Web Development**  
- ✅ **Responsive Templates** - รองรับทุกอุปกรณ์
- ✅ **Form Helpers** - สร้างฟอร์มอัตโนมัติ
- ✅ **Asset Management** - จัดการ CSS/JS
- ✅ **SEO Friendly URLs** - URL เป็นมิตรกับ SEO
- ✅ **Multi-language** - รองรับหลายภาษา

</td>
<td width="50%">

### 🔌 **API Development**
- ✅ **RESTful Architecture** - API มาตรฐานสากล  
- ✅ **JSON Responses** - รองรับ API calls
- ✅ **CORS Support** - Cross-Origin requests
- ✅ **API Versioning** - จัดการเวอร์ชัน API
- ✅ **OpenAPI Ready** - เตรียมพร้อมเอกสาร API

### 🗄️ **Database & Storage**
- ✅ **MySQL/MariaDB/SQLite** - รองรับหลาก DB
- ✅ **Query Builder** - สร้าง query ปลอดภัย  
- ✅ **Migrations** - จัดการโครงสร้าง DB
- ✅ **Seeders** - ติดตั้งข้อมูลทดสอบ
- ✅ **File Upload** - อัปโหลดไฟล์ปลอดภัย

</td>
</tr>
</table>

---

## 🔐 ความปลอดภัย

<div align="center">

| Feature | Status | Description |
|---------|--------|-------------|
| **Authentication** | ✅ | ระบบล็อกอิน/ล็อกเอาต์ |
| **Authorization** | ✅ | จัดการสิทธิ์ตาม Role |
| **CSRF Protection** | ✅ | ป้องกันการโจมตี CSRF |
| **SQL Injection** | ✅ | ป้องกัน SQL Injection |  
| **XSS Protection** | ✅ | ป้องกันการโจมตี XSS |
| **API Key Auth** | ✅ | ยืนยันตัวตนสำหรับ API |
| **Secure Headers** | ✅ | Headers ด้านความปลอดภัย |
| **Input Validation** | ✅ | ตรวจสอบข้อมูลนำเข้า |

</div>

---

## 🚀 การติดตั้งใช้งาน

### ความต้องการของระบบ
```bash
PHP >= 8.0
MySQL >= 5.7 หรือ MariaDB >= 10.3  
Composer >= 2.0
Web Server (Apache/Nginx)
```

### 1. ดาวน์โหลด & ติดตั้ง
```bash
# Clone repository
git clone https://github.com/simplebiz/mvc-framework-v2.git
cd mvc-framework-v2

# ติดตั้ง dependencies
composer install

# ตั้งค่าสิทธิ์ไฟล์
chmod -R 775 storage/
```

### 2. การตั้งค่าเบื้องต้น
```bash
# สร้างไฟล์ .env
cp .env.example .env

# แก้ไขการตั้งค่าใน .env
nano .env
```

```env
# ตัวอย่างการตั้งค่า .env
APP_NAME="My Application"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=your_database
DB_USERNAME=your_username  
DB_PASSWORD=your_password
```

### 3. ตั้งค่าฐานข้อมูล
```bash
# รัน migrations
php console migration:run

# รัน seeders (ถ้าต้องการ)  
php console seed:run
```

### 4. เรียกใช้งาน
```bash
# Development server
php -S localhost:8000 -t public/

# หรือใช้ Web Server (Apache/Nginx)
# ตั้ง Document Root ไปที่โฟลเดอร์ public/
```

---

## 🏗️ โครงสร้าง

```
SimpleBiz-MVC-Framework-V2/
├── 📁 app/
│   ├── 📁 Console/          # CLI Commands
│   ├── 📁 Controllers/      # Web & API Controllers
│   │   ├── 📁 Web/         # Web Controllers (HTML)
│   │   └── 📁 Api/         # API Controllers (JSON)
│   ├── 📁 Core/            # Core Framework Files
│   ├── 📁 Helpers/         # Helper Functions
│   ├── 📁 Middleware/      # HTTP Middleware
│   ├── 📁 Models/          # Database Models
│   ├── 📁 Services/        # Business Logic Services
│   └── 📁 Views/           # HTML Templates
├── 📁 config/              # Configuration Files
├── 📁 database/            # Migrations & Seeders
├── 📁 docs/                # Documentation
├── 📁 modules/             # Custom Modules
├── 📁 public/              # Web Server Document Root
├── 📁 routes/              # Route Definitions
├── 📁 storage/             # Logs, Cache, Uploads
├── 📁 tests/               # Unit & Feature Tests
├── composer.json           # Dependencies
└── .env.example            # Environment Template
```

---

## 💻 การใช้งานพื้นฐาน

### สร้าง Controller
```php
<?php
// app/Controllers/Web/ProductController.php

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::all();
        return $this->view('products.index', ['products' => $products]);
    }
    
    public function show($id)
    {
        $product = Product::find($id);
        return $this->view('products.show', ['product' => $product]);
    }
}
```

### สร้าง Model
```php
<?php
// app/Models/Product.php

namespace App\Models;

use App\Core\Model;

class Product extends Model
{
    protected $table = 'products';
    protected $fillable = ['name', 'price', 'description'];
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
```

### กำหนด Routes
```php
<?php
// routes/web.php

use App\Controllers\Web\ProductController;

// Web Routes
$router->get('/', $webBasePath . 'HomeController@index');
$router->get('/products', $webBasePath . 'ProductController@index');  
$router->get('/products/{id}', $webBasePath . 'ProductController@show');
```

### สร้าง API Controller
```php
<?php
// app/Controllers/Api/ProductApiController.php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\Product;

class ProductApiController extends Controller
{
    public function index()
    {
        $products = Product::all();
        return $this->json(['data' => $products]);
    }
    
    public function store()
    {
        $data = $this->request->getBody();
        $product = Product::create($data);
        return $this->json(['data' => $product], 201);
    }
}
```

### กำหนด API Routes
```php
<?php  
// routes/api.php

use App\Controllers\Api\ProductApiController;
use App\Middleware\ApiKeyMiddleware;

// API Routes with Authentication
$router->get('/api/products', $apiBasePath . 'ProductApiController@index', [
    ApiKeyMiddleware::class
]);

$router->post('/api/products', $apiBasePath . 'ProductApiController@store', [
    ApiKeyMiddleware::class
]);
```

---

## 🛠️ เครื่องมือ Console

```bash
# ดูคำสั่งที่มี
php console

# จัดการ Migrations
php console migration:run           # รัน migrations ทั้งหมด
php console migration:rollback      # ย้อนกลับ migration
php console migration:status        # ดูสถานะ migration

# จัดการ Seeders  
php console seed:run                # รัน seeders ทั้งหมด
php console seed:run --class=UserSeeder  # รัน seeder เฉพาะ

# Cache Management
php console cache:clear             # ล้าง cache
php console route:cache             # สร้าง route cache
```

---

## 🔒 Middleware & Security

### การใช้งาน Middleware
```php
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

// Protected Routes
$router->get('/admin', $webBasePath . 'AdminController@dashboard', [
    AuthMiddleware::class,
    [RoleMiddleware::class, ['admin'], 10, true]
]);

// API with API Key
$router->get('/api/users', $apiBasePath . 'UserApiController@index', [
    ApiKeyMiddleware::class
]);
```

### CSRF Protection
```php
<!-- ในฟอร์ม HTML -->
<form method="POST" action="/products">
    <?= csrf_field() ?>
    <input type="text" name="name" required>
    <button type="submit">Submit</button>
</form>
```

### Input Validation
```php
use App\Core\Validator;

public function store()
{
    $validator = new Validator($this->request->getBody(), [
        'name' => 'required|min:3|max:100',
        'email' => 'required|email',
        'price' => 'required|numeric|min:0'
    ]);
    
    if ($validator->fails()) {
        return $this->json(['errors' => $validator->errors()], 400);
    }
    
    // Process valid data...
}
```

---

## 📦 Modules

### สร้าง Module ใหม่
```bash
# โครงสร้าง Module
modules/
└── MyModule/
    ├── Controllers/
    ├── Models/  
    ├── Views/
    ├── Routes/
    └── config.php
```

### ลงทะเบียน Module
```php
// config/modules.php
return [
    'modules' => [
        'MyModule' => [
            'path' => 'modules/MyModule',
            'namespace' => 'Modules\\MyModule',
            'enabled' => true
        ]
    ]
];
```

---

## 🧪 Testing

```bash
# รัน Unit Tests
composer test

# รัน Tests เฉพาะกลุ่ม
./vendor/bin/phpunit tests/Unit/

# รัน Feature Tests  
./vendor/bin/phpunit tests/Feature/
```

### ตัวอย่าง Test
```php
<?php
// tests/Unit/UserTest.php

use PHPUnit\Framework\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    public function testUserCreation()
    {
        $user = new User([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
    }
}
```

---

## 📚 เอกสาร

- 📖 **[Framework Capabilities](docs/framework-capabilities.md)** - ความสามารถของ Framework
- 🚀 **[Deployment Guide](docs/deployment-guide.md)** - คู่มือ deployment  
- 📋 **[Core Documentation](docs/Cores/)** - เอกสาร Core components
- 🔧 **[Configuration](config/)** - ไฟล์การตั้งค่า
- 💻 **[Examples](modules/HelloWorld/)** - ตัวอย่างการใช้งาน

---

## 🤝 การมีส่วนร่วม

เรายินดีรับ contributions จากทุกคน! 

### วิธีการมีส่วนร่วม:
1. **Fork** repository นี้
2. **สร้าง branch** ใหม่ (`git checkout -b feature/amazing-feature`)
3. **Commit** การเปลี่ยนแปลง (`git commit -m 'Add amazing feature'`)  
4. **Push** ไปยัง branch (`git push origin feature/amazing-feature`)
5. **สร้าง Pull Request**

### แนวทางการพัฒนา:
- ✅ เขียน unit tests สำหรับฟีเจอร์ใหม่
- ✅ ปฏิบัติตาม PSR-12 coding standards
- ✅ เขียน documentation สำหรับ API ใหม่
- ✅ ใช้ descriptive commit messages

---

## 📄 License

สิทธิ์ตาม [MIT License](LICENSE) - ใช้งานฟรี สำหรับโครงการส่วนตัวและเชิงพาณิชย์

---

## 👨‍💻 ผู้พัฒนา

**Mr. Anucha Khemthong (SimpleBiz)**
- 📧 Email: [anuchahaha5@gmail.com](mailto:anuchahaha5@gmail.com)
- 🌐 Website: [https://anucha552.github.io/my-portfolio/](https://anucha552.github.io/my-portfolio/)
- 💼 GitHub: [@simplebiz](https://github.com/simplebiz)

---

## 🙏 กิตติกรรมประกาศ

ขอขอบคุณ:
- **Laravel** - สำหรับแรงบันดาลใจในการออกแบบ API
- **Symfony** - สำหรับ components บางส่วน  
- **PHP Community** - สำหรับการสนับสนุนและแรงบันดาลใจ

---

## 📊 สถิติ

<div align="center">

![GitHub stars](https://img.shields.io/github/stars/simplebiz/mvc-framework-v2?style=social)
![GitHub forks](https://img.shields.io/github/forks/simplebiz/mvc-framework-v2?style=social)
![GitHub issues](https://img.shields.io/github/issues/simplebiz/mvc-framework-v2)
![GitHub pull requests](https://img.shields.io/github/issues-pr/simplebiz/mvc-framework-v2)

**⭐ ถ้าโปรเจ็คนี้มีประโยชน์ อย่าลืมกดดาวให้ด้วยนะครับ! ⭐**

</div>
