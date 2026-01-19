# Security Hardening Guide

This comprehensive guide covers security best practices and hardening techniques for the SimpleBiz MVC Framework in production environments.

## Table of Contents

1. [Application Security Checklist](#application-security-checklist)
2. [HTTP/HTTPS Hardening](#httphttps-hardening)
3. [Session & Cookie Security](#session--cookie-security)
4. [File Permissions](#file-permissions)
5. [Database Security](#database-security)
6. [Input Validation & Sanitization](#input-validation--sanitization)
7. [Protection Against Common Attacks](#protection-against-common-attacks)
8. [Secure Headers Configuration](#secure-headers-configuration)
9. [Rate Limiting](#rate-limiting)
10. [Logging & Monitoring](#logging--monitoring)

---

## Application Security Checklist

### Environment Configuration

**✅ Production Settings (.env)**
```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=your-random-32-character-key-here
API_KEY=your-secure-random-api-key-here
```

**⚠️ Critical Points:**
- Never commit `.env` file to version control
- Use strong, randomly generated keys (minimum 32 characters)
- Keep `APP_DEBUG=false` in production to prevent information disclosure
- Store sensitive credentials in environment variables, not in code

### Security Best Practices Checklist

- [ ] All dependencies are up to date (`composer update`)
- [ ] Error reporting is disabled for end users
- [ ] API keys are rotated regularly (every 90 days)
- [ ] Database credentials use least privilege principle
- [ ] All user inputs are validated and sanitized
- [ ] CSRF protection is enabled on all forms
- [ ] XSS protection is implemented
- [ ] SQL injection prevention via prepared statements
- [ ] Rate limiting is configured
- [ ] Security headers are properly set
- [ ] File upload validation is strict
- [ ] Session configuration is hardened
- [ ] Logging is enabled for security events
- [ ] Regular security audits are scheduled

---

## HTTP/HTTPS Hardening

### Enable HTTPS/TLS

**Apache Configuration (.htaccess)**
```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Enable HSTS (HTTP Strict Transport Security)
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
```

**Nginx Configuration**
```nginx
server {
    listen 80;
    server_name example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name example.com;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
}
```

### TLS/SSL Best Practices

- Use TLS 1.2 or higher (disable SSLv3, TLS 1.0, TLS 1.1)
- Use strong cipher suites
- Enable HSTS with appropriate max-age
- Renew SSL certificates before expiration
- Use certificates from trusted Certificate Authorities

**Check SSL Configuration:**
```bash
# Test SSL/TLS configuration
openssl s_client -connect example.com:443 -tls1_2
```

---

## Session & Cookie Security

### Session Configuration

**config/app.php**
```php
return [
    'session' => [
        'lifetime' => 7200, // 2 hours
        'cookie_name' => '__Secure-SESSIONID',
        'cookie_secure' => true, // HTTPS only
        'cookie_httponly' => true, // Prevent JavaScript access
        'cookie_samesite' => 'Lax', // CSRF protection
        'regenerate_id' => true, // Regenerate on login
    ],
];
```

### Secure Cookie Implementation

**Setting Secure Cookies:**
```php
// In your AuthController or Session class
$cookieOptions = [
    'expires' => time() + 3600,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true, // HTTPS only
    'httponly' => true, // No JavaScript access
    'samesite' => 'Strict' // or 'Lax'
];

setcookie('user_token', $token, $cookieOptions);
```

### Session Hardening Checklist

- [ ] Session IDs are regenerated after login/logout
- [ ] Session timeout is configured appropriately
- [ ] Cookies are marked as `Secure` and `HttpOnly`
- [ ] SameSite attribute is set to prevent CSRF
- [ ] Session data is stored server-side (not in cookies)
- [ ] Session fixation attacks are prevented
- [ ] Concurrent session limits are enforced

**Example: Session Regeneration**
```php
// In AuthController after successful login
public function login()
{
    // Validate credentials...
    
    // Destroy old session
    session_regenerate_id(true);
    
    // Set new session data
    $_SESSION['user_id'] = $user->id;
    $_SESSION['last_activity'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
}
```

---

## File Permissions

### Recommended Permissions Structure

```bash
# Directories
drwxr-xr-x (755)  /app
drwxr-xr-x (755)  /config
drwxr-xr-x (755)  /public
drwxrwx--- (770)  /storage
drwxrwx--- (770)  /storage/logs
drwxrwx--- (770)  /storage/cache

# Files
-rw-r--r-- (644)  *.php
-rw------- (600)  .env
-rw-r--r-- (644)  composer.json
```

### Setting Correct Permissions

**Linux/Unix:**
```bash
# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Storage directories (writable)
chmod -R 770 storage/
chmod -R 770 storage/logs/
chmod -R 770 storage/cache/

# Protect .env file
chmod 600 .env

# Set ownership (if using www-data)
chown -R www-data:www-data storage/
```

**Windows PowerShell:**
```powershell
# Remove write access for non-admin users
icacls "app" /inheritance:r /grant:r "Users:(RX)"
icacls "config" /inheritance:r /grant:r "Users:(RX)"

# Storage needs write access
icacls "storage" /grant "IIS_IUSRS:(OI)(CI)F"
```

### File Upload Directory Security

**Prevent Script Execution in Upload Directories:**

**.htaccess in public/uploads/**
```apache
# Deny execution of PHP files
<FilesMatch "\.php$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Disable directory listing
Options -Indexes
```

---

## Database Security

### Connection Security

**Use Environment Variables:**
```php
// config/database.php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'host' => getenv('DB_HOST'),
            'database' => getenv('DB_DATABASE'),
            'username' => getenv('DB_USERNAME'),
            'password' => getenv('DB_PASSWORD'),
            'port' => getenv('DB_PORT') ?: 3306,
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
    ],
];
```

### SQL Injection Prevention

**✅ Use Prepared Statements (Always)**
```php
// CORRECT - Using prepared statements
$user = User::where('email = ?', [$email])->first();

// In Model class
public static function findByEmail($email)
{
    $sql = "SELECT * FROM users WHERE email = ?";
    return self::query($sql, [$email])->fetch();
}
```

**❌ Never Concatenate User Input**
```php
// WRONG - Vulnerable to SQL injection
$sql = "SELECT * FROM users WHERE email = '$email'";
$user = $db->query($sql);
```

### Database User Privileges

**Create Limited Database User:**
```sql
-- Create application user with minimal privileges
CREATE USER 'app_user'@'localhost' IDENTIFIED BY 'strong_password';

-- Grant only necessary privileges
GRANT SELECT, INSERT, UPDATE, DELETE ON app_database.* TO 'app_user'@'localhost';

-- Do NOT grant:
-- CREATE, DROP, ALTER, INDEX, REFERENCES, etc.

FLUSH PRIVILEGES;
```

### Database Security Checklist

- [ ] Use prepared statements for all queries
- [ ] Database user has minimal required privileges
- [ ] Database password is strong and unique
- [ ] Database is not accessible from public internet
- [ ] Regular backups are automated and tested
- [ ] Sensitive data is encrypted at rest
- [ ] Connection uses SSL/TLS when possible
- [ ] Database error messages don't expose structure

**Enable MySQL SSL Connection:**
```php
'options' => [
    PDO::MYSQL_ATTR_SSL_CA => '/path/to/ca-cert.pem',
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
],
```

---

## Input Validation & Sanitization

### Using the Validator Class

**Basic Validation Example:**
```php
use App\Core\Validator;

// In your controller
public function store()
{
    $data = [
        'username' => $_POST['username'] ?? '',
        'email' => $_POST['email'] ?? '',
        'age' => $_POST['age'] ?? '',
    ];
    
    $rules = [
        'username' => 'required|min:3|max:50|alpha_numeric',
        'email' => 'required|email',
        'age' => 'required|numeric|min:18|max:120',
    ];
    
    $validator = new Validator();
    
    if (!$validator->validate($data, $rules)) {
        $errors = $validator->errors();
        // Return errors to view
        return view('form', ['errors' => $errors]);
    }
    
    // Data is valid, proceed...
}
```

### Input Sanitization

**Sanitize User Input:**
```php
use App\Helpers\SecurityHelper;

// Sanitize string
$clean_name = SecurityHelper::sanitizeString($_POST['name']);

// Sanitize HTML (allow safe tags)
$clean_html = SecurityHelper::sanitizeHtml($_POST['content']);

// Escape output in views
echo SecurityHelper::escape($user_input);

// Or use helper function
echo e($user_input); // Short helper
```

### Validation Rules Reference

Available validation rules in the framework:

- `required` - Field must not be empty
- `email` - Must be valid email format
- `numeric` - Must be numeric value
- `integer` - Must be integer
- `alpha` - Only alphabetic characters
- `alpha_numeric` - Alphanumeric characters only
- `min:n` - Minimum length/value
- `max:n` - Maximum length/value
- `between:min,max` - Value between range
- `in:val1,val2` - Value in list
- `not_in:val1,val2` - Value not in list
- `unique:table,column` - Unique in database
- `exists:table,column` - Must exist in database
- `confirmed` - Must match confirmation field
- `url` - Must be valid URL
- `date` - Must be valid date
- `before:date` - Date before specified
- `after:date` - Date after specified

**Advanced Validation Example:**
```php
$rules = [
    'username' => 'required|min:3|max:20|alpha_numeric|unique:users,username',
    'email' => 'required|email|unique:users,email',
    'password' => 'required|min:8|confirmed',
    'password_confirmation' => 'required',
    'age' => 'required|integer|between:18,100',
    'website' => 'url',
    'terms' => 'required|in:1,yes,true',
];
```

---

## Protection Against Common Attacks

### 1. SQL Injection Protection

**✅ Always Use Prepared Statements:**
```php
// Safe query with parameter binding
public function getUserByEmail($email)
{
    $sql = "SELECT * FROM users WHERE email = ?";
    return $this->db->query($sql, [$email])->fetch();
}

// Safe query with named parameters
public function searchUsers($name, $status)
{
    $sql = "SELECT * FROM users WHERE name LIKE :name AND status = :status";
    return $this->db->query($sql, [
        ':name' => "%{$name}%",
        ':status' => $status
    ])->fetchAll();
}
```

### 2. Cross-Site Scripting (XSS) Protection

**Escape Output in Views:**
```php
<!-- In your view files -->
<!-- CORRECT -->
<h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
<p><?= e($user_content) ?></p>

<!-- Using SecurityHelper -->
<div><?= SecurityHelper::escape($user_input) ?></div>

<!-- For HTML content (sanitize first) -->
<div><?= SecurityHelper::sanitizeHtml($rich_text_content) ?></div>
```

**Content Security Policy (CSP):**
```php
// Add in your middleware or header configuration
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;");
```

### 3. Cross-Site Request Forgery (CSRF) Protection

**Using CSRF Middleware:**
```php
// In routes/web.php
$router->group(['middleware' => 'csrf'], function($router) {
    $router->post('/profile/update', 'ProfileController@update');
    $router->post('/account/delete', 'AccountController@delete');
});
```

**CSRF Token in Forms:**
```html
<form method="POST" action="/profile/update">
    <?php echo csrf_field(); ?>
    
    <!-- Or manually -->
    <input type="hidden" name="_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
    
    <input type="text" name="name" value="<?= e($user->name) ?>">
    <button type="submit">Update</button>
</form>
```

**Verify CSRF Token (in CsrfMiddleware):**
```php
public function handle($request, $next)
{
    if (in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
        $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');
        
        if (!$token || $token !== $_SESSION['csrf_token']) {
            throw new Exception('CSRF token mismatch', 419);
        }
    }
    
    return $next($request);
}
```

### 4. XML External Entity (XXE) Protection

```php
// When parsing XML, disable external entities
libxml_disable_entity_loader(true);

$xml = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOENT);
```

### 5. Remote Code Execution (RCE) Prevention

**File Upload Validation:**
```php
use App\Core\FileUpload;

$uploader = new FileUpload();
$uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif', 'pdf'])
         ->setMaxSize(5 * 1024 * 1024) // 5MB
         ->setUploadPath('public/uploads/');

// Validate MIME type
$file = $_FILES['document'];
$allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];

if (!in_array($file['type'], $allowedMimes)) {
    throw new Exception('Invalid file type');
}

// Validate file extension and MIME type match
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if ($mimeType !== $file['type']) {
    throw new Exception('File type mismatch');
}
```

### 6. Path Traversal Protection

```php
// Prevent directory traversal
public function downloadFile($filename)
{
    // Remove any path traversal characters
    $filename = basename($filename);
    $filename = str_replace(['..', '/', '\\'], '', $filename);
    
    $filepath = realpath('storage/downloads/' . $filename);
    $basepath = realpath('storage/downloads/');
    
    // Verify file is within allowed directory
    if (strpos($filepath, $basepath) !== 0) {
        throw new Exception('Invalid file path');
    }
    
    if (!file_exists($filepath)) {
        throw new Exception('File not found');
    }
    
    // Serve file...
}
```

### 7. Command Injection Prevention

```php
// NEVER use user input directly in shell commands
// If absolutely necessary, use escapeshellarg()

// WRONG
system("convert " . $_POST['filename'] . " output.jpg");

// BETTER (but still avoid if possible)
$filename = escapeshellarg($_POST['filename']);
system("convert $filename output.jpg");

// BEST - Use PHP functions instead of shell commands
```

### 8. Server-Side Request Forgery (SSRF) Protection

```php
// Validate URLs before making external requests
public function fetchRemoteData($url)
{
    // Whitelist allowed domains
    $allowedDomains = ['api.example.com', 'cdn.example.com'];
    
    $parsed = parse_url($url);
    if (!in_array($parsed['host'], $allowedDomains)) {
        throw new Exception('Domain not allowed');
    }
    
    // Prevent access to internal networks
    $ip = gethostbyname($parsed['host']);
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        throw new Exception('Internal IP addresses not allowed');
    }
    
    // Make request...
}
```

---

## Secure Headers Configuration

### Security Headers Overview

**Complete Security Headers Configuration:**

```php
// Add to your middleware or bootstrap file
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;");

// For HTTPS sites
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}
```

### Header Explanations

#### X-Content-Type-Options
```php
header('X-Content-Type-Options: nosniff');
```
Prevents browsers from MIME-sniffing responses away from the declared content-type.

#### X-Frame-Options
```php
header('X-Frame-Options: SAMEORIGIN');
// or
header('X-Frame-Options: DENY');
```
Protects against clickjacking attacks by controlling whether the page can be embedded in frames.

#### Content-Security-Policy (CSP)
```php
$csp = [
    "default-src 'self'",
    "script-src 'self' 'unsafe-inline' https://cdn.example.com",
    "style-src 'self' 'unsafe-inline'",
    "img-src 'self' data: https:",
    "font-src 'self' data:",
    "connect-src 'self' https://api.example.com",
    "frame-ancestors 'none'",
    "base-uri 'self'",
    "form-action 'self'",
];

header('Content-Security-Policy: ' . implode('; ', $csp));
```

#### Referrer-Policy
```php
header('Referrer-Policy: strict-origin-when-cross-origin');
```
Controls how much referrer information is sent with requests.

#### Permissions-Policy
```php
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
```
Controls which browser features and APIs can be used.

### Apache .htaccess Configuration

```apache
# In public/.htaccess
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Permissions-Policy "geolocation=(), microphone=(), camera=()"
    Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';"
    
    # HSTS for HTTPS sites
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" env=HTTPS
</IfModule>
```

---

## Rate Limiting

### Using RateLimitMiddleware

**Configure Rate Limiting:**
```php
// In routes/api.php
$router->group(['middleware' => 'rate_limit:60,1'], function($router) {
    // 60 requests per 1 minute
    $router->get('/api/products', 'Api\ProductController@index');
    $router->get('/api/categories', 'Api\CategoryController@index');
});

$router->group(['middleware' => 'rate_limit:10,1'], function($router) {
    // 10 requests per 1 minute for sensitive endpoints
    $router->post('/api/login', 'Api\AuthController@login');
    $router->post('/api/register', 'Api\AuthController@register');
});
```

**Rate Limit Middleware Implementation:**
```php
// app/Middleware/RateLimitMiddleware.php
namespace App\Middleware;

use App\Core\Cache;

class RateLimitMiddleware
{
    private $maxAttempts;
    private $decayMinutes;
    
    public function __construct($maxAttempts = 60, $decayMinutes = 1)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }
    
    public function handle($request, $next)
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = $this->maxAttempts;
        $decayMinutes = $this->decayMinutes;
        
        $cache = new Cache();
        $attempts = $cache->get($key, 0);
        
        if ($attempts >= $maxAttempts) {
            header('X-RateLimit-Limit: ' . $maxAttempts);
            header('X-RateLimit-Remaining: 0');
            header('Retry-After: ' . ($decayMinutes * 60));
            
            http_response_code(429);
            echo json_encode([
                'error' => 'Too many requests. Please try again later.',
                'retry_after' => $decayMinutes * 60
            ]);
            exit;
        }
        
        $cache->set($key, $attempts + 1, $decayMinutes * 60);
        
        header('X-RateLimit-Limit: ' . $maxAttempts);
        header('X-RateLimit-Remaining: ' . ($maxAttempts - $attempts - 1));
        
        return $next($request);
    }
    
    protected function resolveRequestSignature($request)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $route = $_SERVER['REQUEST_URI'] ?? '/';
        
        return 'rate_limit:' . md5($ip . '|' . $route);
    }
}
```

### IP-Based Rate Limiting

```php
// Track failed login attempts
public function login()
{
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = "login_attempts:{$ip}";
    
    $cache = new Cache();
    $attempts = $cache->get($key, 0);
    
    // Block after 5 failed attempts
    if ($attempts >= 5) {
        $remainingTime = $cache->ttl($key);
        throw new Exception("Too many failed login attempts. Please try again in {$remainingTime} seconds.");
    }
    
    // Validate credentials
    if (!$this->validateCredentials()) {
        $cache->set($key, $attempts + 1, 900); // 15 minutes
        throw new Exception('Invalid credentials');
    }
    
    // Success - clear attempts
    $cache->delete($key);
    
    // Login user...
}
```

---

## Logging & Monitoring

### Security Event Logging

**Using Logger Class:**
```php
use App\Core\Logger;

// Log security events
Logger::security('Failed login attempt', [
    'ip' => $_SERVER['REMOTE_ADDR'],
    'username' => $username,
    'timestamp' => date('Y-m-d H:i:s')
]);

Logger::security('Unauthorized access attempt', [
    'ip' => $_SERVER['REMOTE_ADDR'],
    'route' => $_SERVER['REQUEST_URI'],
    'user_id' => $_SESSION['user_id'] ?? null
]);

Logger::security('File upload blocked', [
    'ip' => $_SERVER['REMOTE_ADDR'],
    'filename' => $filename,
    'reason' => 'Invalid file type'
]);
```

### What to Log

**Critical Security Events:**
- Failed login attempts
- Successful logins
- Password changes
- Account deletions
- Privilege escalation attempts
- Unauthorized access attempts
- File upload/download activities
- CSRF token mismatches
- Rate limit violations
- SQL injection attempts
- XSS attempts
- Session hijacking attempts

**Example Comprehensive Logging:**
```php
// In AuthController
public function login()
{
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $user = User::where('email = ?', [$email])->first();
    
    if (!$user || !password_verify($password, $user->password)) {
        Logger::security('Failed login attempt', [
            'email' => $email,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        // Log to activity log table
        ActivityLog::create([
            'action' => 'login_failed',
            'details' => "Failed login for: {$email}",
            'ip_address' => $ip,
            'user_agent' => $userAgent
        ]);
        
        return redirect('/login')->with('error', 'Invalid credentials');
    }
    
    // Success
    Logger::info('User logged in', [
        'user_id' => $user->id,
        'email' => $user->email,
        'ip' => $ip
    ]);
    
    ActivityLog::create([
        'user_id' => $user->id,
        'action' => 'login_success',
        'details' => "User logged in",
        'ip_address' => $ip,
        'user_agent' => $userAgent
    ]);
    
    // Login user...
}
```

### Monitoring Best Practices

**Set Up Alerts:**
```php
// Monitor for suspicious activity
$failedAttempts = ActivityLog::where('action = ? AND created_at > ?', [
    'login_failed',
    date('Y-m-d H:i:s', strtotime('-1 hour'))
])->count();

if ($failedAttempts > 50) {
    // Send alert to admin
    $mail = new Mail();
    $mail->to('admin@example.com')
         ->subject('Security Alert: Multiple Failed Login Attempts')
         ->body("There have been {$failedAttempts} failed login attempts in the last hour.")
         ->send();
}
```

### Log Rotation

**Automatic Log Rotation Script (logs/rotate.php):**
```php
<?php
$logDir = __DIR__ . '/../storage/logs/';
$maxSize = 10 * 1024 * 1024; // 10MB
$maxFiles = 5;

$logFiles = glob($logDir . '*.log');

foreach ($logFiles as $logFile) {
    if (filesize($logFile) > $maxSize) {
        // Rotate logs
        for ($i = $maxFiles - 1; $i > 0; $i--) {
            $old = $logFile . '.' . $i;
            $new = $logFile . '.' . ($i + 1);
            
            if (file_exists($old)) {
                rename($old, $new);
            }
        }
        
        // Rename current log
        rename($logFile, $logFile . '.1');
        
        // Create new log file
        touch($logFile);
    }
}
```

**Cron Job for Log Rotation:**
```bash
# Run daily at midnight
0 0 * * * php /path/to/app/storage/logs/rotate.php
```

---

## Additional Security Resources

### Security Testing Tools

- **OWASP ZAP** - Web application security scanner
- **Burp Suite** - Security testing platform
- **SQLMap** - SQL injection testing
- **Nikto** - Web server scanner

### Regular Security Tasks

**Weekly:**
- Review access logs for suspicious activity
- Check for failed login attempts
- Monitor application errors

**Monthly:**
- Update dependencies (`composer update`)
- Review and rotate API keys
- Audit user permissions and roles
- Check SSL certificate expiration

**Quarterly:**
- Perform security audit
- Review and update security policies
- Test backup and recovery procedures
- Update security headers configuration

### Security Checklist for Deployment

- [ ] All environment variables are set correctly
- [ ] Debug mode is disabled
- [ ] HTTPS is enforced
- [ ] Security headers are configured
- [ ] File permissions are correct
- [ ] Database user has minimal privileges
- [ ] Rate limiting is enabled
- [ ] CSRF protection is active
- [ ] Input validation is implemented
- [ ] Output is properly escaped
- [ ] Logging is enabled
- [ ] Backups are configured
- [ ] SSL certificate is valid
- [ ] All dependencies are up to date

---

## Related Documentation

- [Middleware Guide](MIDDLEWARE_GUIDE.md) - CSRF, rate limiting, authentication
- [Core Usage Guide](CORE_USAGE.md) - Validator, Session, Database
- [API Reference](API_REFERENCE.md) - Secure API development
- [Deployment Guide](DEPLOYMENT_GUIDE.md) - Production deployment

---

**Last Updated:** January 2026  
**Version:** 2.0
