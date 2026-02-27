<?php
/**
 * เส้นทาง WEB
 * 
 * จุดประสงค์: กำหนดเส้นทางของเว็บแอปพลิเคชัน (การตอบกลับแบบ HTML)
 * 
 * การกำหนดเส้นทาง:
 * $router->get('/path', $webBasePath . 'Controller@method');
 * 
 * การกำหนดเส้นทางแบบมี Middleware:
 * $router->get(
 *  '/path',
 *   $webBasePath . 'Controller@method',
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
 */

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;

// กำหนดตัวแปร Path เริ่มต้นสำหรับ Web
$webBasePath = 'App\\Controllers\\Web\\';

// ===================================
// เส้นทาง Web Routes
// ===================================

$router->get('/', $webBasePath . 'WebController@index');

// แสดงรายการพนักงานทั้งหมด (หน้า List)
$router->get('/employees',  $webBasePath . 'EmployeeController@index');

// แสดงหน้า Form สำหรับเพิ่มพนักงานใหม่
$router->get('/employees/create', $webBasePath . 'EmployeeController@create');

// รับข้อมูลจาก Form แล้วบันทึกพนักงานใหม่ลงฐานข้อมูล
$router->post('/employees', $webBasePath . 'EmployeeController@store', [
    CsrfMiddleware::class
]);

// แสดงรายละเอียดพนักงาน 1 คน ตาม id

// ค้นหาพนักงานตามชื่อหรือแผนก (ต้องกำหนดก่อน route ที่มี {id} เพื่อไม่ให้คำว่า "search" ถูกจับเป็น id)
$router->get('/employees/search', $webBasePath . 'EmployeeController@search');

// แสดงรายละเอียดพนักงาน 1 คน ตาม id
$router->get('/employees/{id}', $webBasePath . 'EmployeeController@show');

// แสดงหน้า Form สำหรับแก้ไขข้อมูลพนักงาน
$router->get('/employees/{id}/edit', $webBasePath . 'EmployeeController@edit');

// รับข้อมูลที่แก้ไขแล้ว แล้วอัปเดตลงฐานข้อมูล
$router->post('/employees/{id}/update', $webBasePath . 'EmployeeController@update', [
    CsrfMiddleware::class
]);

// ลบพนักงานตาม id (มาจากปุ่มลบในหน้าเว็บ)
$router->post('/employees/{id}/delete', $webBasePath . 'EmployeeController@destroy', [
    CsrfMiddleware::class
]);

