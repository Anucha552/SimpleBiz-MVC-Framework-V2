# คู่มือการใช้งาน Helpers

## Helpers คืออะไร

Helper คือฟังก์ชันช่วยเหลือที่ใช้บ่อยๆ ในการพัฒนา เรียกใช้ผ่าน static methods ไม่ต้องสร้าง instance

**การใช้งาน:**
```php
use App\Helpers\StringHelper;

$slug = StringHelper::slug('สวัสดีชาวโลก'); // "สวัสดี-ชาวโลก"
```

---

## 1. ArrayHelper
จัดการ array ขั้นสูง

### Methods:
- `get($array, $key, $default)` - ดึงค่าด้วย dot notation
- `set(&$array, $key, $value)` - ตั้งค่าด้วย dot notation
- `has($array, $key)` - ตรวจสอบว่ามี key
- `forget(&$array, $key)` - ลบ key
- `pluck($array, $value, $key)` - ดึงค่าบาง column
- `flatten($array, $depth)` - แปลงเป็น array เดียว
- `groupBy($array, $key)` - จัดกลุ่มตาม key
- `sortBy($array, $key, $direction)` - เรียงลำดับ
- `filter($array, $callback)` - กรองข้อมูล
- `map($array, $callback)` - แปลงข้อมูล
- `only($array, $keys)` - เลือกเฉพาะ keys
- `except($array, $keys)` - ยกเว้น keys
- `chunk($array, $size)` - แบ่งเป็นชุด
- `every($array, $callback)` - ตรวจสอบทุกตัว
- `some($array, $callback)` - ตรวจสอบบางตัว
- `first($array, $callback, $default)` - หาตัวแรก
- `last($array, $callback, $default)` - หาตัวสุดท้าย
- `wrap($value)` - แปลงเป็น array
- `removeNull($array)` - ลบค่า null
- `removeEmpty($array)` - ลบค่าว่าง
- `unique($array)` - ลบค่าซ้ำ
- `random($array, $count)` - สุ่มค่า
- `shuffle($array)` - สับเปลี่ยน
- `merge(...$arrays)` - รวม arrays
- `isAssoc($array)` - ตรวจสอบ associative
- `fromObject($object)` - แปลงจาก object
- `prepend($array, $value, $key)` - เพิ่มข้างหน้า
- `firstValue($array, $default)` - ค่าแรก
- `lastValue($array, $default)` - ค่าสุดท้าย
- `take($array, $count)` - เอาจำนวนแรก
- `skip($array, $count)` - ข้ามจำนวนแรก
- `paginate($array, $page, $perPage)` - แบ่งหน้า
- `countValues($array)` - นับค่าซ้ำ
- `zip(...$arrays)` - รวม arrays เป็นคู่

**ตัวอย่าง:**
```php
use App\Helpers\ArrayHelper;

$data = ['user' => ['name' => 'John', 'age' => 30]];
$name = ArrayHelper::get($data, 'user.name'); // "John"

$users = [
    ['name' => 'John', 'age' => 30],
    ['name' => 'Jane', 'age' => 25]
];
$names = ArrayHelper::pluck($users, 'name'); // ['John', 'Jane']
```

---

## 2. DateHelper
จัดการวันที่และเวลา

### Methods:
- `thaiDate($date, $shortMonth, $buddhistEra)` - แปลงเป็นภาษาไทย
- `thaiDateTime($datetime, $shortMonth, $buddhistEra)` - วันที่และเวลาไทย
- `thaiDay($date, $short)` - วันไทย
- `humanDate($date)` - วันที่แบบอ่านง่าย
- `timeAgo($datetime)` - เวลาที่ผ่านมา (5 นาทีที่แล้ว)
- `format($date, $format)` - จัดรูปแบบวันที่
- `diff($date1, $date2, $unit)` - คำนวณความต่าง
- `addDays($date, $days)` - เพิ่มวัน
- `subDays($date, $days)` - ลบวัน
- `addMonths($date, $months)` - เพิ่มเดือน
- `addYears($date, $years)` - เพิ่มปี
- `isToday($date)` - วันนี้หรือไม่
- `isYesterday($date)` - เมื่อวานหรือไม่
- `isTomorrow($date)` - พรุ่งนี้หรือไม่
- `isPast($date)` - อดีตหรือไม่
- `isFuture($date)` - อนาคตหรือไม่
- `startOfMonth($date)` - วันแรกของเดือน
- `endOfMonth($date)` - วันสุดท้ายของเดือน
- `fromTimestamp($timestamp)` - แปลงจาก timestamp
- `toTimestamp($date)` - แปลงเป็น timestamp
- `now($format)` - เวลาปัจจุบัน
- `today($format)` - วันนี้
- `yesterday($format)` - เมื่อวาน
- `tomorrow($format)` - พรุ่งนี้
- `isWeekend($date)` - เสาร์-อาทิตย์หรือไม่
- `isWeekday($date)` - จันทร์-ศุกร์หรือไม่

