<?php
/**
 * EmployeeController
 *
 * จุดประสงค์: เป็น Controller สำหรับจัดการพนักงานในระบบ โดยสามารถเพิ่ม, แก้ไข, ลบ และแสดงข้อมูลพนักงานได้ตามต้องการ โดยจะเชื่อมต่อกับ Model ที่เกี่ยวข้องเพื่อดึงข้อมูลจากฐานข้อมูลและส่งข้อมูลไปยัง View เพื่อแสดงผลให้ผู้ใช้เห็น
 */

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Response;
use App\Models\employees;

class EmployeeController extends Controller
{
    /**
     * แสดงรายการพนักงานทั้งหมด (หน้า List)
     */
    public function index(): Response
    {
        return $this->responseView('employees/index', [
            'employees' => employees::getAllEmployees()
        ],
            'layouts/main'
        );
    }

    /**
     * แสดงหน้า Form สำหรับเพิ่มพนักงานใหม่
     */
    public function create(): Response
    {
        return $this->responseView('employees/create', [

        ], 'layouts/main');
    }

    /**
     * รับข้อมูลจาก Form แล้วบันทึกพนักงานใหม่ลงฐานข้อมูล
     */
    public function store(): void
    {
        echo "This is the store method for EmployeeController.";
    }

    /**
     * แสดงรายละเอียดพนักงาน 1 คน ตาม id
     */
    public function show($id): void
    {
        echo "This is the show method for EmployeeController. ID: " . $id;
    }

    /**
     * แสดงหน้า Form สำหรับแก้ไขข้อมูลพนักงาน
     */
    public function edit($id): void
    {
        echo "This is the edit method for EmployeeController. ID: " . $id;
    }

    /**
     * รับข้อมูลที่แก้ไขแล้ว แล้วอัปเดตลงฐานข้อมูล
     */
    public function update($id): void
    {
        echo "This is the update method for EmployeeController. ID: " . $id;
    }

    /**
     * ลบพนักงานตาม id (มาจากปุ่มลบในหน้าเว็บ)
     */
    public function destroy($id): void
    {
        echo "This is the destroy method for EmployeeController. ID: " . $id;
    }
}
