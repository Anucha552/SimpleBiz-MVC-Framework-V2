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

// โหลด Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// โหลดตัวแปรสภาพแวดล้อมจากไฟล์ .env
if (file_exists(__DIR__ . '/../.env')) {
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