**ตัวอย่าง:**
```php
use App\Helpers\DateHelper;

echo DateHelper::thaiDate('2025-12-26'); // "26 ธันวาคม 2568"
echo DateHelper::timeAgo('2025-12-26 10:00:00'); // "5 ชั่วโมงที่แล้ว"
echo DateHelper::addDays('2025-12-26', 7); // "2026-01-02"
```

---

## 3. NumberHelper
จัดการตัวเลข

### Methods:
- `money($number, $decimals, $currency)` - จัดรูปแบบเงิน
- `baht($number, $decimals)` - บาท
- `format($number, $decimals)` - จัดรูปแบบ
- `percent($number, $decimals)` - เปอร์เซ็นต์
- `percentage($value, $total)` - คำนวณเปอร์เซ็นต์
- `fileSize($bytes, $decimals)` - ขนาดไฟล์ (KB, MB)
- `abbreviate($number, $decimals)` - ย่อ (1K, 1M)
- `ordinal($number)` - ลำดับที่ (1st, 2nd)
- `ordinalThai($number)` - ลำดับที่ไทย (ที่ 1, ที่ 2)
- `toThai($number)` - แปลงเป็นเลขไทย
- `fromThai($thaiNumber)` - แปลงจากเลขไทย
- `toWord($number)` - แปลงเป็นคำ (หนึ่งร้อย)
- `ceil($number, $precision)` - ปัดขึ้น
- `floor($number, $precision)` - ปัดลง
- `round($number, $precision)` - ปัดเศษ
- `average($numbers)` - ค่าเฉลี่ย
- `median($numbers)` - ค่ากลาง
- `min($numbers)` - ค่าน้อยสุด
- `max($numbers)` - ค่ามากสุด
- `sum($numbers)` - ผลรวม
- `isEven($number)` - เลขคู่
- `isOdd($number)` - เลขคี่
- `inRange($number, $min, $max)` - อยู่ในช่วง
- `clamp($number, $min, $max)` - จำกัดค่า
- `toRoman($number)` - เลขโรมัน
- `random($min, $max)` - สุ่มตัวเลข
- `vat($price, $vatRate)` - คำนวณ VAT
- `priceWithVat($price, $vatRate)` - ราคารวม VAT
- `priceBeforeVat($priceWithVat, $vatRate)` - ราคาก่อน VAT
- `discount($price, $discount)` - ส่วนลด
- `priceAfterDiscount($price, $discount)` - ราคาหลังลด
- `toBinary($number)` - แปลงเป็น binary
- `toHex($number)` - แปลงเป็น hex
- `toOctal($number)` - แปลงเป็น octal
- `fromBinary($binary)` - แปลงจาก binary
- `fromHex($hex)` - แปลงจาก hex
- `fromOctal($octal)` - แปลงจาก octal

**ตัวอย่าง:**
```php
use App\Helpers\NumberHelper;

echo NumberHelper::money(1250.50); // "฿1,250.50"
echo NumberHelper::baht(1250.50); // "1,250.50 บาท"
echo NumberHelper::percent(75.5); // "75.50%"
echo NumberHelper::fileSize(1048576); // "1.00 MB"
echo NumberHelper::priceWithVat(100, 7); // 107.00
```

---

## 4. StringHelper
จัดการ string

