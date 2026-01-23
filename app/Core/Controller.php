<?php
/**
 * คลาสตัวควบคุมพื้นฐาน
 * 
 * จุดประสงค์: คลาสแม่สำหรับตัวควบคุมทั้งหมด ให้ฟังก์ชันการทำงานทั่วไป
 * ปรัชญา: รักษาตัวควบคุมให้บาง - มอบหมายตรรกะทางธุรกิจให้โมเดล
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
     * 
     * วิวอยู่ใน app/Views/
     * ตัวอย่าง: view('products/index', ['products' => $products])
     * จะโหลด: app/Views/products/index.php
     * 
     * @param string $view เส้นทางไฟล์วิว (ไม่มีนามสกุล .php)
     * @param array $data ข้อมูลที่จะส่งไปยังวิว
     */
    protected function view(string $view, array $data = []): void
    {
        // ใช้ View engine เพื่อรองรับ layouts/sections
        (new View($view, $data))->show();
    }

    /**
     * สร้าง Response สำหรับ view (เหมาะกับ controller ที่ต้องการ return Response)
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
     * 
     * @param string $url URL ที่จะเปลี่ยนเส้นทางไป
     */
    protected function redirect(string $url, int $statusCode = 302): Response
    {
        return Response::redirect($url, $statusCode);
    }

    /**
     * คืนค่าการตอบกลับแบบ JSON
     * 
     * รูปแบบ JSON มาตรฐานสำหรับการตอบกลับ API:
     * {
     *   "success": true|false,
     *   "data": {...},
     *   "message": "...",
     *   "errors": [...]
     * }
     * 
     * @param bool $success สถานะความสำเร็จ
     * @param mixed $data ข้อมูลการตอบกลับ
     * @param string $message ข้อความเพิ่มเติม (ไม่บังคับ)
     * @param array $errors อาร์เรย์ข้อผิดพลาด (ไม่บังคับ)
     * @param int $statusCode รหัสสถานะ HTTP
     */
    protected function json(bool $success, $data = null, string $message = '', array $errors = [], int $statusCode = 200): Response
    {
        return $this->responseJson($success, $data, $message, $errors, $statusCode);
    }

    /**
     * สร้าง Response แบบ JSON (ไม่ exit) เพื่อใช้กับ Router ที่รองรับ return Response
     *
     * @param mixed $data
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
     */
    protected function responseRedirect(string $url, int $statusCode = 302): Response
    {
        return Response::redirect($url, $statusCode);
    }

    /**
     * ตรวจสอบพารามิเตอร์ POST ที่จำเป็น
     * 
     * คืนค่าอาร์เรย์ของพารามิเตอร์ที่ขาดหายหรืออาร์เรย์ว่างถ้ามีครบ
     * 
     * @param array $required อาร์เรย์ของชื่อพารามิเตอร์ที่จำเป็น
     * @return array ชื่อพารามิเตอร์ที่ขาดหาย
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
     * 
     * ลบแท็ก HTML และช่องว่าง
     * ใช้สำหรับข้อมูลข้อความที่ผู้ใช้ป้อนเข้ามา
     * 
     * @param string $input ข้อมูลดิบ
     * @return string ข้อมูลที่ทำความสะอาดแล้ว
     */
    protected function sanitize(string $input): string
    {
        return trim(strip_tags($input));
    }

    /**
     * ตรวจสอบความถูกต้องของข้อมูลจำนวนเต็ม
     * 
     * ตรวจสอบว่าค่าเป็นจำนวนเต็มบวก
     * ใช้สำหรับ ID, จำนวน, ฯลฯ
     * 
     * @param mixed $value ค่าที่จะตรวจสอบ
     * @return int|null จำนวนเต็มที่ถูกต้องหรือ null
     */
    protected function validateInt($value): ?int
    {
        $int = filter_var($value, FILTER_VALIDATE_INT);
        return ($int !== false && $int > 0) ? $int : null;
    }

    /**
     * ตรวจสอบความถูกต้องของข้อมูลทศนิยม
     * 
     * ตรวจสอบว่าค่าเป็นจำนวนบวก
     * ใช้สำหรับราคา, จำนวนเงิน, ฯลฯ
     * 
     * @param mixed $value ค่าที่จะตรวจสอบ
     * @return float|null ทศนิยมที่ถูกต้องหรือ null
     */
    protected function validateFloat($value): ?float
    {
        $float = filter_var($value, FILTER_VALIDATE_FLOAT);
        return ($float !== false && $float >= 0) ? $float : null;
    }

    /**
     * ดึง ID ของผู้ใช้ที่ยืนยันตัวตนปัจจุบัน
     * 
     * @return int|null ID ผู้ใช้หรือ null ถ้าไม่ได้ยืนยันตัวตน
     */
    protected function getUserId(): ?int
    {
        Session::start();
        $userId = Session::get('user_id');
        return is_int($userId) ? $userId : null;
    }

    /**
     * ตรวจสอบว่าผู้ใช้ยืนยันตัวตนหรือไม่
     * 
     * @return bool
     */
    protected function isAuthenticated(): bool
    {
        Session::start();
        return Session::has('user_id');
    }
}
