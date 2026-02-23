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

// ตรวจสอบว่าฟังก์ชัน config ยังไม่ได้ถูกนิยาม
if (!function_exists('config')) {

    /**
     * ฟังก์ชันช่วยเหลือสำหรับอ่านค่า config ด้วย dot notation
     * ตัวอย่าง:
     * - config('app.name')
     * - config('database.host')
     *
     * @param string|null $key คีย์แบบ dot notation เช่น app.name
     * @param mixed $default ค่าดีฟอลต์ถ้าไม่พบ
     * @return mixed
     */
    function config($key = null, $default = null)
    {
        // ถ้ามีคลาส Config อยู่แล้ว ให้ใช้เมธอด get ของคลาสนั้น
        if (class_exists('\\App\\Core\\Config')) {
            return \App\Core\Config::get($key, $default);
        }

        // ใช้ตัวแปร static เพื่อเก็บค่าคอนฟิกที่โหลดแล้ว
        static $config = null;

        // ตรวจสอบว่ากำลังอยู่ในสภาพแวดล้อมการทดสอบหรือไม่ ถ้าใช่ให้ไม่โหลดคอนฟิกจากไฟล์เพื่อให้สามารถทดสอบได้ง่ายขึ้น
        $isTesting = env('APP_ENV', 'development', 'string') === 'testing';

        // ถ้าอยู่ในสภาพแวดล้อมการทดสอบ ให้ตั้งค่า $config เป็น null เพื่อให้สามารถกำหนดค่าเองได้ในการทดสอบ
        if ($isTesting) {
            $config = null;
        }

        // ถ้า $config ยังไม่ได้โหลด ให้โหลดจากไฟล์คอนฟิก
        if ($config === null) {
            $root = dirname(__DIR__);
            $cachedPaths = [
                $root . '/storage/cache/config/config_cached.php',
                $root . '/storage/cache/config_cached.php',
            ];

            // พยายามโหลดคอนฟิกจากไฟล์ที่แคชไว้ก่อน ถ้าไม่มีให้โหลดจากไฟล์คอนฟิกปกติ
            foreach ($cachedPaths as $cachedPath) {
                if (file_exists($cachedPath)) {
                    $config = require $cachedPath;
                    break;
                }
            }

            // ถ้ายังไม่ได้โหลดคอนฟิกจากไฟล์แคช ให้โหลดจากไฟล์คอนฟิกปกติ
            if ($config === null) {
                $config = [];
                $configFiles = glob($root . '/config/*.php');
                foreach ($configFiles as $file) {
                    $base = basename($file);
                    if ($base === 'env.php') {
                        continue;
                    }
                    $keyName = basename($file, '.php');
                    $config[$keyName] = require $file;
                }
            }
        }

        // ถ้าไม่มีคีย์ ให้คืนค่าทั้งหมด
        if ($key === null || $key === '') {
            return $config;
        }

        // แยกคีย์ด้วย dot notation และดึงค่าจากอาร์เรย์คอนฟิก
        $segments = is_array($key) ? $key : explode('.', (string) $key);
        $value = $config;

        // วนลูปผ่านแต่ละส่วนของคีย์และดึงค่าจากอาร์เรย์คอนฟิก
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        // คืนค่าที่ได้จากคอนฟิกในชนิดที่ถูกต้อง
        return $value;
    }
}
