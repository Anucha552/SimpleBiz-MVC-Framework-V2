# Framework vs Examples - แยกส่วนระบบและตัวอย่าง

เอกสารนี้อธิบายว่าส่วนไหนเป็น **ระบบ Framework** (ห้ามลบ) และส่วนไหนเป็น **ตัวอย่าง** (แก้ไข/ลบได้)

---

## 🔵 ส่วนที่เป็น FRAMEWORK (Core System) - ห้ามลบ

### 1. **app/Core/** - ⚙️ ระบบหลักทั้งหมด
```
app/Core/
├── Auth.php              ✅ ระบบ Authentication
├── Cache.php             ✅ ระบบ Cache
├── Controller.php        ✅ Base Controller
├── Database.php          ✅ ระบบเชื่อมต่อฐานข้อมูล
├── ErrorHandler.php      ✅ ระบบจัดการ Error
├── FileUpload.php        ✅ ระบบอัปโหลดไฟล์
├── Logger.php            ✅ ระบบบันทึก Log
├── Mail.php              ✅ ระบบส่งอีเมล
├── Middleware.php        ✅ Base Middleware
├── Migration.php         ✅ ระบบ Migration
├── MigrationRunner.php   ✅ เครื่องมือรัน Migration
├── Model.php             ✅ Base Model (Active Record)
├── Pagination.php        ✅ ระบบแบ่งหน้า
├── Request.php           ✅ ระบบจัดการ HTTP Request
├── Router.php            ✅ ระบบ Routing
├── Seeder.php            ✅ Base Seeder
├── Session.php           ✅ ระบบจัดการ Session
├── Validator.php         ✅ ระบบตรวจสอบข้อมูล
└── View.php              ✅ ระบบ Template Engine
```
**สถานะ:** ⛔ **ห้ามลบ** - เป็นหัวใจของ Framework

---

### 2. **app/Helpers/** - 🛠️ Helper Functions ทั้งหมด
```
app/Helpers/
├── ArrayHelper.php       ✅ จัดการ Array
├── DateHelper.php        ✅ จัดการวันที่
├── NumberHelper.php      ✅ จัดการตัวเลข
├── ResponseHelper.php    ✅ JSON Response
├── SecurityHelper.php    ✅ ฟังก์ชันความปลอดภัย
├── StringHelper.php      ✅ จัดการ String
└── UrlHelper.php         ✅ จัดการ URL
```
**สถานะ:** ⛔ **ห้ามลบ** - ฟังก์ชันช่วยเหลือที่ใช้บ่อย

---

### 3. **app/Middleware/** - 🚦 Middleware ระบบ
```
app/Middleware/
├── ApiKeyMiddleware.php       ✅ ตรวจสอบ API Key
├── AuthMiddleware.php         ✅ ตรวจสอบการ Login
├── CorsMiddleware.php         ✅ จัดการ CORS
├── CsrfMiddleware.php         ✅ ป้องกัน CSRF
├── GuestMiddleware.php        ✅ สำหรับผู้ที่ยังไม่ Login
├── LoggingMiddleware.php      ✅ บันทึก Request
├── MaintenanceMiddleware.php  ✅ โหมดปิดปรุง
├── RateLimitMiddleware.php    ✅ จำกัดจำนวน Request
├── RoleMiddleware.php         ✅ ตรวจสอบ Role/Permission
└── ValidationMiddleware.php   ✅ ตรวจสอบข้อมูล
```
**สถานะ:** ⛔ **ห้ามลบ** - ใช้ในระบบหลัก

---

### 4. **config/** - ⚙️ Configuration Files
```
config/
├── app.php           ✅ ค่า Config ของแอป
└── database.php      ✅ ค่า Config ฐานข้อมูล
```
**สถานะ:** ⛔ **ห้ามลบ** - จำเป็นสำหรับระบบ

---

### 5. **public/** - 🌐 Web Root
```
public/
├── index.php         ✅ Entry Point (Front Controller)
├── .htaccess         ✅ Apache Rewrite Rules
└── assets/           ✅ CSS, JS, Images (แก้ไขได้)
```
**สถานะ:** ⛔ **index.php และ .htaccess ห้ามลบ**

---

### 6. **routes/** - 🛣️ Route Files
```
routes/
├── web.php           ✅ Web Routes (แก้ไขได้)
└── api.php           ✅ API Routes (แก้ไขได้)
```
**สถานะ:** ⚠️ **เก็บไว้แต่แก้ไข routes ได้**

---

