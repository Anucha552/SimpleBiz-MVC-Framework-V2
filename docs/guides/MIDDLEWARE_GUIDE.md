# คู่มือการใช้งาน Middleware (อัปเดตตามโค้ดจริง)

ตำแหน่ง: `app/Middleware/`

Middleware ที่พบในโปรเจคและหน้าที่โดยย่อ:

- `ApiKeyMiddleware` — ตรวจสอบ API key (header หรือ bearer token) สำหรับ endpoints ที่ต้องการคีย์
- `AuthMiddleware` — ตรวจสอบการยืนยันตัวตน (session) และ redirect หรือคืนค่า 401 สำหรับ API
- `CorsMiddleware` — จัดการ header CORS สำหรับ API
- `CsrfMiddleware` — ตรวจสอบ CSRF token สำหรับคำขอแบบฟอร์ม (POST/PUT/DELETE/PATCH)
- `GuestMiddleware` — ป้องกันเส้นทางสำหรับผู้ที่ล็อกอินแล้ว (เช่น หน้า login/register)
- `LoggingMiddleware` — บันทึก request/response และเหตุการณ์สำคัญ
- `MaintenanceMiddleware` — ปิดระบบชั่วคราวเมื่ออยู่ในโหมด maintenance
- `RateLimitMiddleware` — ตรวจสอบอัตราการเรียกใช้งาน (rate limiting)
- `RoleMiddleware` — ตรวจสอบสิทธิ์ตาม role/permission
- `SecurityHeadersMiddleware` — เพิ่ม security headers (CSP, X-Frame-Options ฯลฯ)
- `ValidationMiddleware` — ตรวจสอบและแปลง validation errors ให้เป็น flash/response ที่เหมาะสม

รูปแบบเมธอดหลัก:
- แต่ละ middleware มีเมธอด `handle(?\App\Core\Request $request = null)` ซึ่งคืนค่า `true` (ดำเนินการต่อ), `false` (หยุด chain), หรือ `Response` เมื่อต้องการส่งการตอบกลับทันที

ตัวอย่างการใช้งาน (การลงทะเบียนใน route):

```php
// ตัวอย่างใน routes/web.php
$router->get('/checkout', ['middleware' => ['auth', 'csrf'], 'uses' => 'CheckoutController@index']);

// ตัวอย่างใน routes/api.php
$router->post('/api/v1/orders', ['middleware' => ['api_key', 'rate_limit'], 'uses' => 'Api\\OrdersController@store']);
```

ข้อแนะนำปฏิบัติ:
- ตรวจสอบ signature ของ `handle()` และส่ง `Response` เมื่อจำเป็น (เช่น คืนค่า JSON สำหรับ API)
- อย่าใส่ business logic ซับซ้อน — ใช้ service classes และเรียกจาก middleware
- ทดสอบ middleware โดย mock `Request`/`Session` และเรียก `handle()` เพื่อตรวจพฤติกรรม

ถัดไป ผมจะสแกน `app/Middleware/*.php` เพื่อสกัดตัวอย่างเมธอดสาธารณะและเพิ่มตัวอย่างโค้ดจริงในเอกสาร — ต้องการให้ผมดำเนินการต่อหรือไม่? 
