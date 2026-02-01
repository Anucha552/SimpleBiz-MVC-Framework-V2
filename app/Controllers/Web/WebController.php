<?php

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Auth;

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

    /**
     * debug ข้อมูลดู
     */
    public function debug(): Response
    {

        $text = "นี่คือหน้าดูข้อมูล debug";

        $html = "<h1>{$text}</h1>";
        return Response::html($html, 200);
    }

}