### 7. **storage/** - 💾 Storage Directories
```
storage/
├── logs/             ✅ เก็บ Log Files
└── cache/            ✅ เก็บ Cache Files
```
**สถานะ:** ⛔ **ห้ามลบโฟลเดอร์**

---

### 8. **tests/** - 🧪 Testing Infrastructure
```
tests/
├── TestCase.php      ✅ Base Test Class
├── Unit/             ✅ Unit Tests (แก้ไขได้)
└── Feature/          ✅ Feature Tests (แก้ไขได้)
```
**สถานะ:** ⚠️ **TestCase.php ห้ามลบ, tests อื่นแก้ไขได้**

---

### 9. **CLI Tools** - 💻 Command Line Tools
```
├── console           ✅ CLI Command Runner
├── migrate.php       ✅ Migration Tool
└── seed.php          ✅ Seeder Tool
```
**สถานะ:** ⛔ **ห้ามลบ** - เครื่องมือสำคัญ

---

### 10. **Configuration Files** - 📝 Project Config
```
├── .env.example      ✅ Template Environment
├── .gitignore        ✅ Git Ignore Rules
├── .htaccess         ✅ Apache Config (root)
├── composer.json     ✅ Composer Dependencies
├── phpunit.xml       ✅ PHPUnit Config
└── README.md         ✅ Documentation
```
**สถานะ:** ⛔ **ห้ามลบ** - จำเป็นสำหรับโปรเจค

---

## 🟢 ส่วนที่เป็น EXAMPLES (ตัวอย่าง) - แก้ไข/ลบได้

### 1. **app/Controllers/** - 📋 Controllers ตัวอย่าง
```
app/Controllers/
├── HomeController.php           🟡 ตัวอย่าง (แก้ไข/ลบได้)
├── AuthController.php           🟡 ตัวอย่าง (แต่มีประโยชน์)
├── Api/V1/
│   ├── ProductApiController.php 🟡 ตัวอย่าง E-commerce API
│   ├── CartApiController.php    🟡 ตัวอย่าง E-commerce API
│   └── OrderApiController.php   🟡 ตัวอย่าง E-commerce API
└── Ecommerce/
    ├── ProductController.php    🟡 ตัวอย่าง E-commerce Web
    ├── CartController.php       🟡 ตัวอย่าง E-commerce Web
    └── OrderController.php      🟡 ตัวอย่าง E-commerce Web
```
**สถานะ:** ✅ **แก้ไข/ลบได้** - เป็นตัวอย่างระบบอีคอมเมิร์ซ

**คำแนะนำ:**
- **เก็บไว้** ถ้าต้องการพัฒนาระบบอีคอมเมิร์ซ
- **ลบทิ้ง** ถ้าจะทำระบบอื่น (Blog, CMS, etc.)
- **ใช้เป็น Template** คัดลอกโครงสร้างไปสร้าง Controller ใหม่

---

### 2. **app/Models/** - 📊 Models ตัวอย่าง
```
app/Models/
├── User.php            🟡 ตัวอย่าง (แต่มีประโยชน์มาก - ควรเก็บ)
├── Product.php         🟡 ตัวอย่าง E-commerce
├── Category.php        🟡 ตัวอย่าง E-commerce
├── Cart.php            🟡 ตัวอย่าง E-commerce
├── Order.php           🟡 ตัวอย่าง E-commerce
├── Review.php          🟡 ตัวอย่าง E-commerce
├── Media.php           🟡 ตัวอย่าง
├── Address.php         🟡 ตัวอย่าง
├── Notification.php    🟡 ตัวอย่าง
├── Role.php            🟡 ตัวอย่าง (มีประโยชน์)
├── Permission.php      🟡 ตัวอย่าง (มีประโยชน์)
├── ApiKey.php          🟡 ตัวอย่าง (มีประโยชน์)
├── Page.php            🟡 ตัวอย่าง
├── Setting.php         🟡 ตัวอย่าง
├── ActivityLog.php     🟡 ตัวอย่าง (มีประโยชน์)
└── TestModel.php       🟡 สำหรับ Testing เท่านั้น
```
**สถานะ:** ✅ **แก้ไข/ลบได้** - เป็นตัวอย่าง Data Models

**คำแนะนำ:**
- **User.php** - ควรเก็บไว้ (ใช้บ่อย)
- **Role.php, Permission.php, ApiKey.php, ActivityLog.php** - มีประโยชน์
- **Models อื่น** - ลบได้ถ้าไม่ใช้ หรือแก้ไขตามโปรเจคของคุณ
- **TestModel.php** - เก็บไว้สำหรับ testing

---

