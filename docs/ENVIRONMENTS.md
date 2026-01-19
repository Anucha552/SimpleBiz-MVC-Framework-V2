# Environment Configuration Guide

คู่มือการตั้งค่า Environment Variables สำหรับ SimpleBiz MVC Framework V2

## 📋 สารบัญ

- [ภาพรวม](#ภาพรวม)
- [Application Settings](#application-settings)
- [Database Configuration](#database-configuration)
- [Mail Configuration](#mail-configuration)
- [Security Settings](#security-settings)
- [Session Configuration](#session-configuration)
- [Cache Configuration](#cache-configuration)
- [Environment Matrix](#environment-matrix)

---

## ภาพรวม

ไฟล์ `.env` ใช้สำหรับเก็บค่า configuration ที่แตกต่างกันในแต่ละ environment (development, staging, production)

### การสร้างไฟล์ .env

```bash
# คัดลอกจาก template
cp .env.example .env

# แก้ไขด้วย text editor
nano .env
```

**⚠️ สำคัญ:**
- **อย่า** commit ไฟล์ `.env` ขึ้น Git
- ไฟล์นี้มี credentials ที่เป็นความลับ
- แต่ละ environment ต้องมี `.env` แยกกัน

---

## Application Settings

### APP_NAME
ชื่อแอปพลิเคชัน - แสดงใน email templates และ UI

```env
APP_NAME="SimpleBiz"
```

**ตัวอย่าง:**
- Development: `APP_NAME="SimpleBiz Dev"`
- Staging: `APP_NAME="SimpleBiz Staging"`
- Production: `APP_NAME="SimpleBiz"`

---

### APP_ENV
ระบุ environment ที่กำลังรัน

```env
APP_ENV=production
```

**ค่าที่เป็นไปได้:**
- `development` - สำหรับพัฒนาในเครื่องตัวเอง
- `staging` - สำหรับทดสอบก่อน production
- `production` - สำหรับใช้งานจริง
- `testing` - สำหรับรัน automated tests

**ผลกระทบ:**
- เปลี่ยน error reporting level
- เปลี่ยน cache behavior
- เปลี่ยน logging verbosity

---

### APP_DEBUG
เปิด/ปิด debug mode

```env
APP_DEBUG=false
```

**ค่าที่เป็นไปได้:**
- `true` - แสดง error messages และ stack traces แบบเต็ม
- `false` - แสดงเฉพาะ generic error pages

**⚠️ Production:**
```env
APP_DEBUG=false  # ต้องเป็น false เสมอ!
```

**Development:**
```env
APP_DEBUG=true   # ช่วย debug ได้ง่าย
```

**เมื่อ debug=true:**
- แสดง error messages ละเอียด
- แสดง stack traces
- แสดง database queries
- **เปิดเผยโครงสร้างโค้ด - อันตรายใน production!**

---

### APP_URL
URL หลักของแอปพลิเคชัน

```env
APP_URL=https://your-domain.com
```

**ตัวอย่าง:**
```env
# Development
APP_URL=http://localhost:8000

# Staging
APP_URL=https://staging.your-domain.com

# Production
APP_URL=https://your-domain.com
```

**ใช้สำหรับ:**
- สร้าง absolute URLs ใน emails
- CORS configuration
- Redirect URLs
- Asset URLs

---

## Database Configuration

### DB_HOST
ที่อยู่ของ database server

```env
DB_HOST=localhost
```

**ตัวอย่าง:**
```env
# Local MySQL
DB_HOST=localhost

# Remote server
DB_HOST=192.168.1.100

# Cloud database
DB_HOST=mysql.example.com
```

---

### DB_PORT
Port ของ MySQL/MariaDB

```env
DB_PORT=3306
```

**ค่า default:**
- MySQL/MariaDB: `3306`
- PostgreSQL: `5432`

---

### DB_DATABASE
ชื่อ database

```env
DB_DATABASE=simplebiz_prod
```

**แนะนำ naming:**
```env
# Development
DB_DATABASE=simplebiz_dev

# Staging
DB_DATABASE=simplebiz_staging

# Production
DB_DATABASE=simplebiz_prod
```

---

### DB_USERNAME & DB_PASSWORD
Credentials สำหรับเข้าถึง database

```env
DB_USERNAME=simplebiz_user
DB_PASSWORD=SecurePassword123!
```

**Best Practices:**
- ใช้ dedicated user สำหรับแต่ละ database
- อย่าใช้ `root` user
- Password ต้องมีความซับซ้อน:
  - 12+ ตัวอักษร
  - ตัวพิมพ์ใหญ่-เล็ก
  - ตัวเลข
  - สัญลักษณ์พิเศษ

**สร้าง secure password:**
```bash
# Linux/Mac
openssl rand -base64 20

# Windows PowerShell
-join ((48..57) + (65..90) + (97..122) | Get-Random -Count 20 | % {[char]$_})
```

---

## Mail Configuration

สำหรับส่ง emails (registration, password reset, order confirmations)

### MAIL_HOST
SMTP server address

```env
MAIL_HOST=smtp.gmail.com
```

**ตัวอย่าง SMTP Providers:**
```env
# Gmail
MAIL_HOST=smtp.gmail.com

# SendGrid
MAIL_HOST=smtp.sendgrid.net

# Mailgun
MAIL_HOST=smtp.mailgun.org

# AWS SES
MAIL_HOST=email-smtp.us-east-1.amazonaws.com
```

---

### MAIL_PORT
SMTP port number

```env
MAIL_PORT=587
```

**Ports ที่ใช้บ่อย:**
- `25` - SMTP (ไม่มี encryption, หลีกเลี่ยง)
- `465` - SMTPS (SSL encryption)
- `587` - SMTP with STARTTLS (แนะนำ)
- `2525` - Alternative port (บางที่อาจใช้)

---

### MAIL_USERNAME & MAIL_PASSWORD
Credentials สำหรับ SMTP

```env
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
```

**สำหรับ Gmail:**
1. เปิด 2-Factor Authentication
2. สร้าง "App Password" จาก Google Account Settings
3. ใช้ App Password แทน password จริง

---

### MAIL_ENCRYPTION
ประเภทของ encryption

```env
MAIL_ENCRYPTION=tls
```

**ค่าที่เป็นไปได้:**
- `tls` - STARTTLS (port 587) - แนะนำ
- `ssl` - SSL/TLS (port 465)
- `null` - ไม่มี encryption (หลีกเลี่ยง)

---

### MAIL_FROM_ADDRESS & MAIL_FROM_NAME
ข้อมูลผู้ส่ง default

```env
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="SimpleBiz"
```

**ตัวอย่าง:**
```env
MAIL_FROM_ADDRESS=noreply@simplebiz.com
MAIL_FROM_NAME="SimpleBiz Shop"
```

**Best Practices:**
- ใช้ `noreply@` สำหรับ automated emails
- ใช้ `support@` ถ้าอนุญาตให้ตอบกลับได้
- MAIL_FROM_NAME ควรเป็นชื่อที่จดจำง่าย

---

## Security Settings

### API_KEY
Secret key สำหรับป้องกัน sensitive API endpoints

```env
API_KEY=prod_abcdef1234567890
```

**สร้าง API Key:**
```bash
# Linux/Mac
openssl rand -base64 32

# Windows PowerShell
[Convert]::ToBase64String((1..32 | % { Get-Random -Minimum 0 -Maximum 256 }))
```

**Best Practices:**
- ใช้ prefix ระบุ environment: `dev_`, `staging_`, `prod_`
- เปลี่ยน key เมื่อ deploy production
- อย่าใช้ key เดียวกันทุก environment
- เก็บ key ไว้ที่ปลอดภัย (password manager)

**การใช้งาน:**
```bash
# ส่งผ่าน header
curl -H "X-API-Key: prod_abcdef1234567890" https://api.example.com/orders

# ส่งผ่าน query parameter
curl https://api.example.com/orders?api_key=prod_abcdef1234567890
```

---

## Session Configuration

### SESSION_LIFETIME
อายุของ session (นาที)

```env
SESSION_LIFETIME=120
```

**แนะนำ:**
```env
# Development (ยาวเพื่อสะดวก)
SESSION_LIFETIME=1440  # 24 ชั่วโมง

# Production (สั้นเพื่อความปลอดภัย)
SESSION_LIFETIME=120   # 2 ชั่วโมง
```

---

### SESSION_SECURE
บังคับใช้ session cookies ผ่าน HTTPS เท่านั้น

```env
SESSION_SECURE=true
```

**ค่าที่เป็นไปได้:**
- `true` - ส่ง cookies ผ่าน HTTPS เท่านั้น
- `false` - อนุญาต HTTP (development only)

**Production:**
```env
SESSION_SECURE=true  # ต้องเป็น true!
```

---

### SESSION_HTTP_ONLY
ป้องกัน JavaScript เข้าถึง session cookies

```env
SESSION_HTTP_ONLY=true
```

**ค่าที่เป็นไปได้:**
- `true` - ป้องกัน XSS attacks (แนะนำ)
- `false` - อนุญาตให้ JS เข้าถึง (อันตราย)

**Production:**
```env
SESSION_HTTP_ONLY=true  # ต้องเป็น true!
```

---

## Cache Configuration

### CACHE_DRIVER
ระบุ cache driver ที่ใช้

```env
CACHE_DRIVER=file
```

**ค่าที่เป็นไปได้:**
- `file` - เก็บใน filesystem (default)
- `redis` - ใช้ Redis server
- `memcached` - ใช้ Memcached
- `array` - RAM เท่านั้น (testing)

**สำหรับ production ที่มี traffic สูง:**
```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

---

### CACHE_TTL
Time-to-live สำหรับ cache (วินาที)

```env
CACHE_TTL=3600
```

**ตัวอย่าง:**
```env
CACHE_TTL=300     # 5 นาที
CACHE_TTL=3600    # 1 ชั่วโมง
CACHE_TTL=86400   # 1 วัน
```

---

## Environment Matrix

### Development (.env)

```env
# Application
APP_NAME="SimpleBiz Dev"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=simplebiz_dev
DB_USERNAME=root
DB_PASSWORD=root

# Mail (Mailtrap for testing)
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=dev@simplebiz.local
MAIL_FROM_NAME="SimpleBiz Dev"

# Security
API_KEY=dev_test_key_1234567890

# Session
SESSION_LIFETIME=1440
SESSION_SECURE=false
SESSION_HTTP_ONLY=true

# Cache
CACHE_DRIVER=file
CACHE_TTL=3600
```

---

### Staging (.env)

```env
# Application
APP_NAME="SimpleBiz Staging"
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://staging.simplebiz.com

# Database
DB_HOST=staging-db.internal
DB_PORT=3306
DB_DATABASE=simplebiz_staging
DB_USERNAME=simplebiz_staging_user
DB_PASSWORD=ComplexStagingPassword123!

# Mail (Real SMTP)
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=staging@simplebiz.com
MAIL_FROM_NAME="SimpleBiz Staging"

# Security
API_KEY=staging_random_key_abcdef1234567890

# Session
SESSION_LIFETIME=120
SESSION_SECURE=true
SESSION_HTTP_ONLY=true

# Cache
CACHE_DRIVER=redis
CACHE_TTL=1800
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

---

### Production (.env)

```env
# Application
APP_NAME="SimpleBiz"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://simplebiz.com

# Database
DB_HOST=prod-db.internal
DB_PORT=3306
DB_DATABASE=simplebiz_prod
DB_USERNAME=simplebiz_prod_user
DB_PASSWORD=VerySecureProductionPassword456!

# Mail (Production SMTP)
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.your-production-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@simplebiz.com
MAIL_FROM_NAME="SimpleBiz"

# Security
API_KEY=prod_ultra_secure_key_xyz789abc456def

# Session
SESSION_LIFETIME=120
SESSION_SECURE=true
SESSION_HTTP_ONLY=true

# Cache
CACHE_DRIVER=redis
CACHE_TTL=3600
REDIS_HOST=redis-prod.internal
REDIS_PORT=6379
REDIS_PASSWORD=RedisSecurePassword789!
```

---

## Validation Checklist

### ก่อน Deploy Production

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` ชี้ไปที่ domain จริง
- [ ] Database credentials ถูกต้อง
- [ ] Database password ซับซ้อนเพียงพอ
- [ ] Mail settings ถูกต้องและทดสอบแล้ว
- [ ] API_KEY ถูกสร้างใหม่และปลอดภัย
- [ ] `SESSION_SECURE=true`
- [ ] `SESSION_HTTP_ONLY=true`
- [ ] SESSION_LIFETIME เหมาะสม
- [ ] ไฟล์ `.env` ไม่ถูก commit ใน Git

---

## Troubleshooting

### ปัญหาที่พบบ่อย

**1. Database Connection Failed**
```
เช็ค: DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
ทดสอบ: mysql -h DB_HOST -u DB_USERNAME -p
```

**2. Mail Not Sending**
```
เช็ค: MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD
ทดสอบ: telnet MAIL_HOST MAIL_PORT
```

**3. Session Not Persisting**
```
เช็ค: SESSION_SECURE (ต้อง false ถ้าใช้ HTTP)
เช็ค: storage/sessions directory permissions
```

**4. API Key Invalid**
```
เช็ค: API_KEY ใน .env ตรงกับที่ใช้ใน request
เช็ค: ไม่มีช่องว่างหรือ newline ใน .env
```

---

## เอกสารที่เกี่ยวข้อง

- [Deployment Guide](DEPLOYMENT_GUIDE.md) - ขั้นตอน deploy ครบถ้วน
- [Security Hardening](SECURITY_HARDENING.md) - การรักษาความปลอดภัย
- [CLI Guide](CLI_GUIDE.md) - คำสั่ง console ต่างๆ
