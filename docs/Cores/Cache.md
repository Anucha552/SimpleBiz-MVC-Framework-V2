# Cache Class Guide

คลาส `Cache` ใช้สำหรับจัดการการเก็บข้อมูลชั่วคราว (File-based Cache) เพื่อเพิ่มประสิทธิภาพของแอปพลิเคชัน เช่น ลดการ Query ฐานข้อมูลซ้ำ หรือเก็บผลลัพธ์จากการคำนวณที่ใช้บ่อย

## รองรับ
- TTL (กำหนดเวลาหมดอายุ)
- เก็บแบบไม่หมดอายุ
- Increment / Decrement
- Pattern delete
- ลบข้อมูลหมดอายุ
- สถิติและ Debug

---

## 1) การตั้งค่าโฟลเดอร์ Cache
**ค่าเริ่มต้น:**
- `storage/cache`

**เปลี่ยนโฟลเดอร์เอง**
```php
use App\Core\Cache;
Cache::setCacheDirectory('custom/cache/path');
```

**ใช้ public storage**
```php
Cache::usePublicStorage(true);
```

---

## 2) การบันทึกข้อมูล (set)
```php
Cache::set('user_1', ['name' => 'John'], 600);
```
- `key` = ชื่ออ้างอิง
- `value` = ค่าที่ต้องการเก็บ
- `600` = อายุ 600 วินาที (10 นาที)
- ถ้า `ttl = 0` หมายถึงไม่หมดอายุ

---

## 3) การดึงข้อมูล (get)
```php
$user = Cache::get('user_1', []);
```
ถ้าไม่มีข้อมูล จะคืนค่า default (`[]`)

---

## 4) ตรวจสอบว่ามี cache หรือไม่ (has)
```php
if (Cache::has('user_1')) {
    echo "มี cache";
}
```
จะคืนค่า true เฉพาะกรณีที่:
- มีไฟล์
- ยังไม่หมดอายุ

---

## 5) ลบ cache รายตัว (forget)
```php
Cache::forget('user_1');
```

---

## 6) ดึงแล้วลบทันที (pull)
```php
$token = Cache::pull('session_token', null);
```
ใช้กับข้อมูลแบบใช้ครั้งเดียว เช่น OTP, session ชั่วคราว

---

## 7) remember (ดึงถ้ามี / สร้างถ้าไม่มี)
**รูปแบบที่ใช้บ่อยที่สุด**
```php
$user = Cache::remember('user_1', 3600, function () {
    return ['name' => 'John', 'age' => 30];
});
```
**ลำดับการทำงาน:**
1. ตรวจสอบ cache
2. ถ้ามี → คืนค่า
3. ถ้าไม่มี → เรียก callback
4. บันทึกลง cache
5. คืนค่าที่สร้างใหม่

เหมาะกับ Query Database

**ตัวอย่างใช้งานจริง:**
```php
$users = Cache::remember('users_all', 300, function () {
    return User::all();
});
```

---

## 8) rememberForever
เก็บแบบไม่หมดอายุ
```php
$settings = Cache::rememberForever('app_settings', function () {
    return ['theme' => 'dark'];
});
```

---

## 9) บันทึกแบบไม่หมดอายุ (forever)
```php
Cache::forever('config_data', ['version' => '1.0']);
```

---

## 10) เพิ่มค่า (increment)
ใช้กับตัวเลขเท่านั้น
```php
Cache::increment('page_views');
Cache::increment('page_views', 5);
```
ถ้า key ยังไม่มี จะสร้างใหม่โดยเริ่มต้นตามค่าที่เพิ่ม

---

## 11) ลดค่า (decrement)
```php
Cache::decrement('page_views');
Cache::decrement('page_views', 3);
```

---

## 12) ลบ cache ทั้งหมด (flush)
```php
Cache::flush();
```
ลบทุกไฟล์ในโฟลเดอร์ cache

---

## 13) ลบ cache ที่หมดอายุ
```php
$deleted = Cache::clearExpired();
```
คืนค่าจำนวนไฟล์ที่ถูกลบ

---

## 14) ลบตาม pattern
```php
Cache::forgetPattern('user_*');
```
**ตัวอย่าง:**
- ลบ cache ของ user ทั้งหมด
- ลบ cache ที่ขึ้นต้นด้วย order_

---

## 15) ดูสถิติ cache
```php
$stats = Cache::stats();
```
**ค่าที่ได้:**
```php
[
    'total_files' => 10,
    'total_size' => 20480,
    'total_size_formatted' => '20 KB',
    'expired_files' => 2,
]
```

---

## 16) ดู cache ทั้งหมด (Debug)
```php
$all = Cache::all();
```
**ตัวอย่างผลลัพธ์:**
```php
[
    'user_1' => [
        'key' => 'user_1',
        'expires_at' => 1700000000,
        'created_at' => 1699990000,
        'is_expired' => false,
        'file' => 'a1b2c3.cache',
    ]
]
```

---

## 17) ลบ cache ที่เก่ากว่า X วินาที
```php
Cache::clearOlderThan(3600);
```
ลบ cache ที่ถูกสร้างมากกว่า 1 ชั่วโมง

---

## 18) Reset (สำหรับ testing)
```php
Cache::reset();
```
ใช้ใน Unit Test เพื่อล้างสภาพแวดล้อม

---

## แนวทางการใช้งานที่แนะนำ (Best Practice)
1. **Cache Query Database**
    ```php
    $products = Cache::remember('products_all', 600, function () {
        return Product::all();
    });
    ```
2. **Cache Config**
    ```php
    $config = Cache::rememberForever('app_config', function () {
        return require 'config/app.php';
    });
    ```
3. **Cache Counter**
    ```php
    Cache::increment('homepage_views');
    ```

---

## ข้อควรระวัง
- หลีกเลี่ยงการเก็บ Object ขนาดใหญ่เกินไป
- หลีกเลี่ยงการเก็บ Resource (เช่น DB connection)
- ควรเรียก `clearExpired()` เป็นระยะ
- ใช้ `remember()` แทน get() + set() เพื่อป้องกันเขียนโค้ดซ้ำ

---

## สรุปแนวคิดสำคัญ
- ใช้ `remember()` เป็นหลัก
- ใช้ `forever()` สำหรับ config
- ใช้ `increment()` สำหรับ counter
- ใช้ `flush()` เฉพาะตอน maintenance
