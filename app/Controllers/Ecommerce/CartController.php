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
use App\Core\View;
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

        $view = new View('cart/index', [
            'cartItems' => $items,
            'subtotal' => $totals['subtotal'] ?? 0,
            'shipping' => 50, // ค่าจัดส่งคงที่
            'discount' => 0,
            'total' => ($totals['total'] ?? 0) + 50
        ]);
        
        $view->layout('main')->show();
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
