<?php
/**
 * คลาส Validator สำหรับตรวจสอบความถูกต้องของข้อมูล
 * 
 * จุดประสงค์: ตรวจสอบความถูกต้องของข้อมูล
 * Validator ควรใช้กับอะไร: ข้อมูลที่ต้องการตรวจสอบและกฎการตรวจสอบ
 * ฟีเจอร์: กฎการตรวจสอบหลากหลาย, ข้อความแสดงข้อผิดพลาดภาษาไทย, custom rules
 * 
 * กฎการตรวจสอบที่รองรับ:
 * - required: ต้องมีค่า
 * - email: รูปแบบอีเมล
 * - min: ความยาวขั้นต่ำ
 * - max: ความยาวสูงสุด
 * - numeric: ต้องเป็นตัวเลข
 * - alpha: เฉพาะตัวอักษร
 * - alphanumeric: ตัวอักษรและตัวเลข
 * - url: รูปแบบ URL
 * - match: ต้องตรงกับฟิลด์อื่น
 * - unique: ต้องไม่ซ้ำในฐานข้อมูล
 * - exists: ต้องมีอยู่ในฐานข้อมูล
 * - in: ต้องอยู่ในรายการที่กำหนด
 * - regex: ตรงกับ pattern
 * 
 * ตัวอย่างการใช้งานโดยรวม:
 * ```php
 * $validator = new Validator($data, [
 *     'username' => 'required|alphanumeric|min:3|max:20',
 *     'email' => 'required|email|unique:users,email',
 *     'password' => 'required|min:8',
 *     'password_confirm' => 'required|match:password'
 * ]);
 * 
 * if ($validator->fails()) {
 *     $errors = $validator->errors();
 * }
 * ```
 */

namespace App\Core;

class Validator
{
    /**
     * ข้อมูลที่ต้องการตรวจสอบ
     */
    private array $data;

    /**
     * กฎการตรวจสอบ
     */
    private array $rules;

    /**
     * ข้อความแสดงข้อผิดพลาด
     */
    private array $errors = [];

    /**
     * ข้อความแสดงข้อผิดพลาดแบบกำหนดเอง
     */
    private array $customMessages = [];

    /**
     * ป้ายชื่อฟิลด์ (labels)
     */
    private array $labels = [];

    /**
     * อินสแตนซ์ของ Database สำหรับการตรวจสอบ unique และ existsar Database|null อินสแตนซ์ของ Database หรือ null ถ้าไม่ถูกส่งผ่านเข้ามา
     */
    private ?Database $db = null;

    /**
     * ตัวแปรเพื่อป้องกันการเรียก validate() ซ้ำใน passes() และ fails()
     */
    private bool $validatedRun = false;

    /**
     * Cached validation result to avoid running validate() multiple times.
     * null = not yet validated, true/false = cached result
     */
    private ?bool $result = null;

    /**
     * ข้อความแสดงข้อผิดพลาดเริ่มต้น
     */
    private array $defaultMessages = [
        'required' => ':field จำเป็นต้องกรอก',
        'email' => ':field ต้องเป็นอีเมลที่ถูกต้อง',
        'min' => ':field ต้องมีอย่างน้อย :param ตัวอักษร',
        'max' => ':field ต้องไม่เกิน :param ตัวอักษร',
        'numeric' => ':field ต้องเป็นตัวเลข',
        'alpha' => ':field ต้องเป็นตัวอักษรเท่านั้น',
        'alphanumeric' => ':field ต้องเป็นตัวอักษรและตัวเลขเท่านั้น',
        'url' => ':field ต้องเป็น URL ที่ถูกต้อง',
        'match' => ':field ไม่ตรงกับ :param',
        'unique' => ':field นี้ถูกใช้ไปแล้ว',
        'exists' => ':field ไม่มีในระบบ',
        'in' => ':field ต้องเป็นหนึ่งใน: :param',
        'regex' => ':field มีรูปแบบไม่ถูกต้อง',
        'integer' => ':field ต้องเป็นจำนวนเต็ม',
        'date' => ':field ต้องเป็นวันที่ที่ถูกต้อง',
        'before' => ':field ต้องเป็นวันที่ก่อนหน้า :param',
        'after' => ':field ต้องเป็นวันที่หลังจาก :param',
        'phone' => ':field ต้องเป็นเบอร์โทรศัพท์ที่ถูกต้อง',
        'between' => ':field ต้องอยู่ระหว่าง :min ถึง :max',
    ];

