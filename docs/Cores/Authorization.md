# Authorization Class Manual

## Overview

`Authorization` เป็นคลาสสำหรับจัดการสิทธิ์การเข้าถึง (Authorization) ภายในระบบ
รองรับทั้ง Permission-based และ Role-based Access Control (RBAC) พร้อมระบบ cache และ logging ในตัว

- **Namespace:** `App\Core\Authorization`

## โครงสร้างความสามารถหลัก

- ตรวจสอบสิทธิ์ด้วย `can()`
- ตรวจสอบบทบาทด้วย `hasRole()`
- แปลงรูปแบบ permission ด้วย `normalizePermissions()`
- โหลดสิทธิ์ทั้งหมดพร้อม cache ด้วย `loadAllPermissions()`
- ล้าง cache ด้วย `invalidatePermissionCache()`

---

## 1. การตรวจสอบสิทธิ์ (Permission Check)

### Method

`public static function can(string $permission): bool`

ใช้ตรวจสอบว่าผู้ใช้ปัจจุบันมีสิทธิ์ที่ระบุหรือไม่

### ตัวอย่างการใช้งาน

```php
if (Authorization::can('edit_posts')) {
    echo "อนุญาตให้แก้ไขโพสต์";
} else {
    echo "ไม่มีสิทธิ์";
}
```

### ลำดับการทำงานภายใน

- ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
- ถ้าเป็น `admin` → อนุญาตทันที
- ตรวจสอบ cache ใน session
- ตรวจสอบ permission จากข้อมูลใน user record
- ตรวจสอบจากตาราง `user_permissions`
- ตรวจสอบจาก `role_permissions`
- บันทึก log ผลลัพธ์

---

## 2. การตรวจสอบบทบาท (Role Check)

### Method

`public static function hasRole(string $role): bool`

ใช้ตรวจสอบว่าผู้ใช้มี role ที่กำหนดหรือไม่

### ตัวอย่าง

```php
if (Authorization::hasRole('editor')) {
    echo "ผู้ใช้นี้เป็น editor";
}
```

### การทำงาน

- `Admin` จะผ่านเสมอ
- ตรวจสอบจาก field `role` หรือ `roles`
- ถ้าไม่พบ จะ fallback ไปตรวจสอบฐานข้อมูล (`user_roles` + `roles`)

---

## 3. การ Normalize Permission

### Method

`public static function normalizePermissions($perms): array`

รองรับรูปแบบ:

- `array`
- JSON string
- comma-separated string
- string เดี่ยว

### ตัวอย่าง

```php
$perms = Authorization::normalizePermissions('["edit_posts","delete_posts"]');
// ผลลัพธ์: ['edit_posts', 'delete_posts']
```

---

## 4. โหลดสิทธิ์ทั้งหมดของผู้ใช้

### Method

`public static function loadAllPermissions(?array $user): array`

จะโหลดสิทธิ์จาก:

- user record
- ตาราง `user_permissions`
- ตาราง `role_permissions`
- ตาราง `user_roles`

และ cache ลงใน session อัตโนมัติ

### ตัวอย่าง

```php
$permissions = Authorization::loadAllPermissions(Auth::user());
```

---

## 5. ล้าง Permission Cache

### Method

`public static function invalidatePermissionCache(?int $userId = null): void`

ใช้เมื่อ:

- เปลี่ยนสิทธิ์ผู้ใช้
- เปลี่ยน role ผู้ใช้
- แก้ไข permission ของ role

### ตัวอย่าง

```php
Authorization::invalidatePermissionCache($userId);
```

---

## 6. รูปแบบ Cache ใน Session

Permission จะถูกเก็บใน session ในรูปแบบ:

```php
_auth_permissions = [
    'perms' => [...],
    'ts' => 1700000000
]
```

กำหนดอายุ cache ผ่าน config:

```php
Config::get('auth.permission_cache_ttl', 3600);
```

ค่าเริ่มต้น: 3600 วินาที (1 ชั่วโมง)

---

## 7. Logging ที่เกี่ยวข้อง

เหตุการณ์ที่ถูกบันทึก:

- `authorization.allowed`
- `authorization.denied`
- `authorization.error`
- `authorization.role.allowed`
- `authorization.role.denied`

มีระบบป้องกัน log ซ้ำภายใน request เดียวกัน

---

## 8. ตัวอย่างการใช้งานจริง

### Middleware

```php
if (!Authorization::can('manage_users')) {
    http_response_code(403);
    exit('Forbidden');
}
```

### Controller

```php
public function update()
{
    if (!Authorization::can('edit_posts')) {
        return redirect('/403');
    }

    // ทำงานต่อ
}
```

---

## Best Practices

- ตรวจสอบสิทธิ์ที่ controller หรือ middleware เสมอ
- อย่าเชื่อ frontend validation เพียงอย่างเดียว
- ล้าง cache ทุกครั้งที่มีการแก้ไขสิทธิ์
- ตั้งชื่อ role และ permission ให้สม่ำเสมอ

---

## สรุป

`Authorization` class ถูกออกแบบให้:

- รองรับ RBAC
- มี session cache เพิ่ม performance
- มี logging ครบถ้วน
- ยืดหยุ่นต่อหลายรูปแบบ permission
- เหมาะสำหรับ production system
