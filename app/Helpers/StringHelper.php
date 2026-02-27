<?php
/**
 * String Helper
 * 
 * จุดประสงค์: ฟังก์ชันช่วยเหลือสำหรับจัดการ string
 * 
 * ฟีเจอร์:
 * - สร้าง slug สำหรับ URL
 * - ตัดข้อความ (truncate)
 * - สร้าง string สุ่ม
 * - แปลงเป็น camelCase, snake_case
 * - ตรวจสอบว่าขึ้นต้นหรือลงท้ายด้วย string
 * - แทนที่ครั้งแรก/ครั้งสุดท้าย
 */

namespace App\Helpers;

class StringHelper
{
    /**
     * สร้าง slug จาก string (เหมาะสำหรับ URL)
     * จุดประสงค์: ใช้เพื่อแปลงข้อความเป็นรูปแบบ slug ที่เหมาะสำหรับ URL
     * ตัวอย่างการใช้งาน:
     * ```php
     * $slug = StringHelper::slug('Hello World! This is a Test.'); // ผลลัพธ์: 'hello-world-this-is-a-test'
     * ```
     * 
     * @param string $text ข้อความที่ต้องการแปลงเป็น slug
     * @param string $separator ตัวคั่นที่ใช้แทนช่องว่างและอักขระพิเศษ (ค่าเริ่มต้นคือ '-')
     * @return string ข้อความที่ถูกแปลงเป็น slug
     */
    public static function slug(string $text, string $separator = '-'): string
    {
        // แปลงเป็นตัวพิมพ์เล็ก
        $text = mb_strtolower($text, 'UTF-8');
        
        // แทนที่ช่องว่างและอักขระพิเศษด้วย separator
        $text = preg_replace('/[^\p{L}\p{N}]+/u', $separator, $text);
        
        // ลบ separator ที่ซ้ำกัน
        $text = preg_replace('/' . preg_quote($separator) . '+/', $separator, $text);
        
        // ตัด separator ที่ขึ้นต้นและลงท้าย
        $text = trim($text, $separator);
        
        return $text;
    }

    /**
     * ตัดข้อความให้เหลือความยาวที่กำหนด
     * จุดประสงค์: ใช้เพื่อตัดข้อความให้มีความยาวไม่เกินที่กำหนด โดยเพิ่ม suffix เมื่อถูกตัด
     * ภาษาที่รองรับ: ไทยและอังกฤษ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $truncated = StringHelper::truncate('This is a long text that needs to be truncated.', 20); // ผลลัพธ์: 'This is a long...'
     * ```
     * 
     * @param string $text ข้อความที่ต้องการตัด
     * @param int $length ความยาวสูงสุดของข้อความ
     * @param string $suffix ข้อความที่เพิ่มเมื่อถูกตัด (ค่าเริ่มต้นคือ '...')
     * @return string ข้อความที่ถูกตัด
     */
    public static function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        $truncated = mb_substr($text, 0, $length);
        
