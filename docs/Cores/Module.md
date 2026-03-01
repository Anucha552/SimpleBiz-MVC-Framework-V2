# Module System Guide

## แนวคิดของ Module คืออะไร

**Module** คือหน่วยย่อยของระบบที่สามารถแยกความรับผิดชอบออกจากกันได้อย่างชัดเจน เช่น
- User Management
- Auth
- Blog
- Payment
- API

แต่ละ Module จะเป็นเจ้าของ Route และ Service ของตัวเอง จากนั้น `ModuleManager` จะเป็นตัวรวมและสั่งให้แต่ละ Module ลงทะเบียนกับ Router

### ข้อดีของแนวคิดนี้
- แยกส่วนชัดเจน
- เปิด/ปิดฟีเจอร์ได้ง่าย
- ขยายระบบได้โดยไม่แก้ Core

---

## โครงสร้างที่เกี่ยวข้อง
ระบบประกอบด้วย 2 ส่วนหลัก
- `ModuleInterface`
- `ModuleManager`

### 1) ModuleInterface
- ไฟล์: `App\Core\ModuleInterface`
- กำหนด "สัญญา" ว่า Module ทุกตัวต้องมีเมธอด:

```php
public function register(Router $router): void;
```

หมายความว่า ทุก Module ต้องสามารถลงทะเบียน Route หรือ Service กับ Router ได้

### 2) ModuleManager
- ไฟล์: `App\Core\ModuleManager`
- หน้าที่:
  - โหลดรายชื่อ Module ที่เปิดใช้งานจาก `config/modules.php`
  - ตรวจสอบความถูกต้องของคลาส
  - เรียก `register()` ของแต่ละ Module

---

## ขั้นตอนการใช้งาน Module

### ขั้นตอนที่ 1: สร้าง Module ใหม่
ตัวอย่าง: สร้าง UserModule

```php
namespace App\Modules\User;

use App\Core\ModuleInterface;
use App\Core\Router;

class UserModule implements ModuleInterface
{
    public function register(Router $router): void
    {
        $router->addRoute('/users', 'UserController@index');
        $router->addRoute('/users/create', 'UserController@create');
    }
}
```

**หลักการ:**
- 1 Module = 1 Responsibility
- `register()` มีหน้าที่ "ลงทะเบียน Route เท่านั้น"
- ไม่ควรเขียน Business Logic ใน Module

### ขั้นตอนที่ 2: ลงทะเบียน Module ใน config
สร้างไฟล์: `config/modules.php`

```php
return [
    App\Modules\User\UserModule::class,
];
```

ถ้าต้องการปิด Module เพียงแค่ลบออกจาก array นี้

### ขั้นตอนที่ 3: เรียกใช้งานใน Bootstrap
ในไฟล์เริ่มต้นระบบ เช่น `index.php` หรือ `bootstrap.php`

```php
use App\Core\ModuleManager;

$moduleManager = new ModuleManager();
$moduleManager->registerEnabled($router);
```

เพียงเท่านี้ Module ทั้งหมดที่เปิดใช้งานจะถูกโหลดและ register route ให้อัตโนมัติ

---

## Flow การทำงานของระบบ
1. ระบบสร้าง Router
2. ระบบสร้าง ModuleManager
3. ModuleManager อ่าน `config/modules.php`
4. ตรวจสอบว่า class มีอยู่จริง
5. ตรวจสอบว่า implements ModuleInterface
6. เรียก `$module->register($router)`
7. Route ถูกเพิ่มเข้าสู่ Router

---

## ตัวอย่างโครงสร้างโฟลเดอร์ที่แนะนำ

```
app/
 ├── Core/
 │    ├── ModuleInterface.php
 │    └── ModuleManager.php
 │
 └── Modules/
      ├── User/
      │     ├── UserModule.php
      │     └── Controllers/
      │
      └── Blog/
            ├── BlogModule.php
            └── Controllers/

config/
 └── modules.php
```

---

## กฎการออกแบบที่ควรยึดถือ
- Module ต้องไม่พึ่งพา Module อื่นโดยตรง
- Module ควร register route เท่านั้น
- Service/Binding ถ้ามี ควรทำภายใน register()
- ห้ามเขียน logic หนักใน Module

> **Module = ตัวประกาศว่า "ฉันมี Route อะไรบ้าง"**

---

## ตัวอย่างเพิ่มหลาย Module
```php
return [
    App\Modules\User\UserModule::class,
    App\Modules\Blog\BlogModule::class,
    App\Modules\Auth\AuthModule::class,
];
```
`ModuleManager` จะวนลูปและ register ทีละตัว

---

## การจัดการ Error
ระบบจะ throw `RuntimeException` ในกรณี:
- ไม่พบคลาส Module
- Module ไม่ implements ModuleInterface

ตัวอย่าง error:
```
Module class not found: App\Modules\Fake\FakeModule

หรือ

Module must implement ModuleInterface
```

นี่เป็นการป้องกันระบบพังแบบเงียบ ๆ

---

## เปรียบเทียบแนวคิดกับ Framework อื่น
แนวคิดคล้ายกับระบบ Module ของ
- Laravel (Service Provider)
- Symfony (Bundle)

แต่ของคุณมีความเรียบง่ายและควบคุมได้เองทั้งหมด เหมาะกับการสร้าง Full-featured Framework ตามเป้าหมายของคุณ

---

## แนวคิดการพัฒนาในอนาคต
ถ้าต้องการยกระดับระบบ Module ให้เป็น Framework ระดับ Production แนะนำเพิ่ม:
- Auto Discovery (scan โฟลเดอร์ Modules อัตโนมัติ)
- Module Priority (กำหนดลำดับการโหลด)
- Dependency ระหว่าง Module
- Module Config แยกเฉพาะของตัวเอง
- Event System ต่อ Module

นี่จะทำให้ Framework ของคุณเข้าใกล้ระดับ Enterprise มากขึ้น

---

## สรุป
- Module มีหน้าที่: รวม Route ของฟีเจอร์หนึ่ง
- แยกความรับผิดชอบ
- เปิด/ปิดได้จาก config
- ช่วยให้ระบบขยายง่าย
