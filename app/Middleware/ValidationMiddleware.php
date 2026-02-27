<?php
/**
 * MIDDLEWARE VALIDATION (ตรวจสอบข้อมูล)
 * 
 * จุดประสงค์: ตรวจสอบข้อมูลที่ส่งเข้ามาก่อนส่งไปยัง Controller
 * 
 * การใช้งาน:
 * ใช้กับฟอร์มและ API endpoints ที่ต้องการการตรวจสอบข้อมูล:
 * - ฟอร์มการสมัครสมาชิก
 * - ฟอร์มการเข้าสู่ระบบ
 * - การสร้าง/อัปเดตข้อมูล
 * - API requests
 * 
 * กฎการตรวจสอบ:
 * - required: ต้องมีค่า
 * - email: รูปแบบอีเมล
 * - min: ความยาวขั้นต่ำ
 * - max: ความยาวสูงสุด
 * - numeric: ต้องเป็นตัวเลข
 * - alpha: ต้องเป็นตัวอักษร
 * - alphanumeric: ตัวอักษรและตัวเลข
 * 
 * วิธีการทำงาน:
 * 1. รับกฎการตรวจสอบ
 * 2. ตรวจสอบข้อมูลตามกฎ
 * 3. ถ้าผ่าน ดำเนินการต่อ
 * 4. ถ้าไม่ผ่าน คืนค่าข้อผิดพลาด
 */

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Logger;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;

class ValidationMiddleware extends Middleware
{
    /**
     * ตัวบันทึกเหตุการณ์ (Logger) สำหรับบันทึกความล้มเหลวในการตรวจสอบและเหตุการณ์ที่เกี่ยวข้องกับการตรวจสอบข้อมูล
     */
    private Logger $logger;
    
    /**
     * กฎการตรวจสอบ
     */
    private array $rules = [];

    /**
     * ข้อความ error แบบกำหนดเอง
     */
    private array $messages = [];

    /**
     * Constructor
     * จุดประสงค์: สร้างอินสแตนซ์ ValidationMiddleware ใหม่และเตรียมตัวบันทึกเหตุการณ์สำหรับการตรวจสอบข้อมูล
     * คัวอย่างการใช้งาน:
     * $validation = new ValidationMiddleware([
     *   'email' => 'required|email',
     *   'password' => 'required|min:6',
     *   ], 
     *   [
     *   'email.required' => 'Email is required',
     *   'email.email' => 'Email is not valid',
     *   'password.required' => 'Password is required',
     *   'password.min' => 'Password must be at least 6 characters',
     * ]);
     * 
     * @param array $rules กฎการตรวจสอบในรูปแบบ associative array เช่น ['email' => 'required|email', 'password' => 'required|min:6']
     * @param array $messages ข้อความ error แบบกำหนดเองในรูปแบบ associative array เช่น ['email.required' => 'Email is required', 'email.email' => 'Email is not valid']
     */
    public function __construct(array $rules = [], array $messages = [])
    {
        $this->logger = new Logger();
        $this->rules = $rules;
        $this->messages = $messages;
    }

    /**
     * จัดการการตรวจสอบข้อมูล
     * จุดประสงค์: ตรวจสอบข้อมูลที่ส่งเข้ามาตามกฎที่กำหนดไว้ และจัดการผลลัพธ์ของการตรวจสอบ โดยจะคืนค่าข้อผิดพลาดในรูปแบบ JSON สำหรับ API requests หรือเก็บข้อผิดพลาดใน session และเปลี่ยนเส้นทางกลับสำหรับ web requests
     * 
     * @param \App\Core\Request|null $request คำขอที่ส่งเข้ามา (สามารถเป็น null ได้ถ้าไม่ต้องการใช้ข้อมูลจากคำขอ)
     * @return bool|Response True เพื่อดำเนินการต่อ, false เพื่อหยุด, หรือ Response เพื่อส่งกลับทันที
     */
        public function handle(?\App\Core\Request $request = null): bool|Response
    {
        // ถ้าไม่มีกฎ ดำเนินการต่อ
        if (empty($this->rules)) {
            return true;
        }

        // รับข้อมูลตาม HTTP method
        $data = $this->getRequestData();

        // สร้าง Validator และตรวจสอบข้อมูลตามกฎ
        $validator = new Validator($data, $this->rules, null, $this->messages);
        
        // ถ้าตรวจสอบไม่ผ่าน
        if ($validator->fails()) {
            $errors = $validator->errors();

            $this->logger->warning('validation.failed', [
                'route' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'errors' => $errors,
            ]);

            // กำหนดว่าเป็นคำขอ API หรือไม่
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $isApiRequest = preg_match('#^/api(/|$)#', $uri);

            if ($isApiRequest) {
                // คืนค่า JSON error
                return $this->jsonErrorWithValidation($errors);
            }

            // เก็บ errors ใน session และเปลี่ยนเส้นทางกลับ
            return $this->handleWebValidationError($errors, $data);
        }

        // ตรวจสอบผ่าน
        return true;
    }

