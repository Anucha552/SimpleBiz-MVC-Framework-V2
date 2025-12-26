<?php
/**
 * ตัวควบคุมคำสั่งซื้อ (WEB)
 * 
 * จุดประสงค์: จัดการการชำระเงินและประวัติคำสั่งซื้อ
 * ความปลอดภัย: ผู้ใช้ที่ยืนยันตัวตนเท่านั้น, การตรวจสอบฝั่งเซิร์ฟเวอร์
 * 
 * ความรับผิดชอบ:
 * - แสดงหน้าชำระเงิน
 * - สร้างคำสั่งซื้อจากตะกร้า
 * - แสดงประวัติคำสั่งซื้อ
 * - แสดงรายละเอียดคำสั่งซื้อ
 * 
 * ตัวควบคุมแบบบาง:
 * - ตรรกะคำสั่งซื้อที่ซับซ้อนอยู่ในโมเดล Order
 * - ตัวควบคุมนี้เพียงจัดการชั้น HTTP
 * 
 * หมายเหตุ: นี่เป็นการชำระเงินชั่วคราว
 * การรวมระบบชำระเงินจริงจะเกิดขึ้นที่นี่ (Stripe, PayPal, ฯลฯ)
 */

namespace App\Controllers\Ecommerce;

use App\Core\Controller;
use App\Core\View;
use App\Models\Order;
use App\Models\Cart;

class OrderController extends Controller
{
    private Order $orderModel;
    private Cart $cartModel;

    public function __construct()
    {
        // เริ่มเซสชันถ้ายังไม่ได้เริ่ม
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->orderModel = new Order();
        $this->cartModel = new Cart();
    }

    /**
     * แสดงหน้าชำระเงิน
     */
    public function checkout(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
            return;
        }

        $userId = $this->getUserId();
        $items = $this->cartModel->getItems($userId);
        $totals = $this->cartModel->calculateTotal($userId);

        if (empty($items)) {
            echo "<h1>Cart is Empty</h1>";
            echo "<p><a href='/products'>Shop Now</a></p>";
            return;
        }

        echo "<h1>Checkout</h1>";
        echo "<p><a href='/cart'>Back to Cart</a></p>";

        echo "<h2>Order Summary</h2>";
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th></tr>";

        foreach ($items as $item) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($item['name']) . "</td>";
            echo "<td>$" . number_format($item['current_price'], 2) . "</td>";
            echo "<td>" . $item['qty'] . "</td>";
            echo "<td>$" . number_format($item['subtotal'], 2) . "</td>";
            echo "</tr>";
        }

        echo "</table>";
        echo "<h3>Total: $" . number_format($totals['total'], 2) . "</h3>";

        echo "<h2>Payment Information</h2>";
        echo "<p><em>This is a demo. No real payment processing.</em></p>";
        echo "<form method='POST' action='/order/confirm'>";
        echo "<button type='submit'>Confirm Order</button>";
        echo "</form>";
    }

    /**
     * ยืนยันและสร้างคำสั่งซื้อ
     */
    public function confirm(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
            return;
        }

        $userId = $this->getUserId();

        // สร้างคำสั่งซื้อจากตะกร้า
        $result = $this->orderModel->createFromCart($userId);

        if ($result['success']) {
            echo "<h1>Order Confirmed!</h1>";
            echo "<p>Your order #{$result['order_id']} has been placed successfully.</p>";
            echo "<p>Status: Pending</p>";
            echo "<p><a href='/orders'>View My Orders</a> | <a href='/products'>Continue Shopping</a></p>";
        } else {
            echo "<h1>Order Failed</h1>";
            echo "<p>Error: {$result['message']}</p>";
            echo "<p><a href='/cart'>Back to Cart</a></p>";
        }
    }

    /**
     * แสดงรายการคำสั่งซื้อของผู้ใช้
     */
    public function index(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
            return;
        }

        $userId = $this->getUserId();
        $orders = $this->orderModel->getUserOrders($userId);

        $view = new View('orders/index', [
            'orders' => $orders
        ]);
        
        $view->layout('main')->show();
    }

    /**
     * แสดงรายละเอียดคำสั่งซื้อ
     * 
     * @param string $id รหัสคำสั่งซื้อจาก URL
     */
    public function show(string $id): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
            return;
        }

        $orderId = $this->validateInt($id);
        
        if (!$orderId) {
            echo "Invalid order ID";
            return;
        }

        $order = $this->orderModel->getWithItems($orderId);

        if (!$order) {
            echo "<h1>Order Not Found</h1>";
            echo "<p><a href='/orders'>Back to Orders</a></p>";
            return;
        }

        // ตรวจสอบว่าคำสั่งซื้อเป็นของผู้ใช้ปัจจุบัน
        if ($order['user_id'] !== $this->getUserId()) {
            echo "<h1>Access Denied</h1>";
            echo "<p><a href='/orders'>Back to Orders</a></p>";
            return;
        }

        $view = new View('orders/show', [
            'order' => $order
        ]);
        
        $view->layout('main')->show();
    }
}
