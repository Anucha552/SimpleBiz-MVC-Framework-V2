<?php

// ตรวจสอบว่าฟังก์ชัน env ยังไม่ได้ถูกนิยาม
if (!function_exists('env')) {

    /**
     * ฟังก์ชันช่วยเหลือสำหรับดึงค่าตัวแปรสภาพแวดล้อม (environment variables)
     * จุดประสงค์: ดึงค่าตัวแปรสภาพแวดล้อมจากระบบหรือไฟล์ .env
     * ตัวอย่างการใช้งาน:
     * ```php
     * $debug = env('APP_DEBUG', false, 'bool');
     * $dbHost = env('DB_HOST', 'localhost', 'string');
     * $allowedIPs = env('ALLOWED_IPS', [], 'array');
     * ```
     * 
     * @param string $key ชื่อตัวแปรสภาพแวดล้อม
     * @param mixed $default ค่าดีฟอลต์ถ้าตัวแปรไม่ถูกตั้งค่า
     * @param string|null $type ชนิดข้อมูลที่ต้องการ (bool, int, array, string)
     * @return mixed ค่าของตัวแปรสภาพแวดล้อมในชนิดที่ระบุ หรือค่าดีฟอลต์ถ้าไม่ถูกตั้งค่า
     */
    function env(string $key, $default = null, ?string $type = null)
    {

        $value = getenv($key); // พยายามดึงจาก getenv ก่อน

        // ถ้าไม่ได้ค่า ให้ตรวจสอบใน $_ENV และ $_SERVER
        if ($value === false) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }

        // ถ้ายังไม่ได้ค่า ให้ใช้ค่าดีฟอลต์
        if ($value === false || $value === null) {
            $value = $default;
        }

        // ตรวจสอบชนิดข้อมูลที่ต้องการ
        if ($type === null) {
            return $value;
        }

        switch ($type) {
            case 'bool':
            case 'boolean':
                if (is_bool($value)) return $value;
                $v = strtolower((string)$value);
                return in_array($v, ['1', 'true', 'on', 'yes'], true);
            case 'int':
            case 'integer':
                return (int)$value;
            case 'array':
                if (is_array($value)) return $value;
                if ($value === null) return [];
                return array_map('trim', explode(',', (string)$value));
            case 'string':
            default:
                return (string)$value;
        }
    }
}