### Methods:
- `slug($text, $separator)` - สร้าง URL slug
- `truncate($text, $length, $suffix)` - ตัดความยาว
- `words($text, $words, $suffix)` - ตัดตามจำนวนคำ
- `random($length)` - สุ่ม string
- `camelCase($text)` - camelCase
- `studlyCase($text)` - StudlyCase
- `snakeCase($text)` - snake_case
- `kebabCase($text)` - kebab-case
- `startsWith($haystack, $needles)` - ขึ้นต้นด้วย
- `endsWith($haystack, $needles)` - ลงท้ายด้วย
- `contains($haystack, $needles)` - มีคำว่า
- `replaceFirst($search, $replace, $subject)` - แทนที่ตัวแรก
- `replaceLast($search, $replace, $subject)` - แทนที่ตัวสุดท้าย
- `stripTags($text, $allowedTags)` - ลบ HTML tags
- `upper($text)` - ตัวพิมพ์ใหญ่
- `lower($text)` - ตัวพิมพ์เล็ก
- `title($text)` - Title Case
- `isJson($text)` - ตรวจสอบ JSON
- `bahtText($number)` - แปลงเป็นตัวอักษรบาท
- `collapseWhitespace($text)` - ลบช่องว่างซ้ำ
- `replaceArray($replacements, $subject)` - แทนที่หลายตัว
- `limit($text, $limit, $end)` - จำกัดความยาว
- `mask($text, $start, $length, $mask)` - ซ่อนข้อความ

**ตัวอย่าง:**
```php
use App\Helpers\StringHelper;

echo StringHelper::slug('สินค้า iPhone 15 Pro'); // "สินค้า-iphone-15-pro"
echo StringHelper::truncate('Lorem ipsum dolor sit', 10); // "Lorem..."
echo StringHelper::camelCase('hello_world'); // "helloWorld"
echo StringHelper::random(16); // "a3Dk9fL2xM7nQp8V"
echo StringHelper::bahtText(1234.56); // "หนึ่งพันสองร้อยสามสิบสี่บาทห้าสิบหกสตางค์"
```

---

## 5. SecurityHelper
ความปลอดภัย

### Methods:
- `escape($string)` - Escape HTML (XSS protection)
- `escapeHtml($string)` - Escape HTML แบบเต็ม
- `sanitize($string)` - ทำความสะอาด string
- `sanitizeEmail($email)` - ทำความสะอาด email
- `sanitizeUrl($url)` - ทำความสะอาด URL
- `sanitizeInt($number)` - ทำความสะอาดตัวเลข
- `sanitizeFloat($float)` - ทำความสะอาดทศนิยม
- `stripTags($string, $allowedTags)` - ลบ HTML tags
- `stripJavaScript($string)` - ลบ JavaScript
- `stripSqlKeywords($string)` - ลบคำสั่ง SQL
- `isValidEmail($email)` - ตรวจสอบ email
- `isValidUrl($url)` - ตรวจสอบ URL
- `isValidIp($ip)` - ตรวจสอบ IP
- `base64Encode($string)` - เข้ารหัส Base64
- `base64Decode($string)` - ถอดรหัส Base64
- `base64UrlEncode($string)` - Base64 URL safe
- `base64UrlDecode($string)` - ถอดรหัส Base64 URL
- `hashPassword($password)` - เข้ารหัสรหัสผ่าน
- `verifyPassword($password, $hash)` - ตรวจสอบรหัสผ่าน
- `generateToken($length)` - สร้าง token
- `uuid()` - สร้าง UUID
- `generateCsrfToken()` - สร้าง CSRF token
- `verifyCsrfToken($token)` - ตรวจสอบ CSRF token
- `encrypt($data, $key)` - เข้ารหัสข้อมูล
- `decrypt($data, $key)` - ถอดรหัสข้อมูล
- `hash($data, $algo)` - สร้าง hash
- `hmac($data, $key, $algo)` - สร้าง HMAC
- `hashEquals($known, $user)` - เปรียบเทียบ hash
- `mask($string, $start, $length, $mask)` - ซ่อนข้อความ
- `maskEmail($email)` - ซ่อน email
- `maskPhone($phone)` - ซ่อนเบอร์โทร
- `cleanFilename($filename)` - ทำความสะอาดชื่อไฟล์
- `isAllowedExtension($filename, $allowedExtensions)` - ตรวจสอบนามสกุล
- `isAllowedMimeType($file, $allowedMimeTypes)` - ตรวจสอบ MIME type
- `escapeJson($string)` - Escape JSON
- `escapeJs($string)` - Escape JavaScript
- `escapeAttr($string)` - Escape HTML attribute
- `preventClickjacking()` - ป้องกัน Clickjacking
- `setCSP($policy)` - ตั้งค่า Content Security Policy
- `setSecurityHeaders()` - ตั้งค่า Security headers
- `forceHttps()` - บังคับ HTTPS
- `preventMimeSniffing()` - ป้องกัน MIME sniffing
- `rateLimitCheck($key, $maxAttempts, $decayMinutes)` - จำกัดอัตรา
- `clearRateLimit($key)` - ล้าง rate limit
- `checkPasswordStrength($password)` - ตรวจสอบความแข็งแรงรหัสผ่าน

