<?php

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\ErrorHandler;
use App\Core\Response;

/**
 * HomeController
 * 
 * Controller สำหรับจัดการหน้าแรกของแอปพลิเคชัน
 */
class WebController extends Controller
{
    /**
     * แสดงหน้าต้อนรับ
     */
    public function index()
    {
        $data = [
            'title' => 'ยินดีต้อนรับสู่ SimpleBiz MVC Framework V2',
            'version' => '2.0.0',
            'features' => [
                [
                    'icon' => 'bi-lightning-charge',
                    'title' => 'รวดเร็วและมีประสิทธิภาพ',
                    'description' => 'Framework ที่ออกแบบมาเพื่อประสิทธิภาพสูงสุด พร้อม Routing ที่เร็วและ Database Query Builder'
                ],
                [
                    'icon' => 'bi-shield-check',
                    'title' => 'ความปลอดภัยสูง',
                    'description' => 'มาพร้อมกับ CSRF Protection, XSS Prevention, SQL Injection Protection และ Security Headers'
                ],
                [
                    'icon' => 'bi-puzzle',
                    'title' => 'ใช้งานง่าย',
                    'description' => 'MVC Architecture ที่เข้าใจง่าย พร้อม Documentation ครบถ้วนและตัวอย่างการใช้งาน'
                ],
                [
                    'icon' => 'bi-tools',
                    'title' => 'CLI Tools',
                    'description' => 'Command Line Interface สำหรับสร้าง Controllers, Models, Migrations และอื่นๆ อย่างรวดเร็ว'
                ],
                [
                    'icon' => 'bi-database',
                    'title' => 'Database Migration',
                    'description' => 'ระบบ Migration และ Seeding ที่ทรงพลัง จัดการโครงสร้างฐานข้อมูลได้อย่างมีประสิทธิภาพ'
                ],
                [
                    'icon' => 'bi-gear',
                    'title' => 'Middleware System',
                    'description' => 'Middleware ที่ยืดหยุ่นสำหรับ Authentication, Authorization, CORS, Rate Limiting และอื่นๆ'
                ]
            ],
            'quickLinks' => [
                [
                    'url' => '/docs/QUICK_START.md',
                    'icon' => 'bi-book',
                    'title' => 'Quick Start',
                    'description' => 'เริ่มต้นใช้งานภายใน 5 นาที'
                ],
                [
                    'url' => '/docs/FRAMEWORK_CAPABILITIES.md',
                    'icon' => 'bi-list-check',
                    'title' => 'ความสามารถ',
                    'description' => 'ดูคุณสมบัติทั้งหมดของ Framework'
                ],
                [
                    'url' => '/docs/API_REFERENCE.md',
                    'icon' => 'bi-code-square',
                    'title' => 'API Reference',
                    'description' => 'เอกสารอ้างอิง API แบบละเอียด'
                ],
                [
                    'url' => '/docs/MIDDLEWARE_GUIDE.md',
                    'icon' => 'bi-layers',
                    'title' => 'Middleware Guide',
                    'description' => 'เรียนรู้การใช้งาน Middleware'
                ]
            ]
        ];
        
        $this->view('welcome', $data);
    }
    
    /**
     * แสดงตัวอย่างการใช้งาน Assets (CSS, JS, Images)
     */
    public function assetsDemo()
    {
        $this->view('assets-demo');
    }
    
    /**
     * แสดงข้อมูล PHP Info (สำหรับการ debug เท่านั้น)
     */
    public function phpinfo()
    {
        // ตรวจสอบว่าอยู่ใน development mode หรือไม่
        $config = require __DIR__ . '/../../../config/app.php';
        
        if (($config['env'] ?? 'production') === 'production') {
            return ErrorHandler::response(403, 'PHP Info is disabled in production mode.');
        }

        ob_start();
        phpinfo();
        $html = ob_get_clean();

        return Response::html($html ?: ''); 
    }
}
