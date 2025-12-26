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

// หน้าแรก
$router->get('/', 'App\Controllers\HomeController@index');

// การยืนยันตัวตน
$router->get('/register', 'App\Controllers\AuthController@showRegister');
$router->post('/register', 'App\Controllers\AuthController@register');
$router->get('/login', 'App\Controllers\AuthController@showLogin');
$router->post('/login', 'App\Controllers\AuthController@login');
$router->get('/logout', 'App\Controllers\AuthController@logout');

// สินค้า (สาธารณะ)
$router->get('/products', 'App\Controllers\Ecommerce\ProductController@index');
$router->get('/products/{id}', 'App\Controllers\Ecommerce\ProductController@show');

// ตะกร้าสินค้า (ต้องยืนยันตัวตน)
$router->get('/cart', 'App\Controllers\Ecommerce\CartController@index', [AuthMiddleware::class]);
$router->post('/cart/add', 'App\Controllers\Ecommerce\CartController@add', [AuthMiddleware::class]);
$router->post('/cart/update', 'App\Controllers\Ecommerce\CartController@update', [AuthMiddleware::class]);
$router->post('/cart/remove', 'App\Controllers\Ecommerce\CartController@remove', [AuthMiddleware::class]);

// คำสั่งซื้อ (ต้องยืนยันตัวตน)
$router->get('/checkout', 'App\Controllers\Ecommerce\OrderController@checkout', [AuthMiddleware::class]);
$router->post('/order/confirm', 'App\Controllers\Ecommerce\OrderController@confirm', [AuthMiddleware::class]);
$router->get('/orders', 'App\Controllers\Ecommerce\OrderController@index', [AuthMiddleware::class]);
$router->get('/orders/{id}', 'App\Controllers\Ecommerce\OrderController@show', [AuthMiddleware::class]);
