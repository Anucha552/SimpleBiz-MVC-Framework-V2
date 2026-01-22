<?php
/**
 * เส้นทาง WEB
 * 
 * จุดประสงค์: กำหนดเส้นทางของเว็บแอปพลิเคชัน (การตอบกลับแบบ HTML)
 * 
 * การกำหนดเส้นทาง:
 * $router->get('/path', 'Controller@method', [Middleware::class]);
 * 
 * เมธอดที่ใช้ได้:
 * - get()    - คำขอ GET
 * - post()   - คำขอ POST
 * - put()    - คำขอ PUT
 * - delete() - คำขอ DELETE
 * 
 * Middleware:
 * - AuthMiddleware - ต้องมีการยืนยันตัวตนผู้ใช้
 * - ApiKeyMiddleware - ต้องมี API key (โดยทั่วไปสำหรับเส้นทาง API)
 * 
 * พารามิเตอร์ของเส้นทาง:
 * ใช้ {param} สำหรับส่วนที่เปลี่ยนแปลงได้
 * ตัวอย่าง: /products/{id} จับคู่กับ /products/123
 */

use App\Core\Router;
use App\Middleware\AuthMiddleware;

// ===================================
// เส้นทาง Web Routes
// ===================================

// หน้าแรก - Welcome Page
$router->get('/', 'App\Controllers\HomeController@index');

// ตัวอย่างการใช้งาน Assets
$router->get('/assets-demo', 'App\Controllers\HomeController@assetsDemo');

// PHP Info (สำหรับ development เท่านั้น)
$router->get('/phpinfo', 'App\Controllers\HomeController@phpinfo');

// ===================================
// เพิ่ม routes ของคุณที่นี่
// ===================================
// 
// ตัวอย่าง:
// $router->get('/about', 'App\Controllers\PageController@about');
// $router->get('/contact', 'App\Controllers\PageController@contact');
//
// Routes ที่ต้องการ authentication:
// $router->get('/dashboard', 'App\Controllers\DashboardController@index', [AuthMiddleware::class]);
//
