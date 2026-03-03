# คู่มือการ Deploy - SimpleBiz MVC Framework V2

## สารบัญ
- [ความต้องการของระบบ](#ความต้องการของระบบ)
- [การติดตั้งเบื้องต้น](#การติดตั้งเบื้องต้น)
- [การตั้งค่า Environment](#การตั้งค่า-environment)
- [การติดตั้งฐานข้อมูล](#การติดตั้งฐานข้อมูล)
- [การกำหนดค่า Web Server](#การกำหนดค่า-web-server)
- [การตั้งค่า Production](#การตั้งค่า-production)
- [การ Optimize Performance](#การ-optimize-performance)  
- [การ Monitor และ Maintenance](#การ-monitor-และ-maintenance)
- [Troubleshooting](#troubleshooting)

---

## ความต้องการของระบบ

### ขั้นต่ำ
- **PHP**: >= 8.0
- **Web Server**: Apache 2.4+ หรือ Nginx 1.18+
- **Database**: MySQL 5.7+ หรือ MariaDB 10.3+
- **Composer**: 2.0+
- **Memory**: 128MB+ PHP Memory Limit
- **Storage**: 1GB+ สำหรับไฟล์และ logs

### แนะนำสำหรับ Production
- **PHP**: 8.2+
- **Web Server**: Nginx 1.22+ (สำหรับ performance)
- **Database**: MySQL 8.0+ หรือ MariaDB 10.6+
- **Memory**: 256MB+ PHP Memory Limit
- **Storage**: 10GB+ พร้อม SSD
- **SSL Certificate**: สำหรับ HTTPS

### PHP Extensions ที่จำเป็น
```bash
# Ubuntu/Debian
sudo apt install php8.2-cli php8.2-fpm php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath

# CentOS/RHEL
sudo dnf install php php-cli php-fpm php-mysqlnd php-zip php-gd php-mbstring php-curl php-xml php-bcmath
```

---

## การติดตั้งเบื้องต้น

### 1. ดาวน์โหลดและแตกไฟล์
```bash
# Clone repository
git clone https://github.com/simplebiz/mvc-framework-v2.git
cd mvc-framework-v2

# หรือ Upload ไฟล์ ZIP แล้วแตกไฟล์
unzip simplebiz-mvc-framework-v2.zip
cd simplebiz-mvc-framework-v2
```

### 2. ติดตั้ง Dependencies
```bash
# ติดตั้ง Composer dependencies
composer install --no-dev --optimize-autoloader

# สำหรับ Development
composer install
```

### 3. กำหนดสิทธิ์ไฟล์
```bash
# ให้สิทธิ์เขียนโฟลเดอร์ storage
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/

# กำหนดสิทธิ์ cache และ logs
sudo chmod -R 775 storage/cache/
sudo chmod -R 775 storage/logs/

# ปกป้อง sensitive files
sudo chmod 600 .env
sudo chmod 644 composer.json
```

---

## การตั้งค่า Environment

### 1. สร้างไฟล์ .env
```bash
# Copy ไฟล์ example
cp .env.example .env

# แก้ไขไฟล์ .env
nano .env
```

### 2. การตั้งค่าพื้นฐาน
```env
######################### แอปพลิเคชัน #########################
APP_NAME="My Application"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_KEY=your-secret-key-32-characters-long
APP_TIMEZONE=Asia/Bangkok
APP_COOKIE_DOMAIN=.yourdomain.com

######################### Logging #########################
MAX_LOG_SIZE=10485760          # 10MB
LOG_RETENTION_DAYS=30          # 30 วัน
LOG_DETAILED=false
LOG_REQUEST_BODY=false

######################### Database #########################
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_strong_password
DB_CHARSET=utf8mb4
```

### 3. สร้าง APP_KEY ที่ปลอดภัย
```bash
# สร้าง random key 32 characters
php -r "echo bin2hex(random_bytes(16)) . PHP_EOL;"

# หรือใช้ OpenSSL
openssl rand -hex 16
```

---

## การติดตั้งฐานข้อมูล

### 1. สร้างฐานข้อมูล
```sql
-- เข้า MySQL/MariaDB
CREATE DATABASE your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- สร้าง User และให้สิทธิ์
CREATE USER 'your_username'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON your_database_name.* TO 'your_username'@'localhost';
FLUSH PRIVILEGES;
```

### 2. รัน Migrations
```bash
# รัน migrations ทั้งหมด
php console migration:run

# หรือรัน migrations แบบระบุ path
php console migration:run --path=database/migrations

# Check สถานะ migration
php console migration:status
```

### 3. รัน Seeders (ถ้าจำเป็น)
```bash
# รัน seeders ทั้งหมด
php console seed:run

# รัน seeder เฉพาะ
php console seed:run --class=UserSeeder
```

---

## การกำหนดค่า Web Server

### Apache Configuration

#### 1. Virtual Host
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /path/to/simplebiz-mvc-framework-v2/public
    
    <Directory /path/to/simplebiz-mvc-framework-v2/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Redirect to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /path/to/simplebiz-mvc-framework-v2/public
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    
    <Directory /path/to/simplebiz-mvc-framework-v2/public>
        AllowOverride All
        Require all granted
        
        # Security Headers
        Header always set X-Content-Type-Options nosniff
        Header always set X-Frame-Options DENY
        Header always set X-XSS-Protection "1; mode=block"
        Header always set Strict-Transport-Security "max-age=63072000"
    </Directory>
    
    # Hide sensitive files
    <Files ~ "(\.env|composer\.json|composer\.lock)$">
        Order allow,deny
        Deny from all
    </Files>
</VirtualHost>
```

#### 2. .htaccess (ควรมีอยู่แล้วใน public/)
```apache
RewriteEngine On

# Redirect Trailing Slashes If Not A Folder
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} (.+)/$
RewriteRule ^ %1 [L,R=301]

# Handle Front Controller
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]

# Security
<Files .env>
    Order allow,deny
    Deny from all
</Files>
```

### Nginx Configuration

#### 1. Server Block
```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /path/to/simplebiz-mvc-framework-v2/public;
    index index.php;

    # SSL Configuration
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Strict-Transport-Security "max-age=63072000" always;

    # Handle PHP Files
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Block access to sensitive files
    location ~ /\.(env|git) {
        deny all;
        return 404;
    }

    location ~ /(composer\.(json|lock)|package\.json)$ {
        deny all;
        return 404;
    }

    # Static files caching
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1M;
        add_header Cache-Control "public, immutable";
    }
}
```

---

## การตั้งค่า Production

### 1. เปิดใช้ PHP OPcache
```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

### 2. ปรับแต่ง PHP Settings
```ini
; php.ini - Production Settings
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /path/to/php_errors.log

memory_limit = 256M
post_max_size = 50M
upload_max_filesize = 50M
max_execution_time = 60
max_input_time = 60

session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
```

### 3. Database Optimization
```sql
-- MySQL/MariaDB Tuning
SET GLOBAL innodb_buffer_pool_size = 256M;
SET GLOBAL query_cache_type = ON;
SET GLOBAL query_cache_size = 64M;

-- Index optimization
ANALYZE TABLE table_name;
OPTIMIZE TABLE table_name;
```

### 4. Log Rotation
```bash
# สร้างไฟล์ logrotate
sudo nano /etc/logrotate.d/simplebiz-mvc

# เนื้อหาไฟล์
/path/to/simplebiz-mvc-framework-v2/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    postrotate
        # Reload PHP-FPM if needed
        /usr/sbin/service php8.2-fpm reload > /dev/null
    endscript
}
```

---

## การ Optimize Performance

### 1. Composer Optimization
```bash
# Optimize autoloader for production
composer dump-autoload --optimize --classmap-authoritative

# Clear development packages
composer install --no-dev --optimize-autoloader
```

### 2. Cache Configuration
```bash
# สร้าง cache directories
mkdir -p storage/cache/{views,routes,config}
chmod -R 775 storage/cache/
```

### 3. Database Connection Pooling
```env
# ในไฟล์ .env - เพิ่มการตั้งค่า connection pooling
DB_PERSISTENT=true
DB_POOL_SIZE=10
```

### 4. CDN Setup (แนะนำ)
```html
<!-- ใช้ CDN สำหรับ static assets -->
<link rel="stylesheet" href="https://cdn.yourdomain.com/assets/css/app.css">
<script src="https://cdn.yourdomain.com/assets/js/app.js"></script>
```

---

## การ Monitor และ Maintenance

### 1. Health Check Script
```php
<?php
// health-check.php
require_once 'vendor/autoload.php';

$checks = [
    'database' => checkDatabase(),
    'storage' => checkStorage(),
    'cache' => checkCache(),
];

function checkDatabase() {
    try {
        // Test database connection
        $db = new PDO(
            'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_DATABASE'],
            $_ENV['DB_USERNAME'],
            $_ENV['DB_PASSWORD']
        );
        return ['status' => 'ok', 'message' => 'Database connected'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function checkStorage() {
    if (is_writable('storage/logs') && is_writable('storage/cache')) {
        return ['status' => 'ok', 'message' => 'Storage writable'];
    }
    return ['status' => 'error', 'message' => 'Storage not writable'];
}

function checkCache() {
    $cacheFile = 'storage/cache/health-check-' . time();
    if (file_put_contents($cacheFile, 'test') && unlink($cacheFile)) {
        return ['status' => 'ok', 'message' => 'Cache working'];
    }
    return ['status' => 'error', 'message' => 'Cache not working'];
}

header('Content-Type: application/json');
echo json_encode($checks);
```

### 2. Monitoring Script
```bash
#!/bin/bash
# monitor.sh

LOG_FILE="/var/log/simplebiz-monitor.log"
APP_PATH="/path/to/simplebiz-mvc-framework-v2"

# Check disk space
DISK_USAGE=$(df -h $APP_PATH | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "$(date): WARNING - Disk usage is ${DISK_USAGE}%" >> $LOG_FILE
fi

# Check log file sizes
find $APP_PATH/storage/logs -name "*.log" -size +100M -exec echo "$(date): WARNING - Large log file: {}" \; >> $LOG_FILE

# Check PHP-FPM processes
PHP_PROCS=$(pgrep -c php-fpm)
if [ $PHP_PROCS -lt 2 ]; then
    echo "$(date): ERROR - PHP-FPM processes low: $PHP_PROCS" >> $LOG_FILE
    systemctl restart php8.2-fpm
fi
```

### 3. Backup Script
```bash
#!/bin/bash
# backup.sh

BACKUP_DIR="/backups/simplebiz-mvc"
APP_PATH="/path/to/simplebiz-mvc-framework-v2"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE > $BACKUP_DIR/database_$DATE.sql

# Backup application files (excluding vendor, cache, logs)
tar -czf $BACKUP_DIR/app_$DATE.tar.gz \
    --exclude='vendor' \
    --exclude='storage/cache' \
    --exclude='storage/logs' \
    --exclude='node_modules' \
    $APP_PATH

# Keep only last 7 days of backups
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete

echo "Backup completed: $DATE"
```

---

## Troubleshooting

### ปัญหาที่พบบ่อย

#### 1. "500 Internal Server Error"
```bash
# ตรวจสอบ error logs
tail -f storage/logs/app.log
tail -f /var/log/nginx/error.log
tail -f /var/log/php8.2-fpm.log

# ตรวจสอบสิทธิ์ไฟล์
ls -la storage/
```

#### 2. "Database Connection Failed"
```bash
# ทดสอบการเชื่อมต่อฐานข้อมูล
mysql -h $DB_HOST -u $DB_USERNAME -p $DB_DATABASE

# ตรวจสอบ .env file
cat .env | grep DB_
```

#### 3. "Permission Denied"
```bash
# แก้ไขสิทธิ์
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/

# ตรวจสอบ SELinux (CentOS/RHEL)
sestatus
setsebool -P httpd_can_network_connect 1
```

#### 4. "Class not found"
```bash
# Regenerate autoloader
composer dump-autoload

# ตรวจสอบ namespace ใน composer.json
composer validate
```

#### 5. เว็บไซต์ช้า
```bash
# ตรวจสอบ PHP processes
ps aux | grep php-fpm

# ตรวจสอบ MySQL processes
mysqladmin processlist

# ตรวจสอบ disk I/O
iostat -x 1

# ตรวจสอบ memory usage
free -h
top
```

### คำสั่งที่มีประโยชน์

```bash
# ดูสถานะ services
systemctl status nginx
systemctl status php8.2-fpm
systemctl status mysql

# ดู logs real-time
tail -f storage/logs/app.log
tail -f /var/log/nginx/access.log

# ทดสอบ PHP syntax
php -l public/index.php

# ทดสอบ Nginx config
nginx -t

# Reload services (ไม่ downtime)
systemctl reload nginx
systemctl reload php8.2-fpm
```

---

## Security Checklist

### ก่อน Deploy
- [ ] เปลี่ยน APP_KEY ให้เป็นค่าใหม่
- [ ] ตั้ง APP_DEBUG=false
- [ ] ตั้ง APP_ENV=production  
- [ ] ใช้ HTTPS เท่านั้น
- [ ] ซ่อนไฟล์ .env จาก web access
- [ ] อัพเดท PHP และ extensions เป็นเวอร์ชันล่าสุด
- [ ] ตั้งรหัสผ่านฐานข้อมูลที่แข็งแกร่ง
- [ ] เปิดใช้ firewall และปิด ports ที่ไม่จำเป็น

### หลัง Deploy
- [ ] ทดสอบ backup และ restore
- [ ] ตั้ก monitoring และ alerts
- [ ] ทดสอบ SSL certificate
- [ ] ตรวจสอบ security headers
- [ ] Test health check endpoints
- [ ] ทดสอบ performance

---

**หมายเหตุ**: คู่มือนี้เป็นแนวทางทั่วไป ควรปรับแต่งให้เข้ากับสภาพแวดล้อมของคุณ และทดสอบบน staging environment ก่อน deploy ขึ้น production เสมอ