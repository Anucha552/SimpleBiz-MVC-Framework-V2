<?php
/**
 * CART CONTROLLER (WEB)
 * 
 * Purpose: Shopping cart management
 * Security: Validates user authentication via middleware
 * 
 * Responsibilities:
 * - Display cart contents
 * - Add items to cart
 * - Update quantities
 * - Remove items
 * 
 * THIN CONTROLLER:
 * - All validation and business logic in Cart model
 * - Controller just coordinates and displays
 */

namespace App\Controllers\Ecommerce;

use App\Core\Controller;
use App\Models\Cart;

class CartController extends Controller
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
     * Display cart contents
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

            // Show price change warning if applicable
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
     * Add item to cart
     */
    public function add(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
            return;
        }

        // Validate input
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

        // Call model to add item
        $result = $this->cartModel->addItem($userId, $productId, $quantity);

        if ($result['success']) {
            $this->redirect('/cart');
        } else {
            echo "<p>Error: {$result['message']}</p>";
            echo "<p><a href='/products'>Back to Products</a></p>";
        }
    }

    /**
     * Update cart item quantity
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
     * Remove item from cart
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
