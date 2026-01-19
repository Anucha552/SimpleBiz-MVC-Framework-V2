# คู่มือการ Deploy Production

คู่มือฉบับสมบูรณ์สำหรับการ deploy SimpleBiz MVC Framework V2 ไปยัง production

## 📋 สารบัญ

1. [ข้อกำหนดระบบ](#ข้อกำหนดระบบ)
2. [การเตรียมโปรเจค](#การเตรียมโปรเจค)
3. [การตั้งค่า Environment](#การตั้งค่า-environment)
4. [การติดตั้ง Dependencies](#การติดตั้ง-dependencies)
5. [การตั้งค่า Web Server](#การตั้งค่า-web-server)
6. [การตั้งค่าสิทธิ์ไฟล์](#การตั้งค่าสิทธิ์ไฟล์)
7. [การเตรียมฐานข้อมูล](#การเตรียมฐานข้อมูล)
8. [Security Checklist](#security-checklist)
9. [Post-Deployment Testing](#post-deployment-testing)
10. [การ Rollback](#การ-rollback)

---

## ข้อกำหนดระบบ

### Server Requirements

**PHP:**
- PHP 8.0 หรือสูงกว่า
- Extensions: PDO, PDO_MySQL, OpenSSL, MBString, JSON, Fileinfo

**Database:**
- MySQL 5.7+ หรือ MariaDB 10.3+
- InnoDB storage engine

**Web Server:**
- Apache 2.4+ (พร้อม mod_rewrite)
- หรือ Nginx 1.18+

**ทั่วไป:**
- Composer 2.0+
- SSL Certificate (Let's Encrypt แนะนำ)
- 512MB RAM ขั้นต่ำ, 1GB+ แนะนำ

### ตรวจสอบ PHP Extensions

```bash
php -m | grep -E 'PDO|pdo_mysql|openssl|mbstring|json|fileinfo'
```

---

## การเตรียมโปรเจค

### 1. Clone/Upload โปรเจค

**Option A: ใช้ Git (แนะนำ)**
```bash
cd /var/www
git clone https://github.com/your-username/SimpleBiz-MVC-Framework-V2.git your-domain.com
cd your-domain.com
git checkout main  # หรือ production branch
```

**Option B: Upload ผ่าน FTP/SFTP**
- Zip โปรเจคทั้งหมด
- Upload ไปยังโฟลเดอร์ web root
- Unzip บนเซิร์ฟเวอร์

### 2. ตรวจสอบโครงสร้างไฟล์

```bash
ls -la
# ต้องเห็น: app/, config/, database/, public/, vendor/, .env.example
```

---

## การตั้งค่า Environment

### 1. สร้างไฟล์ .env

```bash
cp .env.example .env
nano .env  # หรือใช้ editor อื่น
```

### 2. ปรับค่า Production

```env
# Application
APP_NAME="SimpleBiz"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=simplebiz_prod
DB_USERNAME=simplebiz_user
DB_PASSWORD=SecurePassword123!

# Mail (SMTP)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="SimpleBiz"

# API Security
API_KEY=prod_$(openssl rand -base64 32)

# Session
SESSION_LIFETIME=120
SESSION_SECURE=true
SESSION_HTTP_ONLY=true
```

### 3. สร้าง API Key แบบปลอดภัย

```bash
# Linux/Mac
openssl rand -base64 32

# Windows PowerShell
[Convert]::ToBase64String((1..32 | ForEach-Object { Get-Random -Minimum 0 -Maximum 256 }))
```

**⚠️ สำคัญ:** อย่า commit ไฟล์ .env ขึ้น Git

---

## การติดตั้ง Dependencies

### 1. ติดตั้ง Composer Packages

```bash
# Production (ไม่รวม dev dependencies)
composer install --no-dev --optimize-autoloader

# ถ้าต้องการ dev dependencies (สำหรับ staging)
composer install --optimize-autoloader
```

### 2. Optimize Autoloader

```bash
composer dump-autoload --optimize --no-dev
```

---

## การตั้งค่า Web Server

### Apache Configuration

**1. Virtual Host Configuration**

สร้างไฟล์: `/etc/apache2/sites-available/your-domain.conf`

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    
    # Redirect to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}$1 [R=301,L]
</VirtualHost>

<VirtualHost *:443>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    
    DocumentRoot /var/www/your-domain.com/public
    
    <Directory /var/www/your-domain.com/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/your-domain-error.log
    CustomLog ${APACHE_LOG_DIR}/your-domain-access.log combined
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/your-domain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/your-domain.com/privkey.pem
    
    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</VirtualHost>
```

**2. เปิดใช้งาน Modules และ Site**

```bash
# เปิดใช้งาน modules ที่จำเป็น
sudo a2enmod rewrite ssl headers

# เปิดใช้งาน site
sudo a2ensite your-domain.conf

# ทดสอบ configuration
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2
```

**3. ตรวจสอบ .htaccess**

ไฟล์ `public/.htaccess` ควรมี:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

### Nginx Configuration

**1. Server Block Configuration**

สร้างไฟล์: `/etc/nginx/sites-available/your-domain`

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    
    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    
    server_name your-domain.com www.your-domain.com;
    root /var/www/your-domain.com/public;
    index index.php index.html;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # Logging
    access_log /var/log/nginx/your-domain-access.log;
    error_log /var/log/nginx/your-domain-error.log;
    
    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP Processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }
    
    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

**2. เปิดใช้งาน Site**

```bash
# Symlink
sudo ln -s /etc/nginx/sites-available/your-domain /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

---

## การตั้งค่าสิทธิ์ไฟล์

### 1. ตั้งค่า Ownership

```bash
# ให้ web server เป็นเจ้าของ
sudo chown -R www-data:www-data /var/www/your-domain.com

# ถ้าใช้ nginx อาจเป็น
sudo chown -R nginx:nginx /var/www/your-domain.com
```

### 2. ตั้งค่า Permissions

```bash
cd /var/www/your-domain.com

# Directories: 755
find . -type d -exec chmod 755 {} \;

# Files: 644
find . -type f -exec chmod 644 {} \;

# Storage ต้องเขียนได้
chmod -R 775 storage
chmod -R 775 storage/logs
chmod -R 775 storage/cache

# Console executable
chmod +x console
```

### 3. ตรวจสอบสิทธิ์

```bash
ls -la storage/
ls -la storage/logs/
```

ต้องเห็น `drwxrwxr-x` สำหรับ directories ที่ต้องเขียน

---

## การเตรียมฐานข้อมูล

### 1. สร้าง Database และ User

```sql
-- Login MySQL as root
mysql -u root -p

-- สร้าง database
CREATE DATABASE simplebiz_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- สร้าง user
CREATE USER 'simplebiz_user'@'localhost' IDENTIFIED BY 'SecurePassword123!';

-- ให้สิทธิ์
GRANT ALL PRIVILEGES ON simplebiz_prod.* TO 'simplebiz_user'@'localhost';

-- ถ้าเชื่อมต่อจาก remote
GRANT ALL PRIVILEGES ON simplebiz_prod.* TO 'simplebiz_user'@'%';

FLUSH PRIVILEGES;
EXIT;
```

### 2. รัน Migrations

```bash
php console migrate
```

Output ที่ควรเห็น:
```
Running migrations...
✓ 001_create_users_table.sql
✓ 002_create_products_table.sql
...
Migrations completed
```

### 3. รัน Seeders (ถ้าต้องการข้อมูลตัวอย่าง)

```bash
# Staging: รัน seeders
php console seed

# Production: อย่ารัน seeders
# สร้าง admin user ด้วยมือแทน
```

### 4. สร้าง Admin User แบบ Manual

```sql
mysql -u simplebiz_user -p simplebiz_prod

-- สร้าง admin user
INSERT INTO users (username, email, password, role, status, created_at)
VALUES (
    'admin',
    'admin@your-domain.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- password: "password"
    'admin',
    'active',
    NOW()
);
```

**⚠️ เปลี่ยนรหัสผ่านหลัง login ครั้งแรก!**

---

## Security Checklist

### ก่อน Go Live

- [ ] `APP_DEBUG=false` ใน .env
- [ ] `APP_ENV=production` ใน .env
- [ ] API Key ถูกสร้างใหม่และปลอดภัย
- [ ] Database password แข็งแรง (12+ chars, mixed case, numbers, symbols)
- [ ] HTTPS/SSL ถูกเปิดใช้งาน
- [ ] Security headers ถูกตั้งค่า
- [ ] ไฟล์ `.env` ไม่ถูก expose
- [ ] Directory listing ปิดไว้
- [ ] Error logs ไม่แสดงในหน้าเว็บ
- [ ] Session cookies เป็น HttpOnly และ Secure
- [ ] CSRF protection เปิดใช้งาน
- [ ] Rate limiting เปิดใช้งาน
- [ ] SQL Injection protection (PDO prepared statements)
- [ ] XSS protection (htmlspecialchars)
- [ ] File upload validation เข้มงวด
- [ ] Backup strategy พร้อม

### ตรวจสอบความปลอดภัย

```bash
# ตรวจสอบ .env ไม่ถูก expose
curl https://your-domain.com/.env
# ต้องได้ 404 หรือ 403

# ตรวจสอบ directory listing
curl https://your-domain.com/storage/
# ต้องได้ 403 Forbidden

# ตรวจสอบ headers
curl -I https://your-domain.com
# ต้องเห็น X-Frame-Options, X-Content-Type-Options, etc.
```

---

## Post-Deployment Testing

### 1. ทดสอบ Basic Functionality

```bash
# Homepage
curl -I https://your-domain.com

# API endpoint
curl https://your-domain.com/api/v1/products

# Static assets
curl -I https://your-domain.com/assets/css/style.css
```

### 2. ทดสอบ Authentication

- เข้าสู่ระบบด้วย admin
- ทดสอบ logout
- ทดสอบ forgot password
- ทดสอบ registration (ถ้าเปิด)

### 3. ทดสอบ E-commerce Functions

- เพิ่มสินค้าลงตะกร้า
- อัพเดทจำนวนสินค้า
- ลบสินค้าออกจากตะกร้า
- สร้างคำสั่งซื้อ
- ตรวจสอบการลดสต็อก

### 4. ทดสอบ Performance

```bash
# Load testing ด้วย Apache Bench
ab -n 1000 -c 10 https://your-domain.com/

# หรือใช้ wrk
wrk -t4 -c100 -d30s https://your-domain.com/
```

### 5. ตรวจสอบ Logs

```bash
# Application logs
tail -f storage/logs/app.log

# Web server logs
tail -f /var/log/apache2/your-domain-error.log
# หรือ
tail -f /var/log/nginx/your-domain-error.log
```

---

## การ Rollback

### แผน Rollback

**ก่อน Deploy ทุกครั้ง:**

1. **Backup Database**
   ```bash
   mysqldump -u simplebiz_user -p simplebiz_prod > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Tag Git Version**
   ```bash
   git tag -a v1.2.3 -m "Production release 1.2.3"
   git push origin v1.2.3
   ```

3. **เก็บ Release ก่อนหน้า**
   ```bash
   cp -r /var/www/your-domain.com /var/www/backups/your-domain.com-$(date +%Y%m%d)
   ```

### ขั้นตอน Rollback

**1. Rollback Code**
```bash
# ใช้ git
cd /var/www/your-domain.com
git checkout v1.2.2  # version ก่อนหน้า
composer install --no-dev --optimize-autoloader

# หรือ restore จาก backup
cd /var/www
mv your-domain.com your-domain.com-failed
cp -r backups/your-domain.com-20260118 your-domain.com
```

**2. Rollback Database**
```bash
mysql -u simplebiz_user -p simplebiz_prod < backup_20260118_100000.sql
```

**3. Clear Cache**
```bash
php console cache:clear
```

**4. Restart Services**
```bash
sudo systemctl restart apache2
# หรือ
sudo systemctl restart nginx
sudo systemctl restart php8.0-fpm
```

**5. Verify**
```bash
curl -I https://your-domain.com
```

---

## Maintenance Tips

### การ Backup อัตโนมัติ

**Cron Job สำหรับ Daily Backup**

```bash
crontab -e
```

เพิ่ม:
```cron
# Backup database ทุกวัน เวลา 2:00 AM
0 2 * * * mysqldump -u simplebiz_user -p'password' simplebiz_prod > /backup/db/simplebiz_$(date +\%Y\%m\%d).sql

# ลบ backup เก่าที่เก็บไว้เกิน 30 วัน
0 3 * * * find /backup/db -name "simplebiz_*.sql" -mtime +30 -delete
```

### Log Rotation

**ตั้งค่า logrotate**

สร้างไฟล์: `/etc/logrotate.d/simplebiz`

```
/var/www/your-domain.com/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0664 www-data www-data
    sharedscripts
}
```

### Monitoring

**Simple Health Check Script**

```bash
#!/bin/bash
# health-check.sh

URL="https://your-domain.com"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" $URL)

if [ $STATUS -ne 200 ]; then
    echo "Site is down! Status: $STATUS" | mail -s "Alert: Site Down" admin@your-domain.com
fi
```

เพิ่มใน cron:
```cron
*/5 * * * * /path/to/health-check.sh
```

---

## Troubleshooting

### ปัญหาที่พบบ่อย

**1. 500 Internal Server Error**
- ตรวจสอบ error logs
- ตรวจสอบ file permissions
- ตรวจสอบ .env configuration
- ตรวจสอบ composer autoload

**2. Database Connection Failed**
- ตรวจสอบ credentials ใน .env
- ตรวจสอบ MySQL service รันอยู่
- ตรวจสอบ firewall rules

**3. 404 on All Pages**
- ตรวจสอบ mod_rewrite enabled (Apache)
- ตรวจสอบ .htaccess file
- ตรวจสอบ nginx configuration

**4. Session Issues**
- ตรวจสอบ session.save_path
- ตรวจสอบ cookie domain settings
- Clear browser cookies

---

## 📚 เอกสารที่เกี่ยวข้อง

- [Security Hardening Guide](SECURITY_HARDENING.md)
- [Environment Configuration](ENVIRONMENTS.md)
- [Migration Guide](MIGRATION_GUIDE.md)
- [CLI Commands](CLI_GUIDE.md)
