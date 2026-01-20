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

## 1. ข้อกำหนดระบบ

### Server Requirements

**ขั้นต่ำ:**
- PHP 8.0 หรือสูงกว่า
- MySQL 5.7+ หรือ MariaDB 10.3+
- Web Server: Apache 2.4+ หรือ Nginx 1.18+
- Memory: 512MB RAM ขั้นต่ำ (แนะนำ 1GB+)
- Disk Space: 1GB+ (ขึ้นกับข้อมูลและ uploads)

**PHP Extensions ที่จำเป็น:**
- PDO และ PDO_MySQL
- mbstring
- json
- openssl
- fileinfo
- curl

**ตรวจสอบ PHP Extensions:**
```bash
php -m | grep -E 'pdo|mbstring|json|openssl|fileinfo|curl'
```

---

## 2. การเตรียมโปรเจค

### 2.1 Clone Repository

```bash
# Clone จาก Git
git clone https://github.com/yourcompany/your-project.git
cd your-project

# หรือ upload ผ่าน FTP/SFTP
```

### 2.2 ติดตั้ง Dependencies

```bash
# ติดตั้ง Composer dependencies (production mode)
composer install --no-dev --optimize-autoloader

# ถ้าต้องการ dev dependencies เพื่อ testing
composer install --optimize-autoloader
```

**หมายเหตุ:** `--no-dev` จะไม่ติดตั้ง development packages (PHPUnit, etc.)

### 2.3 ลบไฟล์ที่ไม่จำเป็น

```bash
# ลบไฟล์พัฒนา
rm -rf tests/
rm phpunit.xml
rm .git/

# ลบ README และเอกสารที่ไม่ต้องการ (optional)
```

---

## 3. การตั้งค่า Environment

### 3.1 สร้างไฟล์ .env

```bash
# คัดลอกจาก template
cp .env.example .env

# แก้ไขด้วย text editor
nano .env
```

### 3.2 ค่า Environment สำหรับ Production

```env
# Application
APP_NAME="Your Application Name"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_KEY=your-32-character-random-key-here

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_production_db
DB_USERNAME=your_db_user
DB_PASSWORD=strong-password-here

# Mail Configuration
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# API Keys
API_KEY=generate-strong-api-key-here

# Security
SESSION_LIFETIME=7200
SESSION_SECURE=true
SESSION_HTTP_ONLY=true
```

### 3.3 สร้าง APP_KEY

```bash
# วิธีที่ 1: ใช้ OpenSSL
openssl rand -hex 16

# วิธีที่ 2: ใช้ PHP
php -r "echo bin2hex(random_bytes(16));"

# วิธีที่ 3: ใช้ console command
php console key:generate
```

### 3.4 ความปลอดภัยของ .env

```bash
# ตั้งสิทธิ์ให้อ่านได้เฉพาะเจ้าของ
chmod 600 .env
chown www-data:www-data .env

# ห้าม .env อยู่ใน git
echo ".env" >> .gitignore
```

---

## 4. การตั้งค่า Web Server

### 4.1 Apache Configuration

**Virtual Host Configuration:**

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    
    DocumentRoot /var/www/your-project/public
    
    <Directory /var/www/your-project/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    
    # Error Log
    ErrorLog ${APACHE_LOG_DIR}/your-project-error.log
    CustomLog ${APACHE_LOG_DIR}/your-project-access.log combined
</VirtualHost>
```

**Enable Required Modules:**

```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod ssl
sudo systemctl restart apache2
```

**ตรวจสอบ .htaccess:**

**/var/www/your-project/.htaccess:**
```apache
# ป้องกันการเข้าถึงไฟล์ sensitive
<FilesMatch "^\.">
    Require all denied
</FilesMatch>

# ส่งทุกอย่างไปยัง public/
RewriteEngine On
RewriteRule ^(.*)$ public/$1 [L]
```

**/var/www/your-project/public/.htaccess:**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Redirect to HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # Route ทุกอย่างผ่าน index.php
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Disable Directory Listing
Options -Indexes

# Protect sensitive files
<FilesMatch "\.(env|log|sql|md)$">
    Require all denied
</FilesMatch>
```

### 4.2 Nginx Configuration

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    
    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/your-project/public;
    
    index index.php index.html;
    
    # SSL Configuration
    ssl_certificate /etc/ssl/certs/yourdomain.com.crt;
    ssl_certificate_key /etc/ssl/private/yourdomain.com.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # Logging
    access_log /var/log/nginx/your-project-access.log;
    error_log /var/log/nginx/your-project-error.log;
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ \.(env|log|sql|md)$ {
        deny all;
    }
    
    # PHP Processing
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Route everything through index.php
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

---

## 5. การจัดการฐานข้อมูล

### 5.1 สร้างฐานข้อมูล

```bash
# เข้า MySQL
mysql -u root -p

# สร้าง database
CREATE DATABASE your_production_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# สร้าง user และ grant permissions
CREATE USER 'your_db_user'@'localhost' IDENTIFIED BY 'strong-password';
GRANT ALL PRIVILEGES ON your_production_db.* TO 'your_db_user'@'localhost';
FLUSH PRIVILEGES;

EXIT;
```

### 5.2 รัน Migrations

```bash
# รัน migrations
php console migrate

# หรือใช้
php migrate.php up
```

### 5.3 รัน Seeders (Optional)

```bash
# สำหรับข้อมูลเริ่มต้น (categories, default users, etc.)
php console seed
```

**⚠️ คำเตือน:** อย่ารัน seeder ที่มีข้อมูลทดสอบใน production

---

## 6. การตั้งค่าความปลอดภัย

### 6.1 สิทธิ์ไฟล์และโฟลเดอร์

