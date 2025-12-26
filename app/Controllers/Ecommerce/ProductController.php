<?php
/**
 * ตัวควบคุมสินค้า (WEB)
 * 
 * จุดประสงค์: แสดงแค็ตตาล็อกสินค้าให้ผู้ใช้
 * 
 * ความรับผิดชอบ:
 * - แสดงรายการสินค้าทั้งหมด
 * - แสดงรายละเอียดสินค้าแต่ละรายการ
 * - ค้นหาสินค้า (ในอนาคต)
 * 
 * ตัวควบคุมนี้เป็นแบบบาง:
 * - เพียงดึงข้อมูลจากโมเดล Product
 * - แสดงผลให้ผู้ใช้
 * - ไม่มีตรรกะทางธุรกิจที่นี่!
 */

namespace App\Controllers\Ecommerce;

use App\Core\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    private Product $productModel;

    public function __construct()
    {
        $this->productModel = new Product();
    }

    /**
     * แสดงรายการสินค้าทั้งหมด
     */
    public function index(): void
    {
        $products = $this->productModel->getAll();

        echo "<h1>Products</h1>";
        echo "<p><a href='/'>Home</a> | <a href='/cart'>Cart</a></p>";

        if (empty($products)) {
            echo "<p>No products available</p>";
            return;
        }

        echo "<div style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;'>";
        foreach ($products as $product) {
            echo "<div style='border: 1px solid #ccc; padding: 15px;'>";
            echo "<h3>" . htmlspecialchars($product['name']) . "</h3>";
            echo "<p>" . htmlspecialchars($product['description']) . "</p>";
            echo "<p><strong>Price: $" . number_format($product['price'], 2) . "</strong></p>";
            echo "<p>Stock: " . $product['stock'] . "</p>";
            echo "<a href='/products/" . $product['id'] . "'>View Details</a>";
            echo "</div>";
        }
        echo "</div>";
    }

    /**
     * แสดงรายละเอียดสินค้า
     * 
     * @param string $id รหัสสินค้าจาก URL
     */
    public function show(string $id): void
    {
        // ตรวจสอบ ID
        $productId = $this->validateInt($id);
        
        if (!$productId) {
            echo "Invalid product ID";
            return;
        }

        $product = $this->productModel->findById($productId);

        if (!$product) {
            echo "<h1>Product Not Found</h1>";
            echo "<p><a href='/products'>Back to Products</a></p>";
            return;
        }

        echo "<h1>" . htmlspecialchars($product['name']) . "</h1>";
        echo "<p><a href='/products'>Back to Products</a></p>";
        echo "<p>" . htmlspecialchars($product['description']) . "</p>";
        echo "<p><strong>Price: $" . number_format($product['price'], 2) . "</strong></p>";
        echo "<p>Stock: " . $product['stock'] . "</p>";
        echo "<p>Status: " . $product['status'] . "</p>";

        if ($product['status'] === 'active' && $product['stock'] > 0) {
            echo "<form method='POST' action='/cart/add'>";
            echo "<input type='hidden' name='product_id' value='" . $product['id'] . "'>";
            echo "<input type='number' name='quantity' value='1' min='1' max='" . $product['stock'] . "'>";
            echo "<button type='submit'>Add to Cart</button>";
            echo "</form>";
        } else {
            echo "<p><strong>Out of Stock</strong></p>";
        }
    }
}
