# โครงสร้างโปรเจค SimpleBiz MVC Framework V2

เอกสารนี้อธิบายโครงสร้างโปรเจคแบบละเอียด พร้อมคำแนะนำว่าควรเพิ่ม/แก้ไขโค้ดที่ไหนเมื่อพัฒนาฟีเจอร์ใหม่

---

## 📁 โครงสร้างทั้งหมด

```
SimpleBiz-MVC-Framework-V2/
├── app/                          # โค้ดหลักของแอปพลิเคชัน
│   ├── Controllers/              # จัดการ HTTP requests
│   │   ├── HomeController.php   # หน้าแรก
│   │   ├── AuthController.php   # ลงทะเบียน/เข้าสู่ระบบ
│   │   ├── Api/V1/              # API Controllers
│   │   │   ├── CartApiController.php
│   │   │   ├── OrderApiController.php
│   │   │   └── ProductApiController.php
│   │   └── Ecommerce/           # Web Controllers
│   │       ├── CartController.php
│   │       ├── OrderController.php
│   │       └── ProductController.php
│   ├── Core/                     # คลาสหลักของเฟรมเวิร์ก
│   │   ├── Controller.php       # Base controller
│   │   ├── Database.php         # PDO singleton
│   │   ├── Logger.php           # ระบบบันทึก
│   │   ├── Middleware.php       # Base middleware
│   │   ├── Router.php           # URL routing
│   │   └── View.php             # Template rendering
│   ├── Helpers/                  # Helper functions
│   │   └── Response.php         # JSON response helper
│   ├── Middleware/               # Request filtering
│   │   ├── ApiKeyMiddleware.php # ตรวจสอบ API key
│   │   └── AuthMiddleware.php   # ตรวจสอบการล็อกอิน
│   └── Models/                   # Business logic
│       ├── Cart.php             # ตะกร้าสินค้า
│       ├── Order.php            # คำสั่งซื้อ
│       ├── Product.php          # สินค้า
│       └── User.php             # ผู้ใช้
├── config/                       # ไฟล์ configuration
│   ├── app.php                  # การตั้งค่าแอปพลิเคชัน
│   └── database.php             # การตั้งค่าฐานข้อมูล
├── database/
│   └── migrations/              # SQL schema files
│       ├── users.sql
│       ├── products.sql
│       ├── carts.sql
│       └── orders.sql
├── docs/                         # เอกสารโปรเจค
│   └── PROJECT_STRUCTURE.md     # ไฟล์นี้
├── public/                       # Web root (เข้าถึงได้จาก browser)
│   ├── index.php                # Entry point
│   └── .htaccess                # Apache config
├── routes/                       # การกำหนด routes
│   ├── api.php                  # API routes
│   └── web.php                  # Web routes
├── storage/
│   └── logs/                    # Application logs
│       └── .gitkeep
├── vendor/                       # Composer dependencies
├── .env.example                  # ตัวอย่างไฟล์ environment
├── .gitignore                    # Git ignore rules
├── .htaccess                     # Root Apache config
├── composer.json                 # Composer configuration
└── README.md                     # เอกสารหลัก
```

---

## 📂 อธิบายแต่ละโฟลเดอร์

### 1️⃣ `app/Controllers/` - Controllers

**หน้าที่:** จัดการ HTTP requests, ตรวจสอบข้อมูล, เรียก Models, ส่งคืน response

**เมื่อไหร่ควรเพิ่ม/แก้ไข:**
- ✅ **เพิ่ม Controller ใหม่** เมื่อมี feature ใหม่ (เช่น BlogController, ReviewController)
- ✅ **แก้ไข Controller** เมื่อต้องการเปลี่ยน validation หรือ response format
- ❌ **ไม่ควรใส่** ตรรกะทางธุรกิจที่ซับซ้อน (ควรอยู่ใน Models)

**โครงสร้าง Controller ตัวอย่าง:**

