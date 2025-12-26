<?php
/**
 * CART API CONTROLLER (V1)
 * 
 * Purpose: RESTful API for cart management
 * Base URL: /api/v1/cart
 * Security: Requires authentication
 * 
 * Endpoints:
 * - GET /api/v1/cart → Get cart contents
 * - POST /api/v1/cart/add → Add item to cart
 * - PUT /api/v1/cart/update → Update item quantity
 * - DELETE /api/v1/cart/remove/{product_id} → Remove item
 * 
 * All endpoints require valid session or API key
 */

namespace App\Controllers\Api\V1;

use App\Core\Controller;
use App\Models\Cart;

class CartApiController extends Controller
{
    private Cart $cartModel;

    public function __construct()
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->cartModel = new Cart();
    }

    /**
     * GET /api/v1/cart
     * Get cart contents
     */
    public function index(): void
    {
        if (!$this->isAuthenticated()) {
            $this->json(false, null, 'Authentication required', [], 401);
            return;
        }

        $userId = $this->getUserId();
        $items = $this->cartModel->getItems($userId);
        $totals = $this->cartModel->calculateTotal($userId);

        $this->json(true, [
            'items' => $items,
            'total' => $totals['total'],
            'item_count' => $totals['item_count'],
        ], 'Cart retrieved successfully');
    }

    /**
     * POST /api/v1/cart/add
     * Add item to cart
     * 
     * Body: {"product_id": 1, "quantity": 2}
     */
    public function add(): void
    {
        if (!$this->isAuthenticated()) {
            $this->json(false, null, 'Authentication required', [], 401);
            return;
        }

        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            $input = $_POST; // Fallback to form data
        }

        // Validate required fields
        if (!isset($input['product_id']) || !isset($input['quantity'])) {
            $this->json(false, null, 'Missing required fields', [
                'product_id' => 'Required',
                'quantity' => 'Required',
            ], 400);
            return;
        }

        $productId = $this->validateInt($input['product_id']);
        $quantity = $this->validateInt($input['quantity']);

        if (!$productId || !$quantity) {
            $this->json(false, null, 'Invalid input', [
                'product_id' => 'Must be positive integer',
                'quantity' => 'Must be positive integer',
            ], 400);
            return;
        }

        $userId = $this->getUserId();
        $result = $this->cartModel->addItem($userId, $productId, $quantity);

        if ($result['success']) {
            $this->json(true, null, $result['message'], [], 201);
        } else {
            $this->json(false, null, $result['message'], [], 400);
        }
    }

    /**
     * PUT /api/v1/cart/update
     * Update item quantity
     * 
     * Body: {"product_id": 1, "quantity": 3}
     */
    public function update(): void
    {
        if (!$this->isAuthenticated()) {
            $this->json(false, null, 'Authentication required', [], 401);
            return;
        }

        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            $input = $_POST;
        }

        if (!isset($input['product_id']) || !isset($input['quantity'])) {
            $this->json(false, null, 'Missing required fields', [
                'product_id' => 'Required',
                'quantity' => 'Required',
            ], 400);
            return;
        }

        $productId = $this->validateInt($input['product_id']);
        $quantity = $this->validateInt($input['quantity']);

        if (!$productId || $quantity === null) {
            $this->json(false, null, 'Invalid input', [], 400);
            return;
        }

        $userId = $this->getUserId();
        $result = $this->cartModel->updateQuantity($userId, $productId, $quantity);

        if ($result['success']) {
            $this->json(true, null, $result['message']);
        } else {
            $this->json(false, null, $result['message'], [], 400);
        }
    }

    /**
     * DELETE /api/v1/cart/remove/{product_id}
     * Remove item from cart
     * 
     * @param string $productId Product ID
     */
    public function remove(string $productId): void
    {
        if (!$this->isAuthenticated()) {
            $this->json(false, null, 'Authentication required', [], 401);
            return;
        }

        $productIdInt = $this->validateInt($productId);

        if (!$productIdInt) {
            $this->json(false, null, 'Invalid product ID', [], 400);
            return;
        }

        $userId = $this->getUserId();
        $result = $this->cartModel->removeItem($userId, $productIdInt);

        if ($result['success']) {
            $this->json(true, null, $result['message']);
        } else {
            $this->json(false, null, $result['message'], [], 404);
        }
    }
}
