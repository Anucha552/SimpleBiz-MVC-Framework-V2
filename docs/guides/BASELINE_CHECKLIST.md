# เช็คลิสต์ความพร้อม (Baseline Checklist)

เอกสารนี้ช่วยให้โปรเจกต์ที่ใช้ SimpleBiz MVC Framework อยู่ในสถานะพร้อมใช้งาน — ตรวจสอบเฉพาะสิ่งที่เกี่ยวข้องกับโครงงานปัจจุบัน
## 1) Response และการไหลของข้อมูล (สำคัญ)

- Controller/Middleware ควรส่งกลับเป็น `App\Core\Response` แทนการ `echo/exit/header()` โดยตรง
- สำหรับ API ให้ใช้รูปแบบเดียวกันเสมอ: `Response::apiSuccess(...)` หรือ `Response::apiError(...)` (หรือผ่าน `App\Helpers\ResponseHelper`)
- ฟอร์ม (Web): เมื่อ validation ล้มเหลว ให้ flash errors + old input แล้ว redirect กลับไปยังฟอร์ม (ใช้ `App\Helpers\FormHelper::old()` และ `FormHelper::firstError()` ใน view)
## 2) การจัดการข้อผิดพลาด

- API: คืนค่า JSON ที่มีโครงสร้างชัดเจน (status/message/data/errors)
- Web: แสดงหน้าข้อผิดพลาดจาก `app/Views/errors/` สำหรับ 404/500
- ใน production อย่าแสดง stack trace ให้ผู้ใช้ทั่วไป แทนด้วยการ log รายละเอียดไว้ใน `storage/logs/`
## 3) การตั้งค่าความปลอดภัยพื้นฐาน

- เปิดใช้งาน middleware ที่เกี่ยวข้อง:
  - `CsrfMiddleware` สำหรับฟอร์ม Web (POST/PUT/DELETE/PATCH)
  - `SecurityHeadersMiddleware` เพื่อเพิ่ม header (CSP, X-Frame-Options, X-Content-Type-Options)
## 4) Authentication และ Authorization

- ใช้ `AuthMiddleware` / `GuestMiddleware` สำหรับเส้นทาง Web
- ใช้ `RoleMiddleware` หรือ policy ชั้นสูงสำหรับการตรวจสิทธิ์ตามบทบาท
- ตัดสินใจชัดเจนสำหรับ API auth: ใช้ API key / Bearer token / JWT หรือ session-based และจัดการการ revoke/rotate คีย์
## 5) ฐานข้อมูลและความปลอดภัยของข้อมูล

- ใช้ prepared statements (PDO) หรือ ORM ที่ปลอดภัย
- ใช้ transaction สำหรับงานที่ต้อง atomic (เช่น ลดสต็อก + สร้าง order)
- ห้าม trust ข้อมูลสำคัญจาก client (เช่น ราคารวม) ให้คำนวณฝั่งเซิร์ฟเวอร์
## 6) Logging และการตรวจสอบ

- บันทึกเหตุการณ์สำคัญ: การเข้าสู่ระบบ/การล็อกเอาต์, ความพยายามล้มเหลว, unexpected exceptions
- เก็บ log ใน `storage/logs/` และอย่า log ความลับ (passwords, tokens)
## 7) การทดสอบ

- รันชุดทดสอบด้วย `php console test` หรือ `./vendor/bin/phpunit` (ถ้ามี)
- สร้าง unit tests สำหรับ flow สำคัญ: router/middleware/response/validation
## 8) การนำขึ้นใช้งาน (Deployment)

- ตั้งค่าตัวแปรสภาพแวดล้อม: `APP_ENV=production`, ปิด debug และ endpoint ที่ไม่จำเป็น
- ตรวจสอบ permission สำหรับ `storage/` และ `storage/cache` ให้เขียนได้ แต่ไม่เปิดเผยผ่าน `public/`
## ตรวจสอบสั้น ๆ ก่อนปล่อย

- [ ] `composer install` ถูกเรียกใช้ในเครื่อง server
- [ ] `APP_KEY` ตั้งค่าแล้ว (`php console key:generate`)
- [ ] ทดสอบ endpoint สำคัญ (web + api) ไม่มี 500/404 ที่ไม่คาดคิด
## Quick reference (ไฟล์/คลาสสำคัญ)

