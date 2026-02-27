<?php
/**
 * EmployeeController
 *
 * จุดประสงค์: เป็น Controller สำหรับจัดการพนักงานในระบบ โดยสามารถเพิ่ม, แก้ไข, ลบ และแสดงข้อมูลพนักงานได้ตามต้องการ โดยจะเชื่อมต่อกับ Model ที่เกี่ยวข้องเพื่อดึงข้อมูลจากฐานข้อมูลและส่งข้อมูลไปยัง View เพื่อแสดงผลให้ผู้ใช้เห็น
 */

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Request;
use App\Models\Employees;
use App\Helpers\NumberHelper;
use App\Core\Validator;

class EmployeeController extends Controller
{
    private Employees $employeeModel;

    public function __construct(Employees $employeeModel)
    {
        $this->employeeModel = $employeeModel;
    }

    /**
     * Request instance injected by Router
     */
    protected Request $request;

    public function setRequest(Request $request)
    {
        $this->request = $request;
    }
    /**
     * แสดงรายการพนักงานทั้งหมด (หน้า List)
     */
    public function index(): Response
    {
        return $this->responseView('employees/index', [
            'employees' => $this->employeeModel->getAllEmployees()
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
        // ตรวจสอบข้อมูลที่ส่งมาจากฟอร์ม และดึงเฉพาะฟิลด์ที่ต้องการ
        $data = $this->checkValidationAndGetData('กรุณากรอกข้อมูลให้ถูกต้องครบถ้วน');
        if ($data === false) {
            // ถ้าข้อมูลไม่ผ่านการตรวจสอบ จะถูกจัดการในฟังก์ชัน checkValidationAndGetData แล้ว และเราจะไม่ดำเนินการต่อ
            return $this->back();
        }
        // เตรียมข้อมูลสำหรับบันทึก (เอาเฉพาะคอลัมน์ที่โมเดลรองรับ)
        $saveData = [
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'department_id' => $data['department'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'salary' => $data['salary'] ?? '',
            'status' => 'active', // กำหนดสถานะเริ่มต้นเป็น active
            'created_at' => date('Y-m-d H:i:s'), // กำหนดวันที่สร้างเป็นเวลาปัจจุบัน
        ];
        
        // สร้างรหัสพนักงานอัตโนมัติ (ตัวอย่าง: EMP001, EMP002, ...)
        $lastEmployee = $this->employeeModel->getAllEmployees();
        if (count($lastEmployee) > 0) {
            $saveData['employee_code'] = NumberHelper::generateCode(null, null, 3, $lastEmployee[count($lastEmployee) - 1]['employee_code'] ?? null);
        } else {
            $saveData['employee_code'] = 'EMP001'; // รหัสเริ่มต้นถ้าไม่มีพนักงานเลย
        }
       
        // บันทึกข้อมูลพนักงานใหม่
        $result = $this->employeeModel->createEmployee($saveData);
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
        $employee = $this->employeeModel->getEmployeeById((int)$id); // ดึงข้อมูลพนักงานตาม id

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
    public function edit($id): Response
    {
        $employee = $this->employeeModel->getEmployeeById((int)$id); // ดึงข้อมูลพนักงานตาม id

        // ถ้าไม่พบข้อมูลพนักงานที่ต้องการ จะแจ้งเตือนและกลับไปยังหน้ารายการพนักงาน
        if (count($employee) === 0) {
            $this->flash('error', 'ไม่พบข้อมูลพนักงานที่คุณต้องการ');
            return $this->redirect('/employees');
        }

        $this->flashInput($employee); // เก็บข้อมูลพนักงานที่ดึงมาไว้ใน session เพื่อใช้ในฟอร์มแก้ไขอีกครั้ง

        // แสดงฟอร์มแก้ไขข้อมูลพนักงานในหน้า edit
        return $this->responseView('employees/edit', [
            'employee' => $employee,
            'id' => $id
        ], 'layouts/main');
    }

    /**
     * รับข้อมูลที่แก้ไขแล้ว แล้วอัปเดตลงฐานข้อมูล
     */
    public function update($id): Response
    {
        // ตรวจสอบข้อมูลที่ส่งมาจากฟอร์ม และดึงเฉพาะฟิลด์ที่ต้องการ
        $data = $this->checkValidationAndGetData('กรุณากรอกข้อมูลให้ถูกต้องครบถ้วน');
        if ($data === false) {
            // ถ้าข้อมูลไม่ผ่านการตรวจสอบ จะถูกจัดการในฟังก์ชัน checkValidationAndGetData แล้ว และเราจะไม่ดำเนินการต่อ
            return $this->back();
        }
        
        // เตรียมข้อมูลสำหรับอัปเดต (เอาเฉพาะคอลัมน์ที่โมเดลรองรับ)
        $updateData = [
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'department_id' => (int)($data['department'] ?? 0),
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'salary' => (int)($data['salary'] ?? 0),
        ];

        // อัปเดตข้อมูลพนักงานตาม id
        $result = $this->employeeModel->updateEmployee((int)$id, $updateData);
        if ($result === 0) {
            // อัปเดตไม่สำเร็จ
            $this->flash('error', 'เกิดข้อผิดพลาดขณะอัปเดตข้อมูล');
            return $this->back(); // กลับไปยังหน้าฟอร์มเดิม
        }

        $this->flash('success', 'แก้ไขพนักงานสำเร็จ');
        return $this->redirect('/employees');
    }

    /**
     * ลบพนักงานตาม id (มาจากปุ่มลบในหน้าเว็บ)
     */
    public function destroy($id): Response
    {
        // ดึงข้อมูลพนักงานที่ต้องการลบเพื่อแสดงรหัสพนักงานในข้อความแจ้งเตือน
        $employee = $this->employeeModel->query()
            ->select('employee_code')
            ->where('id', '=', (int)$id)
            ->first();
        
        
        $result = $this->employeeModel->deleteEmployee((int)$id); // ลบพนักงานตาม id

        // ตรวจสอบผลลัพธ์การลบและแจ้งเตือนผู้ใช้
        if ($result > 0) {
            $this->flash('success', 'ลบพนักงานสำเร็จ รหัสพนักงาน: ' . $employee['employee_code']);
        } else {
            $this->flash('error', 'เกิดข้อผิดพลาดขณะลบพนักงาน รหัสพนักงาน: ' . $employee['employee_code']);
        }

        return $this->redirect('/employees');
    }

    /**
     * ฟังก์ชันช่วยเหลือสำหรับดึงเฉพาะฟิลด์ที่ต้องการจาก Request
     * และตรวจสอบข้อมูลที่ส่งมาว่ามีฟิลด์ที่ต้องการหรือไม่
     */
    private function checkValidationAndGetData(string $messege): bool|array
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
        $validator = new Validator($data, $rules);
        if ($validator->fails()) {
            $this->flash('error', $messege);
            $this->flashInput($data); // เก็บข้อมูลที่ผู้ใช้กรอกไว้ใน session เพื่อใช้ในฟอร์มอีกครั้ง
            $this->flash('validation_errors', $validator->errors()); // เก็บ error แยก
            return false;
        }

        return $data;
    }

    /**
     * ค้นหาพนักงานตามชื่อหรือแผนก
     */
    public function search(): Response
    {
        $query = $this->request->get('query', ''); // รับคำค้นหาจาก query parameter

        // ดึงข้อมูลพนักงานที่ตรงกับคำค้นหา (ค้นหาจากชื่อและแผนก)
        $employees = $this->employeeModel->searchEmployees($query);
        
        // ถ้าไม่พบพนักงานที่ตรงกับคำค้นหา จะแจ้งเตือนและกลับไปยังหน้ารายการพนักงาน
        if (count($employees) === 0) {
            $this->flash('error', 'ไม่พบพนักงานที่ตรงกับคำค้นหา: ' . $query);
            return $this->redirect('/employees');
        }
        

        // แสดงผลลัพธ์การค้นหาในหน้า search_results
        return $this->responseView('employees/index', [
            'employees' => $employees
            ],
                'layouts/main'
            );
    }
}
