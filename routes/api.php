<?php
/**
 * เส้นทาง API (เวอร์ชัน 1)
 * 
 * จุดประสงค์: จุดเชื่อมต่อ RESTful API (การตอบกลับแบบ JSON)
 * เส้นทางหลัก: /api/v1
 * 
 * รูปแบบการตอบกลับ:
 * {
 *   "success": true|false,
 *   "data": {...},
 *   "message": "...",
 *   "errors": [...]
 * }
 * 
 * การยืนยันตัวตน:
 * - จุดเชื่อมต่อส่วนใหญ่ต้องมีการยืนยันตัวตนผู้ใช้ (session)
 * - จุดเชื่อมต่อที่ละเอียดอ่อนต้องมี API key
 * 
 * Middleware:
 * - AuthMiddleware: ตรวจสอบ session ของผู้ใช้
 * - ApiKeyMiddleware: ตรวจสอบ API key
 * 
 * เมธอด HTTP:
 * - GET: ดึงข้อมูล
 * - POST: สร้างทรัพยากรใหม่
 * - PUT: อัปเดตทรัพยากรที่มีอยู่
 * - DELETE: ลบทรัพยากร
 */

use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\ApiKeyMiddleware;

// ============================================================
// API สินค้า
// ============================================================

// จุดเชื่อมต่อสาธารณะ (ไม่ต้องยืนยันตัวตน)
$router->get('/api/v1/products', 'App\Controllers\Api\V1\ProductApiController@index');
$router->get('/api/v1/products/{id}', 'App\Controllers\Api\V1\ProductApiController@show');
$router->get('/api/v1/products/search', 'App\Controllers\Api\V1\ProductApiController@search');

// ============================================================
// API ตะกร้าสินค้า (ต้องยืนยันตัวตน)
// ============================================================

$router->get('/api/v1/cart', 'App\Controllers\Api\V1\CartApiController@index', [AuthMiddleware::class]);
$router->post('/api/v1/cart/add', 'App\Controllers\Api\V1\CartApiController@add', [AuthMiddleware::class]);
$router->put('/api/v1/cart/update', 'App\Controllers\Api\V1\CartApiController@update', [AuthMiddleware::class]);
$router->delete('/api/v1/cart/remove/{product_id}', 'App\Controllers\Api\V1\CartApiController@remove', [AuthMiddleware::class]);

// ============================================================
// API คำสั่งซื้อ (ต้องยืนยันตัวตน + API Key)
// ============================================================

// แสดงรายการและดูคำสั่งซื้อ (ยืนยันตัวตนเท่านั้น)
$router->get('/api/v1/orders', 'App\Controllers\Api\V1\OrderApiController@index', [AuthMiddleware::class]);
$router->get('/api/v1/orders/{id}', 'App\Controllers\Api\V1\OrderApiController@show', [AuthMiddleware::class]);

// สร้างและแก้ไขคำสั่งซื้อ (ยืนยันตัวตน + API key)
$router->post('/api/v1/orders/create', 'App\Controllers\Api\V1\OrderApiController@create', [
    AuthMiddleware::class,
    ApiKeyMiddleware::class,
]);

$router->put('/api/v1/orders/{id}/status', 'App\Controllers\Api\V1\OrderApiController@updateStatus', [
    AuthMiddleware::class,
    ApiKeyMiddleware::class,
]);
