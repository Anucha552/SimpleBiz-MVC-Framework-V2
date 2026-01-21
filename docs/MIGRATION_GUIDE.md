# Migration System Guide

## คำอธิบาย

ระบบ Migration ช่วยจัดการโครงสร้างฐานข้อมูล (Database Schema) อย่างเป็นระบบ เหมาะสำหรับ:
- ทำงานร่วมกันเป็นทีม (Version Control)
- สร้างตารางและแก้ไขโครงสร้างได้ง่าย
- Rollback เมื่อมีปัญหา
- Deploy ไปยัง Production ได้สะดวก

---

## การติดตั้ง

ระบบ Migration ใช้งานได้ทันทีโดยไม่ต้องติดตั้งเพิ่มเติม เพียงแค่มี:
- PHP 8.0+
- MySQL/MariaDB
- PDO Extension
- การตั้งค่า database ใน `config/database.php`

---

## วิธีใช้งาน

### 1. ตรวจสอบสถานะ Migration

```bash
php console migrate:status
```

แสดงรายการ migrations ที่รันแล้ว และที่รอการรัน

### 2. รัน Migrations ทั้งหมด

```bash
php console migrate
```

รัน migrations ที่ยังไม่ได้รัน (pending migrations)

### 3. Rollback Migration

```bash
# Rollback batch ล่าสุด
php console migrate:rollback

# Rollback 2 batch ล่าสุด
php console migrate:rollback 2
```

### 4. Fresh Migration (ลบทุกอย่างและรันใหม่)

```bash
php console migrate:fresh
```

⚠️ **คำเตือน**: คำสั่งนี้จะลบข้อมูลทั้งหมดในฐานข้อมูล

### 5. สร้าง Migration ใหม่

```bash
php console migrate:create CreateUsersTable
```

สร้างไฟล์ Migration แบบ template พร้อม timestamp

### 6. ดูรายการ Module ทั้งหมด

```bash
php console migrate:modules
```

แสดงรายการ module และจำนวน migration ในแต่ละ module

### 7. รัน Migration ตาม Module

```bash
# รันเฉพาะ module core
php console migrate --path=core

# รันเฉพาะ module ecommerce
php console migrate --path=ecommerce

# รันเฉพาะ module content
php console migrate --path=content

# รันเฉพาะ module system
php console migrate --path=system
```

---

## โครงสร้างไฟล์ Migration

Migration แต่ละไฟล์ต้องมีรูปแบบ:

```php
<?php

require_once __DIR__ . '/../../app/Core/Migration.php';

class CreateExampleTable extends Migration
{
    /**
     * Run the migrations - สร้างตาราง
     */
    public function up()
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS example (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_email (email),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        $this->execute($sql);

        // Insert sample data (optional)
        $sampleData = "
        INSERT INTO example (name, email) VALUES
        ('John Doe', 'john@example.com'),
        ('Jane Smith', 'jane@example.com')
        ";

        $this->execute($sampleData);
    }

    /**
     * Reverse the migrations - ลบตาราง
     */
    public function down()
    {
        $this->execute("DROP TABLE IF EXISTS example");
    }
}
```

---

## ตัวอย่างการสร้างตารางแบบต่างๆ

### ตารางพื้นฐาน

```php
public function up()
{
    $sql = "
    CREATE TABLE IF NOT EXISTS posts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    $this->execute($sql);
}
```

### ตารางที่มี Foreign Key

```php
public function up()
{
    $sql = "
    CREATE TABLE IF NOT EXISTS comments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        post_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    $this->execute($sql);
}
```

### สร้างหลายตารางในครั้งเดียว

```php
public function up()
{
    // ตารางแรก
    $table1 = "CREATE TABLE IF NOT EXISTS table1 (...)";
    $this->execute($table1);
    
    // ตารางที่สอง
    $table2 = "CREATE TABLE IF NOT EXISTS table2 (...)";
    $this->execute($table2);
    
    // Pivot table
    $pivot = "CREATE TABLE IF NOT EXISTS pivot_table (...)";
    $this->execute($pivot);
}
```

