<?php
/**
 * ApiEmployeeController
 *
 * จุดประสงค์: เป็น Controller สำหรับจัดการพนักงานในระบบผ่าน API โดยสามารถเพิ่ม, แก้ไข, ลบ และแสดงข้อมูลพนักงานได้ตามต้องการ โดยจะเชื่อมต่อกับ Model ที่เกี่ยวข้องเพื่อดึงข้อมูลจากฐานข้อมูลและส่งข้อมูลไปยัง Response ในรูปแบบ JSON เพื่อให้ผู้ใช้สามารถเข้าถึงข้อมูลพนักงานได้อย่างง่ายดายผ่านทาง API
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Response;

class ApiEmployeeController extends Controller
{
    /**
     * แสดงรายการพนักงานทั้งหมด (ส่งออกเป็น JSON)
     */
    public function index(): Response
    {
        // เขียนโค้ดของคุณที่นี่ หรือสร้าง method อื่นๆ ตามต้องการ
        return Response::apiSuccess([
            'status' => 'ok',
            'timestamp' => date('c'),
        ], 'Hello from ApiEmployeeController!');
    }

    /**
     * แสดงรายละเอียดพนักงาน 1 คน ตาม id (ส่งออกเป็น JSON)
     */
    public function show($id): Response
    {
        return Response::apiSuccess([
            'status' => 'ok',
            'timestamp' => date('c'),
            'employee_id' => $id,
        ], 'This is the show method for ApiEmployeeController. ID: ' . $id);
    }

    /**
     * รับข้อมูลจาก Form แล้วบันทึกพนักงานใหม่ลงฐานข้อมูล (รับข้อมูลเป็น JSON)
     */
    public function store(): Response
    {
        // สมมติว่าเราได้รับข้อมูลจาก JSON แล้วบันทึกลงฐานข้อมูลเรียบร้อย
        return Response::apiSuccess([
            'status' => 'ok',
            'timestamp' => date('c'),
        ], 'This is the store method for ApiEmployeeController.');
    }

    /**
     * รับข้อมูลที่แก้ไขแล้ว แล้วอัปเดตลงฐานข้อมูล (รับข้อมูลเป็น JSON)
     */
    public function update($id): Response
    {
        // สมมติว่าเราได้รับข้อมูลจาก JSON แล้วอัปเดตลงฐานข้อมูลเรียบร้อย
        return Response::apiSuccess([
            'status' => 'ok',
            'timestamp' => date('c'),
            'employee_id' => $id,
        ], 'This is the update method for ApiEmployeeController. ID: ' . $id);
    }

    /**
    * ลบพนักงาน 1 คน ตาม id
    */
    public function destroy($id): Response
    {
        // สมมติว่าเราได้รับคำขอลบแล้วลบข้อมูลจากฐานข้อมูลเรียบร้อย
        return Response::apiSuccess([
            'status' => 'ok',
            'timestamp' => date('c'),
            'employee_id' => $id,
        ], 'This is the destroy method for ApiEmployeeController. ID: ' . $id);
    }
}
