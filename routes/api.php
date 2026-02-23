<?php
/**
 * เส้นทาง API 
 * 
 * จุดประสงค์: กำหนดเส้นทางของเว็บแอปพลิเคชัน (การตอบกลับแบบ JSON)
 * 
 * การกำหนดเส้นทาง:
 * $router->get('/api/path', $apiBasePath . 'Controller@method');
 * 
 * การกำหนดเส้นทางแบบมี Middleware:
 * $router->get(
 *   '/api/path',
 *   $apiBasePath . 'Controller@method',
 *   [
 *     AuthMiddleware::class,
 *     [RoleMiddleware::class, ['admin', 'manager'], 10, true],
 *   ]
 * );
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

// กำหนดตัวแปร Path เริ่มต้นสำหรับ API 
$apiBasePath = 'App\\Controllers\\Api\\';

// ===================================
// เส้นทาง API Routes
// ===================================

$router->get('/api', $apiBasePath . 'ApiController@index');

// ดึงรายการพนักงานทั้งหมด (ส่งออกเป็น JSON)
$router->get('/api/employees', $apiBasePath . 'ApiEmployeeController@index');

// ดึงข้อมูลพนักงาน 1 คน ตาม id (JSON)
$router->get('/api/employees/{id}', $apiBasePath . 'ApiEmployeeController@show');

// สร้างพนักงานใหม่ (รับข้อมูลแบบ JSON)
$router->post('/api/employees', $apiBasePath . 'ApiEmployeeController@store');

// อัปเดตข้อมูลพนักงานทั้งหมด (PUT = แก้ไขทั้ง resource)
$router->put('/api/employees/{id}', $apiBasePath . 'ApiEmployeeController@update');

// ลบพนักงานตาม id (DELETE method)
$router->delete('/api/employees/{id}', $apiBasePath . 'ApiEmployeeController@destroy');