```php
<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    private Product $productModel;
    
    public function __construct()
    {
        $this->productModel = new Product();
    }
    
    /**
     * แสดงรายการสินค้า
     */
    public function index()
    {
        // 1. ดึงข้อมูลจาก Model
        $products = $this->productModel->getAll();
        
        // 2. ส่งไปแสดงผลที่ View
        $this->view('products/index', [
            'products' => $products
        ]);
    }
    
    /**
     * แสดงรายละเอียดสินค้า
     */
    public function show($id)
    {
        // 1. ตรวจสอบ input
        if (!is_numeric($id)) {
            return $this->notFound();
        }
        
        // 2. ดึงข้อมูล
        $product = $this->productModel->findById($id);
        
        // 3. ตรวจสอบว่าพบข้อมูลหรือไม่
        if (!$product) {
            return $this->notFound();
        }
        
        // 4. แสดงผล
        $this->view('products/show', [
            'product' => $product
        ]);
    }
}
```

**คำแนะนำ:**
- Controllers ควรบาง - ทำหน้าที่ประสานงานเท่านั้น
- ใส่ validation เบื้องต้น แต่ตรรกะซับซ้อนให้อยู่ใน Model
- ใช้ inheritance จาก `App\Core\Controller` เพื่อใช้ helper methods

---

### 2️⃣ `app/Models/` - Models

**หน้าที่:** ตรรกะทางธุรกิจ, การติดต่อฐานข้อมูล, validation, business rules

**เมื่อไหร่ควรเพิ่ม/แก้ไข:**
- ✅ **เพิ่ม Model ใหม่** เมื่อมีตารางใหม่ในฐานข้อมูล
- ✅ **แก้ไข Model** เมื่อเปลี่ยนตรรกะธุรกิจ, เพิ่ม methods ใหม่
- ✅ **ใส่ validation** ที่ซับซ้อนไว้ใน Model

**โครงสร้าง Model ตัวอย่าง:**

```php
<?php
namespace App\Models;

use App\Core\Database;

class Product
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * ดึงสินค้าทั้งหมด
     */
    public function getAll(): array
    {
        $stmt = $this->db->query("
            SELECT id, name, price, stock, status 
            FROM products 
            WHERE status = 'active'
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * ดึงสินค้าตาม ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM products 
            WHERE id = ? AND status = 'active'
        ");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        
        return $product ?: null;
    }
    
    /**
     * ตรวจสอบว่ามีสต็อกเพียงพอหรือไม่
     */
    public function checkAvailability(int $productId, int $qty): array
    {
        $product = $this->findById($productId);
        
        if (!$product) {
            return [
                'available' => false,
                'reason' => 'Product not found'
            ];
        }
        
        if ($product['stock'] < $qty) {
            return [
                'available' => false,
                'reason' => 'Insufficient stock',
                'available_stock' => $product['stock']
            ];
        }
        
        return [
            'available' => true,
            'product' => $product
        ];
    }
    
    /**
     * ลดสต็อก (ใช้ตอนสร้างคำสั่งซื้อ)
     */
    public function decreaseStock(int $productId, int $qty): bool
    {
        // ใช้ optimistic locking
        $stmt = $this->db->prepare("
            UPDATE products 
            SET stock = stock - ? 
            WHERE id = ? AND stock >= ?
        ");
        $stmt->execute([$qty, $productId, $qty]);
        
        return $stmt->rowCount() > 0;
    }
}
```

**คำแนะนำ:**
- Models มีหน้าที่หลักคือจัดการข้อมูลและตรรกะธุรกิจ
- ใช้ PDO prepared statements เสมอ
- return array หรือ null สำหรับข้อมูล, return array สำหรับ status
- เขียน validation และ business rules ไว้ใน Model

---

### 3️⃣ `app/Core/` - Core Classes

**หน้าที่:** คลาสพื้นฐานของเฟรมเวิร์ก - **ไม่ค่อยต้องแก้ไข**

#### `Router.php` - URL Routing
```php
// กำหนด routes ใน routes/web.php หรือ routes/api.php
$router->get('/products', 'ProductController@index');
$router->post('/cart/add', 'CartController@add', [AuthMiddleware::class]);
```

#### `Database.php` - PDO Singleton
```php
// เรียกใช้ในทุก Model
$this->db = Database::getInstance()->getConnection();
```

#### `Logger.php` - System Logging
```php
// บันทึก log
$logger = new Logger();
$logger->info('user.login', ['user_id' => 1]);
$logger->security('suspicious.activity', ['ip' => $_SERVER['REMOTE_ADDR']]);
$logger->error('database.error', ['message' => $e->getMessage()]);
```

