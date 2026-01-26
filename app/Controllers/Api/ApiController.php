<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Response;

/**
 * ApiController
 *
 * Controller สำหรับจัดการหน้าแรกของแอปพลิเคชัน
 */
class ApiController extends Controller
{
    /**
     * แสดงหน้า API ต้อนรับ
     */
    public function index(): Response
    {
        return Response::apiSuccess([
            'message' => 'Welcome to the API',
        ], 'OK');
    }

}
