<?php
/**
 * PRODUCT API CONTROLLER (V1)
 * 
 * Purpose: RESTful API for product management
 * Base URL: /api/v1/products
 * 
 * Endpoints:
 * - GET /api/v1/products → List all products
 * - GET /api/v1/products/{id} → Get product details
 * - GET /api/v1/products/search → Search products
 * 
 * Response Format:
 * {
 *   "success": true|false,
 *   "data": {...},
 *   "message": "...",
 *   "errors": [...]
 * }
 * 
 * This controller returns JSON only (no HTML)
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
     * List all products
     */
    public function index(): void
    {
        $products = $this->productModel->getAll();

        $this->json(true, $products, 'Products retrieved successfully');
    }

    /**
     * GET /api/v1/products/{id}
     * Get product by ID
     * 
     * @param string $id Product ID
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
     * Search products
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
