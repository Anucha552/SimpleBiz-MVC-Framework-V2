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

// ===================================
// API Routes
// ===================================

// System/Health endpoints (public)
$router->get('/api/health', 'App\\Controllers\\Api\\V1\\SystemController@health');
$router->get('/api/v1/ping', 'App\\Controllers\\Api\\V1\\SystemController@ping');
// 
// คุณสามารถเพิ่ม API routes ใหม่ที่นี่
// ตัวอย่าง:
//
// Public API (ไม่ต้อง auth):
// $router->get('/api/v1/posts', 'App\Controllers\Api\V1\PostApiController@index');
// $router->get('/api/v1/posts/{id}', 'App\Controllers\Api\V1\PostApiController@show');
//
// Protected API (ต้อง auth):
// $router->post('/api/v1/posts', 'App\Controllers\Api\V1\PostApiController@create', [AuthMiddleware::class]);
// $router->put('/api/v1/posts/{id}', 'App\Controllers\Api\V1\PostApiController@update', [AuthMiddleware::class]);
// $router->delete('/api/v1/posts/{id}', 'App\Controllers\Api\V1\PostApiController@delete', [AuthMiddleware::class]);
//
// API with API Key:
// $router->post('/api/v1/sensitive', 'App\Controllers\Api\V1\DataController@create', [
//     AuthMiddleware::class,
//     ApiKeyMiddleware::class,
// ]);
//
