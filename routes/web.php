<?php
/**
 * เส้นทาง WEB
 * 
 * จุดประสงค์: กำหนดเส้นทางของเว็บแอปพลิเคชัน (การตอบกลับแบบ HTML)
 * 
 * การกำหนดเส้นทาง:
 * $router->get('/path', $webBasePath . 'Controller@method', [Middleware::class]);
 * 
 * เมธอดที่ใช้ได้:
 * - get()    - คำขอ GET
 * - post()   - คำขอ POST
 * - put()    - คำขอ PUT
 * - delete() - คำขอ DELETE
 * 
 * พารามิเตอร์ของเส้นทาง:
 * ใช้ {param} สำหรับส่วนที่เปลี่ยนแปลงได้
 * ตัวอย่าง: /products/{id} จับคู่กับ /products/123
 */

use App\Core\Router;
use App\Middleware\AuthMiddleware;

// กำหนดตัวแปร Path เริ่มต้นสำหรับ Web
$webBasePath = 'App\\Controllers\\Web\\';

// ===================================
// เส้นทาง Web Routes
// ===================================

// หน้าแรก - Welcome Page

$router->get('/', $webBasePath . 'WebController@index');

// ตัวอย่างการใช้งาน Assets
$router->get('/assets-demo', $webBasePath . 'WebController@assetsDemo');

// PHP Info (สำหรับ development เท่านั้น)
$router->get('/phpinfo', $webBasePath . 'WebController@phpinfo');

$router->get('/test', $webBasePath . 'testController@index');