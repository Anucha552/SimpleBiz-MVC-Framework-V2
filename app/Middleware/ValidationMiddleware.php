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
use App\Core\Validator;

class ValidationMiddleware extends Middleware
{
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
     * 
     * @param array $rules กฎการตรวจสอบ
     * @param array $messages ข้อความ error แบบกำหนดเอง
     */
    public function __construct(array $rules = [], array $messages = [])
    {
        $this->logger = new Logger();
        $this->rules = $rules;
        $this->messages = $messages;
    }

    /**
     * จัดการการตรวจสอบข้อมูล
     * 
     * @return bool True เพื่อดำเนินการต่อ, false เพื่อหยุด
     */
    public function handle(): bool
    {
        // ถ้าไม่มีกฎ ดำเนินการต่อ
        if (empty($this->rules)) {
            return true;
        }

        // รับข้อมูลตาม HTTP method
        $data = $this->getRequestData();

        // สร้าง Validator และตรวจสอบข้อมูล
        $validator = new Validator($data, $this->rules, $this->messages);
        
        if ($validator->fails()) {
            $errors = $validator->errors();

            $this->logger->warning('validation.failed', [
                'route' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'errors' => $errors,
            ]);

            // กำหนดว่าเป็นคำขอ API หรือไม่
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $isApiRequest = strpos($uri, '/api/') === 0;

            if ($isApiRequest) {
                // คืนค่า JSON error
                $this->jsonErrorWithValidation($errors);
            } else {
                // เก็บ errors ใน session และเปลี่ยนเส้นทางกลับ
                $this->handleWebValidationError($errors, $data);
            }

            return false;
        }

        // ตรวจสอบผ่าน
        return true;
    }

    /**
     * รับข้อมูลจากคำขอ
     * 
     * @return array
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
     * 
     * @param array $errors
     */
    private function jsonErrorWithValidation(array $errors): void
    {
        http_response_code(422); // Unprocessable Entity
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors,
        ]);
        exit;
    }

    /**
     * จัดการข้อผิดพลาดการตรวจสอบสำหรับ web
     * 
     * @param array $errors
     * @param array $oldInput
     */
    private function handleWebValidationError(array $errors, array $oldInput): void
    {
        // เริ่ม session ถ้ายังไม่ได้เริ่ม
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // เก็บข้อผิดพลาดและข้อมูลเดิมใน session
        $_SESSION['validation_errors'] = $errors;
        $_SESSION['old_input'] = $this->sanitizeOldInput($oldInput);

        // เปลี่ยนเส้นทางกลับไปหน้าเดิม
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header("Location: {$referer}");
        exit;
    }

    /**
     * ทำความสะอาด old input (ลบข้อมูลละเอียดอ่อน)
     * 
     * @param array $input
     * @return array
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
     * 
     * @param array $rules
     */
    public function setRules(array $rules): void
    {
        $this->rules = $rules;
    }

    /**
     * เพิ่มกฎการตรวจสอบ
     * 
     * @param string $field
     * @param string|array $rules
     */
    public function addRule(string $field, $rules): void
    {
        $this->rules[$field] = $rules;
    }

    /**
     * ตั้งค่าข้อความ error
     * 
     * @param array $messages
     */
    public function setMessages(array $messages): void
    {
        $this->messages = $messages;
    }

    /**
     * รับ validation errors จาก session (สำหรับแสดงในฟอร์ม)
     * 
     * @return array
     */
    public static function getErrors(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $errors = $_SESSION['validation_errors'] ?? [];
        unset($_SESSION['validation_errors']);

        return $errors;
    }

    /**
     * รับ old input จาก session (สำหรับแสดงในฟอร์ม)
     * 
     * @param string|null $field
     * @param mixed $default
     * @return mixed
     */
    public static function getOldInput(?string $field = null, $default = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $oldInput = $_SESSION['old_input'] ?? [];

        if ($field === null) {
            unset($_SESSION['old_input']);
            return $oldInput;
        }

        $value = $oldInput[$field] ?? $default;

        return $value;
    }

    /**
     * ตรวจสอบว่ามี error สำหรับ field หรือไม่
     * 
     * @param string $field
     * @return bool
     */
    public static function hasError(string $field): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $errors = $_SESSION['validation_errors'] ?? [];
        return isset($errors[$field]);
    }

    /**
     * รับ error message สำหรับ field
     * 
     * @param string $field
     * @return string|null
     */
    public static function getError(string $field): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $errors = $_SESSION['validation_errors'] ?? [];
        return $errors[$field] ?? null;
    }

    /**
     * สร้าง ValidationMiddleware พร้อมกฎทั่วไป
     * 
     * @param array $additionalRules กฎเพิ่มเติม
     * @return self
     */
    public static function login(array $additionalRules = []): self
    {
        $rules = array_merge([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ], $additionalRules);

        return new self($rules);
    }

    /**
     * สร้าง ValidationMiddleware สำหรับการสมัครสมาชิก
     * 
     * @param array $additionalRules
     * @return self
     */
    public static function register(array $additionalRules = []): self
    {
        $rules = array_merge([
            'name' => 'required|min:2|max:100',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|same:password',
        ], $additionalRules);

        return new self($rules);
    }
}