- View forms: `App\Helpers\FormHelper`
- API responses: `App\Core\Response` / `App\Helpers\ResponseHelper`
- Error handling: `app/Views/errors/` และ `App\Core\ErrorHandler`
- Middleware: `app/Middleware/` (เช่น `AuthMiddleware`, `CsrfMiddleware`, `CsrfMiddleware`, `SecurityHeadersMiddleware`)
- Logs: `storage/logs/`

ถ้าต้องการ ผมสามารถสแกนโค้ดต่อเพื่อแทรกลิงก์ไปยังไฟล์ที่เกี่ยวข้อง (เช่น `app/Middleware/CsrfMiddleware.php`) หรือรันตรวจสอบลิงก์ภายในเอกสารให้ด้วย
```markdown
# Base Readiness Checklist (SimpleBiz MVC V2)

เอกสารนี้คือ “เช็คลิสต์ขั้นต่ำ” เพื่อให้โปรเจกต์ที่ใช้ framework นี้พร้อมใช้งานจริง (web + API) แบบปลอดภัยและดูแลง่าย

---

## 1) Response & Flow (สำคัญสุด)

- Controller/Middleware **ควร `return` `App\Core\Response`** แทนการ `echo/exit/header()` เอง
- ใช้รูปแบบ API เดียวผ่าน `Response::apiSuccess()` และ `Response::apiError()`
- สำหรับฟอร์ม: validation fail ควร flash errors + old input แล้ว redirect กลับ
  - View ใช้ `App\Helpers\FormHelper` (`old/errors/csrf/flash`)

---

## 2) Error Handling

- API: คืน JSON ตามมาตรฐานเดียวเสมอ (รวม 404/403/405/500)
- Web: แสดงหน้า error จาก `app/Views/errors/*`
- Production:
  - หลีกเลี่ยงการเปิดเผย stack trace/รายละเอียด exception
  - ใช้ logging แทน

---

## 3) Security Defaults

- เปิดใช้ middleware ที่จำเป็นเป็น default:
  - `CsrfMiddleware` สำหรับ web forms
  - `SecurityHeadersMiddleware` (CSP/Frame-Options/NoSniff/Referrer-Policy ฯลฯ)
  - `RateLimitMiddleware` สำหรับ endpoint เสี่ยง/สำคัญ
  - `CorsMiddleware` เฉพาะ API (ตั้ง origin ให้ชัด)
- Session cookie policy:
  - `HttpOnly` เปิด
  - `Secure` เมื่อใช้ HTTPS
  - `SameSite=Lax` (หรือ `Strict` หากเหมาะ)

---

## 4) Authentication & Authorization

- กำหนดเส้นทางและ middleware ให้ชัด:
  - Web: `AuthMiddleware` / `GuestMiddleware`
  - Role/Permission: `RoleMiddleware`
- ถ้าต้องมี API auth:
  - เลือกแนวทางให้ชัด (API key / bearer token / session)
  - ทำ policy เรื่องการ rotate key/token และการ revoke

---

## 5) Database & Data Safety

- ใช้ prepared statements ทุกครั้ง (PDO)
- ใช้ transaction สำหรับงานที่ต้อง atomic (เช่น ตัดสต็อก + สร้าง order)
- กำหนด convention:
  - ห้าม trust “ราคา”/“ยอดรวม” จาก client
  - validate input ทุก endpoint (web + api)

---

## 6) Logging & Observability

- log อย่างน้อย:
  - auth events (login/logout/failed)
  - validation failures (ในระดับที่ไม่บันทึกข้อมูลละเอียดอ่อน)
  - rate limit hits
  - unexpected exceptions
- หลีกเลี่ยงการ log passwords/tokens/secrets

---

## 7) Testing

- มี unit tests ครอบคลุม core flow อย่างน้อย:
  - Router dispatch + middleware short-circuit
  - API response format
  - validation -> flash errors/old input
- ก่อน deploy ให้รัน `composer test`

---

## 8) Deployment

- ตั้งค่า env ให้ถูก:
  - `APP_ENV=production`
  - ปิด debug endpoints (เช่น phpinfo)
- ตรวจสอบ permission:
  - `storage/` เขียนได้
  - log/cache ไม่เปิดเผยผ่าน web root

---

## Quick reference

- View forms: `App\Helpers\FormHelper`
- API JSON: `App\Core\Response::apiSuccess/apiError`
- Errors: `App\Core\ErrorHandler::response()`

```