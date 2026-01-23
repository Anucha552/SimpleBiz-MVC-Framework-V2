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
