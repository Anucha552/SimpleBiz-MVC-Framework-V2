# คู่มือ Services (Mail / FileUpload / Cache / Logger)

เอกสารนี้สรุปการใช้งานบริการสำคัญของเฟรมเวิร์ก

---

## 1) Mail
- ตั้งค่าใน .env: MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION
- ใช้งานผ่าน App\Core\Mail
- ใช้ template ใน app/Views/emails

---

## 2) FileUpload
- ใช้งานผ่าน App\Core\FileUpload
- ตรวจสอบนามสกุลและ MIME type ก่อนเสมอ
- เก็บไฟล์ใน public/assets หรือ storage ตามการออกแบบ

---

## 3) Cache
- ใช้งานผ่าน App\Core\Cache
- เก็บ cache ที่ storage/cache
- ใช้สำหรับลดภาระ query หรือคำนวณซ้ำ

---

## 4) Logger
- ใช้งานผ่าน App\Core\Logger
- log จะถูกเก็บใน storage/logs
- ใช้สำหรับ audit, error, และ security events

---

ดูตัวอย่างละเอียดใน docs/CORE_USAGE.md
