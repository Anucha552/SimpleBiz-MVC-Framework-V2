<?php

namespace App\Controllers\Web;

use App\Core\Controller;

/**
 * WebController
 * 
 * Controller สำหรับจัดการหน้าแรกของแอปพลิเคชัน
 */
class WebController extends Controller
{
    /**
     * แสดงหน้าเว็บต้อนรับ
     */
    public function index()
    {
        $this->view('welcome');
    }

}
