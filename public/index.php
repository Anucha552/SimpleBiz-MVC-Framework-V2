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

// เริ่ม session
session_start();

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
        <title>Setup Required - SimpleBiz MVC Framework</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            .icon {
                width: 80px;
                height: 80px;
                margin: 0 auto 20px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 40px;
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
            .docs {
                background: #d1ecf1;
                border-left: 4px solid #17a2b8;
                padding: 20px;
                border-radius: 4px;
            }
            .docs-title {
                font-weight: bold;
                color: #0c5460;
                margin-bottom: 10px;
            }
            .docs-links {
                color: #0c5460;
                line-height: 2;
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
            <div class="icon">⚠️</div>
            <h1>Setup Required</h1>
            
            <div class="error">
                <div class="error-title">Composer Dependencies ยังไม่ได้ติดตั้ง</div>
                <div class="error-message">
                    ไม่พบไฟล์ <strong>vendor/autoload.php</strong><br>
                    กรุณารันคำสั่งใดคำสั่งหนึ่งด้านล่างนี้:
                </div>
                <div class="command">php console setup</div>
                <div style="color: #856404; font-size: 13px; margin-top: 8px;">
                    แนะนำ: ตั้งค่าโปรเจกต์ครบถ้วนอัตโนมัติ
                </div>
                <div style="color: #856404; margin: 15px 0 8px; font-size: 14px;">หรือ</div>
                <div class="command">composer install</div>
                <div style="color: #856404; font-size: 13px; margin-top: 8px;">
                    ติดตั้งแค่ dependencies
                </div>
            </div>

            <div class="docs">
                <div class="docs-title">📚 อ่านเอกสารเพิ่มเติม</div>
                <div class="docs-links">
                    → <a href="https://github.com/Anucha552/SimpleBiz-MVC-Framework-V2/blob/main/docs/QUICK_START.md" target="_blank">
                        คู่มือเริ่มต้นใช้งาน (Quick Start)
                    </a><br>
                    → <a href="https://github.com/Anucha552/SimpleBiz-MVC-Framework-V2/blob/main/docs/SETUP_COMMAND.md" target="_blank">
                        รายละเอียดคำสั่ง setup
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// โหลด Composer autoloader
require_once $autoloadPath;

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
        <title>Setup Required - SimpleBiz MVC Framework</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            .icon {
                width: 80px;
                height: 80px;
                margin: 0 auto 20px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 40px;
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
            .docs {
                background: #d1ecf1;
                border-left: 4px solid #17a2b8;
                padding: 20px;
                border-radius: 4px;
            }
            .docs-title {
                font-weight: bold;
                color: #0c5460;
                margin-bottom: 10px;
            }
            .docs-links {
                color: #0c5460;
                line-height: 2;
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
            <div class="icon">⚙️</div>
            <h1>Setup Required</h1>
            
            <div class="error">
                <div class="error-title">ไม่พบไฟล์ .env</div>
                <div class="error-message">
                    ไฟล์ <strong>.env</strong> ยังไม่ได้สร้าง<br>
                    กรุณารันคำสั่งใดคำสั่งหนึ่งด้านล่างนี้:
                </div>
                <div class="command">php console setup</div>
                <div style="color: #856404; font-size: 13px; margin-top: 8px;">
                    แนะนำ: ตั้งค่าโปรเจกต์ครบถ้วนอัตโนมัติ (สร้าง .env พร้อม APP_KEY)
                </div>
                <div style="color: #856404; margin: 15px 0 8px; font-size: 14px;">หรือ</div>
                <div class="command">cp .env.example .env</div>
                <div style="color: #856404; font-size: 13px; margin-top: 8px;">
                    คัดลอกไฟล์ .env.example (แล้วแก้ไข database และ APP_KEY)
                </div>
            </div>

            <div class="docs">
                <div class="docs-title">📚 อ่านเอกสารเพิ่มเติม</div>
                <div class="docs-links">
                    → <a href="https://github.com/Anucha552/SimpleBiz-MVC-Framework-V2/blob/main/docs/QUICK_START.md" target="_blank">
                        คู่มือเริ่มต้นใช้งาน (Quick Start)
                    </a><br>
                    → <a href="https://github.com/Anucha552/SimpleBiz-MVC-Framework-V2/blob/main/docs/ENVIRONMENTS.md" target="_blank">
                        การตั้งค่า Environment Variables
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// โหลดตัวแปรสภาพแวดล้อมจากไฟล์ .env
if (file_exists($envPath)) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // ข้าม comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // แยก KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // ตั้งค่าตัวแปรสภาพแวดล้อม
            if (!getenv($key)) {
                putenv("{$key}={$value}");
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

// สร้าง router instance
$router = new App\Core\Router();

// โหลดการกำหนด route
require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../routes/api.php';

// จัดการข้อผิดพลาดอย่างเหมาะสม
try {
    // ส่งคำขอ
    $router->dispatch();
} catch (\Exception $e) {
    // บันทึกข้อผิดพลาด
    $logger = new App\Core\Logger();
    $logger->error('application.exception', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);

    // แสดงข้อผิดพลาดตามสภาพแวดล้อม
    if ($config['env'] === 'production') {
        // สภาพแวดล้อมจริง: แสดงข้อความทั่วไป
        http_response_code(500);
        
        // ตรวจสอบว่าเป็นคำขอ API หรือไม่
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/api/') === 0) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred. Please try again later.',
            ]);
        } else {
            echo "<h1>500 - Internal Server Error</h1>";
            echo "<p>Something went wrong. Please try again later.</p>";
        }
    } else {
        // สภาพแวดล้อมพัฒนา: แสดงข้อผิดพลาดโดยละเอียด
        http_response_code(500);
        echo "<h1>Application Error</h1>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "<h2>Stack Trace:</h2>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
}