**ตัวอย่าง:**
```php
use App\Helpers\SecurityHelper;

$safe = SecurityHelper::escape('<script>alert("XSS")</script>');
$hash = SecurityHelper::hashPassword('secret123');
$token = SecurityHelper::generateToken(32);
$masked = SecurityHelper::maskEmail('john@example.com'); // "j***@example.com"
```

---

## 6. UrlHelper
จัดการ URL

### Methods:
- `base()` - รับ base URL
- `to($path, $params)` - สร้าง URL เต็ม
- `current($withQueryString)` - URL ปัจจุบัน
- `previous($default)` - URL ก่อนหน้า
- `isSecure()` - ตรวจสอบ HTTPS
- `redirect($url, $statusCode)` - Redirect
- `back($default)` - กลับหน้าเดิม
- `addQuery($url, $params)` - เพิ่ม query string
- `removeQuery($url, $keys)` - ลบ query string
- `query($key, $default)` - รับค่า query string
- `is($pattern, $url)` - ตรวจสอบ pattern
- `asset($path)` - URL ของ asset
- `encode($url)` - Encode URL
- `decode($url)` - Decode URL
- `isValid($url)` - ตรวจสอบ URL
- `parse($url)` - แยกส่วน URL
- `domain($url)` - รับ domain
- `path($url)` - รับ path
- `cacheBust($url)` - เพิ่ม version query
- `page($page, $additionalParams)` - สร้าง URL หน้า
- `removeTrailingSlash($url)` - ลบ / ท้าย
- `addTrailingSlash($url)` - เพิ่ม / ท้าย
- `signed($url, $secret, $expiration)` - สร้าง signed URL
- `verifySignature($url, $secret)` - ตรวจสอบ signature
- `api($path, $params, $version)` - สร้าง API URL
- `join(...$segments)` - รวม URL segments

**ตัวอย่าง:**
```php
use App\Helpers\UrlHelper;

$base = UrlHelper::base(); // "https://example.com"
$url = UrlHelper::to('products', ['id' => 5]); // "https://example.com/products?id=5"
$current = UrlHelper::current(); // URL ปัจจุบัน
// Redirect (ควร return Response จาก controller/middleware)
return UrlHelper::redirect('/login');
$page = UrlHelper::query('page', 1); // รับค่า ?page=...
```

---

## 7. ResponseHelper
สำหรับ API responses

### Methods:
- `success($data, $message, $meta, $statusCode)` - Response สำเร็จ
- `error($message, $errors, $statusCode)` - Response ข้อผิดพลาด
- `created($data, $message)` - Response 201 Created
- `noContent()` - Response 204 No Content
- `notFound($message)` - Response 404 Not Found
- `unauthorized($message)` - Response 401 Unauthorized
- `forbidden($message)` - Response 403 Forbidden
- `validationError($errors, $message)` - Response 422 Validation Error
- `serverError($message)` - Response 500 Server Error
- `paginated($data, $total, $page, $perPage, $message)` - Response พร้อม pagination

