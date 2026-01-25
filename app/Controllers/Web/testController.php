<?php
/**
 * testController
 * 
 * จุดประสงค์: [อธิบายหน้าที่ของ controller ได้ที่นี่ว่า controller นี้ทำอะไร]
 */

namespace App\Controllers\Web;

use App\Core\Controller;

class testController extends Controller
{
    /**
     * แสดงหน้าหลัก
     */
    public function index(): void
    {
        $this->view('test', [
            'title' => 'Test Page',
            'message' => 'This is a test page from testController.'
        ]);
    }
}
