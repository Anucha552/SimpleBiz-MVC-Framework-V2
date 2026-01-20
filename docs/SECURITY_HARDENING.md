# Security Hardening Checklist (Production)

## Application
- APP_ENV=production
- APP_DEBUG=false
- เปลี่ยน API_KEY เป็นค่าแบบสุ่มและยาว
- จำกัดสิทธิ์ของ DB user ให้เฉพาะที่จำเป็น

## HTTP/HTTPS
- เปิดใช้ HTTPS (TLS)
- เพิ่ม HSTS (ถ้าพร้อม)
- ตรวจสอบ security headers ใน public/.htaccess

## Session & Cookies
- ตั้งค่า session lifetime ให้เหมาะสม
- ใช้ HTTPS เพื่อป้องกัน session hijacking

## File Permissions
- ให้เขียนได้เฉพาะ storage/logs และ storage/cache
- ปิดการเขียนในโฟลเดอร์อื่น

## Logging & Monitoring
- เปิด log error
- จัดการ log rotation
- ตั้งค่า alert สำหรับ error สำคัญ

## Database
- สำรองข้อมูลสม่ำเสมอ
- เปิดการเข้าถึงเฉพาะ IP ที่จำเป็น

## Backup & Recovery
- มีขั้นตอน rollback และ backup ที่ตรวจสอบแล้ว
