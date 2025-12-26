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
 */

namespace App\Helpers;

class StringHelper
{
    /**
     * สร้าง slug จาก string (เหมาะสำหรับ URL)
     * 
     * @param string $text
     * @param string $separator
     * @return string
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
     * 
     * @param string $text
     * @param int $length
     * @param string $suffix
     * @return string
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
     * 
     * @param string $text
     * @param int $words
     * @param string $suffix
     * @return string
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
     * 
     * @param int $length
     * @return string
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
     * 
     * @param string $text
     * @return string
     */
    public static function camelCase(string $text): string
    {
        $text = self::studlyCase($text);
        return lcfirst($text);
    }

    /**
     * แปลงเป็น StudlyCase (PascalCase)
     * 
     * @param string $text
     * @return string
     */
    public static function studlyCase(string $text): string
    {
        $text = str_replace(['-', '_'], ' ', $text);
        $text = ucwords($text);
        return str_replace(' ', '', $text);
    }

    /**
     * แปลงเป็น snake_case
     * 
     * @param string $text
     * @return string
     */
    public static function snakeCase(string $text): string
    {
        $text = preg_replace('/\s+/u', '', ucwords($text));
        return strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', $text));
    }

    /**
     * แปลงเป็น kebab-case
     * 
     * @param string $text
     * @return string
     */
    public static function kebabCase(string $text): string
    {
        return str_replace('_', '-', self::snakeCase($text));
    }

    /**
     * ตรวจสอบว่าขึ้นต้นด้วย string ที่กำหนด
     * 
     * @param string $haystack
     * @param string|array $needles
     * @return bool
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
     * 
     * @param string $haystack
     * @param string|array $needles
     * @return bool
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
     * 
     * @param string $haystack
     * @param string|array $needles
     * @return bool
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
     * 
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
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
     * 
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
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
     * ลบ HTML tags
     * 
     * @param string $text
     * @param string|array $allowedTags
     * @return string
     */
    public static function stripTags(string $text, $allowedTags = null): string
    {
        if ($allowedTags === null) {
            return strip_tags($text);
        }
        
        return strip_tags($text, $allowedTags);
    }

    /**
     * แปลงเป็นตัวพิมพ์ใหญ่
     * 
     * @param string $text
     * @return string
     */
    public static function upper(string $text): string
    {
        return mb_strtoupper($text, 'UTF-8');
    }

    /**
     * แปลงเป็นตัวพิมพ์เล็ก
     * 
     * @param string $text
     * @return string
     */
    public static function lower(string $text): string
    {
        return mb_strtolower($text, 'UTF-8');
    }

    /**
     * แปลงตัวอักษรแรกของแต่ละคำเป็นตัวพิมพ์ใหญ่
     * 
     * @param string $text
     * @return string
     */
    public static function title(string $text): string
    {
        return mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * ตรวจสอบว่าเป็น JSON string หรือไม่
     * 
     * @param string $text
     * @return bool
     */
    public static function isJson(string $text): bool
    {
        json_decode($text);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * แปลงตัวเลขเป็นตัวหนังสือไทย (สำหรับเช็ค)
     * 
     * @param float $number
     * @return string
     */
    public static function bahtText(float $number): string
    {
        $txtNum = ['ศูนย์', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
        $txtDigit = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];
        
        $number = number_format($number, 2, '.', '');
        list($baht, $satang) = explode('.', $number);
        
        $bahtText = self::convertNumberToThai($baht, $txtNum, $txtDigit);
        $satangText = '';
        
        if ($satang > 0) {
            $satangText = self::convertNumberToThai($satang, $txtNum, $txtDigit) . 'สตางค์';
        }
        
        return $bahtText . 'บาท' . ($satangText ? $satangText : 'ถ้วน');
    }

    /**
     * ฟังก์ชันช่วยแปลงตัวเลขเป็นตัวหนังสือไทย
     * 
     * @param string $number
     * @param array $txtNum
     * @param array $txtDigit
     * @return string
     */
    private static function convertNumberToThai(string $number, array $txtNum, array $txtDigit): string
    {
        $length = strlen($number);
        $result = '';
        
        for ($i = 0; $i < $length; $i++) {
            $digit = (int)$number[$i];
            $position = $length - $i - 1;
            
            if ($digit == 0) {
                continue;
            }
            
            if ($position == 1 && $digit == 1) {
                $result .= 'สิบ';
            } elseif ($position == 1 && $digit == 2) {
                $result .= 'ยี่สิบ';
            } elseif ($position == 0 && $digit == 1 && $length > 1) {
                $result .= 'เอ็ด';
            } else {
                $result .= $txtNum[$digit];
            }
            
            if ($position > 0 && $digit != 0) {
                $result .= $txtDigit[$position];
            }
        }
        
        return $result ?: 'ศูนย์';
    }

    /**
     * ลบช่องว่างซ้ำ
     * 
     * @param string $text
     * @return string
     */
    public static function collapseWhitespace(string $text): string
    {
        return preg_replace('/\s+/', ' ', trim($text));
    }

    /**
     * แทนที่หลายค่าพร้อมกัน
     * 
     * @param array $replacements
     * @param string $subject
     * @return string
     */
    public static function replaceArray(array $replacements, string $subject): string
    {
        return str_replace(array_keys($replacements), array_values($replacements), $subject);
    }

    /**
     * จำกัดความยาว string
     * 
     * @param string $text
     * @param int $limit
     * @param string $end
     * @return string
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
     * 
     * @param string $text
     * @param int $start
     * @param int|null $length
     * @param string $mask
     * @return string
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