### เพิ่ม Index

```php
public function up()
{
    $sql = "
    CREATE TABLE IF NOT EXISTS products (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10, 2) NOT NULL,
        
        INDEX idx_price (price),
        FULLTEXT idx_search (name, description)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    $this->execute($sql);
}
```

---

## Helper Methods

Migration Base Class มี helper methods ให้ใช้:

### execute($sql)
รัน SQL statement เดี่ยว

```php
$this->execute("CREATE TABLE example (...)");
```

### executeMultiple($statements)
รันหลาย SQL statements พร้อมกัน

```php
$statements = [
    "CREATE TABLE table1 (...)",
    "CREATE TABLE table2 (...)",
    "INSERT INTO table1 VALUES (...)"
];

$this->executeMultiple($statements);
```

### tableExists($tableName)
ตรวจสอบว่าตารางมีอยู่หรือไม่

```php
if ($this->tableExists('users')) {
    echo "Table exists!";
}
```

### columnExists($tableName, $columnName)
ตรวจสอบว่าคอลัมน์มีอยู่หรือไม่

```php
if ($this->columnExists('users', 'email')) {
    echo "Column exists!";
}
```

---

## Naming Convention

### ชื่อไฟล์ Migration

รูปแบบ: `YYYY_MM_DD_HHMMSS_description.php`

```
2024_01_01_000001_create_users_table.php
2024_01_01_000002_create_products_table.php
2024_01_15_100530_add_status_to_orders.php
```

### ชื่อ Class

- สร้างตาราง: `CreateUsersTable`
- แก้ไขตาราง: `AddStatusToOrders`
- ลบตาราง: `DropOldTable`

---

## Best Practices

### 1. แยก Migration ตามความรับผิดชอบ

❌ **ไม่ควร** - สร้างทุกอย่างใน 1 migration
```php
public function up()
{
    // สร้าง 10 ตารางในไฟล์เดียว
}
```

✅ **ควร** - แยกเป็น migration ย่อยๆ
```
2024_01_01_000001_create_users_table.php
2024_01_01_000002_create_products_table.php
2024_01_01_000003_create_orders_table.php
```

### 2. ใส่ Sample Data เฉพาะ Development

```php
public function up()
{
    $this->execute("CREATE TABLE ...");
    
    // Sample data for development only
    if (getenv('APP_ENV') !== 'production') {
        $this->execute("INSERT INTO ...");
    }
}
```

### 3. ระวัง Foreign Key Dependencies

สร้างตารางตามลำดับ dependency:
```
1. users (ไม่มี FK)
2. categories (ไม่มี FK หรือ self-reference)
3. products (FK to categories)
4. orders (FK to users)
5. order_items (FK to orders, products)
```

### 4. ใช้ IF NOT EXISTS

```php
CREATE TABLE IF NOT EXISTS users (...)
DROP TABLE IF EXISTS users
```

### 5. ทำ Backup ก่อน Rollback

```bash
# Backup database
mysqldump -u root -p database_name > backup.sql

# แล้วค่อย rollback
php console migrate:rollback
```

---

## Workflow แนะนำ

### Development

```bash
# 1. สร้าง migration ใหม่
php console migrate:create CreatePostsTable

# 2. เขียน up() และ down() methods

# 3. รัน migration
php console migrate

# 4. ทดสอบ rollback
php console migrate:rollback

# 5. รันอีกครั้งเพื่อยืนยัน
php console migrate
```

### Production Deployment

```bash
# 1. Pull code ใหม่
git pull origin main

# 2. Backup database
mysqldump -u root -p database > backup_$(date +%Y%m%d).sql

# 3. รัน migrations
php console migrate

# 4. ตรวจสอบ
php console migrate:status

# 5. ถ้ามีปัญหา rollback
php console migrate:rollback
```

---

## Troubleshooting

### Migration ไม่รัน

**ตรวจสอบ:**
1. Database connection ใน `config/database.php`
2. ไฟล์ migration มี syntax ถูกต้อง
3. Class name ตรงกับชื่อไฟล์