**เมื่อไหร่ควรแก้ไข:**
- ❌ **หลีกเลี่ยง** การแก้ไขถ้าไม่จำเป็น (เป็นหัวใจของเฟรมเวิร์ก)
- ⚠️ **ระวัง** ถ้าต้องแก้ไข ต้องเข้าใจการทำงานอย่างลึกซึ้ง
- ✅ **เพิ่มเติม** features ที่ไม่กระทบระบบเดิม

---

### 4️⃣ `app/Middleware/` - Middleware

**หน้าที่:** กรอง/ตรวจสอบ requests ก่อนส่งไป Controller

**เมื่อไหร่ควรเพิ่ม:**
- ✅ ต้องการตรวจสอบ permissions (เช่น AdminMiddleware)
- ✅ ต้องการตรวจสอบ rate limiting
- ✅ ต้องการ log requests
- ✅ ต้องการตรวจสอบ CSRF token

**สร้าง Middleware ใหม่:**

```php
<?php
namespace App\Middleware;

use App\Core\Middleware;

class AdminMiddleware extends Middleware
{
    public function handle(): bool
    {
        // ตรวจสอบว่า user เป็น admin หรือไม่
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            return false;
        }
        
        // ตรวจสอบ role จากฐานข้อมูล
        $userModel = new \App\Models\User();
        $user = $userModel->findById($_SESSION['user_id']);
        
        if (!$user || $user['role'] !== 'admin') {
            http_response_code(403);
            echo "Access Denied";
            return false;
        }
        
        return true;
    }
}
```

**ใช้งาน:**
```php
// ใน routes/web.php
$router->get('/admin/dashboard', 'AdminController@index', [
    AuthMiddleware::class,
    AdminMiddleware::class
]);
```

---

### 5️⃣ `routes/` - Route Definitions

**หน้าที่:** กำหนดเส้นทาง URL และเชื่อมโยงกับ Controllers

#### `routes/web.php` - Web Routes (HTML)
```php
// หน้าแรก
$router->get('/', 'HomeController@index');

// สินค้า (public)
$router->get('/products', 'ProductController@index');
$router->get('/products/{id}', 'ProductController@show');

// ตะกร้า (ต้อง login)
$router->get('/cart', 'CartController@index', [AuthMiddleware::class]);
$router->post('/cart/add', 'CartController@add', [AuthMiddleware::class]);
```

#### `routes/api.php` - API Routes (JSON)
```php
// API สินค้า (public)
$router->get('/api/v1/products', 'Api\V1\ProductApiController@index');

// API ตะกร้า (ต้อง login)
$router->post('/api/v1/cart/add', 'Api\V1\CartApiController@add', [AuthMiddleware::class]);

// API คำสั่งซื้อ (ต้อง login + API key)
$router->post('/api/v1/orders/create', 'Api\V1\OrderApiController@create', [
    AuthMiddleware::class,
    ApiKeyMiddleware::class
]);
```

**คำแนะนำ:**
- แยก web routes และ api routes ชัดเจน
- ใช้ middleware เพื่อป้องกัน routes ที่ต้องการการยืนยันตัวตน
- ตั้งชื่อ route แบบ RESTful (GET /products, POST /products, PUT /products/{id})

---

### 6️⃣ `config/` - Configuration

**หน้าที่:** เก็บการตั้งค่าต่างๆ ของแอปพลิเคชัน

#### `config/app.php`
```php
return [
    'name' => getenv('APP_NAME') ?: 'SimpleBiz MVC Framework V2',
    'env' => getenv('APP_ENV') ?: 'development',
    'debug' => getenv('APP_DEBUG') !== 'false',
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'timezone' => 'Asia/Bangkok',  // เปลี่ยน timezone ได้ที่นี่
];
```

#### `config/database.php`
```php
return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'simplebiz_mvc',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
];
```

**เมื่อไหร่ควรเพิ่ม:**
- ✅ มีการตั้งค่าใหม่ที่ใช้ทั่วทั้งแอปพลิเคชัน
- ✅ ต้องการเก็บค่าคงที่สำหรับ production/development

