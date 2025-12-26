<?php
/**
 * ORDER CONTROLLER (WEB)
 * 
 * Purpose: Handle order checkout and order history
 * Security: Authenticated users only, server-side validation
 * 
 * Responsibilities:
 * - Display checkout page
 * - Create order from cart
 * - Show order history
 * - Display order details
 * 
 * THIN CONTROLLER:
 * - Complex order logic in Order model
 * - This just handles HTTP layer
 * 
 * Note: This is a PLACEHOLDER checkout
 * Real payment integration would happen here (Stripe, PayPal, etc.)
 */

namespace App\Controllers\Ecommerce;

use App\Core\Controller;
use App\Models\Order;
use App\Models\Cart;

class OrderController extends Controller
{
    private Order $orderModel;
    private Cart $cartModel;

    public function __construct()
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->orderModel = new Order();
        $this->cartModel = new Cart();
    }

    /**
     * Display checkout page
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
     * Confirm and create order
     */
    public function confirm(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
            return;
        }

        $userId = $this->getUserId();

        // Create order from cart
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
     * List user's orders
     */
    public function index(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
            return;
        }

        $userId = $this->getUserId();
        $orders = $this->orderModel->getUserOrders($userId);

        echo "<h1>My Orders</h1>";
        echo "<p><a href='/'>Home</a> | <a href='/products'>Products</a></p>";

        if (empty($orders)) {
            echo "<p>You have no orders yet.</p>";
            echo "<p><a href='/products'>Start Shopping</a></p>";
            return;
        }

        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Order ID</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th></tr>";

        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>#" . $order['id'] . "</td>";
            echo "<td>$" . number_format($order['total'], 2) . "</td>";
            echo "<td>" . ucfirst($order['status']) . "</td>";
            echo "<td>" . date('M j, Y', strtotime($order['created_at'])) . "</td>";
            echo "<td><a href='/orders/" . $order['id'] . "'>View Details</a></td>";
            echo "</tr>";
        }

        echo "</table>";
    }

    /**
     * Show order details
     * 
     * @param string $id Order ID from URL
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

        // Verify order belongs to current user
        if ($order['user_id'] !== $this->getUserId()) {
            echo "<h1>Access Denied</h1>";
            echo "<p><a href='/orders'>Back to Orders</a></p>";
            return;
        }

        echo "<h1>Order #" . $order['id'] . "</h1>";
        echo "<p><a href='/orders'>Back to Orders</a></p>";
        echo "<p><strong>Status:</strong> " . ucfirst($order['status']) . "</p>";
        echo "<p><strong>Date:</strong> " . date('M j, Y g:i A', strtotime($order['created_at'])) . "</p>";

        echo "<h2>Items</h2>";
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th></tr>";

        foreach ($order['items'] as $item) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($item['product_name']) . "</td>";
            echo "<td>$" . number_format($item['price'], 2) . "</td>";
            echo "<td>" . $item['qty'] . "</td>";
            echo "<td>$" . number_format($item['subtotal'], 2) . "</td>";
            echo "</tr>";
        }

        echo "</table>";
        echo "<h3>Total: $" . number_format($order['total'], 2) . "</h3>";
    }
}
