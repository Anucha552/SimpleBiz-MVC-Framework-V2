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
