# คู่มือตรวจสอบความปลอดภัย (Security Hardening) — สำหรับการใช้งาน Production

เอกสารนี้รวบรวมการตั้งค่าและตรวจสอบที่แนะนำเพื่อให้แอปของคุณปลอดภัยยิ่งขึ้น โดยอ้างอิงฟีเจอร์ที่มีอยู่ในโค้ด เช่น `CsrfMiddleware`, `CorsMiddleware`, `ApiKeyMiddleware`, `RateLimitMiddleware`, `SecurityHeadersMiddleware`, และ `Logger`

---

1) สภาพแวดล้อมการรัน
- ตั้งค่า `APP_ENV=production` และ `APP_DEBUG=false` ในไฟล์ `.env` เพื่อปิดการแสดงข้อผิดพลาดแบบละเอียด
- ตรวจสอบให้แน่ใจว่า `APP_KEY` ถูกตั้งค่าและเป็นค่าสุ่มยาวพอ (ใช้ `php -r "echo bin2hex(random_bytes(32));"` เพื่อสร้างค่า)

2) HTTPS และ Header ความปลอดภัย
- ใช้ HTTPS เสมอ (กำหนด TLS บน load balancer / reverse proxy)
- ตั้งค่า HSTS (Strict-Transport-Security) ที่ระดับ proxy/web server
- เปิดใช้งาน `SecurityHeadersMiddleware` (ไฟล์: `app/Middleware/SecurityHeadersMiddleware.php`) เพื่อเพิ่ม header เช่น `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Content-Security-Policy`

3) Session & Cookies
- ตรวจสอบให้ cookie session ตั้งค่า `Secure`, `HttpOnly`, และ `SameSite` (ค่าเริ่มต้นใน `App/Core/Session.php` ใช้ `cookie_httponly=true` และพยายามตั้ง `cookie_secure` อัตโนมัติ)
- หากใช้ reverse proxy ให้ส่ง header `X-Forwarded-Proto` เพื่อให้แอปตรวจสอบว่าเชื่อมต่อผ่าน HTTPS

4) CSRF Protection
- ใช้ `CsrfMiddleware` สำหรับ route POST/PUT/DELETE ที่เป็น Web forms (ไฟล์: `app/Middleware/CsrfMiddleware.php`)
- ใส่ token ในฟอร์มด้วย `Session::csrfField()` หรือ `FormHelper::csrfField()`

5) CORS
- ตรวจสอบการตั้งค่า `CorsMiddleware` (ไฟล์: `app/Middleware/CorsMiddleware.php`) ให้อนุญาต origin ที่จำเป็นเท่านั้น และจำกัด methods/headers ที่ต้องการ
- เปิด preflight (OPTIONS) หาก API ถูกเรียกจากเบราว์เซอร์ข้ามโดเมน

6) API Keys & Authorization
- หากมี endpoint ที่ต้องการ API key ให้เปิด `ApiKeyMiddleware` (ไฟล์: `app/Middleware/ApiKeyMiddleware.php`) และยืนยันคีย์จาก header `X-API-Key` หรือพารามิเตอร์ query ตามที่ middleware กำหนด
- ใช้ `RoleMiddleware` (`app/Middleware/RoleMiddleware.php`) เพื่อจำกัดการเข้าถึงตาม role (เช่น `admin`)

7) Rate limiting
- เปิด `RateLimitMiddleware` (`app/Middleware/RateLimitMiddleware.php`) สำหรับ endpoint ที่เสี่ยงต่อการโจมตีแบบ brute-force หรือการเรียกบ่อยเกิน
- กำหนดค่า limit (requests per window) ผ่าน environment variables หรือ config ที่โปรเจกต์รองรับ

8) Input validation และ Sanitization
- ใช้ `Validator` (`app/Core/Validator.php`) และ `ValidationMiddleware` (`app/Middleware/ValidationMiddleware.php`) เพื่อตรวจสอบข้อมูลจากผู้ใช้ก่อนประมวลผลหรือบันทึก
- หลีกเลี่ยงการต่อ string เพื่อสร้าง SQL — ใช้ prepared statements ผ่าน `App/Core/Database.php`

