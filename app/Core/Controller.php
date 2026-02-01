<?php
/**
 * คลาสตัวควบคุมพื้นฐาน หรือ Controller Base จาก core
 * 
 * จุดประสงค์: คลาสแม่สำหรับตัวควบคุมทั้งหมด ให้ฟังก์ชันการทำงานทั่วไป
 * ปรัชญา: รักษาตัวควบคุมให้บาง - มอบหมายตรรกะทางธุรกิจให้โมเดล
 * Controller ควรใช้กับอะไร: เมื่อคุณสร้างตัวควบคุมใหม่สำหรับจัดการคำขอ HTTP
 * 
 * ความรับผิดชอบของตัวควบคุม:
 * - ตรวจสอบความถูกต้องของคำขอที่เข้ามา
 * - เรียกเมธอดของโมเดลสำหรับตรรกะทางธุรกิจ
 * - ส่งข้อมูลไปยังวิวหรือคืนค่าการตอบกลับ
 * - จัดการการตอบกลับ HTTP (การเปลี่ยนเส้นทาง, รหัสสถานะ)
 * 
 * ตัวควบคุมไม่ควร:
 * - มีตรรกะทางธุรกิจที่ซับซ้อน
 * - จัดการฐานข้อมูลโดยตรง
 * - ดำเนินการคำนวณหรือประมวลผลข้อมูล
 * 
 * ตรรกะทางธุรกิจทั้งหมดอยู่ในคลาสโมเดล!
 */

namespace App\Core;

class Controller
{
    /**
     * แสดงผลวิวพร้อมข้อมูล
     * จุดประสงค์: แสดงผลวิว HTML โดยส่งข้อมูลไปยังวิวโดยไม่ต้องคืนค่า Response
     * view() ควรใช้กับอะไร: เมื่อคุณต้องการแสดงผลหน้าวิวพร้อมข้อมูลในตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->view('home', ['name' => 'John']);
     * ```
     * 
     * @param string $view กำหนดชื่อวิวที่จะโหลด
     * @param array $data กำหนดข้อมูลที่จะส่งไปยังวิว
     * @return void ไม่คืนค่าอะไร
     */
    protected function view(string $view, array $data = []): void
    {
        // ใช้ View engine เพื่อรองรับ layouts/sections
        (new View($view, $data))->show();
    }

    /**
     * สร้าง Response สำหรับ view (เหมาะกับ controller ที่ต้องการ return Response)
     * จุดประสงค์: สร้าง Response HTML จากวิวพร้อมข้อมูลและเลย์เอาต์ (ถ้ามี)
     * responseView() ควรใช้กับอะไร: เมื่อคุณต้องการคืนค่า Response HTML จากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->responseView('home', ['name' => 'John'], 'main_layout', 200);
     * ```
     *
     * @param string $view กำหนดชื่อวิวที่จะโหลด
     * @param array $data กำหนดข้อมูลที่จะส่งไปยังวิว
     * @param string|null $layout กำหนดชื่อเลย์เอาต์ (ถ้ามี)
     * @param int $statusCode กำหนดรหัสสถานะ HTTP เช่น 200, 404
     * @return Response คืนค่า Response HTML
     */
    protected function responseView(string $view, array $data = [], ?string $layout = null, int $statusCode = 200): Response
    {
        $engine = new View($view, $data);
        if ($layout !== null && $layout !== '') {
            $engine->layout($layout);
        }

        return Response::html($engine->render(), $statusCode);
    }

    /**
     * เปลี่ยนเส้นทางไปยัง URL อื่น
     * จุดประสงค์: สร้างการตอบกลับ HTTP redirect
     * redirect() ควรใช้กับอะไร: เมื่อคุณต้องการเปลี่ยนเส้นทางผู้ใช้ไปยัง URL อื่นจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->redirect('/home', 301);
     * ```
     * 
     * @param string $url กำหนด URL ที่จะเปลี่ยนเส้นทางไป
     * @param int $statusCode กำหนดรหัสสถานะ HTTP เช่น 302, 301
     * @return Response คืนค่า Response redirect
     */
    protected function redirect(string $url, int $statusCode = 302): Response
    {
        return Response::redirect($url, $statusCode);
    }

