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
use App\Middleware\CsrfMiddleware;

// กำหนดตัวแปร Path เริ่มต้นสำหรับ API 
$apiBasePath = 'App\\Controllers\\Api\\';

// ===================================
// เส้นทาง API Routes
// ===================================

$router->get('/api', $apiBasePath . 'ApiController@index');

// ดึงรายการพนักงานทั้งหมด (ส่งออกเป็น JSON)
$router->get('/api/employees', $apiBasePath . 'ApiEmployeeController@index', [
    AuthMiddleware::class, // ต้องเข้าสู่ระบบก่อนถึงจะดูรายชื่อพนักงานได้
]);

// ดึงข้อมูลพนักงาน 1 คน ตาม id (JSON)
$router->get('/api/employees/{id}', $apiBasePath . 'ApiEmployeeController@show', [
    AuthMiddleware::class, // ต้องเข้าสู่ระบบก่อนถึงจะดูรายละเอียดพนักงานได้
]);

// สร้างพนักงานใหม่ (รับข้อมูลแบบ JSON)
$router->post('/api/employees', $apiBasePath . 'ApiEmployeeController@store', [
    AuthMiddleware::class, // ต้องเข้าสู่ระบบก่อนถึงจะเพิ่มพนักงานได้
    [CsrfMiddleware::class, true], // ตรวจ CSRF และตอบ JSON เมื่อ token ไม่ผ่าน
]);

// อัปเดตข้อมูลพนักงานทั้งหมด (PUT = แก้ไขทั้ง resource)
$router->put('/api/employees/{id}', $apiBasePath . 'ApiEmployeeController@update', [
    AuthMiddleware::class, // ต้องเข้าสู่ระบบก่อนถึงจะอัปเดตพนักงานได้
    [CsrfMiddleware::class, true],
]);

// ลบพนักงานตาม id (DELETE method)
$router->delete('/api/employees/{id}', $apiBasePath . 'ApiEmployeeController@destroy', [
    AuthMiddleware::class, // ต้องเข้าสู่ระบบก่อนถึงจะลบพนักงานได้
    [CsrfMiddleware::class, true],
]);