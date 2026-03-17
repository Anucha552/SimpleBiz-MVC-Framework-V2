<?php
/**
 * ApiEmployeeController
 *
 * จุดประสงค์: เป็น Controller สำหรับจัดการพนักงานในระบบผ่าน API โดยสามารถเพิ่ม, แก้ไข, ลบ และแสดงข้อมูลพนักงานได้ตามต้องการ โดยจะเชื่อมต่อกับ Model ที่เกี่ยวข้องเพื่อดึงข้อมูลจากฐานข้อมูลและส่งข้อมูลไปยัง Response ในรูปแบบ JSON เพื่อให้ผู้ใช้สามารถเข้าถึงข้อมูลพนักงานได้อย่างง่ายดายผ่านทาง API
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Employee as Employees;
use App\Core\Request;
use App\Helpers\NumberHelper;

class ApiEmployeeController extends Controller
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
     * แสดงรายการพนักงานทั้งหมด (ส่งออกเป็น JSON)
     */
    public function index(): Response
    {
        // ดึงข้อมูลพนักงานทั้งหมดจาก Model
        $employees = $this->employeeModel->getAllEmployees();

        // ถ้าไม่มีข้อมูลพนักงานในระบบ ให้ส่ง response ว่างกลับไป
        if ($employees === []) {
            return Response::apiSuccess([
                'status' => 'ok',
                'timestamp' => date('c'),
                'employees' => [],
            ], 'ไม่พบข้อมูลพนักงานในระบบ');
        }

        // ส่งข้อมูลพนักงานทั้งหมดกลับไปในรูปแบบ JSON
        return Response::apiSuccess([
            'status' => 'ok',
            'timestamp' => date('c'),
            'employees' => $employees,
        ], 'ดึงข้อมูลพนักงานสำเร็จ');
    }

    /**
     * แสดงรายละเอียดพนักงาน 1 คน ตาม id (ส่งออกเป็น JSON)
     */
    public function show($id): Response
    {
        // ดึงข้อมูลพนักงานตาม id จาก Model
        $employee = $this->employeeModel->getEmployeeById($id);

        // ถ้าไม่พบพนักงานตาม id ที่ระบุ ให้ส่ง response ว่างกลับไป
        if ($employee === null) {
            return Response::apiSuccess([
                'status' => 'ok',
                'timestamp' => date('c'),
                'employee' => null,
            ], 'ไม่พบข้อมูลพนักงานที่ระบุ');
        }

        // ส่งข้อมูลพนักงานกลับไปในรูปแบบ JSON
        return Response::apiSuccess([
            'status' => 'ok',
            'timestamp' => date('c'),
            'employee' => $employee,
        ], 'ดึงข้อมูลพนักงานสำเร็จ. ID: ' . $id);
    }

    /**
     * รับข้อมูลจาก Form แล้วบันทึกพนักงานใหม่ลงฐานข้อมูล (รับข้อมูลเป็น JSON)
     */
    public function store(): Response
    {
        // รับข้อมูลจาก JSON แล้วบันทึกลงฐานข้อมูลผ่าน Model
        $data = $this->request->json(); 

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

        $lastEmployee = $this->employeeModel->getAllEmployees();
        if (count($lastEmployee) > 0) {
            $saveData['employee_code'] = NumberHelper::generateCode(null, null, 3, $lastEmployee[count($lastEmployee) - 1]['employee_code'] ?? null);
        } else {
            $saveData['employee_code'] = 'EMP001'; // รหัสเริ่มต้นถ้าไม่มีพนักงานเลย
        }

        // บันทึกข้อมูลพนักงานใหม่ลงฐานข้อมูลผ่าน Model
        $newEmployeeId = $this->employeeModel->createEmployee($saveData);

        // ถ้าบันทึกไม่สำเร็จ ให้ส่ง response แสดงข้อผิดพลาดกลับไป
        if ($newEmployeeId === 0) {
            return Response::apiError('เกิดข้อผิดพลาดขณะบันทึกข้อมูล');
        }

        // ส่ง response กลับไปในรูปแบบ JSON พร้อมข้อมูลพนักงานใหม่ที่ถูกสร้างขึ้น
        return Response::apiSuccess([
            'status' => 'ok',
            'timestamp' => date('c'),
            'employee_id' => $newEmployeeId,
        ], 'สร้างพนักงานใหม่สำเร็จ. ID: ' . $newEmployeeId);
    }

    /**
     * รับข้อมูลที่แก้ไขแล้ว แล้วอัปเดตลงฐานข้อมูล (รับข้อมูลเป็น JSON)
     */
    public function update($id): Response
    {
        // ดึงข้อมูลพนักงานตาม id จาก Model เพื่อตรวจสอบว่าพนักงานที่จะแก้ไขมีอยู่จริงหรือไม่
        $employee = $this->employeeModel->getEmployeeById((int)$id);

        // ถ้าไม่พบพนักงานตาม id ที่ระบุ ให้ส่ง response แสดงข้อผิดพลาดกลับไป
        if (empty($employee)) {
            return Response::apiError('ไม่พบข้อมูลพนักงานที่คุณต้องการ');
        }

        // รับข้อมูลจาก JSON แล้วอัปเดตลงฐานข้อมูลผ่าน Model
        $data = $this->request->json(); 

        // ตรวจสอบและเตรียมข้อมูลสำหรับอัปเดต (เอาเฉพาะคอลัมน์ที่โมเดลรองรับ)
        $departmentId = $data['department'] ?? null;
        if ($departmentId === null || $departmentId === '') {
            $departmentId = $employee['department_id'] ?? null;
        }

        // ตรวจสอบว่า department_id เป็นตัวเลขและมีค่ามากกว่า 0 หรือไม่
        if (!is_numeric($departmentId) || (int)$departmentId <= 0) {
            return Response::apiError('กรุณาเลือกแผนกให้ถูกต้อง');
        }

        // ตรวจสอบและเตรียมข้อมูลเงินเดือน
        $salary = $data['salary'] ?? null;
        if ($salary === null || $salary === '') {
            $salary = $employee['salary'] ?? 0;
        }

        // เตรียมข้อมูลสำหรับอัปเดต (เอาเฉพาะคอลัมน์ที่โมเดลรองรับ)
        $updateData = [
            'first_name' => $data['first_name'] !== '' ? $data['first_name'] : ($employee['first_name'] ?? ''),
            'last_name' => $data['last_name'] !== '' ? $data['last_name'] : ($employee['last_name'] ?? ''),
            'department_id' => (int)$departmentId,
            'email' => $data['email'] !== '' ? $data['email'] : ($employee['email'] ?? ''),
            'phone' => $data['phone'] !== '' ? $data['phone'] : ($employee['phone'] ?? ''),
            'salary' => is_numeric($salary) ? (float)$salary : ($employee['salary'] ?? 0),
        ];

        // อัปเดตข้อมูลพนักงานในฐานข้อมูลผ่าน Model
        $result = $this->employeeModel->updateEmployee((int)$id, $updateData);
        if ($result === 0) {
            // อัปเดตไม่สำเร็จ
            return Response::apiError('เกิดข้อผิดพลาดขณะอัปเดตข้อมูล');
        }

        // ส่ง response กลับไปในรูปแบบ JSON พร้อมข้อมูลพนักงานที่ถูกอัปเดต
        return Response::apiSuccess([
            'status' => 'ok',
            'timestamp' => date('c'),
            'employee_id' => $id,
        ], 'อัปเดตข้อมูลพนักงานสำเร็จ. ID: ' . $id);
    }

    /**
    * ลบพนักงาน 1 คน ตาม id
    */
    public function destroy($id): Response
    {
        // ดึงข้อมูลพนักงานตาม id จาก Model เพื่อตรวจสอบว่าพนักงานที่จะแก้ไขมีอยู่จริงหรือไม่
        $employee = $this->employeeModel->getEmployeeById((int)$id);

        // ถ้าไม่พบพนักงานตาม id ที่ระบุ ให้ส่ง response แสดงข้อผิดพลาดกลับไป
        if (empty($employee)) {
            return Response::apiError('ไม่พบข้อมูลพนักงานที่คุณต้องการ');
        }

        // ลบข้อมูลพนักงานจากฐานข้อมูลผ่าน Model
        $result = $this->employeeModel->deleteEmployee((int)$id);
        if ($result === 0) {
            // ลบไม่สำเร็จ
            return Response::apiError('เกิดข้อผิดพลาดขณะลบข้อมูล');
        }

        // ส่ง response กลับไปในรูปแบบ JSON พร้อมข้อมูลพนักงานที่ถูกลบ
        return Response::apiSuccess([
            'status' => 'ok',
            'timestamp' => date('c'),
            'employee_id' => $id,
        ], 'ลบข้อมูลพนักงานสำเร็จ. ID: ' . $id);
    }
}
