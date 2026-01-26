# โครงสร้างโปรเจค (Project Structure)

ภาพรวมโฟลเดอร์และไฟล์สำคัญของโปรเจกต์ SimpleBiz-MVC-Framework-V2 พร้อมคำอธิบายสั้น ๆ เพื่อให้นักพัฒนาทำความเข้าใจได้เร็ว

```
.
├─ app/
│  ├─ Controllers/        # คอนโทรลเลอร์ของแอป แยกเป็น Web และ Api
│  ├─ Core/               # คลาสหลักของเฟรมเวิร์ก (Router, Request, Response, Mail, Cache, Logger, ฯลฯ)
│  ├─ Helpers/            # ฟังก์ชันช่วยเหลือ (ArrayHelper, FormHelper, StringHelper...)
│  ├─ Middleware/         # Middlewares ที่มีให้ใช้ (Auth, Csrf, Cors, RateLimit...)
│  ├─ Models/             # โมเดลของแอป (project-specific)
│  └─ Views/              # ไฟล์ view (PHP templates)

├─ config/
│  ├─ app.php
│  ├─ database.php
│  └─ modules.php         # เปิด/ปิดโมดูลของระบบ

├─ console               # CLI entrypoint (register คำสั่ง เช่น migrate, make:*, serve)
├─ database/
│  ├─ migrations/        # ไฟล์ migration ของระบบ
│  └─ seeders/           # seeders สำหรับเติมข้อมูลตัวอย่าง

├─ docs/                 # เอกสารของโครงการ (ถูกจัดหมวดหมู่แล้ว)
├─ modules/              # โมดูลที่ติดตั้ง (Auth, HelloWorld, ...)
├─ public/               # เว็บรูท (index.php, assets)
├─ routes/               # กำหนดเส้นทาง: web.php, api.php
├─ storage/              # เก็บ cache, logs, views generated
├─ tests/                # Unit / Feature tests
└─ vendor/               # dependencies managed by Composer

```

คำอธิบายเพิ่มเติม (สั้น ๆ)
- `app/Core` — เป็นที่รวมบริการหลักของเฟรมเวิร์ก เช่น `Database`, `Session`, `Logger`, `Cache`, `Mail`, `FileUpload`, `View` และอื่น ๆ
- `modules/` — แต่ละโมดูลมี `*Module.php` ที่ implement `ModuleInterface` แล้วลงทะเบียน route กับ `Router`
- `console` — สคริปต์ CLI ที่ลงทะเบียนคำสั่งช่วยพัฒนา (migrate, make, serve, test)
- `config/modules.php` — เปิด/ปิดโมดูลโดยเพิ่ม/เอา class ของโมดูลออกจากอาร์เรย์

ถ้าต้องการ ผมช่วยสร้างแผนผังภาพ (ASCII หรือ SVG) หรือขยายรายละเอียดแต่ละโฟลเดอร์เป็นหน้าเอกสารเพิ่มเติมได้ครับ
````markdown
# โครงสร้างโปรเจค SimpleBiz MVC Framework V2

(เอกสารย้ายไปยัง `docs/overview/PROJECT_STRUCTURE.md`)

````