### Foreign Key Error

**แก้ไข:**
- ตรวจสอบลำดับการสร้างตาราง
- ตารางที่ reference ต้องสร้างก่อน
- Data type ต้องตรงกัน (INT UNSIGNED)

### Batch Tracking ผิดพลาด

**แก้ไข:**
```sql
-- ตรวจสอบตาราง migrations
SELECT * FROM migrations ORDER BY batch, id;

-- ลบ record ที่ผิดพลาด
DELETE FROM migrations WHERE migration = 'filename.php';
```

---

## ตัวอย่าง Migrations ที่สร้างไว้

ในระบบมี migrations พื้นฐาน 14 ไฟล์ แบ่งตามโครงสร้าง module:

### 📦 Module: core (3 ไฟล์)
ระบบหลักพื้นฐานที่จำเป็นสำหรับแอปพลิเคชัน

1. **2024_01_01_000001_create_users_table** - ระบบผู้ใช้พื้นฐาน (authentication, profile)
2. **2024_01_01_000006_create_roles_permissions_tables** - RBAC (roles, permissions, role_permissions, user_roles)
3. **2024_01_01_000007_create_settings_table** - ตั้งค่าระบบแบบ key-value

### 🛒 Module: ecommerce (6 ไฟล์)
ระบบอีคอมเมิร์ซครบวงจร

4. **2024_01_01_000002_create_categories_table** - หมวดหมู่สินค้าแบบ hierarchical
5. **2024_01_01_000003_create_products_table** - สินค้า (พร้อม SKU, stock, variants)
6. **2024_01_01_000004_create_carts_table** - ตะกร้าสินค้า
7. **2024_01_01_000005_create_orders_table** - คำสั่งซื้อและรายการสินค้า (orders + order_items)
8. **2024_01_01_000013_create_reviews_table** - รีวิวและคะแนนสินค้า (พร้อม triggers)
9. **2024_01_01_000014_create_addresses_table** - ที่อยู่จัดส่งและเรียกเก็บเงิน

### 📝 Module: content (2 ไฟล์)
ระบบจัดการเนื้อหา

10. **2024_01_01_000008_create_media_table** - จัดการไฟล์/รูปภาพ (media + media_relations แบบ polymorphic)
11. **2024_01_01_000012_create_pages_table** - CMS สำหรับหน้าเว็บ

### ⚙️ Module: system (3 ไฟล์)
ระบบสนับสนุนและตรวจสอบ

12. **2024_01_01_000009_create_activity_logs_table** - บันทึกกิจกรรม (audit trail)
13. **2024_01_01_000010_create_notifications_table** - แจ้งเตือนภายในระบบ
14. **2024_01_01_000011_create_api_keys_table** - API Authentication พร้อม rate limiting

### 🎯 การรัน Migration ตาม Module

```bash
# รันทั้งหมด
php console migrate

# รันเฉพาะ module core
php console migrate --path=core

# รันเฉพาะ module ecommerce
php console migrate --path=ecommerce

# ดู module ทั้งหมด
php console migrate:modules
```

---

## คำสั่งที่ใช้บ่อย

```bash
# ดูสถานะ
php console migrate:status

# รัน migrations
php console migrate

# Rollback 1 batch
php console migrate:rollback

# Rollback 3 batches
php console migrate:rollback 3

# Reset และรันใหม่
php console migrate:fresh

# สร้าง migration ใหม่
php console migrate:create AddColumnsToUsers

# ดู modules
php console migrate:modules

# ดูคำสั่งทั้งหมด
php console help
```

---

## สรุป

ระบบ Migration นี้:
- ✅ จัดการ Database Schema แบบ Version Control
- ✅ รองรับ Rollback
- ✅ ติดตาม batch execution
- ✅ สร้าง migration ใหม่ได้ง่าย
- ✅ Helper methods ครบครัน
- ✅ พร้อมใช้งาน Production

Happy Migrating! 🚀
