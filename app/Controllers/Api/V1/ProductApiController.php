<?php
/**
 * ตัวควบคุม API สินค้า (V1)
 * 
 * จุดประสงค์: RESTful API สำหรับจัดการสินค้า
 * Base URL: /api/v1/products
 * 
 * Endpoints:
 * - GET /api/v1/products → แสดงรายการสินค้าทั้งหมด
 * - GET /api/v1/products/{id} → ดึงรายละเอียดสินค้า
 * - GET /api/v1/products/search → ค้นหาสินค้า
 * 
 * รูปแบบการตอบกลับ:
 * {
 *   "success": true|false,
 *   "data": {...},
 *   "message": "...",
 *   "errors": [...]
 * }
 * 
 * ตัวควบคุมนี้คืนค่าเป็น JSON เท่านั้น (ไม่มี HTML)
 */

namespace App\Controllers\Api\V1;

use App\Core\Controller;
use App\Models\Product;

class ProductApiController extends Controller
{
    private Product $productModel;

    public function __construct()
    {
        $this->productModel = new Product();
    }

    /**
     * GET /api/v1/products
     * แสดงรายการสินค้าทั้งหมด
     */
    public function index(): void
    {
        $products = $this->productModel->getAll();

        $this->json(true, $products, 'Products retrieved successfully');
    }

    /**
     * GET /api/v1/products/{id}
     * ดึงข้อมูลสินค้าจาก ID
     * 
     * @param string $id รหัสสินค้า
     */
    public function show(string $id): void
    {
        $productId = $this->validateInt($id);

        if (!$productId) {
            $this->json(false, null, 'Invalid product ID', ['id' => 'Must be a positive integer'], 400);
            return;
        }

        $product = $this->productModel->findById($productId);

        if (!$product) {
            $this->json(false, null, 'Product not found', [], 404);
            return;
        }

        $this->json(true, $product, 'Product retrieved successfully');
    }

    /**
     * GET /api/v1/products/search?q=keyword
     * ค้นหาสินค้า
     */
    public function search(): void
    {
        $query = $_GET['q'] ?? '';

        if (empty($query)) {
            $this->json(false, null, 'Search query is required', ['q' => 'Required'], 400);
            return;
        }

        $products = $this->productModel->search($query);

        $this->json(true, [
            'query' => $query,
            'results' => $products,
            'count' => count($products),
        ], 'Search completed successfully');
    }
}
