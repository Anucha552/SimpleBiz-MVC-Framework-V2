<?php
/**
 * ตัวควบคุม API ตะกร้าสินค้า (V1)
 * 
 * จุดประสงค์: RESTful API สำหรับจัดการตะกร้าสินค้า
 * Base URL: /api/v1/cart
 * ความปลอดภัย: ต้องการการยืนยันตัวตน
 * 
 * Endpoints:
 * - GET /api/v1/cart → ดึงข้อมูลในตะกร้าสินค้า
 * - POST /api/v1/cart/add → เพิ่มสินค้าลงตะกร้า
 * - PUT /api/v1/cart/update → อัปเดตจำนวนสินค้า
 * - DELETE /api/v1/cart/remove/{product_id} → ลบสินค้า
 * 
 * ทุก endpoints ต้องใช้เซสชันหรือ API key ที่ถูกต้อง
 */

namespace App\Controllers\Api\V1;

use App\Core\Controller;
use App\Models\Cart;

class CartApiController extends Controller
{
    private Cart $cartModel;

    public function __construct()
    {
        // เริ่มเซสชันถ้ายังไม่ได้เริ่ม
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->cartModel = new Cart();
    }

    /**
     * GET /api/v1/cart
     * ดึงข้อมูลในตะกร้าสินค้า
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
     * เพิ่มสินค้าลงตะกร้า
     * 
     * Body: {"product_id": 1, "quantity": 2}
     */
    public function add(): void
    {
        if (!$this->isAuthenticated()) {
            $this->json(false, null, 'Authentication required', [], 401);
            return;
        }

        // รับข้อมูล JSON
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            $input = $_POST; // ใช้ form data แทน
        }

        // ตรวจสอบฟิลด์ที่จำเป็น
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
     * อัปเดตจำนวนสินค้า
     * 
     * Body: {"product_id": 1, "quantity": 3}
     */
    public function update(): void
    {
        if (!$this->isAuthenticated()) {
            $this->json(false, null, 'Authentication required', [], 401);
            return;
        }

        // รับข้อมูล JSON
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
     * ลบสินค้าออกจากตะกร้า
     * 
     * @param string $productId รหัสสินค้า
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
