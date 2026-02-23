<?php
/**
 * class Config
 * 
 * จุดประสงค์: จัดการการโหลดและเข้าถึงค่าการตั้งค่าจากไฟล์คอนฟิกต่าง ๆ ในแอปพลิเคชัน
 * การใช้งาน:
 * ```php
 * // ดึงค่าการตั้งค่าทั้งหมด
 * $config = Config::get();
 * // ดึงค่าการตั้งค่าเฉพาะกลุ่ม
 * $dbConfig = Config::get('database');
 * // ดึงค่าการตั้งค่าเฉพาะคีย์
 * $dbHost = Config::get('database.host', 'localhost');
 * ```
 */

namespace App\Core;

require_once __DIR__ . '/../../config/env.php';

class Config
{
    /**
     * แคชค่าการตั้งค่าที่โหลดแล้ว
     */
    private static ?array $config = null;

    /**
     * ดึงค่าการตั้งค่าจากไฟล์คอนฟิก
     * จุดประสงค์: ให้สามารถดึงค่าการตั้งค่าจากไฟล์คอนฟิกต่าง ๆ ได้อย่างง่ายดาย โดยสามารถระบุคีย์แบบ dot notation เพื่อเข้าถึงค่าที่ซับซ้อนได้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $dbHost = Config::get('database.host', 'localhost'); // ดึงค่า host จากกลุ่ม database, ถ้าไม่มีให้ใช้ 'localhost' เป็นค่าเริ่มต้น
     * $appName = Config::get('app.name', 'MyApp'); // ดึงค่า name จากกลุ่ม app, ถ้าไม่มีให้ใช้ 'MyApp' เป็นค่าเริ่มต้น
     * ```
     * 
     * @param string|null $key กำหนดคีย์ของการตั้งค่าที่ต้องการดึง โดยใช้ dot notation สำหรับเข้าถึงค่าที่ซับซ้อน เช่น 'database.host'
     * @param mixed|null $default กำหนดค่าที่จะคืนกลับหากไม่พบคีย์ที่ระบุในคอนฟิก
     * @return mixed คืนค่าการตั้งค่าตามคีย์ที่ระบุ หรือคืนค่า default หากไม่พบคีย์
      */
    public static function get($key = null, $default = null)
    {
        $isTesting = env('APP_ENV', 'development', 'string') === 'testing';
        if ($isTesting) {
            self::$config = null;
        }
        if ($key === null) {
            return $default ?? self::$config;
        }

        $segments = is_array($key) ? $key : explode('.', (string) $key);
        $group = array_shift($segments);

        if (!is_string($group) || $group === '') {
            return $default;
        }

        if (self::$config === null) {
            self::$config = [];
        }

        if (!array_key_exists($group, self::$config)) {
            $root = dirname(__DIR__, 2);
            $path = $root . '/config/' . $group . '.php';
            if (!file_exists($path)) {
                return $default;
            }
            self::$config[$group] = require $path;
        }

        $value = self::$config[$group];
        if (empty($segments)) {
            return $value;
        }

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
