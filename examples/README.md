# Examples - ตัวอย่างการใช้งาน Framework

## 📋 ภาพรวม

โฟลเดอร์นี้มี**ตัวอย่างโค้ด (Example Code)** สำหรับการใช้งาน SimpleBiz MVC Framework V2 

**⚠️ สำคัญ:** 
- ไฟล์เหล่านี้**ไม่ใช่ส่วนหนึ่งของ Framework Core**
- เป็นเพียง**ตัวอย่างการใช้งาน**ที่คุณสามารถ copy และปรับแต่งได้
- Framework Base ยังคงสะอาดและพร้อมใช้งาน

---

## 📁 โครงสร้าง

```
examples/
├── models/              → ตัวอย่าง Model classes
├── migrations/          → ตัวอย่าง Database migrations
├── seeders/             → ตัวอย่าง Database seeders
└── README.md           → ไฟล์นี้
```

---

## 📦 เนื้อหาในโฟลเดอร์นี้

### 1. Models (7 ไฟล์)
ตัวอย่าง Model classes ที่สร้างจาก Base Model:

- **User.php** - การจัดการผู้ใช้และ authentication (⚠️ migration อยู่ใน `database/migrations/core/` แล้ว)
- **Role.php** - บทบาทผู้ใช้ (RBAC)
- **Permission.php** - สิทธิ์การเข้าถึง (RBAC)
- **Setting.php** - การตั้งค่าแอปพลิเคชัน
- **ActivityLog.php** - บันทึกกิจกรรม
- **Notification.php** - ระบบแจ้งเตือน
- **ApiKey.php** - การจัดการ API Keys

**การใช้งาน:**
```php
// คุณสามารถ copy models เหล่านี้ไปที่ app/Models/ และปรับแต่งตามต้องการ
```

---

### 2. Migrations (5 ไฟล์)
ตัวอย่าง Database schema migrations:

**⚠️ หมายเหตุ (ใน examples/):**
**Core Tables (ใน examples/):**
- `2024_01_01_000001_create_users_table.php`
- `2024_01_01_000006_create_roles_permissions_tables.php`
- `2024_01_01_000007_create_settings_table.php`

**System Tables:**
- `2024_01_01_000009_create_activity_logs_table.php`
- `2024_01_01_000010_create_notifications_table.php`
- `2024_01_01_000011_create_api_keys_table.php`

**การใช้งาน:**
```bash
# Copy migrations ที่ต้องการไปที่ database/migrations/
# แล้วรัน migration
php console migrate:run
```

---

### 3. Seeders (1 ไฟล์)
ตัวอย่างการสร้างข้อมูลทดสอบ:

- **UserSeeder.php** - สร้างผู้ใช้ทดสอบ

**การใช้งาน:**
```bash
# Copy seeder ที่ต้องการไปที่ database/seeders/
# แล้วรัน seeder
php console seed:run UserSeeder
```

---

## 🚀 วิธีการใช้งาน

### วิธีที่ 1: Copy ไฟล์ที่ต้องการ
```bash
# Copy model ที่ต้องการ
cp examples/models/User.php app/Models/

# Copy migration ที่ต้องการ
cp examples/migrations/2024_01_01_000001_create_users_table.php database/migrations/core/

# Copy seeder ที่ต้องการ
cp examples/seeders/UserSeeder.php database/seeders/
```

### วิธีที่ 2: Copy ทั้งหมด
```bash
# Copy ทุกอย่างในครั้งเดียว
cp examples/models/*.php app/Models/
cp examples/migrations/*.php database/migrations/core/
cp examples/seeders/*.php database/seeders/
```

### วิธีที่ 3: ใช้เป็นแนวทาง
- เปิดดูโค้ดตัวอย่าง
- เข้าใจวิธีการใช้งาน Framework
- สร้างไฟล์ของคุณเองตามรูปแบบ

---

## 📚 คำแนะนำ

### ✅ สิ่งที่ควรทำ
- ✅ ศึกษาโค้ดตัวอย่างเพื่อเข้าใจ patterns
- ✅ Copy และปรับแต่งตามความต้องการ
- ✅ ใช้เป็นจุดเริ่มต้นสำหรับโปรเจคของคุณ
- ✅ ปรับแต่ง schema ให้เหมาะกับ business logic

### ❌ สิ่งที่ไม่ควรทำ
- ❌ ใช้โค้ดตัวอย่างโดยไม่เข้าใจ
- ❌ Copy ทุกอย่างถ้าไม่จำเป็น
- ❌ แก้ไขโค้ดในโฟลเดอร์ examples/ โดยตรง
- ❌ คาดหวังว่าโค้ดตัวอย่างจะเหมาะกับทุกกรณี

---

## 🔗 ความสัมพันธ์ระหว่างไฟล์

```
User Model → users table (migration)
    ↓
Role Model → roles table (migration)
    ↓
Permission Model → permissions table (migration)
    ↓
Setting Model → settings table (migration)
    ↓
ActivityLog Model → activity_logs table (migration)
    ↓
Notification Model → notifications table (migration)
    ↓
ApiKey Model → api_keys table (migration)

UserSeeder → สร้างข้อมูล users (ต้องมี users table)
```

---

## 📖 เอกสารเพิ่มเติม

- **MODELS_GUIDE.md** - คู่มือการสร้าง Models
- **MIGRATION_GUIDE.md** - คู่มือการสร้าง Migrations
- **SEEDING_GUIDE.md** - คู่มือการสร้าง Seeders
- **CORE_USAGE.md** - วิธีการใช้งาน Framework Core

---

## 💡 ตัวอย่างการสร้างโปรเจคใหม่

### 1. เริ่มต้นด้วย User Management System
```bash
# Copy ไฟล์ที่จำเป็น
cp examples/models/User.php app/Models/
cp examples/migrations/2024_01_01_000001_create_users_table.php database/migrations/core/
cp examples/seeders/UserSeeder.php database/seeders/

# Run migration และ seeder
php console migrate:run
php console seed:run UserSeeder
```

### 2. เพิ่ม RBAC (Role-Based Access Control)
```bash
# Copy ไฟล์ RBAC
cp examples/models/Role.php app/Models/
cp examples/models/Permission.php app/Models/
cp examples/migrations/2024_01_01_000006_create_roles_permissions_tables.php database/migrations/core/

# Run migration
php console migrate:run
```

### 3. เพิ่ม Activity Logging
```bash
# Copy ไฟล์ logging
cp examples/models/ActivityLog.php app/Models/
cp examples/migrations/2024_01_01_000009_create_activity_logs_table.php database/migrations/system/

# Run migration
php console migrate:run
```

---

## 🎯 สรุป

โฟลเดอร์นี้เป็น**คลังตัวอย่าง**ที่ช่วยให้คุณ:
1. 📖 เรียนรู้วิธีการใช้งาน Framework
2. 🚀 เริ่มต้นโปรเจคได้เร็วขึ้น
3. 💡 เข้าใจ patterns และ best practices
4. 🔧 ปรับแต่งให้เหมาะกับความต้องการ

**Framework Base** = เครื่องมือสร้างแอป  
**Examples** = แนวทางการใช้งาน

---

**อัพเดตล่าสุด:** 22 มกราคม 2026  
**Framework Version:** SimpleBiz MVC Framework V2
