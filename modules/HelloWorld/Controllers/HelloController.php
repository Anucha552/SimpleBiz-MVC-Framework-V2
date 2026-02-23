<?php
/**
 * HelloController
 * 
 * Controller สำหรับจัดการเส้นทางที่เกี่ยวข้องกับโมดูล HelloWorld
 * จุดประสงค์: จัดการคำขอที่เข้ามาในเส้นทางที่เกี่ยวข้องกับโมดูล HelloWorld เช่น การแสดงข้อความ "Hello, World!" ในรูปแบบ HTML และ JSON
 * ตัวอย่างการใช้งาน:
 * - http://yourapp/hello จะแสดงข้อความ "Hello, World!" ในรูปแบบ HTML
 * - http://yourapp/api/hello จะแสดงข้อความ "Hello, World!" ในรูปแบบ JSON
 */

namespace Modules\HelloWorld\Controllers;

use App\Core\Request;
use App\Core\Response;

final class HelloController
{
    /**
     * แสดงข้อความ "Hello, World!" ในรูปแบบ HTML
     * จุดประสงค์: ตอบกลับคำขอที่เข้ามาในเส้นทาง /hello ด้วยการแสดงข้อความ "Hello, World!" ในรูปแบบ HTML พร้อมกับแสดง Request ID ที่ได้รับจากคำขอ
     * ตัวอย่างการใช้งาน:
     * - http://yourapp/hello จะแสดงข้อความ "Hello, World!" ในรูปแบบ HTML พร้อมกับ Request ID
     *
     * @param Request $request วัตถุที่แทนคำขอที่เข้ามา ซึ่งสามารถใช้เพื่อดึงข้อมูลต่างๆ เช่น Request ID
     * @return Response วัตถุที่แทนการตอบกลับ ซึ่งจะถูกส่งกลับไปยังผู้ใช้
     */
    public function index(Request $request): Response
    {
        $id = $request->getRequestId();

        $html = '<!doctype html><html><head><meta charset="utf-8"><title>Hello Module</title></head><body>'
            . '<h1>Hello from modules/HelloWorld</h1>'
            . '<p>Request ID: ' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>Try <a href="/api/hello">/api/hello</a></p>'
            . '</body></html>';

        return Response::html($html);
    }

    /**
     * แสดงข้อความ "Hello, World!" ในรูปแบบ JSON
     * จุดประสงค์: ตอบกลับคำขอที่เข้ามาในเส้นทาง /api/hello ด้วยการแสดงข้อความ "Hello, World!" ในรูปแบบ JSON พร้อมกับแสดง Request ID ที่ได้รับจากคำขอ
     * ตัวอย่างการใช้งาน:
     * - http://yourapp/api/hello จะแสดงข้อความ "Hello, World!" ในรูปแบบ JSON พร้อมกับ Request ID
     *
     * @param Request $request วัตถุที่แทนคำขอที่เข้ามา ซึ่งสามารถใช้เพื่อดึงข้อมูลต่างๆ เช่น Request ID
     * @return Response วัตถุที่แทนการตอบกลับ ซึ่งจะถูกส่งกลับไปยังผู้ใช้ในรูปแบบ JSON
     */
    public function api(Request $request): Response
    {
        return Response::apiSuccess([
            'module' => 'HelloWorld',
            'request_id' => $request->getRequestId(),
        ], 'OK');
    }
}