### 3. **app/Views/** - 🎨 View Templates ตัวอย่าง
```
app/Views/
├── layouts/
│   └── main.php            🟡 ตัวอย่าง Layout (แก้ไขได้)
├── home/
│   └── index.php           🟡 ตัวอย่าง Home page
├── auth/
│   ├── login.php           🟡 ตัวอย่าง Login page
│   └── register.php        🟡 ตัวอย่าง Register page
├── products/
│   ├── index.php           🟡 ตัวอย่าง E-commerce
│   └── show.php            🟡 ตัวอย่าง E-commerce
├── cart/
│   └── index.php           🟡 ตัวอย่าง E-commerce
├── orders/
│   ├── index.php           🟡 ตัวอย่าง E-commerce
│   └── show.php            🟡 ตัวอย่าง E-commerce
├── emails/
│   ├── welcome.php         🟡 ตัวอย่าง Email template
│   ├── order_confirmation.php  🟡 ตัวอย่าง
│   └── password_reset.php  🟡 ตัวอย่าง
└── errors/
    ├── 404.php             🟡 แก้ไขได้ (แต่ควรเก็บ)
    ├── 403.php             🟡 แก้ไขได้ (แต่ควรเก็บ)
    ├── 500.php             🟡 แก้ไขได้ (แต่ควรเก็บ)
    └── 503.php             🟡 แก้ไขได้ (แต่ควรเก็บ)
```
**สถานะ:** ✅ **แก้ไข/ลบได้ทั้งหมด**

**คำแนะนำ:**
- **layouts/main.php** - แก้ไขตามธีมของคุณ
- **error pages** - ควรเก็บไว้แต่ปรับแต่งตาม design
- **Views อื่น** - แก้ไขหรือสร้างใหม่ตามโปรเจค

---

### 4. **database/migrations/** - 🗄️ Migration Files ตัวอย่าง
```
database/migrations/
├── core/
│   ├── 2024_01_01_000001_create_users_table.php    🟡 ควรเก็บ
│   └── 2024_01_01_000002_create_roles_table.php    🟡 แก้ไข/ลบได้
├── ecommerce/
│   ├── products, categories, orders, etc.          🟡 ตัวอย่าง E-commerce
└── system/
    ├── api_keys, notifications, etc.               🟡 แก้ไข/ลบได้
```
**สถานะ:** ✅ **แก้ไข/ลบได้** - Migration ตัวอย่าง

**คำแนะนำ:**
- **create_users_table** - ควรเก็บไว้ (ใช้บ่อย)
- **Migrations อื่น** - ลบได้ถ้าไม่ใช้ หรือปรับตามโปรเจค
- สร้าง migrations ใหม่ด้วย `php console make:migration`

---

### 5. **database/seeders/** - 🌱 Seeder Files ตัวอย่าง
```
database/seeders/
├── CategorySeeder.php   🟡 ตัวอย่าง E-commerce
├── UserSeeder.php       🟡 ตัวอย่าง (มีประโยชน์)
└── ProductSeeder.php    🟡 ตัวอย่าง E-commerce
```
**สถานะ:** ✅ **แก้ไข/ลบได้**

---

### 6. **docs/** - 📚 Documentation
```
docs/
├── API_REFERENCE.md      ✅ เอกสาร (เก็บไว้)
├── CHANGELOG.md          ✅ เอกสาร (เก็บไว้)
├── CLI_GUIDE.md          ✅ เอกสาร (เก็บไว้)
├── ... (เอกสารอื่นๆ)     ✅ ทั้งหมดเป็นเอกสาร
```
**สถานะ:** ⚠️ **ควรเก็บไว้** - เอกสารคู่มือการใช้งาน

---

## 📋 คำแนะนำการใช้งาน

### 🎯 สถานการณ์ที่ 1: พัฒนาระบบอีคอมเมิร์ซ
```bash
✅ เก็บทุกอย่างไว้
✅ แก้ไข Controllers, Models, Views ตามต้องการ
✅ ปรับ Migrations ให้เหมาะกับธุรกิจ
```

### 🎯 สถานการณ์ที่ 2: พัฒนาระบบอื่น (Blog, CMS, etc.)
```bash
❌ ลบ app/Controllers/Ecommerce/
❌ ลบ app/Controllers/Api/V1/ (E-commerce APIs)
❌ ลบ Models ที่ไม่ใช้ (Product, Cart, Order, etc.)
❌ ลบ Views ที่ไม่ใช้
❌ ลบ Migrations ที่ไม่ใช้
✅ เก็บ User, Role, Permission (มีประโยชน์)
✅ สร้าง Controllers/Models ใหม่ด้วย CLI
```

