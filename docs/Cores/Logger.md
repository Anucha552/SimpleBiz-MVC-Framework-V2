# Logger Class Guide

## คำอธิบาย
คลาส Logger ใช้สำหรับบันทึกเหตุการณ์ต่าง ๆ ภายในแอปพลิเคชัน โดยเน้นความปลอดภัย การตรวจสอบย้อนหลัง และการดีบักใน production

เหมาะสำหรับ Framework หรือระบบที่ต้องการ:
- ตรวจสอบพฤติกรรมผู้ใช้
- บันทึกเหตุการณ์ด้านความปลอดภัย
- วิเคราะห์ปัญหา production
- รองรับการหมุนไฟล์ (log rotation)
- รองรับการลบไฟล์เก่าตาม retention days
- รองรับการ redaction ข้อมูลสำคัญอัตโนมัติ

## โครงสร้าง Log Format
```
[timestamp] [LEVEL] event {"context":"value"} 
app=appName user_id=xx ip=xx method=xx route=xx request_id=xx
```
ตัวอย่าง:
```
[2026-03-01 10:30:45] [SECURITY] login.failed {"username":"admin"} app=MyApp user_id=guest ip=192.168.1.1 method=POST route=/login request_id=abc123
```

## การสร้าง Logger
### ใช้ค่าเริ่มต้น (แนะนำ)
```php
use App\Core\Logger;

$logger = new Logger();
```
ค่าเริ่มต้นจะบันทึกไฟล์ไว้ที่:
```
/storage/logs/YYYY-MM-DD.log
```
### ระบุ Path เอง
#### ระบุเป็น Directory
```php
$logger = new Logger('/var/www/logs/');
```
ระบบจะสร้างไฟล์เป็น:
```
/var/www/logs/YYYY-MM-DD.log
```
#### ระบุเป็นไฟล์โดยตรง
```php
$logger = new Logger('/var/www/logs/custom.log');
```

## ระดับ Log ที่รองรับ

### 1. Info
ใช้สำหรับเหตุการณ์ทั่วไปของระบบ
```php
$logger->info('cart.add', [
    'product_id' => 123,
    'quantity' => 2
]);
```
เหมาะกับ:
- user activity
- business flow events

### 2. Security
ใช้สำหรับเหตุการณ์ด้านความปลอดภัย
```php
$logger->security('login.failed', [
    'username' => 'admin'
]);
```
เหมาะกับ:
- login failed
- token tampering
- suspicious activity

> หมายเหตุ: SECURITY จะถูกส่งเข้า error_log() ของ PHP เพิ่มเติม

### 3. Error
ใช้สำหรับข้อผิดพลาดของระบบ
```php
$logger->error('db.failure', [
    'message' => $e->getMessage()
]);
```
เหมาะกับ:
- database error
- uncaught exception
- validation failure

### 4. Warning
ใช้สำหรับเหตุการณ์ที่ควรระวัง แต่ไม่ใช่ error
```php
$logger->warning('rate_limit.exceeded', [
    'ip' => '192.168.1.1'
]);
```

## การตั้งค่าใน Config
สามารถตั้งค่าในไฟล์ config เช่น:
```php
'logging' => [
    'max_log_size' => 1048576, // 1MB
    'retention_days' => 7,
],
```
- **max_log_size**: กำหนดขนาดไฟล์สูงสุด (bytes) ถ้าเกินจะหมุนไฟล์เป็น:
  - 2026-03-01-001.log
  - 2026-03-01-002.log
- **retention_days**: กำหนดจำนวนวันที่เก็บ log (7 = เก็บ 7 วันล่าสุด, 1 = เก็บเฉพาะวันนี้)

## การดึง Log ล่าสุด
```php
$recent = $logger->getRecent(50);
foreach ($recent as $line) {
    echo $line . PHP_EOL;
}
```
ระบบจะอ่านไฟล์จากท้ายไฟล์ (memory efficient)

## การล้าง Log เก่าแบบ Manual
```php
$deleted = $logger->cleanup();
echo "Deleted {$deleted} files";
```

## การล้าง Log ทั้งหมด
```php
$logger->clear();
```
> **คำเตือน:** จะลบทุกไฟล์ .log* ใน directory

## ระบบ Redaction (ป้องกันข้อมูลสำคัญรั่ว)
โดยค่าเริ่มต้นจะปิดบัง key เหล่านี้:
- password
- token
- secret
- api_key
- authorization
- cookie

ตัวอย่าง:
```php
$logger->info('user.login', [
    'username' => 'admin',
    'password' => '123456'
]);
```
ผลลัพธ์:
```
{"username":"admin","password":"[REDACTED]"}
```

### ตั้ง Allow List
ถ้ากำหนด allow list ระบบจะบันทึกเฉพาะ key ที่อนุญาต
```php
$logger->setRedactionAllowList([
    'product_id',
    'quantity'
]);
```
### ตั้ง Deny List เพิ่มเติม
```php
$logger->addRedactionDenyKeys([
    'credit_card'
]);
```

## ตัวอย่างใช้งานจริงใน Controller
```php
use App\Core\Logger;

class AuthController
{
    private Logger $logger;

    public function __construct()
    {
        $this->logger = new Logger();
    }

    public function login()
    {
        try {
            // login logic
            $this->logger->info('login.success', [
                'user_id' => 5
            ]);
        } catch (\Exception $e) {
            $this->logger->security('login.failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

## แนวทางการออกแบบเชิง Architecture
- Logger ควรถูก inject ผ่าน DI Container
- ไม่ควร new Logger ในทุก class
- ควรมี Global instance หรือ Service Container
- SECURITY log ควร monitor แยกไฟล์ในอนาคต

## Best Practice
- ห้าม log raw password เด็ดขาด
- ห้าม log JWT เต็ม token
- ไม่ควร log ข้อมูลส่วนตัวเต็มรูปแบบ (PII)
- ใช้ SECURITY level กับเหตุการณ์สำคัญเสมอ
- เปิด rotation เสมอใน production
- ตั้ง retention days ให้เหมาะกับ storage
