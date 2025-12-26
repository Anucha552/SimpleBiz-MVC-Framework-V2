<?php
/**
 * WEB ROUTES
 * 
 * Purpose: Define web application routes (HTML responses)
 * 
 * Route Definition:
 * $router->get('/path', 'Controller@method', [Middleware::class]);
 * 
 * Available Methods:
 * - get()    - GET requests
 * - post()   - POST requests
 * - put()    - PUT requests
 * - delete() - DELETE requests
 * 
 * Middleware:
 * - AuthMiddleware - Requires user authentication
 * - ApiKeyMiddleware - Requires API key (typically for API routes)
 * 
 * Route Parameters:
 * Use {param} for dynamic segments
 * Example: /products/{id} matches /products/123
 */

use App\Core\Router;
use App\Middleware\AuthMiddleware;

// Home
$router->get('/', 'App\Controllers\HomeController@index');

// Authentication
$router->get('/register', 'App\Controllers\AuthController@showRegister');
$router->post('/register', 'App\Controllers\AuthController@register');
$router->get('/login', 'App\Controllers\AuthController@showLogin');
$router->post('/login', 'App\Controllers\AuthController@login');
$router->get('/logout', 'App\Controllers\AuthController@logout');

// Products (Public)
$router->get('/products', 'App\Controllers\Ecommerce\ProductController@index');
$router->get('/products/{id}', 'App\Controllers\Ecommerce\ProductController@show');

// Cart (Requires Authentication)
$router->get('/cart', 'App\Controllers\Ecommerce\CartController@index', [AuthMiddleware::class]);
$router->post('/cart/add', 'App\Controllers\Ecommerce\CartController@add', [AuthMiddleware::class]);
$router->post('/cart/update', 'App\Controllers\Ecommerce\CartController@update', [AuthMiddleware::class]);
$router->post('/cart/remove', 'App\Controllers\Ecommerce\CartController@remove', [AuthMiddleware::class]);

// Orders (Requires Authentication)
$router->get('/checkout', 'App\Controllers\Ecommerce\OrderController@checkout', [AuthMiddleware::class]);
$router->post('/order/confirm', 'App\Controllers\Ecommerce\OrderController@confirm', [AuthMiddleware::class]);
$router->get('/orders', 'App\Controllers\Ecommerce\OrderController@index', [AuthMiddleware::class]);
$router->get('/orders/{id}', 'App\Controllers\Ecommerce\OrderController@show', [AuthMiddleware::class]);