    /**
     * รับข้อมูลจากคำขอ
     * จุดประสงค์: ดึงข้อมูลจากคำขอ HTTP ตาม HTTP method และ content type เพื่อใช้ในการตรวจสอบข้อมูล โดยรองรับทั้ง GET, POST, PUT, PATCH, DELETE และสามารถจัดการกับ JSON payload ได้
     * 
     * @return array ข้อมูลที่ดึงมาจากคำขอในรูปแบบ associative array
     */
    private function getRequestData(): array
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'GET') {
            return $_GET;
        }

        // POST, PUT, PATCH, DELETE
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (strpos($contentType, 'application/json') !== false) {
            // JSON data
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            return $data ?? [];
        }

        // Form data
        return $_POST;
    }

    /**
     * ส่งข้อผิดพลาด JSON พร้อม validation errors
     * ฃจุดประสงค์: สร้างการตอบกลับในรูปแบบ JSON ที่มีข้อมูลข้อผิดพลาดและสถานะ HTTP 422 Unprocessable Entity เพื่อให้ API clients สามารถรับรู้และจัดการกับข้อผิดพลาดการตรวจสอบข้อมูลได้อย่างเหมาะสม
     * 
     * @param array $errors ข้อผิดพลาดการตรวจสอบข้อมูล
     * @return \App\Core\Response การตอบกลับในรูปแบบ JSON ที่มีข้อมูลข้อผิดพลาดและสถานะ HTTP 422 Unprocessable Entity
     */
    private function jsonErrorWithValidation(array $errors): Response
    {
        return Response::apiError('Validation failed', $errors, 422);
    }

    /**
     * จัดการข้อผิดพลาดการตรวจสอบสำหรับ web
     * จุดประสงค์: จัดการข้อผิดพลาดการตรวจสอบข้อมูลสำหรับคำขอ web โดยเก็บข้อผิดพลาดและข้อมูลเดิมใน session แบบ flash และเปลี่ยนเส้นทางกลับไปหน้าเดิม เพื่อให้ผู้ใช้สามารถเห็นข้อผิดพลาดและแก้ไขข้อมูลได้อย่างสะดวก
     * 
     * @param array $errors ข้อผิดพลาดการตรวจสอบข้อมูล
     * @param array $oldInput ข้อมูลเดิมที่ผู้ใช้กรอก
     * @return \App\Core\Response การตอบกลับที่เหมาะสมสำหรับกรณีข้อผิดพลาดการตรวจสอบข้อมูล
     */
    private function handleWebValidationError(array $errors, array $oldInput): Response
    {
        // เก็บข้อผิดพลาดและข้อมูลเดิมใน session แบบ flash
        Session::start();
        Session::flash('validation_errors', $errors);
        Session::flashInput($this->sanitizeOldInput($oldInput));

        // เปลี่ยนเส้นทางกลับไปหน้าเดิม
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return Response::redirect($referer);
    }

    /**
     * ทำความสะอาด old input (ลบข้อมูลละเอียดอ่อน)
     * จุดประสงค์: ลบข้อมูลที่ละเอียดอ่อนออกจาก old input ก่อนที่จะเก็บใน session เพื่อป้องกันการเปิดเผยข้อมูลที่ไม่ควรเปิดเผย เช่น รหัสผ่าน โทเค็น หรือข้อมูลสำคัญอื่นๆ ที่อาจถูกนำไปใช้ในทางที่ผิดได้
     * 
     * @param array $input ข้อมูลเดิมที่ผู้ใช้กรอก
     * @return array ข้อมูลเดิมที่ถูกทำความสะอาดแล้ว
     */
    private function sanitizeOldInput(array $input): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'token',
            'api_key',
            'secret',
        ];

        foreach ($sensitiveFields as $field) {
            unset($input[$field]);
        }

        return $input;
    }

    /**
     * ตั้งค่ากฎการตรวจสอบ
     * จุดประสงค์: กำหนดกฎการตรวจสอบข้อมูลสำหรับฟิลด์ต่างๆ ในคำขอ HTTP โดยสามารถใช้กฎที่มีอยู่แล้วหรือเพิ่มกฎใหม่ได้ตามต้องการ เพื่อให้การตรวจสอบข้อมูลมีความยืดหยุ่นและสามารถปรับแต่งได้ตามความต้องการของแต่ละ endpoint หรือฟอร์ม
     *
     *  @param array $rules กฎการตรวจสอบข้อมูลในรูปแบบ associative array เช่น ['email' => 'required|email', 'password' => 'required|min:6']
     */
    public function setRules(array $rules): void
    {
        $this->rules = $rules;
    }

    /**
     * เพิ่มกฎการตรวจสอบ
     * จุดประสงค์: เพิ่มกฎการตรวจสอบข้อมูลสำหรับฟิลด์ใหม่หรือเพิ่มกฎเพิ่มเติมให้กับฟิลด์ที่มีอยู่แล้ว โดยไม่ต้องเขียนกฎทั้งหมดใหม่ ซึ่งช่วยให้การจัดการกฎการตรวจสอบมีความยืดหยุ่นและง่ายต่อการปรับแต่งตามความต้องการของแต่ละ endpoint หรือฟอร์ม
     * 
     * @param string $field ชื่อฟิลด์ที่ต้องการเพิ่มกฎการตรวจสอบ
     * @param string|array $rules กฎการตรวจสอบข้อมูลสำหรับฟิลด์นั้นในรูปแบบ string เช่น 'required|email' หรือในรูปแบบ array เช่น ['required', 'email']
     */
    public function addRule(string $field, $rules): void
    {
        $this->rules[$field] = $rules;
    }

    /**
     * ตั้งค่าข้อความ error
     * จุดประสงค์: กำหนดข้อความ error แบบกำหนดเองสำหรับกฎการตรวจสอบข้อมูลต่างๆ เพื่อให้สามารถแสดงข้อความที่เหมาะสมและเข้าใจง่ายสำหรับผู้ใช้เมื่อเกิดข้อผิดพลาดในการตรวจสอบข้อมูล
     * 
     * @param array $messages ข้อความ error แบบกำหนดเองสำหรับกฎการตรวจสอบข้อมูลในรูปแบบ associative array เช่น ['email.required' => 'Email is required', 'email.email' => 'Email is not valid']
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะตั้งค่าข้อความ error ในตัวแปร $messages
     */
    public function setMessages(array $messages): void
    {
        $this->messages = $messages;
    }

    /**
     * รับ validation errors จาก session (สำหรับแสดงในฟอร์ม)
     * จุดประสงค์: ดึงข้อผิดพลาดการตรวจสอบข้อมูลที่ถูกเก็บไว้ใน session แบบ flash ภายใต้คีย์ 'validation_errors' เพื่อให้สามารถแสดงข้อผิดพลาดเหล่านี้ในฟอร์มได้อย่างสะดวกและช่วยให้ผู้ใช้สามารถแก้ไขข้อมูลได้อย่างถูกต้อง
     * 
     * @return array ข้อความ error สำหรับฟิลด์ต่างๆ ที่ถูกเก็บไว้ใน session แบบ flash ภายใต้คีย์ 'validation_errors'
     */
    public static function getErrors(): array
    {
        Session::start();
        return Session::getFlash('validation_errors', []);
    }

    /**
     * รับ old input จาก session (สำหรับแสดงในฟอร์ม)
     * จุดประสงค์: ดึงข้อมูลเดิมที่ผู้ใช้กรอกไว้ในฟอร์มจาก session แบบ flash ภายใต้คีย์ 'old_input' เพื่อให้สามารถแสดงข้อมูลเดิมในฟอร์มได้อย่างสะดวกและช่วยให้ผู้ใช้สามารถแก้ไขข้อมูลได้อย่างถูกต้อง
     * 
     * @param string|null $field ชื่อฟิลด์ที่ต้องการดึงข้อมูลเดิม หากไม่ระบุจะดึงข้อมูลเดิมทั้งหมด
     * @param mixed $default ค่าที่จะใช้หากไม่มีข้อมูลเดิมสำหรับฟิลด์นั้น
     * @return mixed ข้อมูลเดิมสำหรับฟิลด์ที่ระบุ หรือค่าดีฟอลต์หากไม่มีข้อมูล
     */
    public static function getOldInput(?string $field = null, $default = null)
    {
        Session::start();
        return Session::old($field, $default);
    }

    /**
     * ตรวจสอบว่ามี error สำหรับ field หรือไม่
     * จุดประสงค์: ตรวจสอบว่ามีข้อผิดพลาดการตรวจสอบข้อมูลสำหรับฟิลด์ที่ระบุหรือไม่ โดยใช้ข้อมูลจาก session แบบ flash เพื่อช่วยให้สามารถแสดงข้อผิดพลาดเฉพาะสำหรับฟิลด์นั้นในฟอร์มได้อย่างสะดวกและช่วยให้ผู้ใช้สามารถแก้ไขข้อมูลได้อย่างถูกต้อง
     * 
     * @param string $field ชื่อฟิลด์ที่ต้องการตรวจสอบข้อผิดพลาด
     * @return bool คืนค่า true หากมีข้อผิดพลาดสำหรับฟิลด์นั้น, false หากไม่มี
     */
    public static function hasError(string $field): bool
    {
        $errors = self::getErrors();
        return isset($errors[$field]);
    }

    /**
     * รับ error message สำหรับ field
     * จุดประสงค์: ดึงข้อความข้อผิดพลาดการตรวจสอบข้อมูลสำหรับฟิลด์ที่ระบุจาก session แบบ flash เพื่อให้สามารถแสดงข้อความข้อผิดพลาดเฉพาะสำหรับฟิลด์นั้นในฟอร์มได้อย่างสะดวกและช่วยให้ผู้ใช้สามารถแก้ไขข้อมูลได้อย่างถูกต้อง
     * 
     * @param string $field ชื่อฟิลด์ที่ต้องการดึงข้อความข้อผิดพลาด
     * @return string|null ข้อความข้อผิดพลาดสำหรับฟิลด์ที่ระบุ หรือ null หากไม่มีข้อผิดพลาด
     */
    public static function getError(string $field): ?string
    {
        $errors = self::getErrors();
        return $errors[$field] ?? null;
    }

    /**
     * สร้าง ValidationMiddleware พร้อมกฎทั่วไป
     * จุดประสงค์: สร้างอินสแตนซ์ของ ValidationMiddleware ที่มีการตั้งค่ากฎการตรวจสอบข้อมูลทั่วไปสำหรับการเข้าสู่ระบบ (login) หรือการสมัครสมาชิก (register) เพื่อให้สามารถนำไปใช้ได้อย่างรวดเร็วและง่ายดายในการตรวจสอบข้อมูลสำหรับฟอร์มเหล่านี้
     * 
     * @param array $additionalRules กฎเพิ่มเติม
     * @return self อินสแตนซ์ของ ValidationMiddleware ที่มีการตั้งค่ากฎการตรวจสอบข้อมูลสำหรับการเข้าสู่ระบบ (login) หรือการสมัครสมาชิก (register) พร้อมกฎเพิ่มเติมที่ระบุ
     */
    public static function login(array $additionalRules = []): self
    {
        $rules = array_merge([
            'login' => 'required|min:3',
            'password' => 'required|min:6',
        ], $additionalRules);

        return new self($rules);
    }

    /**
     * สร้าง ValidationMiddleware สำหรับการสมัครสมาชิก
     * จุดประสงค์: สร้างอินสแตนซ์ของ ValidationMiddleware ที่มีการตั้งค่ากฎการตรวจสอบข้อมูลสำหรับการสมัครสมาชิก (register) พร้อมกฎเพิ่มเติมที่ระบุ
     * 
     * @param array $additionalRules กฎเพิ่มเติมสำหรับการสมัครสมาชิก เช่น กฎสำหรับฟิลด์อื่นๆ ที่ต้องการตรวจสอบเพิ่มเติมนอกเหนือจากกฎพื้นฐานที่มีอยู่แล้ว
     * @return self อินสแตนซ์ของ ValidationMiddleware ที่มีการตั้งค่ากฎการตรวจสอบข้อมูลสำหรับการสมัครสมาชิก (register) พร้อมกฎเพิ่มเติมที่ระบุ
     */
    public static function register(array $additionalRules = []): self
    {
        $rules = array_merge([
            'username' => 'required|alphanumeric|min:3|max:50|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|same:password',
        ], $additionalRules);

        return new self($rules);
    }
}
