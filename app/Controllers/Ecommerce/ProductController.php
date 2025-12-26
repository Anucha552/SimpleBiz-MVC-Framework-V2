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
use App\Core\View;
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

        $view = new View('products/index', [
            'products' => $products
        ]);
        
        $view->layout('main')->show();
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

        // ดึงสินค้าที่เกี่ยวข้อง (optional)
        $relatedProducts = [];

        $view = new View('products/show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts
        ]);
        
        $view->layout('main')->show();
    }
}