**ตัวอย่าง:**
```php
use App\Helpers\ResponseHelper;

// Success
return ResponseHelper::success(['user' => $user], 'User retrieved');

// Error
return ResponseHelper::error('Invalid input', ['email' => 'Required'], 400);

// Not Found
return ResponseHelper::notFound('Product not found');

// Validation
return ResponseHelper::validationError(['name' => 'Name is required']);
```

**Response Format:**
```json
{
  "success": true,
  "data": {...},
  "message": "Success",
  "errors": [],
  "meta": {}
}
```

---

## 8. FormHelper (Views: flash/old/errors)

Helper สำหรับฝั่ง View เพื่อให้เขียนฟอร์ม + validation ง่ายขึ้น โดยอาศัย `Session` flash ที่ถูกตั้งค่าจาก `ValidationMiddleware`.

### Methods:
- `flash($key, $default)` - อ่าน flash message (แสดงในคำขอถัดไป)
- `hasFlash($key)` - ตรวจสอบ flash message
- `old($key, $default, $escape)` - ค่า old input สำหรับใส่ใน `value="..."`
- `oldRaw($key, $default)` - old input แบบ raw (อาจเป็น array)
- `hasOld($key)` - ตรวจสอบ old input
- `errors($field = null)` - errors ทั้งหมด หรือ errors ของ field
- `hasError($field)` - ตรวจสอบว่ามี error ของ field
- `firstError($field, $default, $escape)` - error message ตัวแรกของ field
- `invalidClass($field, $class)` - คืน class เช่น `is-invalid` เมื่อมี error
- `csrfField()` / `csrfMeta()` - HTML สำหรับ CSRF

**ตัวอย่าง (login form):**
```php
use App\Helpers\FormHelper;

<?php if (FormHelper::hasFlash('error')): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars((string) FormHelper::flash('error')) ?>
    </div>
<?php endif; ?>

<form method="POST" action="/login">
    <?= FormHelper::csrfField() ?>

    <input
        type="email"
        name="email"
        value="<?= FormHelper::old('email') ?>"
        class="<?= FormHelper::invalidClass('email') ?>"
    >
    <?php if (FormHelper::hasError('email')): ?>
        <div class="invalid-feedback"><?= FormHelper::firstError('email') ?></div>
    <?php endif; ?>

    <input type="password" name="password">
    <button type="submit">Login</button>
</form>
```

---

## ตัวอย่างการใช้งานรวม

```php
use App\Helpers\{
    StringHelper,
    NumberHelper,
    DateHelper,
    SecurityHelper,
    UrlHelper,
    ArrayHelper
};

// สร้าง product
$product = [
    'name' => 'iPhone 15 Pro',
    'price' => 39900,
    'created_at' => '2025-12-26 10:00:00'
];

// แสดงข้อมูล
$slug = StringHelper::slug($product['name']); // "iphone-15-pro"
$price = NumberHelper::money($product['price']); // "฿39,900.00"
$date = DateHelper::thaiDate($product['created_at']); // "26 ธันวาคม 2568"
$url = UrlHelper::to("products/{$slug}"); // "https://example.com/products/iphone-15-pro"

// ความปลอดภัย
$safeName = SecurityHelper::escape($product['name']);

// จัดการ array
$products = [...];
$names = ArrayHelper::pluck($products, 'name');
$grouped = ArrayHelper::groupBy($products, 'category');
```

---

## สรุป

Helper classes ช่วยให้เขียนโค้ดง่ายและสะอาดขึ้น:
- 📦 **ArrayHelper** - จัดการ array
- 📅 **DateHelper** - วันที่และเวลา
- 🔢 **NumberHelper** - ตัวเลขและเงิน
- 📝 **StringHelper** - ข้อความและ slug
- 🔒 **SecurityHelper** - ความปลอดภัย
- 🔗 **UrlHelper** - URL และ redirect
- 📡 **ResponseHelper** - API responses
- 🧾 **FormHelper** - View helpers (flash/old/errors/CSRF)

ทุก Helper ใช้ static methods เรียกง่าย ไม่ต้องสร้าง instance
