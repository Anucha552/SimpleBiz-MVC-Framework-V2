# โมดูล HelloWorld (Modules\HelloWorld)

โมดูลตัวอย่างที่ให้ route เรียบง่ายสำหรับทดสอบการลงทะเบียนโมดูลและการตอบกลับ API

## สรุปความสามารถ
- `GET /hello` — หน้า HTML แสดงข้อความจากโมดูล
- `GET /api/hello` — ตัวอย่าง API ที่คืน JSON พร้อม `request_id`
- คอนโทรลเลอร์: `Modules\HelloWorld\Controllers\HelloController`

## วิธีใช้งาน
เพียงเปิดโมดูลใน `config/modules.php`:

```php
Modules\HelloWorld\HelloWorldModule::class,
```

และเข้าที่ `http://your-app/hello` หรือ `http://your-app/api/hello`

## รายละเอียด route (จาก `modules/HelloWorld/HelloWorldModule.php`)
- `GET /hello` → `HelloController::index`
- `GET /api/hello` → `HelloController::api`

ตัวอย่างการเรียก API (curl):

```bash
curl -s https://localhost/api/hello | jq
```
