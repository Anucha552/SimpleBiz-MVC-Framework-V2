# Modules Guide

ระบบ `modules/` ช่วยให้คุณสร้าง “ส่วนเสริม” แยกจาก framework base ได้ โดยโปรเจกต์ไหนต้องใช้ค่อยเปิดใช้งานผ่าน config

---

## โครงสร้าง

ตัวอย่างโมดูล:

```
modules/
  HelloWorld/
    HelloWorldModule.php
    Controllers/
      HelloController.php
```

---

## เปิดใช้งานโมดูล

1) เพิ่ม class ของโมดูลใน `config/modules.php`

```php
return [
  Modules\HelloWorld\HelloWorldModule::class,
];
```

2) อัปเดต autoload (ถ้าเพิ่งเพิ่มไฟล์/namespace)

`composer dump-autoload`

---

## โมดูลต้องทำอะไรบ้าง

โมดูลต้อง implement `App\Core\ModuleInterface` และมีเมธอด `register(App\Core\Router $router)`

ภายใน `register()` คุณสามารถเพิ่ม routes ได้ตามปกติ:

```php
$router->get('/hello', Controllers\HelloController::class . '@index');
```

---

## การทำงานตอน runtime

- `public/index.php` โหลด `routes/web.php` และ `routes/api.php` ก่อน
- จากนั้นจะเรียก `ModuleManager::registerEnabled($router)` เพื่อให้โมดูล register routes เพิ่ม
- แล้วค่อยรัน global middleware และ dispatch

หมายเหตุ: เพราะโหลดโมดูล “หลัง” routes หลัก โมดูลสามารถ override route เดิมได้ (path/method เดียวกัน)

---

## โมดูลที่มีให้

- Auth module: ดูรายละเอียดที่ `docs/AUTH_MODULE.md`
