# คู่มือ Services - บริการระบบหลัก

เอกสารนี้อธิบายการใช้งานบริการสำคัญของเฟรมเวิร์กอย่างละเอียด

---

## 📚 สารบัญ

1. [Mail Service](#1-mail-service) - ส่งอีเมล
2. [FileUpload Service](#2-fileupload-service) - อัปโหลดไฟล์
3. [Cache Service](#3-cache-service) - แคชข้อมูล
4. [Logger Service](#4-logger-service) - บันทึกเหตุการณ์

---

## 1. Mail Service

บริการส่งอีเมลพร้อม template support และ SMTP

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

**หมายเหตุสำหรับ Gmail:**
- ใช้ App Password แทนรหัสผ่านปกติ
- เปิด "Less secure app access" หรือใช้ OAuth2

### การใช้งานพื้นฐาน

```php
use App\Core\Mail;

$mail = new Mail();

// ส่งอีเมลแบบง่าย
$mail->to('user@example.com', 'John Doe')
     ->subject('Welcome to Our Platform')
     ->html('<h1>Welcome!</h1><p>Thank you for joining us.</p>')
     ->send();
```

### ส่งด้วย Template

```php
// ส่งอีเมลด้วย template
$mail->to('user@example.com', 'Jane Doe')
     ->subject('Order Confirmation')
     ->template('emails/order-confirmation', [
         'name' => 'Jane Doe',
         'order_id' => 'ORD-12345',
         'total' => 1500.00,
         'items' => [
             ['name' => 'Product 1', 'price' => 500],
             ['name' => 'Product 2', 'price' => 1000]
         ]
     ])
     ->send();
```

### ส่งหลายคน

```php
// ส่งให้หลายคนพร้อมกัน
$mail->to(['user1@example.com', 'user2@example.com', 'user3@example.com'])
     ->subject('Newsletter - January 2026')
     ->template('emails/newsletter', ['month' => 'January'])
     ->send();
```

### แนบไฟล์

```php
// แนบไฟล์เอกสาร
$mail->to('user@example.com')
     ->subject('Invoice #12345')
     ->attach('/path/to/invoice.pdf')
     ->html('<p>Please find attached your invoice.</p>')
     ->send();

// แนบหลายไฟล์
$mail->to('user@example.com')
     ->subject('Documents')
     ->attach('/path/to/file1.pdf')
     ->attach('/path/to/file2.pdf')
     ->html('<p>Please find attached documents.</p>')
     ->send();
```

### Templates ที่มีให้ใช้

Framework มี email templates พื้นฐานใน `app/Views/emails/`:
- `welcome.php` - ยินดีต้อนรับสมาชิกใหม่
- `order-confirmation.php` - ยืนยันคำสั่งซื้อ
- `password-reset.php` - รีเซ็ตรหัสผ่าน

---

## 2. FileUpload Service

บริการอัปโหลดไฟล์พร้อมการตรวจสอบความปลอดภัย

### การใช้งานพื้นฐาน

```php
use App\Core\FileUpload;

$upload = new FileUpload();

// ตั้งค่าพื้นฐาน
$upload->setDestination('public/uploads/products')
       ->setAllowedTypes(['jpg', 'jpeg', 'png', 'gif'])
       ->setMaxSize(5 * 1024 * 1024); // 5MB

// อัปโหลดไฟล์
if (isset($_FILES['product_image'])) {
    $result = $upload->upload($_FILES['product_image']);
    
    if ($result['success']) {
        $filename = $result['filename'];
        echo "อัปโหลดสำเร็จ: " . $filename;
    } else {
        echo "ผิดพลาด: " . $result['error'];
    }
}
```

### ตรวจสอบ MIME Type

```php
// ตรวจสอบ MIME type อย่างละเอียด
$upload->setAllowedTypes(['jpg', 'jpeg', 'png'])
       ->setAllowedMimeTypes([
           'image/jpeg',
           'image/png'
       ]);
```

### เปลี่ยนชื่อไฟล์อัตโนมัติ

```php
// สร้างชื่อไฟล์ unique
$upload->setRenameStrategy('unique'); // timestamp + random

// ใช้ชื่อที่กำหนดเอง
$upload->setRenameStrategy('custom', 'product-' . $productId);
```

### อัปโหลดรูปภาพพร้อม Resize

```php
// อัปโหลดและปรับขนาด
$upload->setDestination('public/uploads/products')
       ->setAllowedTypes(['jpg', 'jpeg', 'png'])
       ->resize(800, 600) // width, height
       ->upload($_FILES['image']);
```

### ตัวอย่างการใช้ใน Controller

```php
class ProductController extends Controller
{
    public function store()
    {
        $request = new Request();
        
        if ($request->hasFile('image')) {
            $upload = new FileUpload();
            $upload->setDestination('public/uploads/products')
                   ->setAllowedTypes(['jpg', 'jpeg', 'png', 'webp'])
                   ->setMaxSize(5 * 1024 * 1024);
            
            $result = $upload->upload($_FILES['image']);
            
            if ($result['success']) {
                // บันทึกชื่อไฟล์ลง database
                $data['image'] = $result['filename'];
            } else {
                // จัดการ error
                Session::flash('error', $result['error']);
                redirect('/products/create');
            }
        }
        
        // บันทึกข้อมูลสินค้า...
    }
}
```

---

## 3. Cache Service

บริการแคชข้อมูลเพื่อเพิ่มประสิทธิภาพ

### การใช้งานพื้นฐาน

```php
use App\Core\Cache;

$cache = new Cache();

// เก็บข้อมูลใน cache
$cache->set('user_123', $userData, 3600); // เก็บ 1 ชั่วโมง

// ดึงข้อมูลจาก cache
$userData = $cache->get('user_123');

// ตรวจสอบว่ามี cache หรือไม่
if ($cache->has('user_123')) {
    echo "มี cache";
}

// ลบ cache
$cache->delete('user_123');
```

### แคชผลลัพธ์ Query

```php
// แคช query ที่ช้า
$cacheKey = 'popular_products';
$products = $cache->get($cacheKey);

if (!$products) {
    // ถ้าไม่มี cache ให้ query database
    $products = Product::where('is_featured = 1')
                       ->orderBy('views DESC')
                       ->limit(10)
                       ->get();
    
    // เก็บใน cache 1 ชั่วโมง
    $cache->set($cacheKey, $products, 3600);
}

return $products;
```

### แคชหน้าเว็บทั้งหน้า

```php
// Page cache
$cacheKey = 'page_' . $_SERVER['REQUEST_URI'];
$html = $cache->get($cacheKey);

if (!$html) {
    // สร้าง HTML
    ob_start();
    include 'view.php';
    $html = ob_get_clean();
    
    // แคช 5 นาที
    $cache->set($cacheKey, $html, 300);
}

echo $html;
```

### ล้าง Cache

```php
// ลบ cache ทั้งหมด
$cache->clear();

// ลบ cache ที่ขึ้นต้นด้วย prefix
$cache->deletePattern('user_*');
```

### ตำแหน่งเก็บ Cache

Cache ถูกเก็บใน `storage/cache/` เป็นไฟล์ `.cache`

---

## 4. Logger Service

บริการบันทึกเหตุการณ์และข้อผิดพลาด

### การใช้งานพื้นฐาน

```php
use App\Core\Logger;

$logger = new Logger();

// บันทึก info
$logger->info('User logged in', [
    'user_id' => 123,
    'ip' => $_SERVER['REMOTE_ADDR']
]);

// บันทึก error
$logger->error('Database connection failed', [
    'error' => $e->getMessage(),
    'file' => $e->getFile(),
    'line' => $e->getLine()
]);

// บันทึก warning
$logger->warning('Low disk space', [
    'available' => '100MB',
    'required' => '1GB'
]);

// บันทึก debug
$logger->debug('Processing payment', [
    'order_id' => 'ORD-123',
    'amount' => 1500.00
]);
```

### ระดับ Log Levels

- `debug` - ข้อมูลสำหรับ debugging
- `info` - ข้อมูลทั่วไป
- `warning` - คำเตือน
- `error` - ข้อผิดพลาด
- `critical` - ข้อผิดพลาดร้ายแรง

### Log Security Events

```php
// บันทึก security events
$logger->warning('Failed login attempt', [
    'username' => $username,
    'ip' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT']
]);

// บันทึก suspicious activity
$logger->error('Possible SQL injection attempt', [
    'query' => $suspiciousQuery,
    'ip' => $_SERVER['REMOTE_ADDR']
]);
```

### ตัวอย่างใน Controller

```php
class OrderController extends Controller
{
    private Logger $logger;
    
    public function __construct()
    {
        $this->logger = new Logger();
    }
    
    public function create()
    {
        try {
            // สร้าง order
            $order = Order::create($data);
            
            // บันทึก log
            $this->logger->info('Order created successfully', [
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'total' => $order->total
            ]);
            
        } catch (Exception $e) {
            // บันทึก error
            $this->logger->error('Failed to create order', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'data' => $data
            ]);
            
            throw $e;
        }
    }
}
```

### ตำแหน่งเก็บ Log

Log files ถูกเก็บใน `storage/logs/` โดยแยกตามวันที่:
- `storage/logs/2026-01-20.log`
- `storage/logs/2026-01-21.log`

---

## Best Practices

### Mail Service
- ใช้ queue สำหรับส่งอีเมลจำนวนมาก
- เก็บ email templates แยกจาก business logic
- ทดสอบอีเมลก่อนส่งจริง
- ตั้งค่า rate limiting เพื่อป้องกัน spam

### FileUpload Service
- **เสมอ** ตรวจสอบ MIME type และนามสกุล
- ตั้งขนาดไฟล์สูงสุด
- เก็บไฟล์นอก document root ถ้าเป็นไฟล์ sensitive
- ใช้ unique filename เพื่อป้องกันการเขียนทับ
- สแกน virus ก่อนเก็บ (ถ้าเป็น production)

### Cache Service
- ตั้ง expiration time ที่เหมาะสม
- ใช้ cache keys ที่มีความหมายชัดเจน
- ล้าง cache เมื่อมีการเปลี่ยนแปลงข้อมูล
- ไม่ควร cache ข้อมูล sensitive

### Logger Service
- บันทึก error ทุกครั้ง
- บันทึก security events
- เก็บ context ที่เป็นประโยชน์
- ตั้งค่า log rotation เพื่อไม่ให้ไฟล์ใหญ่เกินไป
- **อย่า** log sensitive data (passwords, credit cards)

---

ดูข้อมูลเพิ่มเติม:
- [CORE_USAGE.md](CORE_USAGE.md) - คู่มือ Core Classes
- [SECURITY_HARDENING.md](SECURITY_HARDENING.md) - Security Best Practices