9) File upload security
- ใช้ `FileUpload` (`app/Core/FileUpload.php`) เพื่อตรวจสอบ MIME type, นามสกุล และขนาดไฟล์
- เก็บไฟล์อัพโหลดนอกเว็บรูทหรือเซ็ตสิทธิ์การเข้าถึงไฟล์ให้เหมาะสม (`public/uploads` ควรจำกัดชนิดไฟล์ที่ให้เรียก)
- สร้างชื่อไฟล์ใหม่ (sanitize/rename) เพื่อป้องกัน path traversal และการ overwrite

10) Logging และ Monitoring
- ใช้ `Logger` (`app/Core/Logger.php`) สำหรับเหตุการณ์สำคัญและ security events (`security` level)
- เปิด LoggingMiddleware (`app/Middleware/LoggingMiddleware.php`) ใน route ที่ต้องการตรวจสอบกิจกรรม
- ตั้งค่าการเก็บรักษาและหมุนไฟล์ล็อก (ดู `MAX_LOG_SIZE`, `LOG_RETENTION_DAYS` ถ้ามีใน env)

11) Error handling
- ให้ `ErrorHandler` คืน JSON สำหรับ `/api/*` และหน้า HTML สำหรับเว็บปกติ (`app/Core/ErrorHandler.php`) — อย่าเปิด stack traces ใน production

12) Maintenance mode
- ใช้ `MaintenanceMiddleware` (`app/Middleware/MaintenanceMiddleware.php`) เพื่อปิดการให้บริการชั่วคราวเมื่อต้องการบำรุงรักษา

13) Deployment / Operational checklist
- ตรวจสอบสิทธิ์ของโฟลเดอร์ `storage/` (ต้องเขียนได้โดย process ของเว็บเซิร์ฟเวอร์)
- รัน migrations และ seeders ก่อนเปิดใช้งาน production:

```powershell
php console migrate --force
php console db:seed
```

- ตั้งค่า cron / supervisor สำหรับ queue worker และ job scheduler (ถ้ามี)
- ตั้งค่า monitoring และ alert (เช่นตรวจข้อผิดพลาด 5xx หรือ rate of security events)

14) การตรวจสอบเพิ่มเติม (recommended)
- ตรวจสอบโค้ดเพื่อหาจุดที่อาจรับ input โดยไม่ผ่าน validator
- รัน static analysis และ dependency vulnerabilities scan (เช่น `composer audit`)
- ทดสอบ CSRF/CORS/Authorization ในสภาพแวดล้อม staging ก่อนขึ้น production

---

ไฟล์ที่เกี่ยวข้อง (ตรวจสอบและปรับค่าได้ตามต้องการ):
- `app/Middleware/CsrfMiddleware.php`
- `app/Middleware/CorsMiddleware.php`
- `app/Middleware/ApiKeyMiddleware.php`
- `app/Middleware/RateLimitMiddleware.php`
- `app/Middleware/SecurityHeadersMiddleware.php`
- `app/Core/Session.php`, `app/Core/Logger.php`, `app/Core/Database.php`, `app/Core/FileUpload.php`

ต้องการให้ผม: 1) เพิ่มตัวอย่างโค้ดการตั้งค่า `CSP` และ header ที่แนะนำใน `SecurityHeadersMiddleware`, 2) สร้างสคริปต์ตรวจสอบสิทธิ์ `storage/` และ permissions แบบอัตโนมัติ หรือ 3) รันสแกนลิงก์ภายใน `docs/` เพื่ออัปเดต path ที่เปลี่ยนไป — ให้ผมเริ่มทำข้อไหนต่อครับ?
````markdown
# Security Hardening Checklist (Production)

(เอกสารย้ายไปยัง `docs/security/SECURITY_HARDENING.md`)

````