### 🎯 สถานการณ์ที่ 3: ใช้เป็น Learning Framework
```bash
✅ เก็บทุกอย่างไว้เป็น Reference
✅ ศึกษาโครงสร้างและ patterns
✅ ทดลองแก้ไขและเรียนรู้
```

---

## 🚀 วิธีเริ่มต้นโปรเจคใหม่

### ขั้นตอนที่ 1: ทำความสะอาด
```bash
# ลบ Controllers ตัวอย่าง
Remove-Item -Recurse app\Controllers\Ecommerce
Remove-Item -Recurse app\Controllers\Api\V1
Remove-Item app\Controllers\HomeController.php

# ลบ Models ที่ไม่ใช้
Remove-Item app\Models\Product.php
Remove-Item app\Models\Cart.php
# ... (ลบตามต้องการ)

# ลบ Views ตัวอย่าง
Remove-Item -Recurse app\Views\products
Remove-Item -Recurse app\Views\cart
# ... (ลบตามต้องการ)

# ลบ Migrations ที่ไม่ใช้
Remove-Item -Recurse database\migrations\ecommerce
```

### ขั้นตอนที่ 2: สร้างของใหม่
```bash
# สร้าง Controllers ใหม่
php console make:controller BlogController
php console make:controller PostController

# สร้าง Models ใหม่
php console make:model Post
php console make:model Comment

# สร้าง Migrations
# (ยังไม่มี command นี้ แต่สามารถคัดลอก template ได้)
```

### ขั้นตอนที่ 3: ปรับ Routes
```bash
# แก้ไข routes/web.php และ routes/api.php
# ให้เหมาะกับ Controllers ใหม่
```

---

## 📊 สรุปตาราง

| หมวดหมู่ | ห้ามลบ | แก้ไขได้ | ลบได้ | คำแนะนำ |
|---------|--------|---------|-------|---------|
| **app/Core/** | ✅ | ❌ | ❌ | หัวใจของ Framework |
| **app/Helpers/** | ✅ | ❌ | ❌ | ฟังก์ชันช่วยเหลือ |
| **app/Middleware/** | ✅ | ✅ | ❌ | ใช้ในระบบ, แต่ปรับได้ |
| **app/Controllers/** | ❌ | ✅ | ✅ | ตัวอย่าง - ปรับตามโปรเจค |
| **app/Models/** | ❌ | ✅ | ✅ | ตัวอย่าง - ปรับตามโปรเจค |
| **app/Views/** | ❌ | ✅ | ✅ | ตัวอย่าง - ปรับตามธีม |
| **config/** | ✅ | ✅ | ❌ | Config - แก้ไขค่าได้ |
| **routes/** | ✅ | ✅ | ❌ | แก้ไข routes ได้ |
| **public/** | ✅ | ✅ | ❌ | index.php ห้ามลบ |
| **storage/** | ✅ | ❌ | ❌ | จำเป็นสำหรับระบบ |
| **database/migrations/** | ❌ | ✅ | ✅ | ตัวอย่าง - ปรับตามโปรเจค |
| **database/seeders/** | ❌ | ✅ | ✅ | ตัวอย่าง - ปรับตามโปรเจค |
| **tests/** | ⚠️ | ✅ | ⚠️ | TestCase.php เก็บไว้ |
| **CLI Tools** | ✅ | ❌ | ❌ | จำเป็นสำหรับพัฒนา |
| **docs/** | ❌ | ✅ | ⚠️ | เอกสาร - ควรเก็บไว้ |

**สัญลักษณ์:**
- ✅ ใช่
- ❌ ไม่ใช่
- ⚠️ ขึ้นอยู่กับสถานการณ์

---

## 💡 หลักการง่ายๆ

### 🔵 **Framework Core (ห้ามลบ)**
- ทุกอย่างใน `app/Core/`
- ทุกอย่างใน `app/Helpers/`
- Middleware ทั้งหมด
- CLI tools (console, migrate.php, seed.php)
- Configuration files

### 🟢 **Examples (แก้ไข/ลบได้)**
- Controllers
- Models (ยกเว้น User อาจเก็บไว้)
- Views
- Migrations
- Seeders

### 🎯 **กฎทอง**
> **ถ้าอยู่ใน `Core/` หรือ `Helpers/` = Framework**  
> **ถ้าเป็น Controller/Model/View = ตัวอย่าง**

---

**สรุป:** Framework มีความยืดหยุ่นสูง คุณสามารถเก็บตัวอย่างไว้เป็น reference หรือลบทิ้งแล้วสร้างใหม่ตามโปรเจคของคุณได้เลย! 🚀
