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
use App\Helpers\NumberHelper;

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
    public function store(): Response
    {
        // รับข้อมูลจากฟอร์ม
        $data = $this->only([
            'first_name',
            'last_name',
            'department',
            'salary',
            'email',
            'phone'
        ]);

        // กำหนดกฎการตรวจสอบความถูกต้อง
        $rules = [
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'department' => 'required|integer',
            'salary' => 'required|numeric',
            'email' => 'required|email|max:150',
            'phone' => 'required|phone',
        ];

        // ตรวจสอบ ถ้าไม่ผ่านจะ flash errors + old input และ redirect กลับอัตโนมัติ
        $redirectResponse = $this->validateOrRedirect($data, $rules, [], null);
        if ($redirectResponse !== null) {
            return $redirectResponse;
        }

        // เตรียมข้อมูลสำหรับบันทึก (เอาเฉพาะคอลัมน์ที่โมเดลรองรับ)
        $saveData = [
            'first_name' => $this->sanitize($data['first_name'] ?? ''),
            'last_name' => $this->sanitize($data['last_name'] ?? ''),
            'department_id' => $this->sanitize($data['department'] ?? ''),
            'email' => $this->sanitize($data['email'] ?? ''),
            'phone' => $this->sanitize($data['phone'] ?? ''),
            'salary' => $this->sanitize($data['salary'] ?? ''),
            'status' => 'active', // กำหนดสถานะเริ่มต้นเป็น active
            'created_at' => date('Y-m-d H:i:s'), // กำหนดวันที่สร้างเป็นเวลาปัจจุบัน
        ];
        
        // สร้างรหัสพนักงานอัตโนมัติ (ตัวอย่าง: EMP001, EMP002, ...)
        $lastEmployee = employees::getAllEmployees();
        if (count($lastEmployee) > 0) {
            $saveData['employee_code'] = NumberHelper::generateCode(null, null, 3, $lastEmployee[count($lastEmployee) - 1]['employee_code'] ?? null);
        } else {
            $saveData['employee_code'] = 'EMP001'; // รหัสเริ่มต้นถ้าไม่มีพนักงานเลย
        }
       
        // บันทึกข้อมูลพนักงานใหม่
        $result = employees::createEmployee($saveData);
        if ($result === 0) {
            // บันทึกไม่สำเร็จ
            $this->flash('error', 'เกิดข้อผิดพลาดขณะบันทึกข้อมูล');
            return $this->back(); // กลับไปยังหน้าฟอร์มเดิม
        }

        $this->flash('success', 'เพิ่มพนักงานสำเร็จ');
        return $this->redirect('/employees');
    }

    /**
     * แสดงรายละเอียดพนักงาน 1 คน ตาม id
     */
    public function show($id): Response
    {
        $employee = employees::getEmployeeById((int)$id); // ดึงข้อมูลพนักงานตาม id

        // ถ้าไม่พบข้อมูลพนักงานที่ต้องการ จะแจ้งเตือนและกลับไปยังหน้ารายการพนักงาน
        if (count($employee) === 0) {
            $this->flash('error', 'ไม่พบข้อมูลพนักงานที่คุณต้องการ');
            return $this->redirect('/employees');
        }

        // แสดงรายละเอียดพนักงานในหน้า show
        return $this->responseView('employees/show', [
            'employee' => $employee
        ], 'layouts/main');
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
    public function destroy($id): Response
    {
        // ดึงข้อมูลพนักงานที่ต้องการลบเพื่อแสดงรหัสพนักงานในข้อความแจ้งเตือน
        $employee = employees::query()
            ->select('employee_code')
            ->where('id', '=', (int)$id)
            ->first();
        
        
        $result = employees::deleteEmployee((int)$id); // ลบพนักงานตาม id

        // ตรวจสอบผลลัพธ์การลบและแจ้งเตือนผู้ใช้
        if ($result === 0) {
            $this->flash('success', 'ลบพนักงานสำเร็จ รหัสพนักงาน: ' . $employee['employee_code']);
        } else {
            $this->flash('error', 'เกิดข้อผิดพลาดขณะลบพนักงาน รหัสพนักงาน: ' . $employee['employee_code']);
        }

        return $this->redirect('/employees');
    }
}
