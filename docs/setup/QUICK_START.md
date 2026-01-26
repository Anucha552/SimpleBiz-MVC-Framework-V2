# Quick Start — เริ่มต้นใช้งานอย่างรวดเร็ว (ภาษาไทย)

เอกสารฉบับย่อสำหรับการตั้งค่าโปรเจกต์ SimpleBiz MVC Framework V2 บนเครื่องพัฒนา

1) โคลน repository และเข้าโฟลเดอร์โปรเจกต์

```bash
git clone https://github.com/Anucha552/SimpleBiz-MVC-Framework-V2.git
cd SimpleBiz-MVC-Framework-V2
```

2) ติดตั้ง dependencies ด้วย Composer

```bash
composer install
```

3) คัดลอกไฟล์ตัวอย่าง `.env` และปรับค่าเบื้องต้น

```bash
cp .env.example .env
# แก้ค่าใน .env ตามสภาพแวดล้อม (DB, APP_ENV, APP_DEBUG, ฯลฯ)
```

4) สร้าง `APP_KEY` ถ้ายังไม่มี

```bash
php -r "file_exists('.env') || copy('.env.example', '.env');"
php -r "echo 'APP_KEY=' . bin2hex(random_bytes(16)) . PHP_EOL;" >> .env
```

5) (ตัวเลือก) รันคำสั่ง setup อัตโนมัติ

ถ้าต้องการให้สคริปต์ช่วยตั้งค่าขั้นต้น (สร้าง composer name, README, ฯลฯ):

```bash
php console setup
```

6) สร้างฐานข้อมูลและรัน migrations

```bash
# สร้าง database ด้วย MySQL/MariaDB
mysql -u root -p -e "CREATE DATABASE my_project CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# จากนั้นรัน migrations
php console migrate
```

7) รันเซิร์ฟเวอร์ในโหมดพัฒนา

```bash
php console serve
# เปิดเบราว์เซอร์ที่ http://localhost:8000 (ค่าเริ่มต้น)
```

8) รันชุดทดสอบ (ถ้ามี)

```bash
./vendor/bin/phpunit --colors=always
```

เคล็ดลับเพิ่มเติม
- ตรวจดูและตั้งค่า `config/modules.php` เพื่อเปิดโมดูลที่ต้องการ
- ตรวจสอบ `storage/` ให้ web process เขียนได้ (permissions)
- ตั้งค่า `.env` ให้ `APP_DEBUG=false` ใน production

ต้องการให้ผมอัปเดต `docs/setup/SETUP_COMMAND.md` ให้สอดคล้องกับสคริปต์ `console setup` จริงหรือให้แปล/ย่อเพิ่มเติมอีกไหม? 
````markdown
# Quick Start - เริ่มต้นใช้งานอย่างรวดเร็ว

(เอกสารย้ายไปยัง `docs/setup/QUICK_START.md`)

````