    /**
     * คืนค่าการตอบกลับแบบ JSON
     * จุดประสงค์: สร้างการตอบกลับ JSON สำหรับ API
     * json() ควรใช้กับอะไร: เมื่อคุณต้องการคืนค่าการตอบกลับ JSON จากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->json(true, ['id' => 1, 'name' => 'John'], 'User retrieved successfully', [], 200);
     * 
     * รูปแบบ JSON มาตรฐานสำหรับการตอบกลับ API:
     * {
     *   "success": true|false,
     *   "data": {...},
     *   "message": "...",
     *   "errors": [...]
     * }
     * ```
     * 
     * @param bool $success กำหนดสถานะความสำเร็จของการตอบกลับ
     * @param mixed $data กำหนดข้อมูลที่จะส่งกลับ (ถ้ามี)
     * @param string $message กำหนดข้อความเพิ่มเติม (ไม่บังคับ)
     * @param array $errors กำหนดอาร์เรย์ข้อผิดพลาด (ไม่บังคับ)
     * @param int $statusCode กำหนดรหัสสถานะ HTTP
     * @return Response คืนค่า Response JSON
     */
    protected function json(bool $success, $data = null, string $message = '', array $errors = [], int $statusCode = 200): Response
    {
        return $this->responseJson($success, $data, $message, $errors, $statusCode);
    }

    /**
     * สร้าง Response แบบ JSON (ไม่ exit) เพื่อใช้กับ Router ที่รองรับ return Response
     * จุดประสงค์: สร้างการตอบกลับ JSON สำหรับ API
     * responseJson() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างการตอบกลับ JSON จากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->responseJson(true, ['id' => 1, 'name' => 'John'], 'User retrieved successfully', [], 200);
     * ```
     *
     * @param bool $success กำหนดสถานะความสำเร็จของการตอบกลับ
     * @param mixed $data กำหนดข้อมูลที่จะส่งกลับ (ถ้ามี)
     * @param string $message กำหนดข้อความเพิ่มเติม (ไม่บังคับ)
     * @param array $errors กำหนดอาร์เรย์ข้อผิดพลาด (ไม่บังคับ)
     * @param int $statusCode กำหนดรหัสสถานะ HTTP
     * @return Response คืนค่า Response JSON
     */
    protected function responseJson(bool $success, $data = null, string $message = '', array $errors = [], int $statusCode = 200): Response
    {
        if ($success) {
            return Response::apiSuccess($data, $message !== '' ? $message : 'Success', [], $statusCode);
        }

        return Response::apiError($message !== '' ? $message : 'Error', $errors, $statusCode);
    }

    /**
     * สร้าง Response redirect (ไม่ exit)
     * จุดประสงค์: สร้างการตอบกลับ HTTP redirect
     * responseRedirect() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างการตอบกลับ redirect จากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->responseRedirect('/home', 301);
     * ```
     * 
     * @param string $url กำหนด URL ที่จะเปลี่ยนเส้นทางไป
     * @param int $statusCode กำหนดรหัสสถานะ HTTP เช่น 302, 301
     * @return Response คืนค่า Response redirect
     */
    protected function responseRedirect(string $url, int $statusCode = 302): Response
    {
        return Response::redirect($url, $statusCode);
    }

