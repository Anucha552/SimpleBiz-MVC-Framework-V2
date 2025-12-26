<?php
/**
 * คลาส Validator
 * 
 * จุดประสงค์: ตรวจสอบความถูกต้องของข้อมูล
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
 * ตัวอย่างการใช้งาน:
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
     * 
     * @param array $data ข้อมูลที่ต้องการตรวจสอบ
     * @param array $rules กฎการตรวจสอบ
     * @param array $customMessages ข้อความแสดงข้อผิดพลาดแบบกำหนดเอง
     */
    public function __construct(array $data, array $rules, array $customMessages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $customMessages;
    }

    /**
     * ทำการตรวจสอบ
     * 
     * @return bool
     */
    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $rules) {
            $rulesArray = is_array($rules) ? $rules : explode('|', $rules);
            $value = $this->data[$field] ?? null;

            foreach ($rulesArray as $rule) {
                $this->validateRule($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * ตรวจสอบว่าผ่านหรือไม่
     * 
     * @return bool
     */
    public function passes(): bool
    {
        return $this->validate();
    }

    /**
     * ตรวจสอบว่าไม่ผ่านหรือไม่
     * 
     * @return bool
     */
    public function fails(): bool
    {
        return !$this->validate();
    }

    /**
     * รับข้อความแสดงข้อผิดพลาดทั้งหมด
     * 
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * รับข้อความแสดงข้อผิดพลาดของฟิลด์
     * 
     * @param string $field
     * @return array
     */
    public function getError(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * ตรวจสอบกฎแต่ละข้อ
     * 
     * @param string $field
     * @param mixed $value
     * @param string $rule
     */
    private function validateRule(string $field, $value, string $rule): void
    {
        // แยกชื่อกฎและพารามิเตอร์
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $param = $parts[1] ?? null;

        // เรียกเมธอดตรวจสอบ
        $method = 'validate' . ucfirst($ruleName);

        if (method_exists($this, $method)) {
            $result = $this->$method($value, $param);

            if (!$result) {
                $this->addError($field, $ruleName, $param);
            }
        }
    }

    /**
     * เพิ่มข้อความแสดงข้อผิดพลาด
     * 
     * @param string $field
     * @param string $rule
     * @param mixed $param
     */
    private function addError(string $field, string $rule, $param = null): void
    {
        $message = $this->customMessages["{$field}.{$rule}"] 
                ?? $this->customMessages[$rule] 
                ?? $this->defaultMessages[$rule] 
                ?? "ฟิลด์ :field ไม่ถูกต้อง";

        $message = str_replace(':field', $field, $message);
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
     */
    private function validateRequired($value, $param = null): bool
    {
        if (is_null($value)) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        if (is_array($value) && empty($value)) {
            return false;
        }

        return true;
    }

    /**
     * กฎ email: รูปแบบอีเมล
     */
    private function validateEmail($value, $param = null): bool
    {
        if (empty($value)) {
            return true; // ใช้ required แยกต่างหาก
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * กฎ min: ความยาวขั้นต่ำ
     */
    private function validateMin($value, $param): bool
    {
        if (empty($value)) {
            return true;
        }

        if (is_numeric($value)) {
            return $value >= (float)$param;
        }

        return mb_strlen((string)$value) >= (int)$param;
    }

    /**
     * กฎ max: ความยาวสูงสุด
     */
    private function validateMax($value, $param): bool
    {
        if (empty($value)) {
            return true;
        }

        if (is_numeric($value)) {
            return $value <= (float)$param;
        }

        return mb_strlen((string)$value) <= (int)$param;
    }

    /**
     * กฎ numeric: ต้องเป็นตัวเลข
     */
    private function validateNumeric($value, $param = null): bool
    {
        if (empty($value) && $value !== 0 && $value !== '0') {
            return true;
        }

        return is_numeric($value);
    }

    /**
     * กฎ integer: ต้องเป็นจำนวนเต็ม
     */
    private function validateInteger($value, $param = null): bool
    {
        if (empty($value) && $value !== 0 && $value !== '0') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * กฎ alpha: เฉพาะตัวอักษร
     */
    private function validateAlpha($value, $param = null): bool
    {
        if (empty($value)) {
            return true;
        }

        return preg_match('/^[\pL\s]+$/u', $value) === 1;
    }

    /**
     * กฎ alphanumeric: ตัวอักษรและตัวเลข
     */
    private function validateAlphanumeric($value, $param = null): bool
    {
        if (empty($value)) {
            return true;
        }

        return preg_match('/^[\pL\pN\s]+$/u', $value) === 1;
    }

    /**
     * กฎ url: รูปแบบ URL
     */
    private function validateUrl($value, $param = null): bool
    {
        if (empty($value)) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * กฎ match: ต้องตรงกับฟิลด์อื่น
     */
    private function validateMatch($value, $param): bool
    {
        $compareValue = $this->data[$param] ?? null;
        return $value === $compareValue;
    }

    /**
     * กฎ in: ต้องอยู่ในรายการที่กำหนด
     */
    private function validateIn($value, $param): bool
    {
        if (empty($value)) {
            return true;
        }

        $values = explode(',', $param);
        return in_array($value, $values, true);
    }

    /**
     * กฎ regex: ตรงกับ pattern
     */
    private function validateRegex($value, $param): bool
    {
        if (empty($value)) {
            return true;
        }

        return preg_match($param, $value) === 1;
    }

    /**
     * กฎ date: วันที่ที่ถูกต้อง
     */
    private function validateDate($value, $param = null): bool
    {
        if (empty($value)) {
            return true;
        }

        return strtotime($value) !== false;
    }

    /**
     * กฎ phone: เบอร์โทรศัพท์ไทย
     */
    private function validatePhone($value, $param = null): bool
    {
        if (empty($value)) {
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
        if (empty($value) && $value !== 0 && $value !== '0') {
            return true;
        }

        $parts = explode(',', $param);
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
     */
    private function validateUnique($value, $param): bool
    {
        if (empty($value)) {
            return true;
        }

        $parts = explode(',', $param);
        $table = $parts[0] ?? null;
        $column = $parts[1] ?? 'id';
        $exceptId = $parts[2] ?? null;

        if (!$table) {
            return true;
        }

        $db = Database::getInstance()->getConnection();
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = :value";

        if ($exceptId) {
            $sql .= " AND id != :except_id";
        }

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':value', $value);

        if ($exceptId) {
            $stmt->bindValue(':except_id', $exceptId);
        }

        $stmt->execute();
        $count = $stmt->fetchColumn();

        return $count == 0;
    }

    /**
     * กฎ exists: ต้องมีอยู่ในฐานข้อมูล
     * รูปแบบ: exists:table,column
     */
    private function validateExists($value, $param): bool
    {
        if (empty($value)) {
            return true;
        }

        $parts = explode(',', $param);
        $table = $parts[0] ?? null;
        $column = $parts[1] ?? 'id';

        if (!$table) {
            return true;
        }

        $db = Database::getInstance()->getConnection();
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = :value";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':value', $value);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        return $count > 0;
    }

    /**
     * กฎ before: วันที่ต้องเป็นก่อนหน้าวันที่กำหนด
     */
    private function validateBefore($value, $param): bool
    {
        if (empty($value)) {
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
     */
    private function validateAfter($value, $param): bool
    {
        if (empty($value)) {
            return true;
        }

        $compareDate = strtotime($param);
        $valueDate = strtotime($value);

        if ($compareDate === false || $valueDate === false) {
            return false;
        }

        return $valueDate > $compareDate;
    }
}
