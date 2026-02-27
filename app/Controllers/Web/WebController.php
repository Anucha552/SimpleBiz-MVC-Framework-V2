<?php

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Request;

/**
 * WebController
 * 
 * Controller สำหรับจัดการหน้าแรกของแอปพลิเคชัน
 */
class WebController extends Controller
{
    /**
     * รับ Request จาก Router เพื่อให้ Controller สามารถใช้งานข้อมูลจาก Request ได้
     */
    protected Request $request;


    /**
     * เมธอดสำหรับรับ Request จาก Router
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * แสดงหน้าเว็บต้อนรับ
     */
    public function index()
    {
       $this->view('welcome');
    }

}