    /**
     * สร้างอินสแตนซ์ Validator ใหม่
     * จุดประสงค์: สร้างอินสแตนซ์ของ Validator พร้อมข้อมูลและกฎการตรวจสอบ
     * Validator() ควรใช้กับอะไร: ข้อมูลที่ต้องการตรวจสอบและกฎการตรวจสอบ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $validator = new Validator($data, [
     *     'username' => 'required|alphanumeric|min:3|max:20',
     *     'email' => 'required|email|unique:users,email',
     *     'password' => 'required|min:8',
     *     'password_confirm' => 'required|match:password'
     * ], $db);
     * ```
     * 
     * @param array $data ข้อมูลที่ต้องการตรวจสอบ
     * @param array $rules กฎการตรวจสอบ
     * @param Database|null $db อินสแตนซ์ของ Database สำหรับการตรวจสอบ unique และ exists หรือ null ถ้าไม่ถูกส่งผ่านเข้ามา
     * @param array $customMessages ข้อความแสดงข้อผิดพลาดแบบกำหนดเอง
     */
    public function __construct(array $data, array $rules, ?Database $db = null, array $customMessages = [], array $labels = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $customMessages;
        $this->labels = $labels;
        $this->db = $db;
    }

    /**
     * ทำการตรวจสอบ
     * จุอประสงค์: ทำการตรวจสอบข้อมูลตามกฎที่กำหนด
     * validate() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบข้อมูลตามกฎที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * if ($validator->validate()) {
     *     // ข้อมูลถูกต้อง
     * } else {
     *    $errors = $validator->errors();
     * }
     * ```
     * 
     * 
     * @return bool true ถ้าข้อมูลผ่านการตรวจสอบ, false ถ้าไม่ผ่าน
     */
    public function validate(): bool
    {
        if ($this->result !== null) {
            $this->validatedRun = true;
            return $this->result;
        }

        $this->validatedRun = true;

        $this->errors = [];

        foreach ($this->rules as $field => $rules) {
            $rulesArray = is_array($rules) ? $rules : explode('|', $rules);
            $value = $this->data[$field] ?? null;

            // If 'bail' is present, stop validating this field after first failure.
            $hasBail = in_array('bail', $rulesArray, true);

            foreach ($rulesArray as $rule) {
                if ($rule === 'bail') {
                    // Do not treat 'bail' as a validation rule.
                    continue;
                }

                $this->validateRule($field, $value, $rule);

                if ($hasBail && isset($this->errors[$field]) && !$this->isEmpty($this->errors[$field])) {
                    // stop validating this field after the first failure
                    break;
                }
            }
        }

        $this->result = $this->isEmpty($this->errors);

        return $this->result;
    }

    /**
     * ตรวจสอบว่าผ่านหรือไม่
     * จุดประสงค์: ตรวจสอบว่าข้อมูลผ่านการตรวจสอบตามกฎที่กำหนดหรือไม่
     * passes() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าข้อมูลผ่านการตรวจสอบหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * if ($validator->passes()) {
     *    // ข้อมูลถูกต้อง
     * } else {
     *   $errors = $validator->errors();
     * }
     * ```
     * 
     * @return bool true ถ้าข้อมูลผ่านการตรวจสอบ, false ถ้าไม่ผ่าน
     */
    public function passes(): bool
    {
        return $this->validate();
    }

    /**
     * ตรวจสอบว่าไม่ผ่านหรือไม่
     * จุดประสงค์: ตรวจสอบว่าข้อมูลไม่ผ่านการตรวจสอบตามกฎที่กำหนดหรือไม่
     * fails() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าข้อมูลไม่ผ่านการตรวจสอบหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * if ($validator->fails()) {
     *    $errors = $validator->errors();
     * } else {
     *    // ข้อมูลถูกต้อง
     * }
     * ```
     * 
     * @return bool true ถ้าข้อมูลไม่ผ่านการตรวจสอบ, false ถ้าผ่าน
     */
    public function fails(): bool
    {
        return !$this->validate();
    }

