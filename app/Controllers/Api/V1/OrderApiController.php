<?php
/**
 * ตัวควบคุม API คำสั่งซื้อ (V1)
 * 
 * จุดประสงค์: RESTful API สำหรับจัดการคำสั่งซื้อ
 * Base URL: /api/v1/orders
 * ความปลอดภัย: ต้องการการยืนยันตัวตน + API key
 * 
 * Endpoints:
 * - GET /api/v1/orders → แสดงรายการคำสั่งซื้อของผู้ใช้
 * - GET /api/v1/orders/{id} → ดึงรายละเอียดคำสั่งซื้อ
 * - POST /api/v1/orders/create → สร้างคำสั่งซื้อจากตะกร้า
 * - PUT /api/v1/orders/{id}/status → อัปเดตสถานะคำสั่งซื้อ (ผู้ดูแลระบบ)
 * 
 * ความปลอดภัย:
 * - ผู้ใช้สามารถเข้าถึงเฉพาะคำสั่งซื้อของตนเอง
 * - ต้องใช้ API key สำหรับการดำเนินการที่ละเอียดอ่อน
 * - การคำนวณราคาทั้งหมดทำที่ฝั่งเซิร์ฟเวอร์
 */

namespace App\Controllers\Api\V1;

use App\Core\Controller;
use App\Models\Order;

class OrderApiController extends Controller
{
    private Order $orderModel;

    public function __construct()
    {
        // เริ่มเซสชันถ้ายังไม่ได้เริ่ม
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->orderModel = new Order();
    }

    /**
     * GET /api/v1/orders
     * แสดงรายการคำสั่งซื้อของผู้ใช้
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
     * ดึงรายละเอียดคำสั่งซื้อ
     * 
     * @param string $id รหัสคำสั่งซื้อ
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

        // ตรวจสอบว่าคำสั่งซื้อเป็นของผู้ใช้ปัจจุบัน
        if ($order['user_id'] !== $this->getUserId()) {
            $this->json(false, null, 'Access denied', [], 403);
            return;
        }

        $this->json(true, $order, 'Order retrieved successfully');
    }

    /**
     * POST /api/v1/orders/create
     * สร้างคำสั่งซื้อจากตะกร้า
     */
    public function create(): void
    {
        if (!$this->isAuthenticated()) {
            $this->json(false, null, 'Authentication required', [], 401);
            return;
        }

        $userId = $this->getUserId();

        // สร้างคำสั่งซื้อจากตะกร้า
        $result = $this->orderModel->createFromCart($userId);

        if ($result['success']) {
            // ดึงรายละเอียดคำสั่งซื้อที่สร้าง
            $order = $this->orderModel->getWithItems($result['order_id']);

            $this->json(true, $order, $result['message'], [], 201);
        } else {
            $this->json(false, null, $result['message'], [], 400);
        }
    }

    /**
     * PUT /api/v1/orders/{id}/status
     * อัปเดตสถานะคำสั่งซื้อ
     * 
     * Body: {"status": "paid"}
     * 
     * @param string $id รหัสคำสั่งซื้อ
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

        // รับข้อมูล JSON
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            $input = $_POST;
        }

        if (!isset($input['status'])) {
            $this->json(false, null, 'Missing status field', ['status' => 'Required'], 400);
            return;
        }

        $status = $this->sanitize($input['status']);

        // ตรวจสอบว่าคำสั่งซื้อเป็นของผู้ใช้
        $order = $this->orderModel->findById($orderId);

        if (!$order) {
            $this->json(false, null, 'Order not found', [], 404);
            return;
        }

        if ($order['user_id'] !== $this->getUserId()) {
            $this->json(false, null, 'Access denied', [], 403);
            return;
        }

        // อัปเดตสถานะ
        $result = $this->orderModel->updateStatus($orderId, $status);

        if ($result['success']) {
            $this->json(true, null, $result['message']);
        } else {
            $this->json(false, null, $result['message'], [], 400);
        }
    }
}
