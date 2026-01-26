# Modules Guide

คู่มือนี้อธิบายระบบโมดูลของโปรเจกต์ — วิธีเปิดใช้งานโมดูลที่มีอยู่ และแนวทางเขียนโมดูลใหม่ (สั้น ๆ)

## โมดูลที่ติดตั้งในโปรเจกต์
- `Modules\Auth` — [เอกสาร](AUTH_MODULE.md)
- `Modules\HelloWorld` — [เอกสาร](HELLOWORLD_MODULE.md)

## วิธีเปิด/ปิดโมดูล
1. เปิดไฟล์ `config/modules.php`
2. เพิ่มหรือเอา class ของโมดูลเข้า/ออกจากอาร์เรย์ เช่น:

```php
return [
    // โมดูลของระบบ
    Modules\Auth\AuthModule::class,
    Modules\HelloWorld\HelloWorldModule::class,
];
```

3. ถ้าต้องการ ให้รัน `composer dump-autoload` เพื่ออัปเดต autoloader

## โครงสร้างโมดูล (แนะนำ)
- `ModuleName/ModuleNameModule.php` — คลาสที่ implement `App\Core\ModuleInterface` และลงทะเบียน routes
- `Controllers/` — คอนโทรลเลอร์ของโมดูล
- `Repositories/` — โค้ดเข้าถึงข้อมูล (optional)
- `Views/` — หากมี view เฉพาะโมดูล
- `migrations/` หรือ `database/migrations/` — ถ้ามี migration เฉพาะโมดูล

## แนวทางการพัฒนาโมดูลใหม่ (สั้นๆ)
1. สร้างโฟลเดอร์ `modules/MyModule` และไฟล์ `MyModule.php` ที่ implement `ModuleInterface` และมีเมธอด `register(Router $router)` เพื่อเพิ่ม route
2. สร้างคอนโทรลเลอร์ภายใต้ `Controllers/` และไฟล์ view ใน `app/Views/` หรือ `modules/MyModule/Views/` ตามต้องการ
3. เพิ่มโมดูลใน `config/modules.php` และทดสอบ route

---

ถ้าต้องการ ผมช่วย scaffold ตัวอย่างโมดูลใหม่หรือเพิ่มตัวอย่าง migration ให้ได้ครับ
````markdown
# Modules Guide

(เอกสารย้ายไปยัง `docs/modules/MODULES_GUIDE.md`)

````