    /**
     * รับข้อความแสดงข้อผิดพลาดทั้งหมด
     * จุดประสงค์: รับข้อความแสดงข้อผิดพลาดทั้งหมดที่เกิดขึ้นจากการตรวจสอบ
     * errors() ควรใช้กับอะไร: เมื่อคุณต้องการดึงข้อความแสดงข้อผิดพลาดทั้งหมด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $errors = $validator->errors();
     * ```
     * 
     * @return array ข้อความแสดงข้อผิดพลาดทั้งหมด
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * รับข้อความแสดงข้อผิดพลาดของฟิลด์
     * จุดประสงค์: รับข้อความแสดงข้อผิดพลาดของฟิลด์ที่ระบุ
     * getError() ควรใช้กับอะไร: เมื่อคุณต้องการดึงข้อความแสดงข้อผิดพลาดของฟิลด์เฉพาะ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $usernameErrors = $validator->getError('username');
     * ```
     * @param string $field ชื่อฟิลด์ที่ต้องการดึงข้อผิดพลาด
     * @return array ข้อความแสดงข้อผิดพลาดของฟิลด์
     */
    public function getError(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * รับข้อมูลที่ผ่านการตรวจสอบแล้ว (validated)
     * คืนค่าเฉพาะฟิลด์ที่กำหนดในกฎเท่านั้น, มีอยู่ในข้อมูล, และไม่มีข้อผิดพลาด
     *
     * @return array
     */
    public function validated(): array
    {
        $result = [];

        if (!$this->validatedRun) {
                $this->validate();
            }

        foreach ($this->rules as $field => $rules) {
            if (!array_key_exists($field, $this->data)) {
                continue;
            }

            if (isset($this->errors[$field]) && !$this->isEmpty($this->errors[$field])) {
                continue;
            }

            $result[$field] = $this->data[$field];
        }

        return $result;
    }

    /**
     * ตรวจสอบกฎแต่ละข้อ
     * จุดประสงค์: ตรวจสอบข้อมูลตามกฎที่กำหนด
     * validateRule() ควรใช้กับอะไร: ชื่อฟิลด์, ค่าของฟิลด์, กฎที่ต้องการตรวจสอบ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->validateRule('email', 'test@example.com', 'required|email');
     * ```
     * @param string $field ชื่อฟิลด์
     * @param mixed $value ค่าของฟิลด์
     * @param string $rule กฎที่ต้องการตรวจสอบ
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    private function validateRule(string $field, $value, string $rule): void
    {
        // แยกชื่อกฎและพารามิเตอร์
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
            $param = $parts[1] ?? null;

            // normalize parameter: trim and convert empty string to null
            if ($param !== null) {
                $param = trim($param);
                if ($param === '') {
                    $param = null;
                }
            }
        // เรียกเมธอดตรวจสอบ
        $method = 'validate' . ucfirst($ruleName);

        if (!method_exists($this, $method)) {
            throw new \InvalidArgumentException("Validation rule {$ruleName} does not exist.");
        }

        $result = $this->$method($value, $param);

        if (!$result) {
            $this->addError($field, $ruleName, $param);
        }
    }

    /**
     * เพิ่มข้อความแสดงข้อผิดพลาด
     * จุดประสงค์: เพิ่มข้อความแสดงข้อผิดพลาดสำหรับฟิลด์และกฎที่ระบุ
     * addError() ควรใช้กับอะไร: ชื่อฟิลด์, ชื่อกฎ, และพารามิเตอร์ของกฎ (ถ้ามี)
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->addError('email', 'required');
     * ```
     * @param string $field ชื่อฟิลด์
     * @param string $rule ชื่อกฎ
     * @param mixed $param พารามิเตอร์ของกฎ (ถ้ามี)
     */
    private function addError(string $field, string $rule, $param = null): void
    {
        $message = $this->customMessages["{$field}.{$rule}"] 
            ?? $this->customMessages[$rule] 
            ?? $this->defaultMessages[$rule] 
            ?? "ฟิลด์ :field ไม่ถูกต้อง";

        $label = $this->labels[$field] ?? $field;
        $message = str_replace(':field', $label, $message);
        $message = str_replace(':param', (string)$param, $message);

        // สำหรับ between rule
        if ($rule === 'between' && $param) {
            $parts = explode(',', $param);
            $message = str_replace(':min', $parts[0] ?? '', $message);
            $message = str_replace(':max', $parts[1] ?? '', $message);
        }

        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }

    // ========== กฎการตรวจสอบ ==========

    /**
     * กฎ required: ต้องมีค่า
     * จุดประสงค์: ตรวจสอบว่าฟิลด์มีค่าหรือไม่
     * validateRequired() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าฟิลด์มีค่าหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->validateRequired($value);
     * ```
     * @param mixed $value ค่าของฟิลด์
     */
    private function validateRequired($value, $param = null): bool
    {
        return !$this->isEmpty($value);
    }

    /**
     * กฎ email: รูปแบบอีเมล
     * จุดประสงค์: ตรวจสอบว่าฟิลด์มีรูปแบบอีเมลที่ถูกต้องหรือไม่
     * validateEmail() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบรูปแบบอีเมล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->validateEmail($value);
     * ```
     * 
     * @param mixed $value ค่าของฟิลด์
     */
    private function validateEmail($value, $param = null): bool
    {
        if ($this->isEmpty($value)) {
            return true; // ใช้ required แยกต่างหาก
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * กฎ min: ความยาวขั้นต่ำ
     * จุดประสงค์: ตรวจสอบว่าฟิลด์มีความยาวขั้นต่ำที่กำหนดหรือไม่
     * validateMin() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบความยาวขั้นต่ำ
     * ตัวอย่างการใช้งาน:
     * ```
     * $this->validateMin($value, 3);
     * ```
     *
     * @param mixed $value ค่าของฟิลด์
     * @param mixed $param พารามิเตอร์ของกฎ (ความยาวขั้นต่ำ)
     */
    private function validateMin($value, $param): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        if (is_numeric($value)) {
            return $value >= (float)$param;
        }

        return mb_strlen((string)$value) >= (int)$param;
    }

    /**
     * กฎ max: ความยาวสูงสุด
     * จุดประสงค์: ตรวจสอบว่าฟิลด์มีความยาวสูงสุดที่กำหนดหรือไม่
     * validateMax() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบความยาวสูงสุด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->validateMax($value, 10);
     * ```
     * 
     * @param mixed $value ค่าของฟิลด์
     * @param mixed $param พารามิเตอร์ของกฎ (ความยาวสูงสุด)
     */
    private function validateMax($value, $param): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        if (is_numeric($value)) {
            return $value <= (float)$param;
        }

        return mb_strlen((string)$value) <= (int)$param;
    }

    /**
     * กฎ numeric: ต้องเป็นตัวเลข
     * จุดประสงค์: ตรวจสอบว่าฟิลด์เป็นตัวเลขหรือไม่
     * validateNumeric() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าฟิลด์เป็นตัวเลข
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->validateNumeric($value);
     * ```
     * 
     * @param mixed $value ค่าของฟิลด์
     * @return bool true ถ้าเป็นตัวเลข, false ถ้าไม่ใช่
     * @return bool true ถ้าเป็นตัวเลข, false ถ้าไม่ใช่
     */
    private function validateNumeric($value, $param = null): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        return is_numeric($value);
    }

