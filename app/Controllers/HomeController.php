<?php
/**
 * ตัวควบคุมหน้าหลัก
 * 
 * จุดประสงค์: จัดการหน้าแรกและหน้าทั่วไป
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;

class HomeController extends Controller
{
    /**
     * แสดงหน้าแรก
     */
    public function index(): void
    {
        $view = new View('home/index');
        $view->layout('main')->show();
    }
}
