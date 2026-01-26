# โมดูล Auth (Modules\Auth)

โมดูลนี้ให้ระบบยืนยันตัวตนแบบ session-based พร้อมหน้าจอ login/register และตัวอย่าง API แบบ session-authenticated

## สรุปความสามารถ
- ฟอร์ม `GET /login`, `POST /login` (Guest middleware + CSRF)
- ฟอร์ม `GET /register`, `POST /register` (Guest middleware + CSRF)
- `POST /logout` (ต้อง auth + CSRF)
- ตัวอย่าง API: `GET /api/me` (Auth middleware)
- คอนโทรลเลอร์: `Modules\Auth\Controllers\AuthController`, `AuthApiController`
- รีโพซิทอรี: `Modules\Auth\Repositories\UserRepository` (ใช้ PDO ผ่าน `App\Core\Database`)

## วิธีเปิดใช้งาน
1. เปิดโมดูลในไฟล์ `config/modules.php`:

```php
Modules\Auth\AuthModule::class,
```

2. รัน `composer dump-autoload` ถ้าจำเป็น

3. เตรียมฐานข้อมูลและรัน migrations (ถ้ามี):

```powershell
php console migrate
```

หมายเหตุ: ถ้าไม่มีตาราง `users` ฟอร์มจะ flash ข้อความแนะนำให้รัน migrations

## เส้นทาง (Routes) และ middleware (ตรงกับ `modules/Auth/AuthModule.php`)

- Web:
  - `GET /login` → `AuthController::showLogin` (GuestMiddleware)
  - `POST /login` → `AuthController::login` (GuestMiddleware, CsrfMiddleware)
  - `GET /register` → `AuthController::showRegister` (GuestMiddleware)
  - `POST /register` → `AuthController::register` (GuestMiddleware, CsrfMiddleware)
  - `POST /logout` → `AuthController::logout` (AuthMiddleware, CsrfMiddleware)

- API:
  - `GET /api/me` → `AuthApiController::me` (AuthMiddleware)

## Session keys ที่ใช้โดยโมดูล

- `user_id`
- `user_username`
- `user_email`
- `auth.intended` (เก็บ URL ที่พยายามเข้าเมื่อยังไม่ได้ login)

## Repository (ข้อมูลผู้ใช้)

ไฟล์ `modules/Auth/Repositories/UserRepository.php` มีเมธอดหลัก:
- `findById(int $id): ?array`
- `findByEmail(string $email): ?array`
- `findByUsername(string $username): ?array`
- `create(array $data): int` (คืน `id` ใหม่)

## หมายเหตุด้านความปลอดภัย
- ใช้ `Session::generateCsrfToken()` / `Session::csrfField()` สำหรับฟอร์มเพื่อป้องกัน CSRF
- รหัสผ่านถูกเก็บแบบ hash โดย `password_hash()` ในตัวอย่าง register
- ควรใช้ HTTPS ในโปรดักชัน และตั้งค่า cookie security flags

---

ถ้าต้องการ ให้ผมเพิ่มตัวอย่าง view (`app/Views/auth/*.php`) หรือ migration ตัวอย่างสำหรับ `users` ให้ด้วยครับ
```markdown
# Auth Module (Modules\\Auth)

โมดูลนี้เพิ่มระบบ Auth แบบ session-based ให้กับ SimpleBiz MVC Framework

## เปิดใช้งาน

1) เปิดโมดูลใน config

- แก้ไฟล์ [config/modules.php](config/modules.php) แล้วเพิ่ม:
  - `Modules\Auth\AuthModule::class,`

2) อัปเดต autoload

- รัน: `composer dump-autoload`

3) เตรียมตาราง users

Framework มี migration สำหรับ `users` อยู่แล้ว:
- `database/migrations/system/2024_01_01_000001_create_users_table.php`

รัน migrations:
- `php console migrate`

## Routes

Web:
- `GET /login` แสดงฟอร์มเข้าสู่ระบบ
- `POST /login` เข้าสู่ระบบ (ต้องมี CSRF)
- `GET /register` แสดงฟอร์มสมัครสมาชิก
- `POST /register` สมัครสมาชิก (ต้องมี CSRF)
- `POST /logout` ออกจากระบบ (ต้องมี CSRF และต้อง login)

API (ตัวอย่าง):
- `GET /api/me` คืนข้อมูลผู้ใช้ปัจจุบัน (ต้อง login)

## Session keys

- `user_id`
- `user_username`
- `user_email`
- `auth.intended` (เก็บ URL ที่พยายามเข้า ก่อนถูกบังคับไป login)

## Notes

- CSRF ใช้ field `_csrf_token` (ผ่าน `Session::csrfField()` / `FormHelper::csrfField()`) และยังรองรับ legacy `csrf_token` เพื่อ backward compatibility.
- ถ้ายังไม่ได้รัน migrations (ไม่มีตาราง users) หน้า login/register จะ flash ข้อความให้ไปตั้งค่าฐานข้อมูลก่อน.


```