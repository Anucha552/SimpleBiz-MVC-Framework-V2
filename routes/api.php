<?php
/**
 * เส้นทาง API 
 * 
 * จุดประสงค์: กำหนดเส้นทางของเว็บแอปพลิเคชัน (การตอบกลับแบบ JSON)
 * 
 * การกำหนดเส้นทาง:
 * $router->get('/api/path', $apiBasePath . 'Controller@method', [Middleware::class]);
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
 * 
 */

use App\Middleware\AuthMiddleware;
use App\Middleware\ApiKeyMiddleware;

// กำหนดตัวแปร Path เริ่มต้นสำหรับ API 
$apiBasePath = 'App\\Controllers\\Api\\';

// ===================================
// เส้นทาง API Routes
// ===================================

$router->get('/api', $apiBasePath . 'ApiController@index',[
    ApiKeyMiddleware::class
]);