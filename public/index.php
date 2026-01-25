<?php
/**
 * จุดเริ่มต้นของแอปพลิเคชัน
 * 
 * จุดประสงค์: Front controller - คำขอทั้งหมดไหลผ่านไฟล์นี้
 * 
 * หน้าที่รับผิดชอบ:
 * 1. โหลด dependencies (Composer autoloader)
 * 2. โหลดตัวแปรสภาพแวดล้อม
 * 3. ตั้งค่าการรายงานข้อผิดพลาดตามสภาพแวดล้อม
 * 4. เริ่ม session
 * 5. สร้าง router และโหลด routes
 * 6. ส่งคำขอไปยัง controller ที่เหมาะสม
 * 
 * วิธีการทำงาน:
 * - Web server (Apache/Nginx) เปลี่ยนเส้นทางคำขอทั้งหมดมาที่นี่
 * - Router จับคู่ URL กับ controller
 * - Controller จัดการคำขอและส่งคืนการตอบกลับ
 * 
 * ความปลอดภัย:
 * - มีเพียงไฟล์นี้เท่านั้นที่ควรอยู่ในโฟลเดอร์ public
 * - ไฟล์อื่นๆ ทั้งหมดอยู่นอก web root (ได้รับการปกป้อง)
 * - การแสดงข้อผิดพลาดถูกปิดในสภาพแวดล้อมจริง
 */

// เริ่ม session ให้ใช้งานได้แม้ก่อนโหลด autoloader
// (ใช้สำหรับหน้า setup/errors ก่อน require vendor/autoload.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบว่า Composer dependencies ถูกติดตั้งแล้วหรือไม่
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    // แสดงข้อความแนะนำที่เป็นมิตรสำหรับนักพัฒนา
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/png" href="assets/img/logo.png">
        <title>Setup Required - SimpleBiz MVC Framework</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background: #667eea;
                color: #333;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 600px;
                width: 100%;
                padding: 40px;
            }
            h1 {
                color: #667eea;
                margin-bottom: 10px;
                font-size: 28px;
                text-align: center;
            }
            .error {
                background: #fff3cd;
                border-left: 4px solid #ffc107;
                padding: 20px;
                margin: 25px 0;
                border-radius: 4px;
            }
            .error-title {
                font-weight: bold;
                color: #856404;
                margin-bottom: 10px;
                font-size: 16px;
            }
            .error-message {
                color: #856404;
                line-height: 1.6;
                margin-bottom: 15px;
            }
            .command {
                background: #2d2d2d;
                color: #f8f8f2;
                padding: 12px 16px;
                border-radius: 4px;
                font-family: "Courier New", Courier, monospace;
                margin-top: 10px;
                font-size: 14px;
            }
            a {
                color: #667eea;
                text-decoration: none;
                font-weight: 500;
            }
            a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="container">
            
            <h1>Setup Required</h1>
            
            <div class="error">
                <div class="error-title">Composer Dependencies ยังไม่ได้ติดตั้ง</div>
                <div class="error-message">
                    ไม่พบไฟล์ <strong>vendor/autoload.php</strong><br>
                    กรุณารันคำสั่งใดคำสั่ง
                </div>
                <div class="command">php console setup</div>
                <div style="color: #856404; font-size: 13px; margin-top: 8px;">
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// โหลด Composer autoloader
require_once $autoloadPath;

// เริ่ม session ด้วยค่าเริ่มต้นที่ปลอดภัย (หลังจากโหลด autoloader แล้ว)
App\Core\Session::start();

// ตรวจสอบไฟล์ .env
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    // แสดงหน้าแจ้งเตือนไฟล์ .env ไม่มี
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/png" href="assets/img/logo.png">
        <title>Setup Required - SimpleBiz MVC Framework</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background: #667eea;
                color: #333;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 600px;
                width: 100%;
                padding: 40px;
            }
            h1 {
                color: #667eea;
                margin-bottom: 10px;
                font-size: 28px;
                text-align: center;
            }
            .error {
                background: #fff3cd;
                border-left: 4px solid #ffc107;
                padding: 20px;
                margin: 25px 0;
                border-radius: 4px;
            }
            .error-title {
                font-weight: bold;
                color: #856404;
                margin-bottom: 10px;
                font-size: 16px;
            }
            .error-message {
                color: #856404;
                line-height: 1.6;
                margin-bottom: 15px;
            }
            .command {
                background: #2d2d2d;
                color: #f8f8f2;
                padding: 12px 16px;
                border-radius: 4px;
                font-family: "Courier New", Courier, monospace;
                margin-top: 10px;
                font-size: 14px;
            }
            a {
                color: #667eea;
                text-decoration: none;
                font-weight: 500;
            }
            a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Setup Required</h1>
            
            <div class="error">
                <div class="error-title">ไม่พบไฟล์ .env</div>
                <div class="error-message">
                    ไฟล์ <strong>.env</strong> ยังไม่ได้สร้าง<br>
                    กรุณารันคำสั่งใดคำสั่ง
                </div>
                <div class="command">php console setup</div>
                <div style="color: #856404; font-size: 13px; margin-top: 8px;">
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// โหลดตัวแปรสภาพแวดล้อมจากไฟล์ .env
if (file_exists($envPath)) {
    // Prefer vlucas/phpdotenv (รองรับ quotes/spacing ได้ดีกว่า)
    if (class_exists(\Dotenv\Dotenv::class)) { 
        $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->safeLoad();
    } else {
        // Fallback loader แบบง่าย
        $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                if (!getenv($key)) {
                    putenv("{$key}={$value}");
                }
            }
        }
    }
}

