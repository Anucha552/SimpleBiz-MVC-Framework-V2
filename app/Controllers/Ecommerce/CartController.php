<?php
/**
 * ตัวควบคุมตะกร้าสินค้า (WEB)
 * 
 * จุดประสงค์: จัดการตะกร้าสินค้า
 * ความปลอดภัย: ตรวจสอบการยืนยันตัวตนผู้ใช้ผ่าน middleware
 * 
 * ความรับผิดชอบ:
 * - แสดงเนื้อหาในตะกร้าสินค้า
 * - เพิ่มสินค้าลงตะกร้า
 * - อัปเดตจำนวน
 * - ลบสินค้า
 * 
 * ตัวควบคุมแบบบาง:
 * - การตรวจสอบและตรรกะทางธุรกิจทั้งหมดอยู่ในโมเดล Cart
 * - ตัวควบคุมเพียงประสานงานและแสดงผล
 */

namespace App\Controllers\Ecommerce;

use App\Core\Controller;
use App\Models\Cart;

class CartController extends Controller
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
     * แสดงเนื้อหาในตะกร้าสินค้า
     */
    public function index(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
            return;
        }

        $userId = $this->getUserId();
        $items = $this->cartModel->getItems($userId);
        $totals = $this->cartModel->calculateTotal($userId);

        echo "<h1>Shopping Cart</h1>";
        echo "<p><a href='/products'>Continue Shopping</a> | <a href='/'>Home</a></p>";

        if (empty($items)) {
            echo "<p>Your cart is empty</p>";
            return;
        }

        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th>Actions</th></tr>";

        foreach ($items as $item) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($item['name']) . "</td>";
            echo "<td>$" . number_format($item['current_price'], 2) . "</td>";
            echo "<td>";
            echo "<form method='POST' action='/cart/update' style='display:inline;'>";
            echo "<input type='hidden' name='product_id' value='" . $item['product_id'] . "'>";
            echo "<input type='number' name='quantity' value='" . $item['qty'] . "' min='1' max='" . $item['stock'] . "' style='width:60px;'>";
            echo "<button type='submit'>Update</button>";
            echo "</form>";
            echo "</td>";
            echo "<td>$" . number_format($item['subtotal'], 2) . "</td>";
            echo "<td>";
            echo "<form method='POST' action='/cart/remove' style='display:inline;'>";
            echo "<input type='hidden' name='product_id' value='" . $item['product_id'] . "'>";
            echo "<button type='submit'>Remove</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";

            // แสดงคำเตือนการเปลี่ยนแปลงราคาถ้ามี
            if (abs($item['added_price'] - $item['current_price']) > 0.01) {
                echo "<tr><td colspan='5' style='background:#ffffcc;'>";
                echo "⚠️ Price changed: was $" . number_format($item['added_price'], 2);
                echo " now $" . number_format($item['current_price'], 2);
                echo "</td></tr>";
            }
        }

        echo "</table>";
        echo "<h3>Total: $" . number_format($totals['total'], 2) . "</h3>";
        echo "<p><a href='/checkout'><button>Proceed to Checkout</button></a></p>";
    }

    /**
     * เพิ่มสินค้าลงตะกร้า
     */
    public function add(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
            return;
        }

        // ตรวจสอบข้อมูลนำเข้า
        $missing = $this->validateRequired(['product_id', 'quantity']);
        
        if (!empty($missing)) {
            echo "Missing required fields";
            return;
        }

        $productId = $this->validateInt($_POST['product_id']);
        $quantity = $this->validateInt($_POST['quantity']);

        if (!$productId || !$quantity) {
            echo "Invalid input";
            return;
        }

        $userId = $this->getUserId();

        // เรียกใช้โมเดลเพื่อเพิ่มสินค้า
        $result = $this->cartModel->addItem($userId, $productId, $quantity);

        if ($result['success']) {
            $this->redirect('/cart');
        } else {
            echo "<p>Error: {$result['message']}</p>";
            echo "<p><a href='/products'>Back to Products</a></p>";
        }
    }

    /**
     * อัปเดตจำนวนสินค้าในตะกร้า
     */
    public function update(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
            return;
        }

        $missing = $this->validateRequired(['product_id', 'quantity']);
        
        if (!empty($missing)) {
            echo "Missing required fields";
            return;
        }

        $productId = $this->validateInt($_POST['product_id']);
        $quantity = $this->validateInt($_POST['quantity']);

        if (!$productId || $quantity === null) {
            echo "Invalid input";
            return;
        }

        $userId = $this->getUserId();
        $result = $this->cartModel->updateQuantity($userId, $productId, $quantity);

        $this->redirect('/cart');
    }

    /**
     * ลบสินค้าออกจากตะกร้า
     */
    public function remove(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
            return;
        }

        $productId = $this->validateInt($_POST['product_id'] ?? '');

        if (!$productId) {
            echo "Invalid product ID";
            return;
        }

        $userId = $this->getUserId();
        $this->cartModel->removeItem($userId, $productId);

        $this->redirect('/cart');
    }
}
