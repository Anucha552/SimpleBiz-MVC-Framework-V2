<?php
/**
 * API ROUTES (Version 1)
 * 
 * Purpose: RESTful API endpoints (JSON responses)
 * Base Path: /api/v1
 * 
 * Response Format:
 * {
 *   "success": true|false,
 *   "data": {...},
 *   "message": "...",
 *   "errors": [...]
 * }
 * 
 * Authentication:
 * - Most endpoints require user authentication (session)
 * - Sensitive endpoints require API key
 * 
 * Middleware:
 * - AuthMiddleware: Validates user session
 * - ApiKeyMiddleware: Validates API key
 * 
 * HTTP Methods:
 * - GET: Retrieve data
 * - POST: Create new resource
 * - PUT: Update existing resource
 * - DELETE: Delete resource
 */

use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\ApiKeyMiddleware;

// ============================================================
// PRODUCTS API
// ============================================================

// Public endpoints (no auth required)
$router->get('/api/v1/products', 'App\Controllers\Api\V1\ProductApiController@index');
$router->get('/api/v1/products/{id}', 'App\Controllers\Api\V1\ProductApiController@show');
$router->get('/api/v1/products/search', 'App\Controllers\Api\V1\ProductApiController@search');

// ============================================================
// CART API (Requires Authentication)
// ============================================================

$router->get('/api/v1/cart', 'App\Controllers\Api\V1\CartApiController@index', [AuthMiddleware::class]);
$router->post('/api/v1/cart/add', 'App\Controllers\Api\V1\CartApiController@add', [AuthMiddleware::class]);
$router->put('/api/v1/cart/update', 'App\Controllers\Api\V1\CartApiController@update', [AuthMiddleware::class]);
$router->delete('/api/v1/cart/remove/{product_id}', 'App\Controllers\Api\V1\CartApiController@remove', [AuthMiddleware::class]);

// ============================================================
// ORDERS API (Requires Authentication + API Key)
// ============================================================

// List and view orders (auth only)
$router->get('/api/v1/orders', 'App\Controllers\Api\V1\OrderApiController@index', [AuthMiddleware::class]);
$router->get('/api/v1/orders/{id}', 'App\Controllers\Api\V1\OrderApiController@show', [AuthMiddleware::class]);

// Create and modify orders (auth + API key)
$router->post('/api/v1/orders/create', 'App\Controllers\Api\V1\OrderApiController@create', [
    AuthMiddleware::class,
    ApiKeyMiddleware::class,
]);

$router->put('/api/v1/orders/{id}/status', 'App\Controllers\Api\V1\OrderApiController@updateStatus', [
    AuthMiddleware::class,
    ApiKeyMiddleware::class,
]);
