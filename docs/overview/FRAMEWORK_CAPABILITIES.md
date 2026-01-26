# ความสามารถของ SimpleBiz MVC Framework V2

เอกสารสรุปความสามารถหลักของเฟรมเวิร์กและประเภทงานที่เหมาะสมสำหรับการใช้งาน

## จุดเด่น
- เบาและเป็นมิตรกับผู้พัฒนา: โครงสร้าง MVC แบบเรียบง่าย เหมาะสำหรับโปรเจกต์ขนาดเล็กถึงขนาดกลาง
- ระบบโมดูล (modules): รองรับการเพิ่มฟีเจอร์เป็นโมดูลแยกกัน (`modules/`) เพื่อแยกความรับผิดชอบ
- Routing ที่ชัดเจน: ลงทะเบียน route แบบ HTTP verb + controller@method พร้อมกำหนด middleware ได้ง่าย
- Middleware: มี middleware สำเร็จรูป (Auth, CSRF, CORS, RateLimit, Logging ฯลฯ)
- Helpers และ Core services: มีชุด helper และบริการหลัก เช่น `Mail`, `Cache`, `Logger`, `Database`, `Session`, `FileUpload`, `View`
- Template views: ระบบวิวแบบไฟล์ PHP พร้อมเลย์เอาท์และ sections
- Migrations & Seeders: มีระบบ migration และ seeder สำหรับเตรียมโครงสร้างฐานข้อมูล
- CLI (console): คำสั่งช่วยพัฒนา เช่น `migrate`, `make:*`, `serve`, `test`

## งานที่เหมาะสม
- เว็บไซต์แบบ server-rendered (business apps, admin panels)
- APIs ขนาดเล็กถึงกลาง (session-based หรือ token-based โดยการติดตั้ง module/เพิ่มเติม)
- โปรเจกต์ที่ต้องการโครงสร้างเรียบง่ายแต่มีบริการพื้นฐานครบถ้วน

## ข้อจำกัด / เมื่อไม่ควรใช้
- ไม่แนะนำสำหรับแอปพลิเคชันขนาดใหญ่ที่ต้องการระบบ dependency injection ซับซ้อนหรือ microservices ที่ต้องการ orchestration ขั้นสูง
- ถ้าต้องการ performance ในระดับสูง (หลายล้านคำขอ/วัน) อาจต้องพิจารณา scale ที่ระดับ infrastructure เพิ่มเติม

---

ถ้าต้องการ ผมสามารถเพิ่มตัวอย่างสถาปัตยกรรมการ deploy, การใช้ queue หรือตัวอย่างการขยายระบบสำหรับงานระดับสูงได้
````markdown
# ความสามารถและประเภทเว็บที่เหมาะสม

(เอกสารย้ายไปยัง `docs/overview/FRAMEWORK_CAPABILITIES.md`)

````