    /**
     * กฎ integer: ต้องเป็นจำนวนเต็ม
     * จุดประสงค์: ตรวจสอบว่าฟิลด์เป็นจำนวนเต็มหรือไม่
     * validateInteger() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าฟิลด์เป็นจำนวนเต็ม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->validateInteger($value);
     * ```
     * 
     * @param mixed $value ค่าของฟิลด์
     * @param mixed $param พารามิเตอร์ของกฎ (ถ้ามี)
     * @return bool true ถ้าเป็นจำนวนเต็ม, false ถ้าไม่ใช่
     */
    private function validateInteger($value, $param = null): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * กฎ alpha: เฉพาะตัวอักษร
     * จุดประสงค์: ตรวจสอบว่าฟิลด์ประกอบด้วยตัวอักษรเท่านั้น
     * validateAlpha() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าฟิลด์ประกอบด้วยตัวอักษรเท่านั้น
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->validateAlpha($value);
     * ```
     * 
     * @param mixed $value ค่าของฟิลด์
     * @param mixed $param พารามิเตอร์ของกฎ (ถ้ามี)
     * @return bool true ถ้าประกอบด้วยตัวอักษรเท่านั้น, false ถ้าไม่ใช่
     */
    private function validateAlpha($value, $param = null): bool
    {

        if ($this->isEmpty($value)) {
            return true;
        }

        return preg_match('/^[\pL\s]+$/u', $value) === 1;
    }

    /**
     * กฎ alphanumeric: ตัวอักษรและตัวเลข
     * จุดประสงค์: ตรวจสอบว่าฟิลด์ประกอบด้วยตัวอักษรและตัวเลขเท่านั้น
     * validateAlphanumeric() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าฟิลด์ประกอบด้วยตัวอักษรและตัวเลขเท่านั้น
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->validateAlphanumeric($value);
     * ```
     * 
     * @param mixed $value ค่าของฟิลด์
     * @param mixed $param พารามิเตอร์ของกฎ (ถ้ามี)
     * @return bool true ถ้าประกอบด้วยตัวอักษรและตัวเลขเท่านั้น, false ถ้าไม่ใช่
     */
    private function validateAlphanumeric($value, $param = null): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        return preg_match('/^[\pL\pN\s]+$/u', $value) === 1;
    }

    /**
     * กฎ url: รูปแบบ URL
     * จุดประสงค์: ตรวจสอบว่าฟิลด์เป็นรูปแบบ URL ที่ถูกต้องหรือไม่
     * validateUrl() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบรูปแบบ URL
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->validateUrl($value);
     * ```
     * @param mixed $value ค่าของฟิลด์
     * @param mixed $param พารามิเตอร์ของกฎ (ถ้ามี)
     * @return bool true ถ้าเป็น URL ที่ถูกต้อง, false ถ้าไม่ใช
     */
    private function validateUrl($value, $param = null): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * กฎ match: ต้องตรงกับฟิลด์อื่น
     * จุดประสงค์: ตรวจสอบว่าฟิลด์ตรงกับฟิลด์อื่นที่ระบุหรือไม่
     * validateMatch() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าฟิลด์ตรงกับฟิลด์อื่น
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->validateMatch($value, 'password_confirm');
     * ```
     * 
     * @param mixed $value ค่าของฟิลด์
     * @param mixed $param ชื่อฟิลด์ที่ต้องการเปรียบเทียบ
     * @return bool true ถ้าตรงกัน, false ถ้าไม่ตรง
     */
    private function validateMatch($value, $param): bool
    {
        if (!$param) {
            return false;
        }
        
        if ($this->isEmpty($value)) {
            return true;
        }

        $compareValue = $this->data[$param] ?? null;
        return $value === $compareValue;
    }

    /**
     * กฎ in: ต้องอยู่ในรายการที่กำหนด
     * จุดประสงค์: ตรวจสอบว่าฟิลด์มีค่าอยู่ในรายการที่กำหนดหรือไม่
     * validateIn() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าฟิลด์มีค่าอยู่ในรายการที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->validateIn($value, 'apple,banana,orange');
     * ```
     * 
     * @param mixed $value ค่าของฟิลด์
     * @param mixed $param รายการค่าที่อนุญาต (คั่นด้วยเครื่องหมายจุลภาค)
     * @return bool true ถ้าอยู่ในรายการ, false ถ้าไม่ใช่
     */
    private function validateIn($value, $param): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        $values = explode(',', $param);
        return in_array($value, $values, true);
    }

    /**
     * กฎ regex: ตรงกับ pattern
     * จุดประสงค์: ตรวจสอบว่าฟิลด์ตรงกับ pattern ที่กำหนดหรือไม่
     * validateRegex() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าฟิลด์ตรงกับ pattern ที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->validateRegex($value, '/^[a-zA-Z0-9]+$/');
     * ```
     * 
     * @param mixed $value ค่าของฟิลด์
     * @param mixed $param pattern ที่ต้องการตรวจสอบ
     * @return bool true ถ้าตรงกับ pattern, false ถ้าไม่ตรง
     */
    private function validateRegex($value, $param): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        return preg_match($param, $value) === 1;
    }

    /**
     * กฎ date: วันที่ที่ถูกต้อง
     * จุดประสงค์: ตรวจสอบว่าฟิลด์เป็นวันที่ที่ถูกต้องหรือไม่
     * validateDate() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าฟิลด์เป็นวันที่ที่ถูกต้อง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->validateDate($value);
     * ```
     * 
     * @param mixed $value ค่าของฟิลด์
     * @param mixed $param พารามิเตอร์ของกฎ (ถ้ามี)
     * @return bool true ถ้าเป็นวันที่ที่ถูกต้อง, false ถ้าไม่ใช่
     */
    private function validateDate($value, $param = null): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        return strtotime($value) !== false;
    }

    /**
     * กฎ phone: เบอร์โทรศัพท์ไทย
     * จุดประสงค์: ตรวจสอบว่าฟิลด์เป็นเบอร์โทรศัพท์ไทยที่ถูกต้องหรือไม่
     * validatePhone() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบรูปแบบเบอร์โทรศัพท์ไทย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->validatePhone($value);
     * ```
     * 
     * @param mixed $value ค่าของฟิลด์
     * @param mixed $param พารามิเตอร์ของกฎ (ถ้ามี)
     * @return bool true ถ้าเป็นเบอร์โทรศัพท์ไทยที่ถูกต้อง, false ถ้าไม่ใช่
     */
    private function validatePhone($value, $param = null): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        // รูปแบบเบอร์โทรศัพท์ไทย: 0812345678 หรือ 02-1234567
        return preg_match('/^(0[0-9]{1,2}-?[0-9]{6,7})$|^(0[0-9]{9})$/', $value) === 1;
    }

    /**
     * กฎ between: ต้องอยู่ระหว่างค่าที่กำหนด
     */
    private function validateBetween($value, $param): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        $parts = explode(',', $param);
        
        if (count($parts) < 2) {
            return false;
        }

        $min = (float)($parts[0] ?? 0);
        $max = (float)($parts[1] ?? 0);

        if (is_numeric($value)) {
            return $value >= $min && $value <= $max;
        }

        $length = mb_strlen((string)$value);
        return $length >= $min && $length <= $max;
    }

    /**
     * กฎ unique: ต้องไม่ซ้ำในฐานข้อมูล
     * รูปแบบ: unique:table,column,except_id
     * จุดประสงค์: ตรวจสอบว่าฟิลด์ไม่มีค่าเดียวกันในฐานข้อมูล
     * validateUnique() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบความไม่ซ้ำกันในฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->validateUnique($value, 'users,email,1');
     * ```
     * 
     * @param mixed $value ค่าของฟิลด์
     * @param mixed $param พารามิเตอร์ของกฎ (table,column,except_id)
     * @return bool true ถ้าไม่ซ้ำ, false ถ้าซ้ำ
     */
    private function validateUnique($value, $param): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        $parts = explode(',', $param);
        $table = $parts[0] ?? null;
        $column = $parts[1] ?? 'id';
        $exceptId = $parts[2] ?? null;

        if (!$table) {
            return true;
        }

        // If no database instance provided, skip unique check safely.
        if ($this->db === null) {
            throw new \RuntimeException('Database instance is required for unique/exists validation.');
        }

        // Validate table and column names strictly to avoid injection.
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
            // Invalid identifier — skip this rule safely.
            return true;
        }

        $db = $this->db;
        $escapedTable = $this->escapeIdentifier($table);
        $escapedColumn = $this->escapeIdentifier($column);

        if (!$escapedTable || !$escapedColumn) {
            // If escaping fails for any reason, skip the rule safely.
            return true;
        }

        $sql = "SELECT COUNT(*) FROM {$escapedTable} WHERE {$escapedColumn} = :value";

        if ($exceptId) {
            $sql .= " AND `id` != :except_id";
        }

        $params = ['value' => $value];
        if ($exceptId) {
            $params['except_id'] = $exceptId;
        }

        $stmt = $db->query($sql, $params);
        $count = $stmt->fetchColumn();

        return $count == 0;
    }

    /**
     * กฎ exists: ต้องมีอยู่ในฐานข้อมูล
     * รูปแบบ: exists:table,column
     * จุดประสงค์: ตรวจสอบว่าฟิลด์มีค่าอยู่ในฐานข้อมูล
     * validateExists() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบความมีอยู่ของค่าภายในฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->validateExists($value, 'users,email');
     * ```
     * 
     * @param mixed $value ค่าของฟิลด์
     * @param mixed $param พารามิเตอร์ของกฎ (table,column)
     * @return bool true ถ้ามีอยู่ในฐานข้อมูล, false ถ้าไม่มี
     */
    private function validateExists($value, $param): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        $parts = explode(',', $param);
        $table = $parts[0] ?? null;
        $column = $parts[1] ?? 'id';

        if (!$table) {
            return true;
        }

        // If no database instance provided, skip exists check safely.
        if ($this->db === null) {
            throw new \RuntimeException('Database instance is required for unique/exists validation.');
        }

        // Validate table and column names strictly to avoid injection.
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
            // Invalid identifier — skip this rule safely.
            return true;
        }

        $db = $this->db;
        $escapedTable = $this->escapeIdentifier($table);
        $escapedColumn = $this->escapeIdentifier($column);

        if (!$escapedTable || !$escapedColumn) {
            // If escaping fails for any reason, skip the rule safely.
            return true;
        }

        $sql = "SELECT COUNT(*) FROM {$escapedTable} WHERE {$escapedColumn} = :value";
        $stmt = $db->query($sql, ['value' => $value]);
        $count = $stmt->fetchColumn();

        return $count > 0;
    }

    /**
     * กฎ before: วันที่ต้องเป็นก่อนหน้าวันที่กำหนด
     * จุดประสงค์: ตรวจสอบว่าฟิลด์เป็นวันที่ก่อนวันที่กำหนด
     * validateBefore() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบวันที่ก่อนวันที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->validateBefore($value, '2024-01-01');
     * ```
     * 
     * @param mixed $value ค่าของฟิลด์
     * @param mixed $param วันที่เปรียบเทียบ
     * @return bool true ถ้าวันที่เป็นก่อนวันที่กำหนด, false ถ้าไม่ใช่
     */
    private function validateBefore($value, $param): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        $compareDate = strtotime($param);
        $valueDate = strtotime($value);

        if ($compareDate === false || $valueDate === false) {
            return false;
        }

        return $valueDate < $compareDate;
    }

    /**
     * กฎ after: วันที่ต้องเป็นหลังจากวันที่กำหนด
     * จุดประสงค์: ตรวจสอบว่าฟิลด์เป็นวันที่หลังวันที่กำหนด
     * validateAfter() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบวันที่หลังวันที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->validateAfter($value, '2024-01-01');
     * ```
     * 
     * @param mixed $value ค่าของฟิลด์
     * @param mixed $param วันที่เปรียบเทียบ
     * @return bool true ถ้าวันที่เป็นหลังจากวันที่กำหนด, false ถ้าไม่ใช่
     */
    private function validateAfter($value, $param): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }

        $compareDate = strtotime($param);
        $valueDate = strtotime($value);

        if ($compareDate === false || $valueDate === false) {
            return false;
        }

        return $valueDate > $compareDate;
    }

    /**
     * Escape and validate a table or column identifier to prevent SQL injection.
     * Allows dot-separated identifiers (e.g. schema.table). Returns null if invalid.
     *
     * @param string $identifier
     * @return string|null
     */
    private function escapeIdentifier(string $identifier): ?string
    {
        $parts = explode('.', $identifier);

        foreach ($parts as &$part) {
            // Only allow alphanumeric and underscore for identifier parts
            if (!preg_match('/^[A-Za-z0-9_]+$/', $part)) {
                return null;
            }

            // remove any backticks if present and wrap with backticks
            $part = '`' . str_replace('`', '', $part) . '`';
        }

        return implode('.', $parts);
    }

    /**
     * Determine if a value should be considered empty for validation rules.
     * Treats null, empty string (after trim), and empty array as empty.
     * Does NOT treat 0 or "0" as empty.
     *
     * @param mixed $value
     * @return bool
     */
    private function isEmpty($value): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        if (is_array($value) && empty($value)) {
            return true;
        }

        return false;
    }
}
