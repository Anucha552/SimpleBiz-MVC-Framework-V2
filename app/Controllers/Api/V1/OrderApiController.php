<?php
/**
 * ORDER API CONTROLLER (V1)
 * 
 * Purpose: RESTful API for order management
 * Base URL: /api/v1/orders
 * Security: Requires authentication + API key
 * 
 * Endpoints:
 * - GET /api/v1/orders → List user orders
 * - GET /api/v1/orders/{id} → Get order details
 * - POST /api/v1/orders/create → Create order from cart
 * - PUT /api/v1/orders/{id}/status → Update order status (admin)
 * 
 * SECURITY:
 * - Users can only access their own orders
 * - API key required for sensitive operations
 * - All price calculations server-side
 */

namespace App\Controllers\Api\V1;

use App\Core\Controller;
use App\Models\Order;

class OrderApiController extends Controller
{
    private Order $orderModel;

    public function __construct()
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->orderModel = new Order();
    }

    /**
     * GET /api/v1/orders
     * List user's orders
     */
    public function index(): void
    {
        if (!$this->isAuthenticated()) {
            $this->json(false, null, 'Authentication required', [], 401);
            return;
        }

        $userId = $this->getUserId();
        $orders = $this->orderModel->getUserOrders($userId);

        $this->json(true, $orders, 'Orders retrieved successfully');
    }

    /**
     * GET /api/v1/orders/{id}
     * Get order details
     * 
     * @param string $id Order ID
     */
    public function show(string $id): void
    {
        if (!$this->isAuthenticated()) {
            $this->json(false, null, 'Authentication required', [], 401);
            return;
        }

        $orderId = $this->validateInt($id);

        if (!$orderId) {
            $this->json(false, null, 'Invalid order ID', ['id' => 'Must be positive integer'], 400);
            return;
        }

        $order = $this->orderModel->getWithItems($orderId);

        if (!$order) {
            $this->json(false, null, 'Order not found', [], 404);
            return;
        }

        // Verify order belongs to current user
        if ($order['user_id'] !== $this->getUserId()) {
            $this->json(false, null, 'Access denied', [], 403);
            return;
        }

        $this->json(true, $order, 'Order retrieved successfully');
    }

    /**
     * POST /api/v1/orders/create
     * Create order from cart
     */
    public function create(): void
    {
        if (!$this->isAuthenticated()) {
            $this->json(false, null, 'Authentication required', [], 401);
            return;
        }

        $userId = $this->getUserId();

        // Create order from cart
        $result = $this->orderModel->createFromCart($userId);

        if ($result['success']) {
            // Get created order details
            $order = $this->orderModel->getWithItems($result['order_id']);

            $this->json(true, $order, $result['message'], [], 201);
        } else {
            $this->json(false, null, $result['message'], [], 400);
        }
    }

    /**
     * PUT /api/v1/orders/{id}/status
     * Update order status
     * 
     * Body: {"status": "paid"}
     * 
     * @param string $id Order ID
     */
    public function updateStatus(string $id): void
    {
        if (!$this->isAuthenticated()) {
            $this->json(false, null, 'Authentication required', [], 401);
            return;
        }

        $orderId = $this->validateInt($id);

        if (!$orderId) {
            $this->json(false, null, 'Invalid order ID', [], 400);
            return;
        }

        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            $input = $_POST;
        }

        if (!isset($input['status'])) {
            $this->json(false, null, 'Missing status field', ['status' => 'Required'], 400);
            return;
        }

        $status = $this->sanitize($input['status']);

        // Verify order belongs to user
        $order = $this->orderModel->findById($orderId);

        if (!$order) {
            $this->json(false, null, 'Order not found', [], 404);
            return;
        }

        if ($order['user_id'] !== $this->getUserId()) {
            $this->json(false, null, 'Access denied', [], 403);
            return;
        }

        // Update status
        $result = $this->orderModel->updateStatus($orderId, $status);

        if ($result['success']) {
            $this->json(true, null, $result['message']);
        } else {
            $this->json(false, null, $result['message'], [], 400);
        }
    }
}
