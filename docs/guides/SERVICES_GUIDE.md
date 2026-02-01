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
# **Services Guide**

เอกสารนี้สรุปบริการหลัก (services) ที่มีอยู่ในโค้ดของเฟรมเวิร์ก พร้อมตัวอย่างการใช้งานและรายการเมธอดสาธารณะ (public API) เพื่อให้นำไปใช้ได้ตรงกับโค้ดจริง

---

**สรุปไฟล์บริการที่อ้างอิง:**
- [app/Core/Mail.php](app/Core/Mail.php#L1)
- [app/Core/FileUpload.php](app/Core/FileUpload.php#L1)
- [app/Core/Cache.php](app/Core/Cache.php#L1)
- [app/Core/Logger.php](app/Core/Logger.php#L1)
- [app/Core/Database.php](app/Core/Database.php#L1)
- [app/Core/Session.php](app/Core/Session.php#L1)
- [app/Core/View.php](app/Core/View.php#L1)

---

**การอ่านเอกสารนี้:** แต่ละหัวข้อมีคำอธิบายสั้น ๆ ของบริการ รายการเมธอดสำคัญ และตัวอย่างการใช้งานเป็นภาษาไทย

**1) Mail Service**

- **ไฟล์:** [app/Core/Mail.php](app/Core/Mail.php#L1)
- **จุดประสงค์:** ส่งอีเมลแบบ HTML/text, template support, แนบไฟล์
- **เมธอดสำคัญ:** `to()`, `from()`, `subject()`, `html()`, `text()`, `template()`, `attach()`, `send()`, `quick()`

ตัวอย่างพื้นฐาน:

```php
use App\Core\Mail;

$mail = new Mail();
$mail->to('user@example.com', 'John')
     ->subject('ยืนยันการสมัคร')
     ->html('<p>ขอบคุณ</p>')
     ->send();
```

ตัวอย่างส่งด้วย template:

```php
$mail->to('user@example.com')
     ->subject('แจ้งเตือนคำสั่งซื้อ')
     ->template('emails/order-confirmation', ['name' => 'Jane', 'order_id' => 'ORD-123'])
     ->send();
```

---

**2) FileUpload Service**

- **ไฟล์:** [app/Core/FileUpload.php](app/Core/FileUpload.php#L1)
- **จุดประสงค์:** จัดการการอัปโหลดไฟล์, ตรวจสอบชนิดไฟล์, ขนาด, เปลี่ยนชื่ออัตโนมัติ
- **เมธอดสำคัญ:** `setAllowedTypes()`, `setAllowedMimeTypes()`, `setMaxSize()`, `setUploadPath()`/`setDestination()`, `upload()`, `uploadMultiple()`, `resize()`, `getUploadedFileName()`, `deleteFile()`

ตัวอย่างการอัปโหลด (เรียบง่าย):

```php
use App\Core\FileUpload;

$u = new FileUpload();
$u->setUploadPath('public/uploads')
  ->setAllowedTypes(['jpg','png'])
  ->setMaxSize(5 * 1024 * 1024);

$result = $u->upload($_FILES['image']);
if ($result['success']) {
    $filename = $result['filename'];
}
```

---

**3) Cache Service**

- **ไฟล์:** [app/Core/Cache.php](app/Core/Cache.php#L1)
- **จุดประสงค์:** File-based cache (storage/cache/*.cache) พร้อม TTL และ helper utilities
- **เมธอดสำคัญ:** `set()`, `get()`, `has()`, `forget()` (หรือ `delete` alias), `remember()`, `rememberForever()`, `pull()`, `forever()`, `increment()`, `decrement()`, `flush()`, `clearExpired()`, `forgetPattern()`, `stats()`, `all()`

ตัวอย่างการใช้:

```php
use App\Core\Cache;

Cache::set('popular_products', $items, 3600);
$items = Cache::get('popular_products');
if (!Cache::has('popular_products')) {
    // สร้างค่าและเก็บ
}
```

หมายเหตุ: โครงสร้างเป็น file-based cache เก็บที่ `storage/cache/` (ไฟล์ .cache)

---

**4) Logger Service**

- **ไฟล์:** [app/Core/Logger.php](app/Core/Logger.php#L1)
- **จุดประสงค์:** บันทึกเหตุการณ์ (info, warning, error, security) และรองรับการหมุนไฟล์ตามวันที่/ขนาด
- **เมธอดสำคัญ:** `info()`, `warning()`, `error()`, `security()` (และ helper ภายในเช่น `getRecent()`, `clear()`)

ตัวอย่างการใช้งาน:

```php
use App\Core\Logger;

$logger = new Logger();
$logger->info('User.login', ['user_id' => 12]);
$logger->error('DB.failed', ['error' => $e->getMessage()]);
```

ตำแหน่งไฟล์ล็อก: `storage/logs/` แยกตามวันที่

---

**5) Database (PDO wrapper / Singleton)**

- **ไฟล์:** [app/Core/Database.php](app/Core/Database.php#L1)
- **จุดประสงค์:** จัดการการเชื่อมต่อ PDO แบบ singleton, ตั้งค่า PDO option เพื่อความปลอดภัย (prepared statements)
 - **เมธอดสำคัญ:** `getInstance()` และเมธอด wrapper ที่ควรใช้: `query()`, `fetch()`, `fetchAll()`, `fetchColumn()`, `fetchList()`, `fetchPairs()`, `execute()`, `prepare()`, `transaction()` (หลีกเลี่ยงการเรียก `getConnection()` โดยตรง)

 ตัวอย่างการใช้งานที่แนะนำ (ใช้เมธอดของ `Database` โดยตรง):

 ```php
 use App\Core\Database;

 $db = Database::getInstance();

 // ดึงแถวเดียว
 $user = $db->fetch('SELECT * FROM users WHERE id = :id', ['id' => $id]);

 // ดึงหลายแถว
 $rows = $db->fetchAll('SELECT * FROM posts WHERE user_id = :id', ['id' => $id]);

 // รันคำสั่งที่ไม่คืนค่า
 $db->execute('UPDATE users SET last_login = NOW() WHERE id = :id', ['id' => $id]);

 // ตัวช่วยสำหรับคอลัมน์เดี่ยว / คู่
 $emails = $db->fetchList('SELECT email FROM users WHERE active = 1');
 $map = $db->fetchPairs('SELECT id, username FROM users');

 // เตรียม statement หากต้องการใช้ PDOStatement โดยตรง
 $stmt = $db->prepare('SELECT * FROM sessions WHERE user_id = :id');
 $stmt->execute(['id' => $id]);
 $sessions = $stmt->fetchAll();
 ```

---

**6) Session Service**

- **ไฟล์:** [app/Core/Session.php](app/Core/Session.php#L1)
- **จุดประสงค์:** จัดการ session lifecycle, flash messages, CSRF token, old input
- **เมธอดสำคัญ:** `start()`, `isStarted()`, `set()`, `get()`, `has()`, `remove()`, `pull()`, `all()`, `clear()`, `destroy()`, `regenerate()`,
  `flash()`, `getFlash()`, `hasFlash()`, `getAllFlash()`, `keepFlash()`, `flashInput()`, `old()`, `generateCsrfToken()`, `getCsrfToken()`, `verifyCsrfToken()`, `csrfField()`, `csrfMeta()`

ตัวอย่างการใช้งาน flash / csrf:

```php
use App\Core\Session;

Session::start();
Session::flash('success', 'บันทึกสำเร็จ');
$msg = Session::getFlash('success');

// CSRF
$token = Session::generateCsrfToken();
echo Session::csrfField();
```

---

**7) View helper**

- **ไฟล์:** [app/Core/View.php](app/Core/View.php#L1)
- **จุดประสงค์:** จัดการการเรนเดอร์วิว, เลย์เอาท์, ส่วน (sections)
- **เมธอดสำคัญ:** `__construct($view, $data)`, `layout()`, `section()`/`start()`, `endSection()`/`end()`, `yieldSection()`/`yield()`, `render()`, `show()`

ตัวอย่าง:

```php
use App\Core\View;

$v = new View('products/show', ['product' => $product]);
echo $v->layout('main')->render();
```

---

**ข้อแนะนำสั้น ๆ (Best Practices)**
- ใช้ queue สำหรับส่งอีเมลจำนวนมาก แทนส่งแบบ synchronous
- ตรวจสอบและ sanitize ไฟล์ก่อนบันทึก (FileUpload)
- ตั้ง TTL สำหรับ cache ตามชนิดข้อมูล และใช้ `remember()` สำหรับ expensive queries
- บันทึกเหตุการณ์ความปลอดภัยแยกเป็น `security` level ใน `Logger`
- ใช้ prepared statements ผ่านเมธอดของ `Database` (เช่น `prepare()`, `fetch()`, `execute()`) เสมอ

---

ถัดไป: ถ้าต้องการ ผมจะรันการตรวจสอบลิงก์ภายใน `docs/` เพื่ออัปเดตลิงก์ที่อาจชี้ไปยังไฟล์ต้นฉบับที่ถูกย้าย/ลบ — ให้ผมเริ่มขั้นตอนตรวจลิงก์ต่อหรือไม่?
