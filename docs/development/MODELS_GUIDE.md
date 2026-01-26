# คู่มือการใช้งาน Models (ภาษาไทย)

เอกสารนี้สรุปวิธีใช้งาน `Model` พื้นฐานของโปรเจค รวมตัวอย่างการสร้าง บันทึก แก้ไข ลบ และการค้นหาข้อมูล

---

## แนวคิดพื้นฐาน
- `Model` เป็นคลาสฐานที่ช่วยติดต่อฐานข้อมูลผ่าน PDO
- แต่ละ Model มักแมปกับตารางในฐานข้อมูล เช่น `App\\Models\\User` → ตาราง `users`

---

## ตัวอย่าง CRUD พื้นฐาน

สร้างเรคอร์ดใหม่:

```php
$user = new \\App\\Models\\User();
$user->name = 'Somchai';
$user->email = 'somchai@example.com';
$user->save();
```

ค้นหา (ตาม id):

```php
$user = \\App\\Models\\User::find(1);
```

อัปเดต:

```php
$user = \\App\\Models\\User::find(1);
$user->name = 'Somkid';
$user->save();
```

ลบ:

```php
$user = \\App\\Models\\User::find(1);
$user->delete();
```

---

## Query Builder / เงื่อนไข
ตัวอย่างการค้นหาแบบมีเงื่อนไข:

```php
$users = \\App\\Models\\User::where('active = ?', [1])->orderBy('created_at DESC')->limit(10)->get();
```

---

## ความสัมพันธ์ (ถ้ามี)
- ถ้าโปรเจคมี implementation ให้ใช้เมธอดสำหรับความสัมพันธ์ เช่น `hasMany`, `belongsTo` (รายละเอียดขึ้นกับโค้ดของโปรเจค)

---

## คำแนะนำ
- ตรวจสอบโครงสร้างฐานข้อมูลและชื่อคอลัมน์ให้ตรงกับ Model
- ใช้การ prepare/parameter binding (PDO) เสมอเมื่อรับ input จากผู้ใช้
