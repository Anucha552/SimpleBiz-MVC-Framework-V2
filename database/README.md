# โครงสร้าง Migration Modules

ระบบ Migration ถูกแยกตาม **modules** เพื่อความยืดหยุ่นในการพัฒนาและ deploy

## โครงสร้าง Folder

```
database/migrations/
├── core/           - User, Roles, Permissions, Settings (3 migrations)
├── ecommerce/      - Categories, Products, Carts, Orders, Reviews, Addresses (6 migrations)
├── content/        - Media, Pages (2 migrations)
└── system/         - Activity Logs, Notifications, API Keys (3 migrations)
```

## การใช้งาน

### 1. ดูรายการ Modules

```bash
php migrate.php modules
```

แสดงผล:
```
Available Modules
=================

- content (2 migrations)
- core (3 migrations)
- ecommerce (6 migrations)
- system (3 migrations)
```

### 2. รัน Migrations ทั้งหมด

```bash
php migrate.php up
```

รันทุก module พร้อมกัน

### 3. รันเฉพาะ Module

```bash
# รันเฉพาะ core module
php migrate.php up --path=core

# รันเฉพาะ ecommerce module
php migrate.php up --path=ecommerce

# รันเฉพาะ content module
php migrate.php up --path=content

# รันเฉพาะ system module
php migrate.php up --path=system
```

### 4. ตรวจสอบสถานะ

```bash
php migrate.php status
```

### 5. Rollback

```bash
# Rollback 1 batch
php migrate.php down

# Rollback 3 batches
php migrate.php rollback 3
```

## รายละเอียด Modules

### Core Module
**ไฟล์:** `database/migrations/core/`

| Migration | ตาราง | คำอธิบาย |
|-----------|-------|----------|
| 2024_01_01_000001_create_users_table | users | ระบบผู้ใช้งาน |
| 2024_01_01_000006_create_roles_permissions_tables | roles, permissions, role_permissions, user_roles | RBAC (4 ตาราง) |
| 2024_01_01_000007_create_settings_table | settings | การตั้งค่าระบบ |

**ใช้สำหรับ:** ระบบพื้นฐาน, Authentication, Authorization

### E-commerce Module
**ไฟล์:** `database/migrations/ecommerce/`

| Migration | ตาราง | คำอธิบาย |
|-----------|-------|----------|
| 2024_01_01_000002_create_categories_table | categories | หมวดหมู่สินค้า |
| 2024_01_01_000003_create_products_table | products | สินค้า |
| 2024_01_01_000004_create_carts_table | carts | ตะกร้าสินค้า |
| 2024_01_01_000005_create_orders_table | orders, order_items | คำสั่งซื้อ (2 ตาราง) |
| 2024_01_01_000013_create_reviews_table | reviews | รีวิวสินค้า |
| 2024_01_01_000014_create_addresses_table | addresses | ที่อยู่จัดส่ง |

**ใช้สำหรับ:** ร้านค้าออนไลน์, ระบบขาย

### Content Module
**ไฟล์:** `database/migrations/content/`

| Migration | ตาราง | คำอธิบาย |
|-----------|-------|----------|
| 2024_01_01_000008_create_media_table | media, media_relations | จัดการไฟล์/รูปภาพ (2 ตาราง) |
| 2024_01_01_000012_create_pages_table | pages | CMS - หน้าเนื้อหา |

**ใช้สำหรับ:** จัดการเนื้อหา, CMS

### System Module
**ไฟล์:** `database/migrations/system/`

| Migration | ตาราง | คำอธิบาย |
|-----------|-------|----------|
| 2024_01_01_000009_create_activity_logs_table | activity_logs | บันทึกกิจกรรม (Audit Trail) |
| 2024_01_01_000010_create_notifications_table | notifications | การแจ้งเตือน |
| 2024_01_01_000011_create_api_keys_table | api_keys, api_key_requests | API Authentication (2 ตาราง) |

**ใช้สำหรับ:** Logging, Monitoring, API

## Workflow แนะนำ

### Development ใหม่

```bash
# 1. รัน core ก่อน (users, roles)
php migrate.php up --path=core

# 2. รัน ecommerce (ขึ้นกับ core)
php migrate.php up --path=ecommerce

# 3. รัน content (ไม่ขึ้นกับ module อื่น)
php migrate.php up --path=content

# 4. รัน system (ขึ้นกับ core)
php migrate.php up --path=system
```

### Production Deployment

```bash
# รันทั้งหมดพร้อมกัน
php migrate.php up

# หรือแยกทีละ module เพื่อควบคุมได้ดีกว่า
php migrate.php up --path=core
php migrate.php up --path=ecommerce
php migrate.php up --path=content
php migrate.php up --path=system
```

### Development ฟีเจอร์ใหม่

เช่น ต้องการเพิ่มระบบ Blog:

```bash
# 1. สร้าง folder ใหม่
mkdir database/migrations/blog

# 2. สร้าง migration
php migrate.php create create_posts_table
# ย้ายไฟล์ไปที่ database/migrations/blog/

# 3. รันเฉพาะ blog module
php migrate.php up --path=blog
```

## การเพิ่ม Module ใหม่

1. **สร้าง folder** ใน `database/migrations/`
   ```bash
   mkdir database/migrations/blog
   ```

2. **สร้าง migration files** ในfolder นั้น

3. **รัน migration**
   ```bash
   php migrate.php up --path=blog
   ```

4. **ตรวจสอบ**
   ```bash
   php migrate.php modules
   ```

## ข้อดีของการแยก Module

✅ **ยืดหยุ่น** - เลือกรันเฉพาะ module ที่ต้องการ  
✅ **ปลอดภัย** - ป้องกัน deploy ฟีเจอร์ที่ยังไม่พร้อม  
✅ **ทีมงาน** - แต่ละคนพัฒนา module ของตัวเองได้  
✅ **Microservices** - แยก database ได้ในอนาคต  
✅ **Testing** - ทดสอบ module แยกกันได้  
✅ **Documentation** - เข้าใจโครงสร้างได้ง่าย  

## ลำดับ Dependencies

```
core (ไม่ขึ้นกับใคร)
  ├── ecommerce (ใช้ users)
  ├── content (ใช้ users)
  └── system (ใช้ users)
```

**คำแนะนำ:** รัน `core` ก่อนเสมอ เพราะ module อื่นต้องใช้ `users` table

## การ Rollback

```bash
# Rollback ทั้งหมด
php migrate.php down

# Rollback เฉพาะ module ไม่ได้ (จะ rollback ตาม batch)
# แนะนำให้ใช้ fresh แทน
php migrate.php fresh
php migrate.php up --path=core
php migrate.php up --path=ecommerce
```

## Tips

💡 ใช้ `php migrate.php modules` เพื่อดูว่ามี module อะไรบ้าง  
💡 ใช้ `--path` เมื่อต้องการรันเฉพาะ module  
💡 ไม่ระบุ `--path` จะรันทุก module  
💡 การเพิ่ม module ใหม่ไม่ต้องแก้ไขโค้ด - แค่สร้าง folder  