// โหลดการตั้งค่าแอปพลิเคชัน
$config = require __DIR__ . '/../config/app.php';

// ตั้งค่าการรายงานข้อผิดพลาดตามสภาพแวดล้อม
if ($config['env'] === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// ตั้งค่า timezone
date_default_timezone_set($config['timezone']);

// ===== Global error/exception handlers =====
$logger = new App\Core\Logger();

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    // หากข้อผิดพลาดถูกปิดใช้งานโดยการตั้งค่า error_reporting ปัจจุบัน ให้ข้ามไป
    if (!(error_reporting() & $severity)) {
        return false;
    }

    throw new \ErrorException($message, 0, $severity, $file, $line);
});

// จัดการข้อยกเว้นที่ไม่ได้จับ
set_exception_handler(static function (\Throwable $e) use ($config, $logger): void {
    $logger->error('application.exception', [
        'type' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);

    $message = '';
    if (($config['env'] ?? 'development') !== 'production') {
        $message = $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    }

    App\Core\ErrorHandler::serverError($message);
});

// จัดการข้อผิดพลาดร้ายแรงก่อนปิดโปรแกรม
register_shutdown_function(static function () use ($config, $logger): void {
    $error = error_get_last(); // ดึงข้อผิดพลาดล่าสุดจาก shutdown function
    
    // ถ้าไม่มีข้อผิดพลาด ให้คืนค่า
    if (!$error) {
        return;
    }

    // ตรวจสอบว่าข้อผิดพลาดเป็นประเภทร้ายแรงหรือไม่
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if (!in_array($error['type'] ?? 0, $fatalTypes, true)) {
        return;
    }

    // บันทึกข้อผิดพลาดร้ายแรงในล็อก
    $logger->error('application.fatal', [
        'type' => $error['type'] ?? null,
        'message' => $error['message'] ?? '',
        'file' => $error['file'] ?? '',
        'line' => $error['line'] ?? 0,
    ]);

    // แสดงหน้าข้อผิดพลาดทั่วไป
    $message = '';
    if (($config['env'] ?? 'development') !== 'production') {
        $message = ($error['message'] ?? '') . ' in ' . ($error['file'] ?? '') . ':' . ($error['line'] ?? 0);
    }

    App\Core\ErrorHandler::serverError($message);
});

// สร้าง router instance
$router = new App\Core\Router();

// โหลดการกำหนด route
require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../routes/api.php';

// โหลด modules (ส่วนเสริม)
(new App\Core\ModuleManager())->registerEnabled($router);

// ===== Global middleware (ก่อน routing) =====
$request = new App\Core\Request();

// กำหนด middleware ทั่วไป
$globalMiddleware = [
    App\Middleware\SecurityHeadersMiddleware::class, // ตั้งค่า HTTP security headers
    App\Middleware\MaintenanceMiddleware::class, // ตรวจสอบสถานะการปิดปรับปรุงระบบ
    App\Middleware\LoggingMiddleware::class, // บันทึกคำขอเข้า
];

// เพิ่ม middleware เฉพาะสำหรับคำขอ API
$uri = $_SERVER['REQUEST_URI'] ?? '';
$isApiRequest = strpos($uri, '/api/') === 0;

// ตรวจสอบถ้าเป็นคำขอ API
if ($isApiRequest) {
    // API defaults
    array_unshift($globalMiddleware, App\Middleware\CorsMiddleware::class); // ตั้งค่า CORS สำหรับ API
    $globalMiddleware[] = App\Middleware\RateLimitMiddleware::class; // จำกัดอัตราคำขอ API
}

// เรียกใช้ global middleware
foreach ($globalMiddleware as $middlewareClass) {
    $middleware = new $middlewareClass();

    $result = $middleware->handle($request);

    if ($result instanceof App\Core\Response) {
        $result->withHeaders($request->getResponseHeaders(), false)->send();
        exit;
    }

    if ($result === false) {
        exit;
    }
}

// ส่งคำขอ (ข้อผิดพลาดจะถูกจัดการโดย global handlers)
$router->dispatch($request);