    /**
     * ตรวจสอบพารามิเตอร์ POST ที่จำเป็น
     * จุดประสงค์: ตรวจสอบว่าพารามิเตอร์ที่จำเป็นทั้งหมดมีอยู่ในคำขอ POST
     * validateRequired() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าพารามิเตอร์ที่จำเป็นถูกส่งมาหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $missing = $this->validateRequired(['username', 'password', 'email']);
     * ```
     * 
     * @param array $required กำหนดอาร์เรย์ของชื่อพารามิเตอร์ที่จำเป็น
     * @return array คืนค่าอาร์เรย์ของชื่อพารามิเตอร์ที่ขาดหาย
     */
    protected function validateRequired(array $required): array
    {
        $missing = [];

        foreach ($required as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * ทำความสะอาดสตริงที่ป้อนเข้า
     * จุดประสงค์: ป้องกัน XSS โดยการลบแท็ก HTML และช่องว่าง
     * sanitize() ควรใช้กับอะไร: เมื่อคุณต้องการทำความสะอาดข้อมูลสตริงที่ป้อนเข้าจากผู้ใช้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $cleanInput = $this->sanitize($_POST['user_input']);
     * ```
     * 
     * @param string $input กำหนดสตริงที่จะทำความสะอาด
     * @return string คืนค่าสตริงที่ทำความสะอาดแล้ว
     */
    protected function sanitize(string $input): string
    {
        return trim(strip_tags($input));
    }

    /**
     * ตรวจสอบความถูกต้องของข้อมูลจำนวนเต็ม
     * จุดประสงค์: ตรวจสอบว่าค่าเป็นจำนวนเต็มบวก
     * validateInt() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าค่าที่ป้อนเข้าคือจำนวนเต็มบวก
     * ตัวอย่างการใช้งาน:
     * ```php
     * $validId = $this->validateInt($_POST['id']);
     * ```
     * 
     * @param mixed $value กำหนดค่าที่จะตรวจสอบ
     * @return int|null คืนค่าจำนวนเต็มที่ถูกต้องหรือ null
     */
    protected function validateInt($value): ?int
    {
        $int = filter_var($value, FILTER_VALIDATE_INT);
        return ($int !== false && $int > 0) ? $int : null;
    }

    /**
     * ตรวจสอบความถูกต้องของข้อมูลทศนิยม
     * จุดประสงค์: ตรวจสอบว่าค่าเป็นจำนวนบวกทศนิยม
     * validateFloat() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าค่าที่ป้อนเข้าคือจำนวนบวกทศนิยม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $validPrice = $this->validateFloat($_POST['price']);
     * ```
     * 
     * @param mixed $value กำหนดค่าที่จะตรวจสอบ
     * @return float|null คืนค่าทศนิยมที่ถูกต้องหรือ null
     */
    protected function validateFloat($value): ?float
    {
        $float = filter_var($value, FILTER_VALIDATE_FLOAT);
        return ($float !== false && $float >= 0) ? $float : null;
    }

    /**
     * ดึง ID ของผู้ใช้ที่ยืนยันตัวตนปัจจุบัน
     * จุดประสงค์: รับ ID ของผู้ใช้ที่ยืนยันตัวตน
     * getUserId() ควรใช้กับอะไร: เมื่อคุณต้องการรับ ID ของผู้ใช้ที่ยืนยันตัวตนในระบบ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $userId = $this->getUserId();
     * ```
     * 
     * @return int|null คืนค่า ID ผู้ใช้หรือ null ถ้าไม่ได้ยืนยันตัวตน
     */
    protected function getUserId(): ?int
    {
        // Use Auth to obtain the current authenticated user id
        return Auth::id();
    }

    /**
     * ตรวจสอบว่าผู้ใช้ยืนยันตัวตนหรือไม่
     * จุดประสงค์: ตรวจสอบสถานะการยืนยันตัวตนของผู้ใช้
     * isAuthenticated() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าผู้ใช้ได้ยืนยันตัวตนแล้วหรือไม่
     * ตัวอย่างการใช้งานโดยรวม:
     * ```php
     * // ตรวจสอบว่าผู้ใช้ยืนยันตัวตนหรือไม่
     * if ($this->isAuthenticated()) {
     *    // ผู้ใช้ยืนยันตัวตนแล้ว
     * } else {
     *    // ผู้ใช้ไม่ได้ยืนยันตัวตน
     * }
     * ```
     * 
     * @return bool คืนค่า true หากผู้ใช้ยืนยันตัวตน, false หากไม่ใช่
     */
    protected function isAuthenticated(): bool
    {
        // Delegate authentication check to Auth
        return Auth::check();
    }
}