---

### 7️⃣ `database/migrations/` - Database Schema

**หน้าที่:** เก็บไฟล์ SQL สำหรับสร้างตาราง

**เมื่อไหร่ควรเพิ่ม:**
- ✅ สร้างตารางใหม่
- ✅ เพิ่ม columns ในตารางเดิม
- ✅ สร้าง indexes ใหม่

**ตัวอย่าง: สร้างตาราง reviews**

```sql
/*
 * ไฟล์สร้างตาราง REVIEWS
 * 
 * จุดประสงค์: เก็บรีวิวสินค้าจากลูกค้า
 */

CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (fk_review_product) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (fk_review_user) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_product (product_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 8️⃣ `public/` - Web Root

**หน้าที่:** จุดเข้าใช้งานเว็บไซต์ - **อย่าแก้ไขถ้าไม่จำเป็น**

- `index.php` - Entry point (รับ request ทั้งหมด)
- `.htaccess` - Apache configuration

**เมื่อไหร่ควรเพิ่ม:**
- ✅ ไฟล์ static (CSS, JS, images) - แต่แนะนำใช้ CDN หรือ assets pipeline
- ❌ **ห้าม** ใส่โค้ด PHP หลักที่นี่

---

## 🎯 Workflow: เพิ่มฟีเจอร์ใหม่

### ตัวอย่าง: เพิ่มระบบรีวิวสินค้า

#### ขั้นตอนที่ 1: สร้างตาราง
```bash
# สร้างไฟล์ database/migrations/reviews.sql
# รัน SQL ในฐานข้อมูล
```

#### ขั้นตอนที่ 2: สร้าง Model
```php
// app/Models/Review.php
namespace App\Models;

class Review
{
    public function create(array $data): array { }
    public function getByProduct(int $productId): array { }
    public function getByUser(int $userId): array { }
}
```

#### ขั้นตอนที่ 3: สร้าง Controller
```php
// app/Controllers/ReviewController.php
namespace App\Controllers;

class ReviewController extends Controller
{
    public function store() { }
    public function index($productId) { }
}
```

#### ขั้นตอนที่ 4: เพิ่ม Routes
```php
// routes/web.php
$router->get('/products/{id}/reviews', 'ReviewController@index');
$router->post('/reviews', 'ReviewController@store', [AuthMiddleware::class]);
```

#### ขั้นตอนที่ 5: สร้าง API (ถ้าต้องการ)
```php
// app/Controllers/Api/V1/ReviewApiController.php
// routes/api.php
$router->post('/api/v1/reviews', 'Api\V1\ReviewApiController@create');
```

---

## 📝 Best Practices

### ✅ ควรทำ
- แยก business logic ไว้ใน Models
- ใช้ prepared statements เสมอ
- validate ข้อมูลทั้งฝั่ง client และ server
- เขียน comments ภาษาไทยอธิบายโค้ด
- ตั้งชื่อตัวแปรและ functions ให้ชัดเจน
- บันทึก log สำหรับ actions สำคัญ

### ❌ ไม่ควรทำ
- ใส่ business logic ใน Controllers
- ใช้ raw SQL queries
- เชื่อถือข้อมูลจาก client
- hard-code ค่าต่างๆ (ใช้ config)
- ทำ transactions ยาวๆ ที่ไม่จำเป็น

---

## 🔍 การ Debug

### ดูข้อผิดพลาด
```php
// ใน .env
APP_ENV=development
APP_DEBUG=true

// ตรวจสอบ logs
tail -f storage/logs/app.log
```

### เพิ่ม Debug Code
```php
// ใน Controller หรือ Model
error_log("Debug: " . print_r($data, true));

// หรือใช้ Logger
$logger = new \App\Core\Logger();
$logger->info('debug.data', ['data' => $data]);
```

---

## 📚 เอกสารเพิ่มเติม

- [README.md](../README.md) - ภาพรวมโปรเจค
- [composer.json](../composer.json) - Dependencies และ autoload
- [.env.example](../.env.example) - ตัวอย่างการตั้งค่า environment

---

**อัปเดตล่าสุด:** 26 ธันวาคม 2568  
**เวอร์ชัน:** 2.0
