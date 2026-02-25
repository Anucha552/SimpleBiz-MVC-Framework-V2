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

use App\Helpers\UrlHelper;
use App\Helpers\FormHelper;
use App\Helpers\ArrayHelper;

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
     * แสดงผลวิวพร้อมข้อมูลและคืนค่า Response พร้อมกำหนด Cache
     * จุดประสงค์: แสดงผลวิว HTML โดยส่งข้อมูลไปยังวิวและคืนค่า Response
     * responseView() ควรใช้กับอะไร: เมื่อคุณต้องการแสดงผลหน้าวิวพร้อมข้อมูลและคืนค่า Response ในตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->responseView('home', ['name' => 'John'], 'main_layout', 200);
     * ```
     *
     * @param string $view กำหนดชื่อวิวที่จะโหลด
     * @param array $data กำหนดข้อมูลที่จะส่งไปยังวิว
     * @param string|null $layout กำหนดชื่อเลย์เอาต์ (ถ้ามี)
     * @param int $statusCode กำหนดรหัสสถานะ HTTP เช่น 200, 404
     * @param int|null $cacheSeconds กำหนดเวลาการแคชในหน่วยวินาที (ถ้ามี)
     * @return Response คืนค่า Response HTML
     */
    protected function responseView(string $view, array $data = [], ?string $layout = null, int $statusCode = 200, ?int $cacheSeconds = null): Response {
        $engine = new View($view, $data);

        if ($layout) {
            $engine->layout($layout);
        }

        if ($cacheSeconds !== null) {
            $engine->cache($cacheSeconds);
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

    /**
     * คืนค่า Request object (wrapper)
     * จุดประสงค์: ให้สามารถเข้าถึงข้อมูลคำขอผ่าน Request object ได้อย่างสะดวก
     * request() ควรใช้กับอะไร: เมื่อคุณต้องการเข้าถึงข้อมูลคำขอ เช่น พารามิเตอร์, เฮดเดอร์, หรือข้อมูลอื่น ๆ ผ่าน Request object
     * ตัวอย่างการใช้งาน:
     * ```php
     * $request = $this->request();
     * $username = $request->input('username');
     * ```
     */
    protected function request(): Request
    {
        return new Request();
    }

    /**
     * ย่อการเรียก input จาก Request
     * จุดประสงค์: ให้สามารถเข้าถึงข้อมูล input จากคำขอได้อย่างสะดวก
     * input() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่าจากคำขอ เช่น พารามิเตอร์หรือข้อมูลฟอร์ม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $username = $this->input('username');
     * ```
     * 
     * @param string|null $key กำหนดชื่อพารามิเตอร์ที่ต้องการดึง หรือ null เพื่อดึงทั้งหมด
     * @param mixed $default กำหนดค่าดีฟอลต์เมื่อพารามิเตอร์ไม่ถูกส่งมา
     * @return mixed คืนค่าพารามิเตอร์ที่ดึงมา หรือค่าดีฟอลต์ถ้าไม่ถูกส่งมา
     */
    protected function input(?string $key = null, $default = null)
    {
        return $this->request()->input($key, $default);
    }

    /**
     * ย่อการเรียก only จาก Request
     * จุดประสงค์: ให้สามารถเข้าถึงข้อมูลคำขอเฉพาะบางพารามิเตอร์ได้อย่างสะดวก
     * only() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่าจากคำขอเฉพาะพารามิเตอร์ที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $data = $this->only(['username', 'email']);
     * ```
     * 
     * @param array $keys กำหนดอาร์เรย์ของชื่อพารามิเตอร์ที่ต้องการดึง
     * @return array คืนค่าอาร์เรย์ของพารามิเตอร์ที่ดึงมา
     */
    protected function only(array $keys): array
    {
        return $this->request()->only($keys);
    }

    /**
     * ย่อการเรียก all จาก Request
     * จุดประสงค์: ให้สามารถเข้าถึงข้อมูลคำขอทั้งหมดได้อย่างสะดวก
     * all() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่าจากคำขอทั้งหมด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $data = $this->all();
     * ```
     * 
     * @return array คืนค่าอาร์เรย์ของพารามิเตอร์ทั้งหมด
     */
    protected function all(): array
    {
        return $this->request()->all();
    }

    /**
     * Redirect กลับไปหน้าที่แล้ว (wrapper)
     * จุดประสงค์: ให้สามารถเปลี่ยนเส้นทางกลับไปยังหน้าที่แล้วได้อย่างสะดวก
     * back() ควรใช้กับอะไร: เมื่อคุณต้องการเปลี่ยนเส้นทางกลับไปยังหน้าที่แล้วจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->back();
     * ```
     * 
     * @param string|null $default กำหนด URL ดีฟอลต์เมื่อไม่สามารถย้อนกลับได้
     * @return Response คืนค่า Response redirect กลับไปยังหน้าที่แล้ว หรือ URL ดีฟอลต์ถ้าไม่สามารถย้อนกลับได้
     */
    protected function back(?string $default = null): Response
    {
        return UrlHelper::back($default);
    }

    /**
     * ตั้ง flash message (wrapper)
     * จุดประสงค์: ให้สามารถตั้งค่า flash message เพื่อใช้ในคำขอถัดไปได้อย่างสะดวก
     * flash() ควรใช้กับอะไร: เมื่อคุณต้องการตั้งค่า flash message เพื่อใช้ในคำขอถัดไปจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->flash('success', 'Your profile has been updated successfully.');
     * ```
     * 
     * @param string $key กำหนดชื่อของ flash message
     * @param mixed $value กำหนดค่าของ flash message
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะตั้งค่า flash message ใน session เพื่อใช้ในคำขอถัดไป
     */
    protected function flash(string $key, $value): void
    {
        // Session::flash จะจัดการ start() เอง
        \App\Core\Session::flash($key, $value);
    }

    /**
     * ดึงค่า old input ผ่าน FormHelper
     * จุดประสงค์: ให้สามารถดึงค่า old input ที่ถูกแฟลชไว้ในคำขอถัดไปได้อย่างสะดวก
     * old() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่า old input ที่ถูกแฟลชไว้ในคำขอถัดไปจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $oldUsername = $this->old('username', 'default_username');
     * ```
     * 
     * @param string $key กำหนดชื่อของค่า old input ที่ต้องการดึง
     * @param mixed $default กำหนดค่าดีฟอลต์เมื่อค่า old input ไม่ถูกแฟลชไว้
     * @param bool $escape กำหนดว่าควรทำการ escape ค่าที่ดึงมาหรือไม่ (ป้องกัน XSS)
     * @return string คืนค่าของ old input ที่ดึงมา หรือค่าดีฟอลต์ถ้าไม่ถูกแฟลชไว้ โดยอาจถูก escape ตามที่กำหนดไว้ในพารามิเตอร์ $escape
     */
    protected function old(string $key, $default = '', bool $escape = true): string
    {
        return FormHelper::old($key, $default, $escape);
    }

    /**
     * เบื้องต้น: สร้าง Validator และคืน Validator instance
     * ไม่ทำ redirect อัตโนมัติ เพื่อให้ caller ควบคุมการไหลได้
     * จุดประสงค์: ให้สามารถตรวจสอบความถูกต้องของข้อมูลด้วย Validator ได้อย่างสะดวก
     * validate() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบความถูกต้องของข้อมูลด้วย Validator จากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $validator = $this->validate($data, [
     *     'username' => 'required|min:3|max:20',
     *     'email' => 'required|email',
     * ]);
     * ```
     * 
     * @param array $data กำหนดอาร์เรย์ของข้อมูลที่จะตรวจสอบ
     * @param array $rules กำหนดอาร์เรย์ของกฎการตรวจสอบความถูกต้อง
     * @param array $customMessages กำหนดอาร์เรย์ของข้อความและกฎที่กำหนดเอง (ไม่บังคับ)
     * @return Validator คืนค่า Validator instance หลังจากทำการ validate แล้ว
     */
    protected function validate(array $data, array $rules, array $customMessages = []): Validator
    {
        $validator = new Validator($data, $rules, $customMessages);
        $validator->validate();
        return $validator;
    }

    /**
     * ตรวจสอบสิทธิ์อย่างง่าย: ถ้าไม่ผ่าน คืน Response redirect ตามที่ระบุ
     * จุดประสงค์: ให้สามารถตรวจสอบสิทธิ์และเปลี่ยนเส้นทางผู้ใช้ได้อย่างสะดวก
     * authorize() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบสิทธิ์และเปลี่ยนเส้นทางผู้ใช้จากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->authorize(Auth::check(), '/login');
     * ```
     * 
     * @param bool $allowed กำหนดสถานะการอนุญาต (true ถ้าอนุญาต, false ถ้าไม่อนุญาต)
     * @param string $redirect กำหนด URL ที่จะเปลี่ยนเส้นทางไปเมื่อไม่อนุญาต
     * @return Response|null คืนค่า Response redirect เมื่อไม่อนุญาต หรือ null เมื่ออนุญาต
     */
    protected function authorize(bool $allowed, string $redirect = '/login'): ?Response
    {
        if ($allowed) {
            return null;
        }

        return $this->redirect($redirect);
    }

    /**
     * คืนข้อมูลผู้ใช้แบบเต็ม (wrapper)
     * จุดประสงค์: ให้สามารถเข้าถึงข้อมูลผู้ใช้ที่ยืนยันตัวตนได้อย่างสะดวก
     * currentUser() ควรใช้กับอะไร: เมื่อคุณต้องการเข้าถึงข้อมูลผู้ใช้ที่ยืนยันตัวตนจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $user = $this->currentUser();
     * ```
     * 
     * @return User|null คืนค่าข้อมูลผู้ใช้ที่ยืนยันตัวตน หรือ null ถ้าไม่มีผู้ใช้ที่ยืนยันตัวตน
     */
    protected function currentUser()
    {
        return Auth::user();
    }

    /**
     * ดึง flash message (wrapper)
     * จุดประสงค์: ให้สามารถดึงค่า flash message ที่ถูกตั้งไว้ในคำขอถัดไปได้อย่างสะดวก
     * getFlash() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่า flash message ที่ถูกตั้งไว้ในคำขอถัดไปจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $successMessage = $this->getFlash('success', 'No message');
     * ```
     * 
     * @param string $key กำหนดชื่อของ flash message ที่ต้องการดึง
     * @param mixed $default กำหนดค่าดีฟอลต์เมื่อ flash message ไม่ถูกตั้งไว้
     * @return mixed คืนค่าของ flash message ที่ดึงมา หรือค่าดีฟอลต์ถ้าไม่ถูกตั้งไว้
     */
    protected function getFlash(string $key, $default = null)
    {
        return FormHelper::flash($key, $default);
    }

    /**
     * ตรวจสอบว่ามี flash message มีหรือไม่
     * จุดประสงค์: ให้สามารถตรวจสอบว่ามี flash message ที่ถูกตั้งไว้หรือไม่อย่างสะดวก
     * hasFlash() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่ามี flash message ที่ถูกตั้งไว้จากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * if ($this->hasFlash('success')) {
     *     // ทำบางอย่าง
     * }
     * ```
     * 
     * @param string $key กำหนดชื่อของ flash message ที่ต้องการตรวจสอบ
     * @return bool คืนค่า true ถ้ามี flash message ที่ถูกตั้งไว้ หรือ false ถ้าไม่มี
     */
    protected function hasFlash(string $key): bool
    {
        return FormHelper::hasFlash($key);
    }

    /**
     * ดึง flash messages ทั้งหมด
     * จุดประสงค์: ให้สามารถดึงค่า flash messages ทั้งหมดที่ถูกตั้งไว้ในคำขอถัดไปได้อย่างสะดวก
     * allFlash() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่า flash messages ทั้งหมดที่ถูกตั้งไว้ในคำขอถัดไปจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $allFlash = $this->allFlash();
     * ```
     * 
     * @return array คืนค่าอาร์เรย์ของ flash messages ทั้งหมดที่ถูกตั้งไว้ในคำขอถัดไป
     */
    protected function allFlash(): array
    {
        return FormHelper::allFlash();
    }

    /**
     * แฟลช input เพื่อใช้กับ old() ในคำขอถัดไป
     * จุดประสงค์: ให้สามารถแฟลชค่า input เพื่อใช้กับ old() ในคำขอถัดไปได้อย่างสะดวก
     * flashInput() ควรใช้กับอะไร: เมื่อคุณต้องการแฟลชค่า input เพื่อใช้กับ old() ในคำขอถัดไปจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->flashInput($this->all());
     * ```
     * 
     * @param array $input กำหนดอาร์เรย์ของค่า input ที่ต้องการแฟลช
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะแฟลชค่า input ใน session เพื่อใช้กับ old() ในคำขอถัดไป
     */
    protected function flashInput(array $input): void
    {
        \App\Core\Session::flashInput($input);
    }

    /**
     * ดึงค่า old input แบบดิบ
     * จุดประสงค์: ให้สามารถดึงค่า old input แบบดิบที่ถูกแฟลชไว้ในคำขอถัดไปได้อย่างสะดวก
     * oldRaw() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่า old input แบบดิบที่ถูกแฟลชไว้ในคำขอถัดไปจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $oldUsernameRaw = $this->oldRaw('username', 'default_username');
     * ```
     * 
     * @param string|null $key กำหนดชื่อของค่า old input ที่ต้องการดึง หรือ null เพื่อดึงทั้งหมด
     * @param mixed $default กำหนดค่าดีฟอลต์เมื่อค่า old input ไม่ถูกแฟลชไว้
     * @return mixed คืนค่าของ old input แบบดิบที่ดึงมา หรือค่าดีฟอลต์ถ้าไม่ถูกแฟลชไว้ โดยไม่มีการ escape หรือการแปลงใด ๆ
     */
    protected function oldRaw(?string $key = null, $default = null)
    {
        return FormHelper::oldRaw($key, $default);
    }

    /**
     * Validate และถ้าล้มเหลวให้ flash errors + old input แล้ว redirect back/ไปที่ $redirect
     * คืนค่า Response เมื่อ redirect เกิดขึ้น หรือ null เมื่อผ่าน
     * จุดประสงค์: ให้สามารถตรวจสอบความถูกต้องของข้อมูลและจัดการการตอบกลับเมื่อเกิดข้อผิดพลาดได้อย่างสะดวก
     * validateOrRedirect() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบความถูกต้องของข้อมูลและจัดการการตอบกลับเมื่อเกิดข้อผิดพลาดจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->validateOrRedirect($data, [
     *     'username' => 'required|string|max:255',
     *     'email' => 'required|email|max:255',
     * ], [
     *     'username.required' => 'กรุณากรอกชื่อผู้ใช้',
     *     'email.required' => 'กรุณากรอกอีเมล',
     * ]);
     * ```
     * 
     * @param array $data กำหนดอาร์เรย์ของข้อมูลที่จะตรวจสอบ
     * @param array $rules กำหนดอาร์เรย์ของกฎการตรวจสอบความถูกต้อง
     * @param array $customMessages กำหนดอาร์เรย์ของข้อความและกฎที่กำหนดเอง (ไม่บังคับ)
     * @param string|null $redirect กำหนด URL ที่จะเปลี่ยนเส้นทางไปเมื่อเกิดข้อผิดพลาด (ถ้า null จะ redirect กลับไปยังหน้าที่แล้ว)
     * @return Response|null คืนค่า Response redirect เมื่อเกิดข้อผิดพลาด หรือ null เมื่อข้อมูลถูกต้องและผ่านการตรวจสอบความถูกต้อง
     */
    protected function validateOrRedirect(array $data, array $rules, array $customMessages = [], ?string $redirect = null): ?Response
    {
        // สร้าง Validator และทำการ validate
        $validator = $this->validate($data, $rules, $customMessages);

        // ถ้า validation ล้มเหลว ให้ flash errors และ old input แล้ว redirect
        if ($validator->fails()) {
            \App\Core\Session::flash('validation_errors', $validator->errors()); // แฟลชข้อผิดพลาดการตรวจสอบความถูกต้อง
            \App\Core\Session::flashInput($data); // แฟลชข้อมูล input เพื่อใช้กับ old() ในคำขอถัดไป

            // ถ้า $redirect ถูกกำหนด ให้ redirect ไปที่ URL นั้น, ถ้าไม่ก็ redirect กลับไปยังหน้าที่แล้ว
            if ($redirect !== null) {
                return $this->redirect($redirect);
            }

            return $this->back();
        }

        return null;
    }

    /**
     * ส่ง JSON success response (wrapper)
     * จุดประสงค์: ให้สามารถส่งการตอบกลับ JSON ที่แสดงถึงความสำเร็จได้อย่างสะดวก
     * jsonSuccess() ควรใช้กับอะไร: เมื่อคุณต้องการส่งการตอบกลับ JSON ที่แสดงถึงความสำเร็จจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->jsonSuccess(['id' => 1, 'name' => 'John'], 'User created successfully', 201);
     * ```
     * 
     * @param mixed $data กำหนดข้อมูลที่จะส่งกลับ (ถ้ามี)
     * @param string $message กำหนดข้อความเพิ่มเติม (ไม่บังคับ)     
     * @param int $statusCode กำหนดรหัสสถานะ HTTP
     * @return Response คืนค่า Response JSON ที่แสดงถึงความสำเร็จ
     */
    protected function jsonSuccess($data = null, string $message = 'Success', int $statusCode = 200): Response
    {
        return $this->responseJson(true, $data, $message, [], $statusCode);
    }

    /**
     * ส่ง JSON error response (wrapper)
     * จุดประสงค์: ให้สามารถส่งการตอบกลับ JSON ที่แสดงถึงข้อผิดพลาดได้อย่างสะดวก
     * jsonError() ควรใช้กับอะไร: เมื่อคุณต้องการส่งการตอบกลับ JSON ที่แสดงถึงข้อผิดพลาดจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->jsonError('Invalid request', ['field' => 'Error message'], 422);
     * ```
     * 
     * @param string $message กำหนดข้อความข้อผิดพลาด
     * @param array $errors กำหนดรายละเอียดข้อผิดพลาดเพิ่มเติม (ไม่บังคับ)
     * @param int $statusCode กำหนดรหัสสถานะ HTTP
     * @return Response คืนค่า Response JSON ที่แสดงถึงข้อผิดพลาด
     */
    protected function jsonError(string $message = 'Error', array $errors = [], int $statusCode = 400): Response
    {
        return $this->responseJson(false, null, $message, $errors, $statusCode);
    }

    /**
     * สร้าง CSRF hidden field (wrapper)
     * จุดประสงค์: ให้สามารถสร้างฟิลด์ CSRF แบบซ่อนสำหรับฟอร์มได้อย่างสะดวก
     * csrfField() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างฟิลด์ CSRF แบบซ่อนสำหรับฟอร์มจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $csrfField = $this->csrfField();
     * ```
     * 
     * @return string คืนค่าฟิลด์ CSRF แบบซ่อนในรูปแบบ HTML
     */
    protected function csrfField(): string
    {
        return FormHelper::csrfField();
    }

    /**
     * สร้าง CSRF meta tag (wrapper)
     * จุดประสงค์: ให้สามารถสร้าง meta tag สำหรับ CSRF ได้อย่างสะดวก
     * csrfMeta() ควรใช้กับอะไร: เมื่อคุณต้องการสร้าง meta tag สำหรับ CSRF จากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $csrfMeta = $this->csrfMeta();
     * ```
     * 
     * @return string คืนค่า meta tag สำหรับ CSRF ในรูปแบบ HTML
     */
    protected function csrfMeta(): string
    {
        return FormHelper::csrfMeta();
    }

    /**
     * สร้าง URL สะดวก ๆ
     * จุดประสงค์: ให้สามารถสร้าง URL ได้อย่างสะดวกโดยใช้ UrlHelper
     * url() ควรใช้กับอะไร: เมื่อคุณต้องการสร้าง URL จากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $url = $this->url('/home', ['ref' => 'dashboard']);
     * ```
     * 
     * @param string $path กำหนด path หรือ route-like string ที่ต้องการสร้าง URL
     * @param array $params กำหนดอาร์เรย์ของพารามิเตอร์ที่จะเพิ่มลงใน URL
     * @return string คืนค่า URL ที่สร้างขึ้น
     */
    protected function url(string $path = '', array $params = []): string
    {
        return UrlHelper::to($path, $params);
    }

    /**
     * สร้าง asset URL
     * จุดประสงค์: ให้สามารถสร้าง URL สำหรับไฟล์ asset ได้อย่างสะดวก
     * asset() ควรใช้กับอะไร: เมื่อคุณต้องการสร้าง URL สำหรับไฟล์ asset จากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $assetUrl = $this->asset('css/app.css');
     * ```
     * 
     * @param string $path กำหนด path ของไฟล์ asset ที่ต้องการสร้าง URL
     * @return string คืนค่า URL สำหรับไฟล์ asset ที่สร้างขึ้น
     */
    protected function asset(string $path): string
    {
        return UrlHelper::asset($path);
    }

    /**
     * รับไฟล์อัปโหลดจากคำขอ
     * จุดประสงค์: ให้สามารถเข้าถึงไฟล์อัปโหลดจากคำขอได้อย่างสะดวก
     * file() ควรใช้กับอะไร: เมื่อคุณต้องการเข้าถึงไฟล์อัปโหลดจากคำขอในตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $uploadedFile = $this->file('avatar');
     * ```
     * 
     * @param string|null $key กำหนดชื่อของไฟล์อัปโหลดที่ต้องการดึง หรือ null เพื่อดึงทั้งหมด
     * @return UploadedFile|array|null คืนค่า UploadedFile หรืออาร์เรย์ของ UploadedFile ที่ดึงมา หรือ null ถ้าไม่มีไฟล์อัปโหลดที่ตรงกับชื่อที่ระบุ
     */
    protected function file(?string $key = null)
    {
        return $this->request()->file($key);
    }

    /**
     * สร้าง URL โดยใช้ path/route-like string
     * จุดประสงค์: ให้สามารถสร้าง URL ได้อย่างสะดวกโดยใช้ UrlHelper
     * route() ควรใช้กับอะไร: เมื่อคุณต้องการสร้าง URL จากตัวควบคุมโดยใช้ path หรือ route-like string
     * ตัวอย่างการใช้งาน:
     * ```php
     * $routeUrl = $this->route('/users/{id}', ['id' => 1]);
     * ```
     * 
     * @param string $path กำหนด path หรือ route-like string ที่ต้องการสร้าง URL
     * @param array $params กำหนดอาร์เรย์ของพารามิเตอร์ที่จะเพิ่มลงใน URL
     * @return string คืนค่า URL ที่สร้างขึ้น
     */
    protected function route(string $path = '', array $params = []): string
    {
        return UrlHelper::to($path, $params);
    }

    /**
     * โหลด/instantiate model โดยคาดว่าอยู่ใน App\Models namespace
     * จุดประสงค์: ให้สามารถโหลดหรือสร้างอินสแตนซ์ของโมเดลได้อย่างสะดวกโดยคาดว่าโมเดลอยู่ใน namespace ที่กำหนด
     * model() ควรใช้กับอะไร: เมื่อคุณต้องการโหลดหรือสร้างอินสแตนซ์ของโมเดลจากตัวควบคุมโดยคาดว่าโมเดลอยู่ใน namespace ที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $userModel = $this->model('User');
     * ```
     * 
     * @param string $name กำหนดชื่อของโมเดลที่ต้องการโหลดหรือสร้างอินสแตนซ์
     * @return object|null คืนค่าอินสแตนซ์ของโมเดลที่โหลดหรือสร้างขึ้น หรือ null ถ้าไม่พบคลาสโมเดลที่ตรงกับชื่อที่ระบุ
     */
    protected function model(string $name)
    {
        $className = "App\\Models\\$name";
        if (class_exists($className)) {
            return $className::class;
        }
        return null;
    }

    /**
     * Paginate an array (wrapper to ArrayHelper)
     * จุดประสงค์: ให้สามารถแบ่งหน้าอาร์เรย์ได้อย่างสะดวกโดยใช้ ArrayHelper
     * paginate() ควรใช้กับอะไร: เมื่อคุณต้องการแบ่งหน้าอาร์เรย์จากตัวควบคุมโดยใช้ ArrayHelper
     * ตัวอย่างการใช้งาน:
     * ```php
     * $paginatedItems = $this->paginate($items, $page, $perPage);
     * ```
     * 
     * @param array $items กำหนดอาร์เรย์ของไอเท็มที่ต้องการแบ่งหน้า
     * @param int $page กำหนดหมายเลขหน้าปัจจุบัน (เริ่มต้นที่ 1)
     * @param int $perPage กำหนดจำนวนไอเท็มต่อหน้า (เริ่มต้นที่ 15)
     * @return array คืนค่าอาร์เรย์ของไอเท็มที่ถูกแบ่งหน้า
     */
    protected function paginate(array $items, int $page = 1, int $perPage = 15): array
    {
        return ArrayHelper::paginate($items, $page, $perPage);
    }

    /**
     * ส่ง JSON 201 Created
     * จุดประสงค์: ให้สามารถส่งการตอบกลับ JSON ที่แสดงถึงการสร้างทรัพยากรใหม่ได้อย่างสะดวก
     * respondCreated() ควรใช้กับอะไร: เมื่อคุณต้องการส่งการตอบกลับ JSON ที่แสดงถึงการสร้างทรัพยากรใหม่จากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->respondCreated(['id' => 1, 'name' => 'John'], 'User created successfully');
     * ```
     * 
     * @param mixed $data กำหนดข้อมูลที่จะส่งกลับ (ถ้ามี)
     * @param string $message กำหนดข้อความเพิ่มเติม (ไม่บังคับ)
     * @return Response คืนค่า Response ที่สร้างขึ้น
     */
    protected function respondCreated($data = null, string $message = 'Created'): Response
    {
        return $this->responseJson(true, $data, $message, [], 201);
    }

    /**
     * คืนค่า 204 No Content
     * จุดประสงค์: ให้สามารถส่งการตอบกลับที่แสดงถึงไม่มีเนื้อหาได้อย่างสะดวก
     * noContent() ควรใช้กับอะไร: เมื่อคุณต้องการส่งการตอบกลับที่แสดงถึงไม่มีเนื้อหาจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->noContent();
     * ```
     * 
     * @return Response คืนค่า Response ที่แสดงถึงไม่มีเนื้อหา (204 No Content)
     */
    protected function noContent(): Response
    {
        return Response::noContent();
    }

    /**
     * ตรวจสอบว่ามี old input หรือไม่ (wrapper)
     * จุดประสงค์: ให้สามารถตรวจสอบว่ามีค่า old input ที่ถูกแฟลชไว้ในคำขอถัดไปหรือไม่อย่างสะดวก
     * hasOld() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่ามีค่า old input ที่ถูกแฟลชไว้ในคำขอถัดไปจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * if ($this->hasOld('username')) {
     *     // มีค่า old input สำหรับ 'username'
     * }
     * ```
     * 
     * @param string $key กำหนดชื่อของค่า old input ที่ต้องการตรวจสอบ
     * @return bool คืนค่า true ถ้ามีค่า old input ที่ถูกแฟลชไว้ในคำขอถัดไปสำหรับชื่อที่ระบุ หรือ false ถ้าไม่มี
     */
    protected function hasOld(string $key): bool
    {
        return FormHelper::hasOld($key);
    }

    /**
     * ดึงข้อผิดพลาดสำหรับฟิลด์หรือทั้งหมด (wrapper)
     * จุดประสงค์: ให้สามารถดึงข้อผิดพลาดสำหรับฟิลด์เฉพาะหรือทั้งหมดได้อย่างสะดวก
     * errors() ควรใช้กับอะไร: เมื่อคุณต้องการดึงข้อผิดพลาดสำหรับฟิลด์เฉพาะหรือทั้งหมดจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $errors = $this->errors('username'); // ดึงข้อผิดพลาดสำหรับฟิลด์ 'username'
     * $allErrors = $this->errors(); // ดึงข้อผิดพลาดทั้งหมด
     * ```
     * 
     * @param string|null $field กำหนดชื่อของฟิลด์ที่ต้องการดึงข้อผิดพลาด (ไม่ระบุจะดึงข้อผิดพลาดทั้งหมด)
     * @return array คืนค่าอาร์เรย์ของข้อผิดพลาดสำหรับฟิลด์ที่ระบุ หรือทั้งหมดถ้าไม่ระบุฟิลด์
     */
    protected function errors(?string $field = null): array
    {
        return FormHelper::errors($field);
    }

    /**
     * ตรวจสอบว่ามีข้อผิดพลาดสำหรับฟิลด์หรือไม่ (wrapper)
     * จุดประสงค์: ให้สามารถตรวจสอบว่ามีข้อผิดพลาดสำหรับฟิลด์เฉพาะหรือไม่อย่างสะดวก
     * hasError() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่ามีข้อผิดพลาดสำหรับฟิลด์เฉพาะจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * if ($this->hasError('username')) {
     *     // มีข้อผิดพลาดสำหรับฟิลด์ 'username'
     * }
     * ```
     * 
     * @param string $field กำหนดชื่อของฟิลด์ที่ต้องการตรวจสอบ
     * @return bool คืนค่า true ถ้ามีข้อผิดพลาดสำหรับฟิลด์ที่ระบุ หรือ false ถ้าไม่มี
     */
    protected function hasError(string $field): bool
    {
        return FormHelper::hasError($field);
    }

    /**
     * ดึงข้อความข้อผิดพลาดแรกสำหรับฟิลด์ (wrapper)
     * จุดประสงค์: ให้สามารถดึงข้อความข้อผิดพลาดแรกสำหรับฟิลด์เฉพาะได้อย่างสะดวก
     * firstError() ควรใช้กับอะไร: เมื่อคุณต้องการดึงข้อความข้อผิดพลาดแรกสำหรับฟิลด์เฉพาะจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $firstError = $this->firstError('username', 'No error', true);
     * ```
     * 
     * @param string $field กำหนดชื่อของฟิลด์ที่ต้องการดึงข้อความข้อผิดพลาดแรก
     * @param string|null $default กำหนดค่าดีฟอลต์เมื่อไม่มีข้อผิดพลาดสำหรับฟิลด์ที่ระบุ
     * @param bool $escape กำหนดว่าควรทำการ escape ค่าที่ดึงมาหรือไม่ (ป้องกัน XSS)
     * @return string|null คืนค่าข้อความข้อผิดพลาดแรกสำหรับฟิลด์ที่ระบุ หรือค่าดีฟอลต์ถ้าไม่มีข้อผิดพลาดสำหรับฟิลด์นั้น โดยอาจถูก escape ตามที่กำหนดไว้ในพารามิเตอร์ $escape
     */
    protected function firstError(string $field, ?string $default = null, bool $escape = true): ?string
    {
        return FormHelper::firstError($field, $default, $escape);
    }

    /**
     * คืนคลาส CSS เมื่อตรวจพบข้อผิดพลาด (wrapper)
     * invalidClass() ควรใช้กับอะไร: เมื่อคุณต้องการคืนคลาส CSS สำหรับฟิลด์ที่มีข้อผิดพลาดจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $usernameClass = $this->invalidClass('username', 'is-invalid');
     * ```
     * 
     * @param string $field กำหนดชื่อของฟิลด์ที่ต้องการตรวจสอบข้อผิดพลาด
     * @param string $class กำหนดชื่อคลาส CSS ที่จะคืนเมื่อตรวจพบข้อผิดพลาด (ค่าเริ่มต้นคือ 'is-invalid')
     * @return string คืนค่าชื่อคลาส CSS ที่กำหนดไว้ถ้ามีข้อผิดพลาดสำหรับฟิลด์ที่ระบุ หรือคืนค่าว่างถ้าไม่มีข้อผิดพลาดสำหรับฟิลด์นั้น
     */
    protected function invalidClass(string $field, string $class = 'is-invalid'): string
    {
        return FormHelper::invalidClass($field, $class);
    }

    /**
     * แชร์ข้อมูลให้ทุกวิว (wrapper)
     * จุดประสงค์: ให้สามารถแชร์ข้อมูลให้ทุกวิวได้อย่างสะดวก
     * share() ควรใช้กับอะไร: เมื่อคุณต้องการแชร์ข้อมูลให้ทุกวิวจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->share('key', 'value');
     * ```
     * 
     * @param string|array $key กำหนดชื่อของข้อมูลที่ต้องการแชร์ หรืออาร์เรย์ของชื่อและค่าที่ต้องการแชร์
     * @param mixed|null $value กำหนดค่าของข้อมูลที่ต้องการแชร์ (ไม่จำเป็นถ้า $key เป็นอาร์เรย์)
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะทำการแชร์ข้อมูลให้ทุกวิวผ่าน View::share
     */
    protected function share(string $key,mixed $value): void
    {
        View::share($key, $value);
    }

    /**
     * อ่านข้อมูลที่แชร์ไว้ (wrapper)
     * จุดประสงค์: ให้สามารถอ่านข้อมูลที่แชร์ไว้ได้อย่างสะดวก
     * shared() ควรใช้กับอะไร: เมื่อคุณต้องการอ่านข้อมูลที่แชร์ไว้จากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $value = $this->shared('key', 'default');
     * ```
     * 
     * @param string|null $key กำหนดชื่อของข้อมูลที่ต้องการอ่าน หรือ null เพื่ออ่านทั้งหมด
     * @param mixed $default กำหนดค่าดีฟอลต์เมื่อข้อมูลที่ระบุไม่มีการแชร์ไว้
     * @return mixed คืนค่าของข้อมูลที่แชร์ไว้สำหรับชื่อที่ระบุ หรือค่าดีฟอลต์ถ้าไม่มีการแชร์ข้อมูลสำหรับชื่อที่ระบุ หรืออาร์เรย์ของข้อมูลทั้งหมดที่แชร์ไว้ถ้า $key เป็น null
     */
    protected function shared(?string $key = null, $default = null)
    {
        return View::shared($key, $default);
    }
}
