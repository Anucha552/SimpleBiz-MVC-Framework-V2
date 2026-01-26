````markdown
# คู่มือการ Deploy (Production Deployment Guide)

เอกสารนี้อธิบายขั้นตอนการ deploy SimpleBiz MVC Framework ไปยัง production อย่างละเอียด เพื่อความปลอดภัยและประสิทธิภาพสูงสุด

---

## 📋 สารบัญ

1. [ข้อกำหนดระบบ](#1-ข้อกำหนดระบบ)
2. [การเตรียมโปรเจค](#2-การเตรียมโปรเจค)
3. [การตั้งค่า Environment](#3-การตั้งค่า-environment)
4. [การตั้งค่า Web Server](#4-การตั้งค่า-web-server)
5. [การจัดการฐานข้อมูล](#5-การจัดการฐานข้อมูล)
6. [การตั้งค่าความปลอดภัย](#6-การตั้งค่าความปลอดภัย)
7. [Performance Optimization](#7-performance-optimization)
8. [การตั้งค่า SSL/HTTPS](#8-การตั้งค่า-sslhttps)
9. [Monitoring และ Logging](#9-monitoring-และ-logging)
10. [Backup และ Recovery](#10-backup-และ-recovery)
11. [Post-Deployment Checklist](#11-post-deployment-checklist)

---

(เอกสารยาว ถูกย้ายไปยัง `docs/deployment/DEPLOYMENT_GUIDE.md`)

﻿# คู่มือการ Deploy (ฉบับย่อเป็นภาษาไทย)

เอกสารฉบับนี้สรุปขั้นตอนที่สำคัญสำหรับการนำ SimpleBiz MVC Framework ขึ้นใช้งานบนสภาพแวดล้อม production ของจริง โดยตัดเนื้อหาที่ไม่เกี่ยวข้องออกและเน้นขั้นตอนที่จำเป็นจริง

---

## ข้อกำหนดพื้นฐาน
- ระบบปฏิบัติการ: Linux (Ubuntu/Debian, CentOS/RHEL แนะนำ)
- PHP: 8.0+ (ติดตั้ง PHP-FPM)
- Composer: ล่าสุด
- Database: MySQL/MariaDB หรือที่รองรับ (PDO)
- Web server: Nginx หรือ Apache (แนะนำ Nginx + PHP-FPM)
- พื้นที่เก็บ: เขียนได้ (`storage/`, `storage/logs/`, `storage/cache/`, `public/uploads`)

---

## การเตรียมโปรเจคบนเซิร์ฟเวอร์
1. โคลน repo ลงเซิร์ฟเวอร์หรือดึงจาก CI/CD

```bash
git clone <repo-url> my-app
cd my-app
```

2. ติดตั้ง dependency ด้วย Composer (production):

```bash
composer install --no-dev --optimize-autoloader
```

3. สร้างไฟล์ `.env` (สามารถคัดลอกจาก `.env.example`) และตั้งค่าให้ถูกต้อง

```bash
cp .env.example .env
# แก้ค่าใน .env ตาม environment
```

4. สร้าง `APP_KEY` ถ้าจำเป็น (โปรเจคนี้มีสคริปต์ setup):

```bash
php console setup
```

หรือถ้าไม่มี ให้ใช้สคริปต์สร้าง key ของโปรเจค

---

## การตั้งค่าสิทธิ์ไฟล์ (Permissions)
- ให้ `storage/` และ `public/uploads/` สามารถเขียนได้โดยผู้ใช้ที่รัน PHP-FPM (มักเป็น `www-data` หรือ `nginx`)

ตัวอย่าง (Ubuntu):

```bash
chown -R www-data:www-data storage public/uploads
chmod -R 750 storage public/uploads
```

---

## การตั้งค่า Web Server (ตัวอย่าง Nginx)
ตัวอย่างไฟล์ site config สำหรับ Nginx (ปรับตาม domain และ path):

```nginx
server {
	listen 80;
	server_name example.com www.example.com;

	root /var/www/my-app/public;
	index index.php;

	access_log /var/log/nginx/my-app.access.log;
	error_log /var/log/nginx/my-app.error.log;

	location / {
		try_files $uri $uri/ /index.php?$query_string;
	}

	location ~ \.php$ {
		include fastcgi_params;
		fastcgi_pass unix:/run/php/php8.0-fpm.sock;
		fastcgi_index index.php;
		fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
	}

	location ~ /\.ht {
		deny all;
	}
}
```

หลังปรับแล้ว reload Nginx:

```bash
sudo systemctl reload nginx
```

---

## ฐานข้อมูล
1. สร้าง database และ user ตามค่าที่กำหนดใน `.env`
2. รัน migrations และ seeders (ถ้ามี):

```bash
php console migrate
php console seed
```

หมายเหตุ: ตรวจสอบ `config/database.php` หรือการตั้งค่า database ของโปรเจคให้ตรงกับ `.env`

---

## การจัดการ environment และความปลอดภัย
- ตั้งค่า `APP_ENV=production` และ `APP_DEBUG=false` ใน `.env`
- เก็บ credentials ใน `.env` เท่านั้นและอย่า commit ขึ้น repo
- ปรับ header ความปลอดภัย (X-Frame-Options, X-Content-Type-Options, Referrer-Policy) ใน server config หรือ middleware

---

## การปรับแต่งประสิทธิภาพ
- เปิดใช้ OPcache ใน PHP
- ใช้ `composer install --no-dev --optimize-autoloader`
- ใช้ caching สำหรับ view/response ถ้าโปรเจครองรับ
- ตั้งค่า reverse proxy / load balancer เมื่อจำเป็น

---

## SSL / HTTPS
- แนะนำให้ใช้ HTTPS เสมอ เช่น ติดตั้งด้วย Certbot (Let's Encrypt)

ตัวอย่าง (Certbot + Nginx):

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d example.com -d www.example.com
```

---

## Logging และ Monitoring
- Log อยู่ที่ `storage/logs/` ตรวจสอบ permission ให้เขียนได้
- แนะนำเชื่อมกับระบบ monitoring (Prometheus, Grafana) หรือ Sentry สำหรับ error tracking

---

## การสำรองข้อมูล (Backup)
- สำรองฐานข้อมูลเป็นประจำ (mysqldump หรือ service ที่ใช้)
- สำรองไฟล์สำคัญ (uploads, storage) ไปยัง remote storage (S3 หรือ backup server)

---

## Post-Deployment Checklist (สั้น)
- ตรวจสอบว่า `APP_ENV=production` และ `APP_DEBUG=false`
- ตรวจสอบ permissions ของ `storage/` และ `public/uploads/`
- รัน `php console migrate` และ `php console seed` ถ้าจำเป็น
- ตรวจสอบว่าเว็บเซิร์ฟเวอร์ตอบคำขอและไม่แสดง error stack
- ตั้งค่า SSL และตรวจสอบ renewal

---

ถ้าต้องการ ผมจะแปลงตัวอย่างไฟล์ `.env` และเพิ่มตัวอย่างการตั้งค่า Nginx/Apache สำหรับ environment ของคุณ หรือสร้าง checklist ที่ละเอียดขึ้นสำหรับการ deploy แบบ CI/CD
