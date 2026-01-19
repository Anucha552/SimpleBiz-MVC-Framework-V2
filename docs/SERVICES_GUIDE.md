# Services Guide - Complete Reference

This comprehensive guide covers all core services in the SimpleBiz MVC Framework with detailed examples and best practices.

## Table of Contents

1. [Mail Service](#mail-service)
2. [FileUpload Service](#fileupload-service)
3. [Cache Service](#cache-service)
4. [Logger Service](#logger-service)
5. [ErrorHandler Service](#errorhandler-service)

---

## Mail Service

The Mail service provides a simple interface for sending emails with support for templates, attachments, and multiple recipients.

### Configuration

**Environment Variables (.env):**
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="SimpleBiz App"
```

### Basic Usage

**Sending a Simple Email:**
```php
use App\Core\Mail;

// Create mail instance
$mail = new Mail();

// Send simple email
$mail->to('user@example.com')
     ->subject('Welcome to SimpleBiz')
     ->body('Thank you for joining our platform!')
     ->send();
```

**Sending to Multiple Recipients:**
```php
$mail = new Mail();

$mail->to(['user1@example.com', 'user2@example.com'])
     ->cc('manager@example.com')
     ->bcc('admin@example.com')
     ->subject('Monthly Report')
     ->body('Please find the monthly report attached.')
     ->send();
```

### Using Email Templates

**Create Email Template (app/Views/emails/welcome.php):**
```php
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .button { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to <?= $app_name ?></h1>
        </div>
        <div class="content">
            <h2>Hello <?= $name ?>!</h2>
            <p>Thank you for registering with us. We're excited to have you on board.</p>
            <p>Your account details:</p>
            <ul>
                <li><strong>Email:</strong> <?= $email ?></li>
                <li><strong>Username:</strong> <?= $username ?></li>
            </ul>
            <p>
                <a href="<?= $activation_link ?>" class="button">Activate Your Account</a>
            </p>
            <p>If you didn't create this account, please ignore this email.</p>
        </div>
    </div>
</body>
</html>
```

**Sending Template Email:**
```php
use App\Core\Mail;
use App\Core\View;

// Prepare template data
$data = [
    'app_name' => 'SimpleBiz',
    'name' => $user->name,
    'email' => $user->email,
    'username' => $user->username,
    'activation_link' => 'https://example.com/activate/' . $user->activation_token
];

// Render template
$htmlBody = View::render('emails/welcome', $data);

// Send email
$mail = new Mail();
$mail->to($user->email)
     ->subject('Welcome to SimpleBiz - Please Activate Your Account')
     ->html($htmlBody)
     ->send();
```

### Sending Emails with Attachments

```php
$mail = new Mail();

$mail->to('client@example.com')
     ->subject('Invoice #12345')
     ->body('Please find your invoice attached.')
     ->attach('/path/to/invoice.pdf')
     ->attach('/path/to/receipt.pdf')
     ->send();
```

### Advanced Email Examples

**Password Reset Email:**
```php
// app/Views/emails/password-reset.php
<!DOCTYPE html>
<html>
<head>
    <style>
        /* Same styling as above */
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Password Reset Request</h1>
        </div>
        <div class="content">
            <h2>Hello <?= $name ?>!</h2>
            <p>We received a request to reset your password.</p>
            <p>Click the button below to reset your password:</p>
            <p>
                <a href="<?= $reset_link ?>" class="button">Reset Password</a>
            </p>
            <p>This link will expire in <?= $expires_in ?> minutes.</p>
            <p>If you didn't request a password reset, please ignore this email.</p>
        </div>
    </div>
</body>
</html>

// In your controller
public function forgotPassword()
{
    $email = $_POST['email'];
    $user = User::where('email = ?', [$email])->first();
    
    if (!$user) {
        return redirect()->back()->with('error', 'Email not found');
    }
    
    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $user->reset_token = $token;
    $user->reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $user->save();
    
    // Send email
    $data = [
        'name' => $user->name,
        'reset_link' => url('reset-password/' . $token),
        'expires_in' => 60
    ];
    
    $htmlBody = View::render('emails/password-reset', $data);
    
    $mail = new Mail();
    $mail->to($user->email)
         ->subject('Password Reset Request')
         ->html($htmlBody)
         ->send();
    
    return redirect()->back()->with('success', 'Password reset link sent to your email');
}
```

**Order Confirmation Email:**
```php
// app/Views/emails/order-confirmation.php
<!DOCTYPE html>
<html>
<body>
    <div class="container">
        <div class="header">
            <h1>Order Confirmation</h1>
        </div>
        <div class="content">
            <h2>Thank you for your order, <?= $customer_name ?>!</h2>
            <p>Order #<?= $order_number ?> has been confirmed.</p>
            
            <h3>Order Details:</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f0f0f0;">
                        <th style="padding: 10px; text-align: left;">Product</th>
                        <th style="padding: 10px; text-align: right;">Qty</th>
                        <th style="padding: 10px; text-align: right;">Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td style="padding: 10px;"><?= $item['name'] ?></td>
                        <td style="padding: 10px; text-align: right;"><?= $item['quantity'] ?></td>
                        <td style="padding: 10px; text-align: right;">$<?= number_format($item['price'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f0f0f0; font-weight: bold;">
                        <td colspan="2" style="padding: 10px;">Total:</td>
                        <td style="padding: 10px; text-align: right;">$<?= number_format($total, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
            
            <p>Estimated delivery: <?= $estimated_delivery ?></p>
            <p><a href="<?= $tracking_link ?>" class="button">Track Order</a></p>
        </div>
    </div>
</body>
</html>
```

### Mail Service Best Practices

✅ **Do:**
- Use environment variables for mail configuration
- Use HTML templates for better formatting
- Include plain text alternative for HTML emails
- Validate email addresses before sending
- Log email sending failures
- Use queue for bulk emails (if implementing)

❌ **Don't:**
- Hardcode SMTP credentials in code
- Send emails synchronously in loops
- Include sensitive data in email subjects
- Send emails without proper error handling

---

## FileUpload Service

The FileUpload service provides secure file upload functionality with validation, sanitization, and storage management.

### Configuration

**Allowed File Types and Sizes:**
```php
// In your upload configuration
$config = [
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
    'max_file_size' => 5 * 1024 * 1024, // 5MB
    'upload_path' => 'public/uploads/',
    'create_thumbnails' => true, // For images
    'thumbnail_sizes' => [
        'small' => [150, 150],
        'medium' => [300, 300],
        'large' => [800, 600]
    ]
];
```

### Basic File Upload

**Simple Upload Example:**
```php
use App\Core\FileUpload;

// In your controller
public function uploadAvatar()
{
    if (!isset($_FILES['avatar'])) {
        return json(['error' => 'No file uploaded'], 400);
    }
    
    $uploader = new FileUpload();
    
    try {
        // Configure uploader
        $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif'])
                 ->setMaxSize(2 * 1024 * 1024) // 2MB
                 ->setUploadPath('public/uploads/avatars/');
        
        // Upload file
        $result = $uploader->upload($_FILES['avatar']);
        
        // Save to database
        $user = User::find($_SESSION['user_id']);
        $user->avatar = $result['filename'];
        $user->save();
        
        return json(['success' => true, 'filename' => $result['filename']]);
        
    } catch (Exception $e) {
        return json(['error' => $e->getMessage()], 400);
    }
}
```

### Advanced Upload with Validation

**Complete Upload Example:**
```php
use App\Core\FileUpload;
use App\Core\Logger;
use App\Models\Media;

public function uploadDocument()
{
    // Validate file exists
    if (!isset($_FILES['document'])) {
        return json(['error' => 'No file uploaded'], 400);
    }
    
    $file = $_FILES['document'];
    
    // Basic validation
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = $this->getUploadErrorMessage($file['error']);
        Logger::error('File upload error', ['error' => $errorMessage]);
        return json(['error' => $errorMessage], 400);
    }
    
    $uploader = new FileUpload();
    
    try {
        // Configure uploader
        $uploader->setAllowedExtensions(['pdf', 'doc', 'docx', 'xls', 'xlsx'])
                 ->setMaxSize(10 * 1024 * 1024) // 10MB
                 ->setUploadPath('storage/documents/')
                 ->setAllowedMimeTypes([
                     'application/pdf',
                     'application/msword',
                     'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                     'application/vnd.ms-excel',
                     'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                 ]);
        
        // Validate file
        if (!$uploader->validate($file)) {
            throw new Exception('File validation failed');
        }
        
        // Upload file
        $result = $uploader->upload($file);
        
        // Save to media table
        $media = Media::create([
            'filename' => $result['filename'],
            'original_name' => $file['name'],
            'mime_type' => $result['mime_type'],
            'size' => $result['size'],
            'path' => $result['path'],
            'user_id' => $_SESSION['user_id'] ?? null,
            'uploaded_at' => date('Y-m-d H:i:s')
        ]);
        
        // Log upload
        Logger::info('Document uploaded', [
            'media_id' => $media->id,
            'filename' => $result['filename'],
            'user_id' => $_SESSION['user_id'] ?? null
        ]);
        
        return json([
            'success' => true,
            'media_id' => $media->id,
            'filename' => $result['filename'],
            'url' => url('storage/documents/' . $result['filename'])
        ]);
        
    } catch (Exception $e) {
        Logger::error('File upload failed', [
            'error' => $e->getMessage(),
            'file' => $file['name']
        ]);
        return json(['error' => $e->getMessage()], 400);
    }
}

private function getUploadErrorMessage($errorCode)
{
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by PHP extension'
    ];
    
    return $errors[$errorCode] ?? 'Unknown upload error';
}
```

### Image Upload with Thumbnails

```php
public function uploadProductImage()
{
    if (!isset($_FILES['image'])) {
        return json(['error' => 'No image uploaded'], 400);
    }
    
    $uploader = new FileUpload();
    
    try {
        $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif', 'webp'])
                 ->setMaxSize(5 * 1024 * 1024) // 5MB
                 ->setUploadPath('public/uploads/products/');
        
        // Upload original
        $result = $uploader->upload($_FILES['image']);
        
        // Create thumbnails
        $thumbnails = $this->createThumbnails($result['path'], [
            'thumb' => [150, 150],
            'small' => [300, 300],
            'medium' => [600, 600]
        ]);
        
        // Save to database
        $media = Media::create([
            'filename' => $result['filename'],
            'original_name' => $_FILES['image']['name'],
            'mime_type' => $result['mime_type'],
            'size' => $result['size'],
            'path' => $result['path'],
            'thumbnails' => json_encode($thumbnails),
            'user_id' => $_SESSION['user_id'] ?? null
        ]);
        
        return json([
            'success' => true,
            'media_id' => $media->id,
            'original' => url($result['path']),
            'thumbnails' => array_map(fn($path) => url($path), $thumbnails)
        ]);
        
    } catch (Exception $e) {
        return json(['error' => $e->getMessage()], 400);
    }
}

private function createThumbnails($originalPath, $sizes)
{
    $thumbnails = [];
    
    foreach ($sizes as $name => $dimensions) {
        [$width, $height] = $dimensions;
        
        $pathInfo = pathinfo($originalPath);
        $thumbnailPath = $pathInfo['dirname'] . '/' . 
                        $pathInfo['filename'] . '_' . $name . '.' . 
                        $pathInfo['extension'];
        
        // Create thumbnail using GD or Imagick
        $this->resizeImage($originalPath, $thumbnailPath, $width, $height);
        
        $thumbnails[$name] = $thumbnailPath;
    }
    
    return $thumbnails;
}

private function resizeImage($source, $destination, $maxWidth, $maxHeight)
{
    list($origWidth, $origHeight) = getimagesize($source);
    
    $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
    $newWidth = (int)($origWidth * $ratio);
    $newHeight = (int)($origHeight * $ratio);
    
    $imageType = exif_imagetype($source);
    
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $srcImage = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $srcImage = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $srcImage = imagecreatefromgif($source);
            break;
        default:
            throw new Exception('Unsupported image type');
    }
    
    $dstImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency
    if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
    }
    
    imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, 
                      $newWidth, $newHeight, $origWidth, $origHeight);
    
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            imagejpeg($dstImage, $destination, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($dstImage, $destination, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($dstImage, $destination);
            break;
    }
    
    imagedestroy($srcImage);
    imagedestroy($dstImage);
}
```

### Multiple File Upload

```php
public function uploadMultiple()
{
    if (!isset($_FILES['files'])) {
        return json(['error' => 'No files uploaded'], 400);
    }
    
    $files = $_FILES['files'];
    $uploaded = [];
    $errors = [];
    
    $uploader = new FileUpload();
    $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'pdf'])
             ->setMaxSize(5 * 1024 * 1024)
             ->setUploadPath('public/uploads/documents/');
    
    // Reformat files array
    $fileCount = count($files['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = [
                'file' => $files['name'][$i],
                'error' => $this->getUploadErrorMessage($files['error'][$i])
            ];
            continue;
        }
        
        $file = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i]
        ];
        
        try {
            $result = $uploader->upload($file);
            $uploaded[] = [
                'filename' => $result['filename'],
                'url' => url($result['path'])
            ];
        } catch (Exception $e) {
            $errors[] = [
                'file' => $file['name'],
                'error' => $e->getMessage()
            ];
        }
    }
    
    return json([
        'success' => count($uploaded) > 0,
        'uploaded' => $uploaded,
        'errors' => $errors
    ]);
}
```

### FileUpload Best Practices

✅ **Do:**
- Always validate file extensions and MIME types
- Set appropriate file size limits
- Sanitize filenames
- Store files outside public directory for sensitive documents
- Create thumbnails for images
- Log upload activities
- Check available disk space before upload

❌ **Don't:**
- Trust user-provided filenames or MIME types
- Allow executable file uploads
- Store uploads without validation
- Use original filenames (use generated names)

---

## Cache Service

The Cache service provides a simple key-value storage system for caching data and reducing database queries.

### Basic Caching

**Simple Cache Operations:**
```php
use App\Core\Cache;

$cache = new Cache();

// Store data in cache (default TTL: 3600 seconds)
$cache->set('user:123', $userData);

// Store with custom TTL (1 hour)
$cache->set('products:featured', $products, 3600);

// Retrieve from cache
$userData = $cache->get('user:123');

// Check if key exists
if ($cache->has('user:123')) {
    $userData = $cache->get('user:123');
}

// Delete from cache
$cache->delete('user:123');

// Clear all cache
$cache->clear();
```

### Caching Database Queries

**Cache Query Results:**
```php
public function getFeaturedProducts()
{
    $cache = new Cache();
    $cacheKey = 'products:featured';
    
    // Try to get from cache
    $products = $cache->get($cacheKey);
    
    if ($products === null) {
        // Cache miss - fetch from database
        $products = Product::where('featured = ?', [1])
                          ->orderBy('created_at', 'DESC')
                          ->limit(10)
                          ->get();
        
        // Store in cache for 1 hour
        $cache->set($cacheKey, $products, 3600);
    }
    
    return $products;
}
```

**Cache with Helper Method:**
```php
public function getCategories()
{
    return Cache::remember('categories:all', 3600, function() {
        return Category::orderBy('name', 'ASC')->get();
    });
}

// Implementation of remember method in Cache class
public function remember($key, $ttl, $callback)
{
    $value = $this->get($key);
    
    if ($value === null) {
        $value = $callback();
        $this->set($key, $value, $ttl);
    }
    
    return $value;
}
```

### Cache Invalidation

**Invalidate Related Caches:**
```php
// When creating a new product
public function store()
{
    // Validate and create product...
    $product = Product::create($data);
    
    // Invalidate related caches
    $cache = new Cache();
    $cache->delete('products:all');
    $cache->delete('products:featured');
    $cache->delete('products:category:' . $product->category_id);
    
    // Or use pattern matching (if implemented)
    $cache->deletePattern('products:*');
    
    return redirect('/products')->with('success', 'Product created');
}
```

### Cache Tags (Advanced)

```php
// Cache with tags for easier invalidation
public function cacheProductData($productId)
{
    $cache = new Cache();
    
    $product = Product::find($productId);
    
    // Cache with tags
    $cache->tags(['products', 'product:' . $productId])
          ->set('product:full:' . $productId, $product, 3600);
    
    // Invalidate all product caches
    $cache->tags(['products'])->flush();
    
    // Invalidate specific product cache
    $cache->tags(['product:' . $productId])->flush();
}
```

### Cache Best Practices

✅ **Do:**
- Use descriptive cache keys
- Set appropriate TTL values
- Invalidate cache when data changes
- Use cache for expensive operations
- Monitor cache hit/miss ratios

❌ **Don't:**
- Cache user-specific data globally
- Set TTL too long for frequently changing data
- Cache everything (cache strategically)
- Forget to handle cache misses

---

## Logger Service

The Logger service provides structured logging for debugging, auditing, and monitoring application events.

### Basic Logging

**Log Levels:**
```php
use App\Core\Logger;

// Debug - detailed debug information
Logger::debug('User query executed', ['sql' => $sql, 'params' => $params]);

// Info - interesting events
Logger::info('User logged in', ['user_id' => $userId, 'ip' => $ip]);

// Warning - exceptional occurrences that are not errors
Logger::warning('Disk space running low', ['available' => $available]);

// Error - runtime errors that do not require immediate action
Logger::error('Failed to send email', ['to' => $email, 'error' => $error]);

// Critical - critical conditions
Logger::critical('Database connection failed', ['host' => $host]);

// Security - security-related events
Logger::security('Failed login attempt', ['username' => $username, 'ip' => $ip]);
```

### Logging in Controllers

**Example: Authentication Logging:**
```php
use App\Core\Logger;

public function login()
{
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    Logger::info('Login attempt', [
        'email' => $email,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    $user = User::where('email = ?', [$email])->first();
    
    if (!$user || !password_verify($password, $user->password)) {
        Logger::security('Failed login attempt', [
            'email' => $email,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'reason' => !$user ? 'user_not_found' : 'invalid_password'
        ]);
        
        return redirect('/login')->with('error', 'Invalid credentials');
    }
    
    // Success
    $_SESSION['user_id'] = $user->id;
    
    Logger::info('User logged in successfully', [
        'user_id' => $user->id,
        'email' => $user->email,
        'ip' => $_SERVER['REMOTE_ADDR']
    ]);
    
    return redirect('/dashboard');
}
```

### Structured Logging

**Log with Context:**
```php
public function processOrder($orderId)
{
    $context = [
        'order_id' => $orderId,
        'user_id' => $_SESSION['user_id'] ?? null,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    Logger::info('Processing order', $context);
    
    try {
        $order = Order::find($orderId);
        
        if (!$order) {
            Logger::error('Order not found', $context);
            throw new Exception('Order not found');
        }
        
        // Process payment
        $payment = $this->processPayment($order);
        
        if (!$payment->success) {
            Logger::error('Payment failed', array_merge($context, [
                'error' => $payment->error,
                'amount' => $order->total
            ]));
            throw new Exception('Payment failed: ' . $payment->error);
        }
        
        // Update order status
        $order->status = 'paid';
        $order->paid_at = date('Y-m-d H:i:s');
        $order->save();
        
        Logger::info('Order processed successfully', array_merge($context, [
            'amount' => $order->total,
            'payment_id' => $payment->id
        ]));
        
        return ['success' => true, 'order' => $order];
        
    } catch (Exception $e) {
        Logger::error('Order processing failed', array_merge($context, [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]));
        
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
```

### Performance Logging

```php
public function slowQueryDetection()
{
    $startTime = microtime(true);
    
    $result = $this->expensiveQuery();
    
    $executionTime = microtime(true) - $startTime;
    
    if ($executionTime > 1.0) {
        Logger::warning('Slow query detected', [
            'execution_time' => $executionTime,
            'query' => 'expensiveQuery',
            'threshold' => 1.0
        ]);
    }
    
    return $result;
}
```

### Logger Best Practices

✅ **Do:**
- Use appropriate log levels
- Include relevant context data
- Log security events
- Log errors with stack traces
- Implement log rotation
- Sanitize sensitive data before logging

❌ **Don't:**
- Log passwords or sensitive data
- Over-log (log spam)
- Log in tight loops
- Ignore log file sizes

---

## ErrorHandler Service

The ErrorHandler service provides centralized error and exception handling for the application.

### Basic Error Handling

**Register Error Handler:**
```php
// In public/index.php or bootstrap
use App\Core\ErrorHandler;

$errorHandler = new ErrorHandler();
$errorHandler->register();
```

### Custom Error Pages

**Create Error Views (app/Views/errors/):**

**404.php:**
```php
<!DOCTYPE html>
<html>
<head>
    <title>404 - Page Not Found</title>
    <style>
        body { font-family: Arial; text-align: center; padding: 50px; }
        h1 { font-size: 72px; margin: 0; }
        p { font-size: 24px; }
    </style>
</head>
<body>
    <h1>404</h1>
    <p>Page Not Found</p>
    <p>The page you're looking for doesn't exist.</p>
    <a href="/">Go Home</a>
</body>
</html>
```

**500.php:**
```php
<!DOCTYPE html>
<html>
<head>
    <title>500 - Internal Server Error</title>
</head>
<body>
    <h1>500</h1>
    <p>Internal Server Error</p>
    <p>Something went wrong. We're working on it!</p>
    <?php if (getenv('APP_DEBUG') === 'true'): ?>
        <div style="text-align: left; padding: 20px; background: #f0f0f0;">
            <h3>Error Details:</h3>
            <pre><?= htmlspecialchars($error ?? 'No details available') ?></pre>
        </div>
    <?php endif; ?>
</body>
</html>
```

### Exception Handling

**Throw and Handle Exceptions:**
```php
public function deleteUser($userId)
{
    try {
        $user = User::find($userId);
        
        if (!$user) {
            throw new Exception('User not found', 404);
        }
        
        if ($user->id === $_SESSION['user_id']) {
            throw new Exception('Cannot delete your own account', 403);
        }
        
        $user->delete();
        
        Logger::info('User deleted', ['user_id' => $userId]);
        
        return json(['success' => true]);
        
    } catch (Exception $e) {
        Logger::error('Failed to delete user', [
            'user_id' => $userId,
            'error' => $e->getMessage()
        ]);
        
        http_response_code($e->getCode() ?: 500);
        return json(['error' => $e->getMessage()]);
    }
}
```

### Global Exception Handler

```php
// In ErrorHandler class
public function handleException($exception)
{
    $code = $exception->getCode() ?: 500;
    
    // Log exception
    Logger::error('Uncaught exception', [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
    
    // Send error response
    http_response_code($code);
    
    if ($this->isApiRequest()) {
        echo json_encode([
            'error' => $exception->getMessage(),
            'code' => $code
        ]);
    } else {
        $this->renderErrorPage($code, $exception);
    }
    
    exit;
}

private function isApiRequest()
{
    return strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') === 0 ||
           isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
}
```

---

## Related Documentation

- [Core Usage Guide](CORE_USAGE.md) - More core classes
- [Security Hardening](SECURITY_HARDENING.md) - Security best practices
- [API Reference](API_REFERENCE.md) - API development
- [Middleware Guide](MIDDLEWARE_GUIDE.md) - Request handling

---

**Last Updated:** January 2026  
**Version:** 2.0
