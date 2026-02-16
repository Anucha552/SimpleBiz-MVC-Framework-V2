<?php
/**
 * DemoController
 *
 * จุดประสงค์: [อธิบายหน้าที่ของ controller ได้ที่นี่ว่า controller นี้ทำอะไร]
 */

namespace App\Controllers\Web;

use App\Core\Controller;

class DemoController extends Controller
{
    /**
     * แสดงหน้าหลัก
     */
    public function index(): void
    {
        // เขียนโค้ดของคุณที่นี่ หรือสร้าง method อื่นๆ ตามต้องการ
        echo "Hello from DemoController!";
    }
}
