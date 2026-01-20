# คู่มือการ Deploy (Production)

เอกสารนี้สรุปขั้นตอน deploy ให้ปลอดภัยและพร้อมใช้งานจริง

---

## 1) ข้อกำหนดระบบ
- PHP 8.0+
- MySQL/MariaDB
- Apache (mod_rewrite) หรือ Nginx
- Composer

---

## 2) ตั้งค่า Environment
ปรับไฟล์ .env ให้เป็น production

ตัวอย่างค่าที่ควรเปลี่ยน:
- APP_ENV=production
- APP_DEBUG=false
- APP_URL=https://your-domain.com
- DB_HOST/DB_DATABASE/DB_USERNAME/DB_PASSWORD ให้ตรงกับ production
- MAIL_HOST/MAIL_PORT/MAIL_USERNAME/MAIL_PASSWORD ให้ครบ
- API_KEY เปลี่ยนเป็นค่าจริงที่ปลอดภัย

---

## 3) ติดตั้ง Dependencies
- รัน composer install
- ถ้าเป็น production ให้ใช้ตัวเลือกที่เหมาะสมกับระบบของคุณ

---

## 4) ตั้งค่า Web Server

### Apache
- วางโปรเจคใน DocumentRoot
- ให้ชี้ไปที่ public/ เป็น root
- เปิดใช้งาน mod_rewrite
- ตรวจสอบไฟล์ .htaccess ทั้ง root และ public

### Nginx (ตัวอย่างแนวทาง)
- ตั้ง root ไปที่ public/
- ส่งทุก request ไปที่ index.php

---

## 5) สิทธิ์โฟลเดอร์
ต้องให้เว็บเซิร์ฟเวอร์เขียนได้ใน:
- storage/logs
- storage/cache

---

## 6) เตรียมฐานข้อมูล
- สร้าง DB ตามที่ตั้งค่าไว้
- รัน migrate และ seed ตามต้องการ

---

## 7) ตรวจสอบก่อนเปิดจริง
- APP_DEBUG ต้องเป็น false
- เช็ค error log และ log ระหว่างทดสอบ
- ทดสอบ login, API, และฟีเจอร์หลัก

---

## 8) Post‑Deploy Checklist
- เปิด HTTPS + HSTS (ถ้ามี)
- ตั้งค่า backup DB
- ตั้งค่า log rotation
- ตรวจสอบ permission อีกครั้ง

---

## 9) Rollback แนวทาง (ย่อ)
- สำรอง DB ก่อน deploy ทุกครั้ง
- เก็บ release ย้อนหลังอย่างน้อย 1 เวอร์ชัน
- หาก deploy fail ให้ rollback code และ DB ตามลำดับ