        // ตัดที่ช่องว่างคำสุดท้าย
        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace !== false) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }
        
        return $truncated . $suffix;
    }

    /**
     * ตัดข้อความตามจำนวนคำ
     * จุดประสงค์: ใช้เพื่อตัดข้อความให้เหลือจำนวนคำที่กำหนด โดยเพิ่ม suffix เมื่อถูกตัด
     * ภาษาที่รองรับ: ไทยและอังกฤษ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $truncated = StringHelper::words('This is a long text that needs to be truncated by words.', 5); // ผลลัพธ์: 'This is a long text...'
     * ```
     * 
     * @param string $text ข้อความที่ต้องการตัด
     * @param int $words จำนวนคำสูงสุด
     * @param string $suffix ข้อความที่เพิ่มเมื่อถูกตัด (ค่าเริ่มต้นคือ '...')
     * @return string ข้อความที่ถูกตัด
     */
    public static function words(string $text, int $words = 100, string $suffix = '...'): string
    {
        preg_match('/^\s*+(?:\S++\s*+){1,' . $words . '}/u', $text, $matches);
        
        if (!isset($matches[0]) || mb_strlen($text) === mb_strlen($matches[0])) {
            return $text;
        }
        
        return rtrim($matches[0]) . $suffix;
    }

    /**
     * สร้าง string สุ่ม
     * จุดประสงค์: ใช้เพื่อสร้าง string สุ่มที่ประกอบด้วยตัวอักษรและตัวเลข
     * ตัวอย่างการใช้งาน:
     * ```php
     * $randomString = StringHelper::random(16); // ผลลัพธ์: 'a1b2c3d4e5f6g7h8'
     * ```
     * 
     * @param int $length ความยาวของ string สุ่ม
     * @return string string สุ่ม
     */
    public static function random(int $length = 16): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        
        return $randomString;
    }

    /**
     * แปลงเป็น camelCase
     * จุดประสงค์: ใช้เพื่อแปลงข้อความเป็นรูปแบบ camelCase
     * ตัวอย่างการใช้งาน:
     * ```php
     * $camelCase = StringHelper::camelCase('hello world this is a test'); // ผลลัพธ์: 'helloWorldThisIsATest'
     * ```
     * 
     * @param string $text ข้อความที่ต้องการแปลงเป็น camelCase
     * @return string ข้อความที่ถูกแปลงเป็น camelCase
     */
    public static function camelCase(string $text): string
    {
        $text = self::studlyCase($text);
        return lcfirst($text);
    }

    /**
     * แปลงเป็น StudlyCase (PascalCase)
     * จุดประสงค์: ใช้เพื่อแปลงข้อความเป็นรูปแบบ StudlyCase หรือ PascalCase
     * ตัวอย่างการใช้งาน:
     * ```php
     * $studlyCase = StringHelper::studlyCase('hello world this is a test'); // ผลลัพธ์: 'HelloWorldThisIsATest'
     * ```
     * 
     * @param string $text ข้อความที่ต้องการแปลงเป็น StudlyCase
     * @return string ข้อความที่ถูกแปลงเป็น StudlyCase
     */
    public static function studlyCase(string $text): string
    {
        $text = str_replace(['-', '_'], ' ', $text);
        $text = ucwords($text);
        return str_replace(' ', '', $text);
    }

    /**
     * แปลงเป็น snake_case
     * จุดประสงค์: ใช้เพื่อแปลงข้อความเป็นรูปแบบ snake_case
     * ตัวอย่างการใช้งาน:
     * ```php
     * $snakeCase = StringHelper::snakeCase('hello world this is a test'); // ผลลัพธ์: 'hello_world_this_is_a_test'
     * ```
     * 
     * @param string $text ข้อความที่ต้องการแปลงเป็น snake_case
     * @return string ข้อความที่ถูกแปลงเป็น snake_case
     */
    public static function snakeCase(string $text): string
    {
        $text = preg_replace('/\s+/u', '', ucwords($text));
        return strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', $text));
    }

    /**
     * แปลงเป็น kebab-case
     * จุดประสงค์: ใช้เพื่อแปลงข้อความเป็นรูปแบบ kebab-case
     * ตัวอย่างการใช้งาน:
     * ```php
     * $kebabCase = StringHelper::kebabCase('hello world this is a test'); // ผลลัพธ์: 'hello-world-this-is-a-test'
     * ```
     * 
     * @param string $text ข้อความที่ต้องการแปลงเป็น kebab-case
     * @return string ข้อความที่ถูกแปลงเป็น kebab-case
     */
    public static function kebabCase(string $text): string
    {
        return str_replace('_', '-', self::snakeCase($text));
    }

    /**
     * ตรวจสอบว่าขึ้นต้นด้วย string ที่กำหนด
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่า string ขึ้นต้นด้วยข้อความที่กำหนดหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $startsWithHello = StringHelper::startsWith('hello world', 'hello'); // ผลลัพธ์: true
     * ```
     * 
     * @param string $haystack ข้อความที่ต้องการตรวจสอบ
     * @param string|array $needles ข้อความหรืออาเรย์ของข้อความที่ต้องการตรวจสอบ
     * @return bool ผลลัพธ์การตรวจสอบ
     */
    public static function startsWith(string $haystack, $needles): bool
    {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && str_starts_with($haystack, $needle)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * ตรวจสอบว่าลงท้ายด้วย string ที่กำหนด
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่า string ลงท้ายด้วยข้อความที่กำหนดหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $endsWithTxt = StringHelper::endsWith('hello world', 'world'); // ผลลัพธ์: true
     * ```
     * 
     * @param string $haystack ข้อความที่ต้องการตรวจสอบ
     * @param string|array $needles ข้อความหรืออาเรย์ของข้อความที่ต้องการตรวจสอบ
     * @return bool ผลลัพธ์การตรวจสอบ
     */
    public static function endsWith(string $haystack, $needles): bool
    {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && str_ends_with($haystack, $needle)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * ตรวจสอบว่ามี string อยู่ข้างใน
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่ามีข้อความที่กำหนดอยู่ภายใน string หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $containsHello = StringHelper::contains('hello world', 'hello'); // ผลลัพธ์: true
     * ```
     * 
     * @param string $haystack ข้อความที่ต้องการตรวจสอบ
     * @param string|array $needles ข้อความหรืออาเรย์ของข้อความที่ต้องการตรวจสอบ
     * @return bool ผลลัพธ์การตรวจสอบ
     */
    public static function contains(string $haystack, $needles): bool
    {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * แทนที่ครั้งแรกเท่านั้น
     * จุดประสงค์: ใช้เพื่อแทนที่ข้อความครั้งแรกที่พบในสตริง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $result = StringHelper::replaceFirst('world', 'there', 'hello world world'); // ผลลัพธ์: 'hello there world'
     * ```
     * 
     * @param string $search ข้อความที่ต้องการค้นหา
     * @param string $replace ข้อความที่ใช้แทนที่
     * @param string $subject ข้อความต้นฉบับ
     * @return string ข้อความที่ถูกแทนที่ครั้งแรก
     */
    public static function replaceFirst(string $search, string $replace, string $subject): string
    {
        $position = strpos($subject, $search);
        
        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }
        
        return $subject;
    }

    /**
     * แทนที่ครั้งสุดท้ายเท่านั้น
     * จุดประสงค์: ใช้เพื่อแทนที่ข้อความครั้งสุดท้ายที่พบในสตริง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $result = StringHelper::replaceLast('world', 'there', 'hello world world'); // ผลลัพธ์: 'hello world there'
     * ```
     * 
     * @param string $search ข้อความที่ต้องการค้นหา
     * @param string $replace ข้อความที่ใช้แทนที่
     * @param string $subject ข้อความต้นฉบับ
     * @return string ข้อความที่ถูกแทนที่ครั้งสุดท้าย
     */
    public static function replaceLast(string $search, string $replace, string $subject): string
    {
        $position = strrpos($subject, $search);
        
        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }
        
        return $subject;
    }

    /**
     * แปลงเป็นตัวพิมพ์ใหญ่
     * จุดประสงค์: ใช้เพื่อแปลงข้อความเป็นตัวพิมพ์ใหญ่ทั้งหมด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $upperText = StringHelper::upper('hello world'); // ผลลัพธ์: 'HELLO WORLD'
     * ```
     * 
     * @param string $text ข้อความที่ต้องการแปลง
     * @return string ข้อความที่แปลงเป็นตัวพิมพ์ใหญ่
     */
    public static function upper(string $text): string
    {
        return mb_strtoupper($text, 'UTF-8');
    }

    /**
     * แปลงเป็นตัวพิมพ์เล็ก
     * จุดประสงค์: ใช้เพื่อแปลงข้อความเป็นตัวพิมพ์เล็กทั้งหมด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $lowerText = StringHelper::lower('HELLO WORLD'); // ผลลัพธ์: 'hello world'
     * ```
     * 
     * @param string $text ข้อความที่ต้องการแปลง
     * @return string ข้อความที่แปลงเป็นตัวพิมพ์เล็ก
     */
    public static function lower(string $text): string
    {
        return mb_strtolower($text, 'UTF-8');
    }

    /**
     * แปลงตัวอักษรแรกของแต่ละคำเป็นตัวพิมพ์ใหญ่
     * จุดประสงค์: ใช้เพื่อแปลงตัวอักษรแรกของแต่ละคำเป็นตัวพิมพ์ใหญ่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $titleText = StringHelper::title('hello world'); // ผลลัพธ์: 'Hello World'
     * ```
     * 
     * @param string $text ข้อความที่ต้องการแปลง
     * @return string ข้อความที่แปลงตัวอักษรแรกของแต่ละคำเป็นตัวพิมพ์ใหญ่
     */
    public static function title(string $text): string
    {
        return mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * ตรวจสอบว่าเป็น JSON string หรือไม่
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าสตริงที่กำหนดเป็น JSON ที่ถูกต้องหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isJson = StringHelper::isJson('{"name":"John","age":30}'); // ผลลัพธ์: true
     * ```
     * 
     * @param string $text ข้อความที่ต้องการตรวจสอบ
     * @return bool ผลลัพธ์การตรวจสอบว่าเป็น JSON หรือไม่
     */
    public static function isJson(string $text): bool
    {
        json_decode($text);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * ลบช่องว่างซ้ำ
     * จุดประสงค์: ใช้เพื่อลบช่องว่างที่ซ้ำกันในข้อความ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $cleanText = StringHelper::collapseWhitespace('This   is   a    test.'); // ผลลัพธ์: 'This is a test.'
     * ```
     * 
     * @param string $text ข้อความที่ต้องการลบช่องว่างซ้ำ
     * @return string ข้อความที่ลบช่องว่างซ้ำ
     */
    public static function collapseWhitespace(string $text): string
    {
        return preg_replace('/\s+/', ' ', trim($text));
    }

    /**
     * แทนที่หลายค่าพร้อมกัน
     * จุดประสงค์: ใช้เพื่อแทนที่หลายค่าพร้อมกันในข้อความ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $replacements = ['apple' => 'orange', 'banana' => 'grape'];
     * $text = "I like apple and banana.";
     * $newText = StringHelper::replaceArray($replacements, $text); // ผลลัพธ์: 'I like orange and grape.'
     * ```
     * 
     * @param array $replacements ข้อมูลการแทนที่ในรูปแบบ key => value
     * @param string $subject ข้อความที่ต้องการแทนที่
     * @return string ข้อความที่ถูกแทนที่
     */
    public static function replaceArray(array $replacements, string $subject): string
    {
        return str_replace(array_keys($replacements), array_values($replacements), $subject);
    }

    /**
     * จำกัดความยาว string
     * จุดประสงค์: ใช้เพื่อจำกัดความยาวของข้อความตามที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $limitedText = StringHelper::limit('This is a long text that needs to be limited.', 20, '...'); // ผลลัพธ์: 'This is a long text...'
     * ```
     * 
     * @param string $text ข้อความที่ต้องการจำกัดความยาว
     * @param int $limit จำนวนตัวอักษรสูงสุด
     * @param string $end ข้อความที่จะแสดงเมื่อข้อความถูกตัด
     * @return string ข้อความที่ถูกจำกัดความยาว
     */
    public static function limit(string $text, int $limit = 100, string $end = '...'): string
    {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }
        
        return rtrim(mb_substr($text, 0, $limit)) . $end;
    }

    /**
     * Mask string (เช่น email, เบอร์โทร)
     * จุดประสงค์: ใช้เพื่อมาสก์ส่วนหนึ่งของข้อความด้วยอักขระที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $maskedEmail = StringHelper::mask('example@example.com', 2, 6); // ผลลัพธ์: 'ex******@example.com'
     * ```
     * 
     * @param string $text ข้อความที่ต้องการมาสก์
     * @param int $start ตำแหน่งเริ่มต้นที่ต้องการมาสก์ (ค่าเริ่มต้นคือ 0)
     * @param int|null $length จำนวนตัวอักษรที่ต้องการมาสก์ (ค่าเริ่มต้นคือจนถึงสิ้นสุดข้อความ)
     * @param string $mask อักขระที่ใช้มาสก์ (ค่าเริ่มต้นคือ '*')
     * @return string ข้อความที่ถูกมาสก์
     */
    public static function mask(string $text, int $start = 0, ?int $length = null, string $mask = '*'): string
    {
        if ($length === null) {
            $length = mb_strlen($text) - $start;
        }
        
        $segment = mb_substr($text, $start, $length);
        
        if (mb_strlen($segment) === 0) {
            return $text;
        }
        
        $maskString = str_repeat($mask, mb_strlen($segment));
        
        return mb_substr($text, 0, $start) . $maskString . mb_substr($text, $start + $length);
    }
}