```bash
# ตั้งเจ้าของ
sudo chown -R www-data:www-data /var/www/your-project

# ตั้งสิทธิ์โฟลเดอร์
sudo find /var/www/your-project -type d -exec chmod 755 {} \;

# ตั้งสิทธิ์ไฟล์
sudo find /var/www/your-project -type f -exec chmod 644 {} \;

# ให้เขียนได้สำหรับ storage
sudo chmod -R 775 /var/www/your-project/storage
sudo chmod -R 775 /var/www/your-project/public/uploads
```

### 6.2 ปิด Debug Mode

```env
APP_DEBUG=false
```

### 6.3 ป้องกัน Directory Listing

ตรวจสอบว่า `.htaccess` มี `Options -Indexes`

### 6.4 ซ่อนข้อมูล Server

**Apache:**
```apache
ServerTokens Prod
ServerSignature Off
```

**Nginx:**
```nginx
server_tokens off;
```

### 6.5 Rate Limiting

ใช้ `RateLimitMiddleware` ใน routes ที่สำคัญ:

```php
$router->post('/api/v1/orders/create', 'OrderApiController@create', [
    AuthMiddleware::class,
    RateLimitMiddleware::class
]);
```

---

## 7. Performance Optimization

### 7.1 PHP OpCode Cache

```bash
# ติดตั้ง OPcache
sudo apt-get install php8.0-opcache

# แก้ไข php.ini
sudo nano /etc/php/8.0/apache2/php.ini
```

**php.ini:**
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

### 7.2 Database Optimization

```sql
-- เพิ่ม indexes สำหรับ queries ที่ช้า
CREATE INDEX idx_products_status ON products(status);
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_created_at ON orders(created_at);
```

### 7.3 Enable Compression

**Apache:**
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
```

**Nginx:**
```nginx
gzip on;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
```

---

## 8. การตั้งค่า SSL/HTTPS

### 8.1 ใช้ Let's Encrypt (ฟรี)

```bash
# ติดตั้ง Certbot
sudo apt-get install certbot python3-certbot-apache

# สร้าง SSL Certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Auto-renewal
sudo certbot renew --dry-run
```

### 8.2 Force HTTPS

เพิ่มใน `.env`:
```env
APP_URL=https://yourdomain.com
SESSION_SECURE=true
```

---

## 9. Monitoring และ Logging

### 9.1 Error Logging

ตรวจสอบ logs อยู่เสมอ:
```bash
tail -f storage/logs/$(date +%Y-%m-%d).log
```

### 9.2 Log Rotation

สร้าง `/etc/logrotate.d/your-project`:
```
/var/www/your-project/storage/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
}
```

---

## 10. Backup และ Recovery

### 10.1 Database Backup

```bash
# สำรอง database
mysqldump -u your_db_user -p your_production_db > backup-$(date +%Y%m%d).sql

# Restore
mysql -u your_db_user -p your_production_db < backup-20260120.sql
```

### 10.2 อัตโนมัติด้วย Cron

```bash
# แก้ไข crontab
crontab -e

# เพิ่มบรรทัด (backup ทุกวันเวลา 2:00 AM)
0 2 * * * mysqldump -u your_db_user -pYourPassword your_production_db > /backups/db-$(date +\%Y\%m\%d).sql
```

---

## 11. Post-Deployment Checklist

### ✅ Security Checklist

- [ ] `APP_DEBUG=false` ใน production
- [ ] สิทธิ์ไฟล์และโฟลเดอร์ถูกต้อง
- [ ] SSL/HTTPS เปิดใช้งานและ force redirect
- [ ] ไฟล์ `.env` มีสิทธิ์ 600
- [ ] Security headers ถูกตั้งค่า
- [ ] Rate limiting เปิดใช้งาน
- [ ] Directory listing ปิดการใช้งาน

### ✅ Functionality Checklist

- [ ] ทดสอบ login/logout
- [ ] ทดสอบการสร้าง order
- [ ] ทดสอบ API endpoints
- [ ] ทดสอบการส่งอีเมล
- [ ] ทดสอบ file uploads
- [ ] ทดสอบ payment (ถ้ามี)

### ✅ Performance Checklist

- [ ] OpCode cache เปิดใช้งาน
- [ ] Database indexes สร้างแล้ว
- [ ] Static files มี cache headers
- [ ] Gzip compression เปิดใช้งาน
- [ ] CDN ตั้งค่าแล้ว (ถ้ามี)

### ✅ Monitoring Checklist

- [ ] Error logging ทำงาน
- [ ] Log rotation ตั้งค่าแล้ว
- [ ] Database backup automated
- [ ] Uptime monitoring ตั้งค่าแล้ว
- [ ] Performance monitoring ตั้งค่าแล้ว

---

## 🚨 Troubleshooting

### ปัญหา: 500 Internal Server Error

**แก้ไข:**
1. ตรวจสอบ error log: `tail -f storage/logs/*.log`
2. ตรวจสอบ Apache/Nginx error log
3. ตรวจสอบสิทธิ์โฟลเดอร์ storage/
4. ตรวจสอบ .htaccess syntax

### ปัญหา: Database Connection Failed

**แก้ไข:**
1. ตรวจสอบ credentials ใน `.env`
2. ตรวจสอบว่า MySQL service รัน: `sudo systemctl status mysql`
3. ทดสอบ connection: `mysql -u user -p`

### ปัญหา: Permission Denied

**แก้ไข:**
```bash
sudo chown -R www-data:www-data /var/www/your-project
sudo chmod -R 775 storage/
```

---

## เอกสารเพิ่มเติม

- [SECURITY_HARDENING.md](SECURITY_HARDENING.md) - Security best practices
- [ENVIRONMENTS.md](ENVIRONMENTS.md) - Environment variables reference
- [CLI_GUIDE.md](CLI_GUIDE.md) - CLI commands
