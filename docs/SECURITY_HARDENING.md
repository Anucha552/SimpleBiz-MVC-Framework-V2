# Security Hardening Checklist (Production)

คู่มือที่ครบถ้วนสำหรับการเสริมความปลอดภัยของ SimpleBiz MVC Framework ใน production environment

---

## 📋 สารบัญ

1. [Application Security](#1-application-security)
2. [Server Configuration](#2-server-configuration)
3. [Database Security](#3-database-security)
4. [Authentication & Authorization](#4-authentication--authorization)
5. [Input Validation & Sanitization](#5-input-validation--sanitization)
6. [File Upload Security](#6-file-upload-security)
7. [Session Security](#7-session-security)
8. [API Security](#8-api-security)
9. [Logging & Monitoring](#9-logging--monitoring)
10. [Backup & Recovery](#10-backup--recovery)

---

## 1. Application Security

### ✅ Environment Configuration

**ต้องทำ:**
- [ ] ตั้ง `APP_ENV=production`
- [ ] ตั้ง `APP_DEBUG=false` (ห้ามแสดง error details ให้ user เห็น)
- [ ] สร้าง `APP_KEY` แบบสุ่มและปลอดภัย (32+ characters)
- [ ] เปลี่ยน `API_KEY` จากค่า default
- [ ] ตรวจสอบว่า `.env` ไม่อยู่ใน git repository

**.env.production:**
```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=random-32-character-key-here
API_KEY=strong-api-key-with-64-chars-minimum
```

### ✅ Hide Sensitive Information

```bash
# ตรวจสอบว่าไฟล์เหล่านี้ถูก block
curl https://yourdomain.com/.env       # ต้องได้ 403/404
curl https://yourdomain.com/composer.json  # ต้องได้ 403/404
curl https://yourdomain.com/.git/config    # ต้องได้ 403/404
```

**ใน .htaccess:**
```apache
<FilesMatch "^\.|(\.env|\.git|composer\.(json|lock)|package\.json)$">
    Require all denied
</FilesMatch>
```

### ✅ Remove Development Files

```bash
# ลบไฟล์/โฟลเดอร์พัฒนา
rm -rf .git/
rm -rf tests/
rm phpunit.xml
rm -rf node_modules/
```

### ✅ Error Handling

**การตั้งค่า:**
- ไม่แสดง stack traces ให้ users
- Log errors ไปที่ `storage/logs/`
- แสดง generic error page

**ใน php.ini:**
```ini
display_errors = Off
log_errors = On
error_log = /var/www/your-project/storage/logs/php-errors.log
```

---

## 2. Server Configuration

### ✅ HTTP Security Headers

**Apache (.htaccess):**
```apache
# X-Frame-Options (ป้องกัน Clickjacking)
Header always set X-Frame-Options "SAMEORIGIN"

# X-Content-Type-Options (ป้องกัน MIME sniffing)
Header always set X-Content-Type-Options "nosniff"

# X-XSS-Protection
Header always set X-XSS-Protection "1; mode=block"

# Strict-Transport-Security (HSTS)
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"

# Content-Security-Policy
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';"

# Referrer-Policy
Header always set Referrer-Policy "strict-origin-when-cross-origin"

# Permissions-Policy
Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
```

**Nginx:**
```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
```

### ✅ SSL/TLS Configuration

**ต้องทำ:**
- [ ] ติดตั้ง SSL certificate (Let's Encrypt แนะนำ)
- [ ] Force HTTPS redirect
- [ ] ใช้ TLS 1.2+ เท่านั้น (ปิด TLS 1.0, 1.1)
- [ ] ใช้ strong ciphers

**Apache SSL Config:**
```apache
SSLProtocol -all +TLSv1.2 +TLSv1.3
SSLCipherSuite HIGH:!aNULL:!MD5:!3DES
SSLHonorCipherOrder on
```

**ทดสอบ SSL:**
- https://www.ssllabs.com/ssltest/

### ✅ Disable Directory Listing

```apache
Options -Indexes
```

### ✅ Hide Server Version

**Apache:**
```apache
ServerTokens Prod
ServerSignature Off
```

**Nginx:**
```nginx
server_tokens off;
```

### ✅ File Permissions

```bash
# ตั้งเจ้าของ
sudo chown -R www-data:www-data /var/www/your-project

# Directories: 755
sudo find /var/www/your-project -type d -exec chmod 755 {} \;

# Files: 644
sudo find /var/www/your-project -type f -exec chmod 644 {} \;

# Storage (writable): 775
sudo chmod -R 775 /var/www/your-project/storage
sudo chmod -R 775 /var/www/your-project/public/uploads

# .env (อ่านได้เฉพาะเจ้าของ): 600
sudo chmod 600 /var/www/your-project/.env
```

---

## 3. Database Security

### ✅ Database User Permissions

```sql
-- สร้าง user เฉพาะสำหรับแอป (ไม่ใช้ root)
CREATE USER 'app_user'@'localhost' IDENTIFIED BY 'StrongPassword123!@#';

-- ให้สิทธิ์เฉพาะที่จำเป็น (ไม่ต้องใช้ ALL)
GRANT SELECT, INSERT, UPDATE, DELETE ON your_db.* TO 'app_user'@'localhost';

-- ไม่ให้สิทธิ์ CREATE, DROP, ALTER ใน production
FLUSH PRIVILEGES;
```

### ✅ Prevent SQL Injection

**ใช้ Prepared Statements เสมอ:**
```php
// ✅ ถูกต้อง - ใช้ prepared statements
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

// ❌ ผิด - vulnerable to SQL injection
$query = "SELECT * FROM users WHERE email = '$email'";
```

### ✅ Database Connection Security

**.env:**
```env
DB_HOST=127.0.0.1  # ใช้ localhost/127.0.0.1 ไม่เปิด remote access
DB_PORT=3306
```

**MySQL Config:**
```ini
# จำกัดการเชื่อมต่อเฉพาะ localhost
bind-address = 127.0.0.1

# ปิด remote root login
skip-networking
```

### ✅ Database Backup Encryption

```bash
# Backup และ encrypt
mysqldump -u user -p database | gzip | openssl enc -aes-256-cbc -salt -out backup.sql.gz.enc

# Restore
openssl enc -d -aes-256-cbc -in backup.sql.gz.enc | gunzip | mysql -u user -p database
```

---

## 4. Authentication & Authorization

### ✅ Password Security

**ต้องทำ:**
- [ ] ใช้ `password_hash()` และ `password_verify()` เสมอ
- [ ] ไม่เก็บ plain text passwords
- [ ] กำหนด password policy (ความยาว, ความซับซ้อน)
- [ ] Implement password reset securely

```php
// ✅ ถูกต้อง
$hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Verify
if (password_verify($inputPassword, $hashedPassword)) {
    // Password correct
}
```

### ✅ Session Management

**.env:**
```env
SESSION_LIFETIME=7200  # 2 hours
SESSION_SECURE=true    # HTTPS only
SESSION_HTTP_ONLY=true # Prevent XSS
```

**ใน Session class:**
```php
session_set_cookie_params([
    'lifetime' => 7200,
    'path' => '/',
    'domain' => 'yourdomain.com',
    'secure' => true,      // HTTPS only
    'httponly' => true,    // No JavaScript access
    'samesite' => 'Strict' // CSRF protection
]);
```

### ✅ Implement CSRF Protection

```php
// ใช้ CsrfMiddleware
$router->post('/orders/create', 'OrderController@store', [
    AuthMiddleware::class,
    CsrfMiddleware::class
]);
```

**ใน form:**
```php
<form method="POST" action="/orders/create">
    <?php csrf_field(); ?>
    <!-- form fields -->
</form>
```

### ✅ Rate Limiting

```php
// ใช้ RateLimitMiddleware ป้องกัน brute force
$router->post('/login', 'AuthController@login', [
    RateLimitMiddleware::class
]);
```

---

## 5. Input Validation & Sanitization

### ✅ Validate All Inputs

```php
// ใช้ Validator class
$validator = new Validator($data, [
    'email' => 'required|email',
    'username' => 'required|alphanumeric|min:3|max:20',
    'age' => 'required|numeric|between:18,100'
]);

if ($validator->fails()) {
    // Handle validation errors
}
```

### ✅ Sanitize Output

```php
// XSS protection
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// หรือใช้ helper
echo SecurityHelper::escape($userInput);
```

### ✅ Prevent XSS

**ต้องทำ:**
- Escape output ทุกครั้ง
- ใช้ Content-Security-Policy headers
- Validate และ sanitize user input

```php
// ✅ ถูกต้อง
<div><?= htmlspecialchars($user->name) ?></div>

// ❌ ผิด - vulnerable to XSS
<div><?= $user->name ?></div>
```

---

## 6. File Upload Security

### ✅ Validate File Uploads

```php
$upload = new FileUpload();

// ตรวจสอบนามสกุลและ MIME type
$upload->setAllowedTypes(['jpg', 'jpeg', 'png'])
       ->setAllowedMimeTypes(['image/jpeg', 'image/png'])
       ->setMaxSize(5 * 1024 * 1024); // 5MB

// Validate
if (!$upload->validate($_FILES['image'])) {
    // Reject upload
}
```

### ✅ Store Files Safely

**ต้องทำ:**
- [ ] เก็บไฟล์นอก webroot (ถ้าเป็น sensitive files)
- [ ] เปลี่ยนชื่อไฟล์แบบสุ่ม
- [ ] ไม่เชื่อถือ extension จาก client
- [ ] ตรวจสอบ MIME type จริง

```php
// เปลี่ยนชื่อไฟล์
$filename = bin2hex(random_bytes(16)) . '.jpg';
```

### ✅ Scan for Malware

```bash
# ใช้ ClamAV สแกนไฟล์
clamscan /path/to/uploaded/file
```

---

## 7. Session Security

### ✅ Session Configuration

```php
// Regenerate session ID หลัง login
session_regenerate_id(true);

// ทำลาย session หลัง logout
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
```

### ✅ Prevent Session Hijacking

**ต้องทำ:**
- [ ] ใช้ HTTPS เท่านั้น (`secure` flag)
- [ ] ตั้ง `httponly` flag
- [ ] ตั้ง `samesite` flag
- [ ] Implement session timeout
- [ ] Regenerate session ID หลัง login

---

## 8. API Security

### ✅ API Authentication

```php
// ต้องมี API Key สำหรับ sensitive operations
$router->post('/api/v1/orders/create', 'OrderApiController@create', [
    AuthMiddleware::class,
    ApiKeyMiddleware::class
]);
```

### ✅ Rate Limiting API

```php
// จำกัดจำนวน requests
$router->group([
    'prefix' => '/api/v1',
    'middleware' => [RateLimitMiddleware::class]
], function($router) {
    // API routes
});
```

### ✅ Validate API Inputs

```php
// Validate JSON inputs
$data = $request->input();
$validator = new Validator($data, $rules);

if ($validator->fails()) {
    return json_response([
        'success' => false,
        'errors' => $validator->errors()
    ], 422);
}
```

---

## 9. Logging & Monitoring

### ✅ Security Event Logging

```php
// Log security events
$logger = new Logger();

// Failed login
$logger->warning('Failed login attempt', [
    'username' => $username,
    'ip' => $_SERVER['REMOTE_ADDR']
]);

// Suspicious activity
$logger->error('Possible attack detected', [
    'type' => 'SQL Injection',
    'ip' => $_SERVER['REMOTE_ADDR'],
    'query' => $suspiciousInput
]);
```

### ✅ Monitor Logs

```bash
# ตรวจสอบ error logs
tail -f storage/logs/$(date +%Y-%m-%d).log

# ค้นหา suspicious activities
grep -i "attack\|injection\|failed" storage/logs/*.log
```

### ✅ Log Rotation

```bash
# /etc/logrotate.d/your-project
/var/www/your-project/storage/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
}
```

---

## 10. Backup & Recovery

### ✅ Automated Backups

```bash
#!/bin/bash
# /usr/local/bin/backup-app.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups"

# Backup database
mysqldump -u user -p'password' database > "$BACKUP_DIR/db_$DATE.sql"

# Backup files
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" /var/www/your-project

# Encrypt
openssl enc -aes-256-cbc -salt -in "$BACKUP_DIR/db_$DATE.sql" -out "$BACKUP_DIR/db_$DATE.sql.enc"

# Remove old backups (keep 30 days)
find $BACKUP_DIR -name "*.enc" -mtime +30 -delete

echo "Backup completed: $DATE"
```

**Cron:**
```bash
# Backup daily at 2 AM
0 2 * * * /usr/local/bin/backup-app.sh
```

### ✅ Test Recovery Process

```bash
# ทดสอบ restore เป็นประจำ
mysql -u user -p database_test < backup.sql
```

---

## 🔍 Security Audit Checklist

### Pre-Launch Checklist

- [ ] `APP_DEBUG=false`
- [ ] SSL/TLS enabled และ force HTTPS
- [ ] Security headers ครบถ้วน
- [ ] ไฟล์ sensitive ถูก blocked
- [ ] File permissions ถูกต้อง
- [ ] Database user มีสิทธิ์เท่าที่จำเป็น
- [ ] Password hashing ใช้ bcrypt/argon2
- [ ] CSRF protection enabled
- [ ] XSS protection ทุกจุด
- [ ] SQL injection protection (prepared statements)
- [ ] File upload validation ครบถ้วน
- [ ] Rate limiting enabled
- [ ] Session security configured
- [ ] Error logging enabled
- [ ] Backup automated
- [ ] Monitoring setup

### Regular Maintenance

**รายเดือน:**
- [ ] ตรวจสอบ security logs
- [ ] ทดสอบ backup restoration
- [ ] อัพเดท dependencies (Composer)
- [ ] Scan for vulnerabilities

**รายไตรมาส:**
- [ ] Security audit โดยผู้เชี่ยวชาญ
- [ ] Penetration testing
- [ ] Review access controls
- [ ] Update security policies

---

## 🛠️ Security Tools

### Recommended Tools:

1. **OWASP ZAP** - Web application security scanner
2. **Nikto** - Web server scanner
3. **sqlmap** - SQL injection testing
4. **Burp Suite** - Web vulnerability scanner
5. **ClamAV** - Antivirus scanner
6. **Fail2Ban** - Intrusion prevention

---

## 📚 Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Best Practices](https://phptherightway.com/#security)
- [Mozilla Security Guidelines](https://infosec.mozilla.org/guidelines/)
- [CIS Benchmarks](https://www.cisecurity.org/cis-benchmarks/)

---

## See Also

- [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) - Deployment steps
- [CORE_USAGE.md](CORE_USAGE.md) - Security features in core classes
