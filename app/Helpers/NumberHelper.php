<?php
/**
 * Number Helper
 * 
 * จุดประสงค์: ฟังก์ชันช่วยเหลือสำหรับจัดการตัวเลข
 * 
 * ฟีเจอร์:
 * - จัดรูปแบบตัวเลข
 * - แปลงเงิน, เปอร์เซ็นต์
 * - ตัวเลขไทย
 * - คำนวณทางคณิตศาสตร์พื้นฐาน
 * - ฟังก์ชันช่วยเหลือสำหรับการจัดการตัวเลขทั่วไป
 */

namespace App\Helpers;

class NumberHelper
{
    /**
     * จัดรูปแบบเงิน
     * จุดประสงค์: ใช้เพื่อจัดรูปแบบตัวเลขเป็นสกุลเงินที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $money = NumberHelper::money(1234.56); // ผลลัพธ์: '฿1,234.56'
     * ```
     * 
     * returns string ตัวเลขที่จัดรูปแบบเป็นสกุลเงิน
     * 
     * @param float $number
     * @param int $decimals
     * @param string $currency
     * @return string
     */
    public static function money(float $number, int $decimals = 2, string $currency = '฿'): string
    {
        return $currency . number_format($number, $decimals);
    }

    /**
     * จัดรูปแบบเงินบาท
     * จุดประสงค์: ใช้เพื่อจัดรูปแบบตัวเลขเป็นเงินบาท
     * ตัวอย่างการใช้งาน:
     * ```php
     * $baht = NumberHelper::baht(1234.56); // ผลลัพธ์: '1,234.56 บาท'
     * ```
     * 
     * returns string ตัวเลขที่จัดรูปแบบเป็นเงินบาท
     * 
     * @param float $number
     * @param int $decimals
     * @return string
     */
    public static function baht(float $number, int $decimals = 2): string
    {
        return number_format($number, $decimals) . ' บาท';
    }

    /**
     * จัดรูปแบบตัวเลข
     * จุดประสงค์: ใช้เพื่อจัดรูปแบบตัวเลขตามที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $formatted = NumberHelper::format(1234.56, 2, '.', ','); // ผลลัพธ์: '1,234.56'
     * ```
     * 
     * returns string ตัวเลขที่จัดรูปแบบตามที่กำหนด
     * 
     * @param float $number
     * @param int $decimals
     * @param string $decPoint
     * @param string $thousandsSep
     * @return string
     */
    public static function format(
        float $number,
        int $decimals = 0,
        string $decPoint = '.',
        string $thousandsSep = ','
    ): string {
        return number_format($number, $decimals, $decPoint, $thousandsSep);
    }

    /**
     * จัดรูปแบบเปอร์เซ็นต์
     * จุดประสงค์: ใช้เพื่อจัดรูปแบบตัวเลขเป็นเปอร์เซ็นต์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $percent = NumberHelper::percent(85.5); // ผลลัพธ์: '85.50%'
     * ```
     * 
     * returns string ตัวเลขที่จัดรูปแบบเป็นเปอร์เซ็นต์
     * 
     * @param float $number
     * @param int $decimals
     * @return string
     */
    public static function percent(float $number, int $decimals = 2): string
    {
        return number_format($number, $decimals) . '%';
    }

    /**
     * คำนวณเปอร์เซ็นต์
     * จุดประสงค์: ใช้เพื่อคำนวณเปอร์เซ็นต์จากค่าที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $percentage = NumberHelper::percentage(50, 200); // ผลลัพธ์: 25.00
     * ```
     * 
     * returns float ค่าเปอร์เซ็นต์ที่คำนวณได้
     * 
     * @param float $value
     * @param float $total
     * @param int $decimals
     * @return float
     */
    public static function percentage(float $value, float $total, int $decimals = 2): float
    {
        if ($total == 0) {
            return 0;
        }
        
        return round(($value / $total) * 100, $decimals);
    }

    /**
     * จัดรูปแบบขนาดไฟล์
     * จุดประสงค์: ใช้เพื่อจัดรูปแบบขนาดไฟล์ให้อ่านง่าย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $fileSize = NumberHelper::fileSize(2048); // ผลลัพธ์: '2 KB'
     * ```
     * 
     * returns string ขนาดไฟล์ที่จัดรูปแบบ
     * 
     * @param int $bytes
     * @param int $decimals
     * @return string
     */
    public static function fileSize(int $bytes, int $decimals = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $decimals) . ' ' . $units[$i];
    }

    /**
     * จัดรูปแบบตัวเลขขนาดใหญ่ (1K, 1M, 1B)
     * จุดประสงค์: ใช้เพื่อจัดรูปแบบตัวเลขขนาดใหญ่ให้อ่านง่าย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $abbreviated = NumberHelper::abbreviate(1500); // ผลลัพธ์: '1.5K'
     * ```
     * 
     * returns string ตัวเลขที่จัดรูปแบบขนาดใหญ่
     * 
     * @param float $number
     * @param int $decimals
     * @return string
     */
    public static function abbreviate(float $number, int $decimals = 1): string
    {
        $units = ['', 'K', 'M', 'B', 'T'];
        
        for ($i = 0; abs($number) >= 1000 && $i < count($units) - 1; $i++) {
            $number /= 1000;
        }
        
        return round($number, $decimals) . $units[$i];
    }

    /**
     * แปลงเป็นลำดับที่ (1st, 2nd, 3rd)
     * จุดประสงค์: ใช้เพื่อแปลงตัวเลขเป็นลำดับที่ในภาษาอังกฤษ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $ordinal = NumberHelper::ordinal(1); // ผลลัพธ์: '1st'
     * ```
     * 
     * returns string ลำดับที่ในรูปแบบภาษาอังกฤษ
     * 
     * @param int $number
     * @return string
     */
    public static function ordinal(int $number): string
    {
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        
        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return $number . 'th';
        }
        
        return $number . $ends[$number % 10];
    }

    /**
     * แปลงเป็นลำดับที่ภาษาไทย
     * จุดประสงค์: ใช้เพื่อแปลงตัวเลขเป็นลำดับที่ในภาษาไทย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $ordinalThai = NumberHelper::ordinalThai(1); // ผลลัพธ์: 'ที่ ๑'
     * ```
     * 
     * returns string ลำดับที่ในรูปแบบภาษาไทย
     * 
     * @param int $number
     * @return string
     */
    public static function ordinalThai(int $number): string
    {
        return 'ที่ ' . self::toThai($number);
    }

    /**
     * แปลงตัวเลขเป็นภาษาไทย
     * จุดประสงค์: ใช้เพื่อแปลงตัวเลขอารบิกเป็นตัวเลขไทย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $thaiNumber = NumberHelper::toThai(123); // ผลลัพธ์: '๑๒๓'
     * ```
     * 
     * returns string ตัวเลขในรูปแบบภาษาไทย
     * 
     * @param int $number
     * @return string
     */
    public static function toThai(int $number): string
    {
        $thaiDigits = ['๐', '๑', '๒', '๓', '๔', '๕', '๖', '๗', '๘', '๙'];
        
        return str_replace(
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $thaiDigits,
            (string)$number
        );
    }

    /**
     * แปลงตัวเลขไทยเป็นตัวเลขอารบิก
     * จุดประสงค์: ใช้เพื่อแปลงตัวเลขไทยเป็นตัวเลขอารบิก
     * ตัวอย่างการใช้งาน:
     * ```php
     * $arabicNumber = NumberHelper::fromThai('๑๒๓'); // ผลลัพธ์: '123'
     * ```
     * 
     * returns string ตัวเลขในรูปแบบอารบิก
     * 
     * @param string $thaiNumber
     * @return string
     */
    public static function fromThai(string $thaiNumber): string
    {
        $thaiDigits = ['๐', '๑', '๒', '๓', '๔', '๕', '๖', '๗', '๘', '๙'];
        
        return str_replace(
            $thaiDigits,
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $thaiNumber
        );
    }

    /**
     * แปลงตัวเลขเป็นคำอ่าน (ภาษาไทย)
     * จุดประสงค์: ใช้เพื่อแปลงตัวเลขเป็นคำอ่านในภาษาไทย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $word = NumberHelper::toWord(123.45); // ผลลัพธ์: 'หนึ่งร้อยยี่สิบสามบาทสี่สิบห้าสตางค์'
     * ```
     * 
     * returns string คำอ่านของตัวเลขในภาษาไทย
     * 
     * @param float $number
     * @return string
     */
    public static function toWord(float $number): string
    {
        $txtNum1 = ['', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
        $txtNum2 = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];
        
        $number = number_format($number, 2, '.', '');
        $numberArr = explode('.', $number);
        $baht = $numberArr[0];
        $satang = $numberArr[1] ?? '00';
        
        $bahtText = self::convertNumber($baht, $txtNum1, $txtNum2);
        $satangText = self::convertNumber($satang, $txtNum1, $txtNum2);
        
        $result = $bahtText . 'บาท';
        
        if ((int)$satang > 0) {
            $result .= $satangText . 'สตางค์';
        } else {
            $result .= 'ถ้วน';
        }
        
        return $result;
    }

    /**
     * ฟังก์ชันช่วยแปลงตัวเลข
     * จุดประสงค์: ใช้เป็นฟังก์ชันช่วยในการแปลงตัวเลขเป็นคำอ่าน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $text = NumberHelper::convertNumber('1234567', $txtNum1, $txtNum2); // ผลลัพธ์: 'หนึ่งล้านสองแสนสามหมื่นสี่พันห้าร้อยหกสิบเจ็ด'
     * ```  
     * 
     * returns string คำอ่านของตัวเลข
     * 
     * @param string $number
     * @param array $txtNum1
     * @param array $txtNum2
     * @return string
     */
    private static function convertNumber(string $number, array $txtNum1, array $txtNum2): string
    {
        $number = str_pad($number, 7, '0', STR_PAD_LEFT);
        $result = '';
        
        for ($i = 0; $i < 7; $i++) {
            $digit = (int)$number[$i];
            
            if ($digit > 0) {
                if ($i == 5 && $digit == 1) {
                    $result .= 'เอ็ด';
                } elseif ($i == 6 && $digit == 1) {
                    $result .= 'เอ็ด';
                } elseif ($i == 6 && $digit == 2) {
                    $result .= 'ยี่';
                } else {
                    $result .= $txtNum1[$digit];
                }
                
                $result .= $txtNum2[6 - $i];
            }
        }
        
        return $result;
    }

    /**
     * ปัดเศษขึ้น
     * จุดประสงค์: ใช้เพื่อปัดเศษตัวเลขขึ้นตามจำนวนทศนิยมที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $roundedUp = NumberHelper::ceil(1.234, 2); // ผลลัพธ์: 1.24
     * ```
     * 
     * returns float ตัวเลขที่ปัดเศษขึ้น
     * 
     * @param float $number
     * @param int $precision
     * @return float
     */
    public static function ceil(float $number, int $precision = 0): float
    {
        $multiplier = pow(10, $precision);
        return ceil($number * $multiplier) / $multiplier;
    }

    /**
     * ปัดเศษลง
     * จุดประสงค์: ใช้เพื่อปัดเศษตัวเลขลงตามจำนวนทศนิยมที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $roundedDown = NumberHelper::floor(1.236, 2); // ผลลัพธ์: 1.23
     * ```
     * 
     * returns float ตัวเลขที่ปัดเศษลง
     * 
     * @param float $number
     * @param int $precision
     * @return float
     */
    public static function floor(float $number, int $precision = 0): float
    {
        $multiplier = pow(10, $precision);
        return floor($number * $multiplier) / $multiplier;
    }

    /**
     * ปัดเศษ
     * จุดประสงค์: ใช้เพื่อปัดเศษตัวเลขตามจำนวนทศนิยมที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $rounded = NumberHelper::round(1.235, 2); // ผลลัพธ์: 1.24
     * ```
     * 
     * returns float ตัวเลขที่ปัดเศษ
     * 
     * @param float $number
     * @param int $precision
     * @return float
     */
    public static function round(float $number, int $precision = 0): float
    {
        return round($number, $precision);
    }

    /**
     * คำนวณค่าเฉลี่ย
     * จุดประสงค์: ใช้เพื่อหาค่าเฉลี่ยของชุดตัวเลข
     * ตัวอย่างการใช้งาน:
     * ```php
     * $average = NumberHelper::average([1, 2, 3, 4, 5]); // ผลลัพธ์: 3.0
     * ```
     * 
     * returns float ค่าเฉลี่ยของชุดตัวเลข
     * 
     * @param array $numbers
     * @return float
     */
    public static function average(array $numbers): float
    {
        if (empty($numbers)) {
            return 0;
        }
        
        return array_sum($numbers) / count($numbers);
    }

    /**
     * หาค่ามัธยฐาน
     * จุดประสงค์: ใช้เพื่อหาค่ามัธยฐานของชุดตัวเลข
     * ตัวอย่างการใช้งาน:
     * ```php
     * $median = NumberHelper::median([1, 2, 3, 4, 5]); // ผลลัพธ์: 3
     * ```
     * 
     * returns float ค่ามัธยฐานของชุดตัวเลข
     * 
     * @param array $numbers
     * @return float
     */
    public static function median(array $numbers): float
    {
        if (empty($numbers)) {
            return 0;
        }
        
        sort($numbers);
        $count = count($numbers);
        $middle = floor($count / 2);
        
        if ($count % 2) {
            return $numbers[$middle];
        }
        
        return ($numbers[$middle - 1] + $numbers[$middle]) / 2;
    }

    /**
     * หาค่าต่ำสุด
     * จุดประสงค์: ใช้เพื่อหาค่าต่ำสุดของชุดตัวเลข
     * ตัวอย่างการใช้งาน:
     * ```php
     * $min = NumberHelper::min([1, 2, 3, 4, 5]); // ผลลัพธ์: 1
     * ```
     * 
     * returns float|null ค่าต่ำสุดของชุดตัวเลข หรือ null หากไม่มีตัวเลข
     * 
     * @param array $numbers
     * @return float|null
     */
    public static function min(array $numbers): ?float
    {
        return empty($numbers) ? null : min($numbers);
    }

    /**
     * หาค่าสูงสุด
     * จุดประสงค์: ใช้เพื่อหาค่าสูงสุดของชุดตัวเลข
     * ตัวอย่างการใช้งาน:
     * ```php
     * $max = NumberHelper::max([1, 2, 3, 4, 5]); // ผลลัพธ์: 5
     * ```
     * 
     * returns float|null ค่าสูงสุดของชุดตัวเลข หรือ null หากไม่มีตัวเลข
     * 
     * @param array $numbers
     * @return float|null
     */
    public static function max(array $numbers): ?float
    {
        return empty($numbers) ? null : max($numbers);
    }

    /**
     * หาผลรวม
     * จุดประสงค์: ใช้เพื่อหาผลรวมของชุดตัวเลข
     * ตัวอย่างการใช้งาน:
     * ```php
     * $sum = NumberHelper::sum([1, 2, 3, 4, 5]); // ผลลัพธ์: 15
     * ```
     * 
     * returns float ผลรวมของชุดตัวเลข
     * 
     * @param array $numbers
     * @return float
     */
    public static function sum(array $numbers): float
    {
        return array_sum($numbers);
    }

    /**
     * ตรวจสอบว่าเป็นเลขคู่
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าตัวเลขที่กำหนดเป็นเลขคู่หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isEven = NumberHelper::isEven(4); // ผลลัพธ์: true
     * ```
     * returns bool ผลลัพธ์ว่าเป็นเลขคู่หรือไม่
     * 
     * @param int $number
     * @return bool
     */
    public static function isEven(int $number): bool
    {
        return $number % 2 === 0;
    }

    /**
     * ตรวจสอบว่าเป็นเลขคี่
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าตัวเลขที่กำหนดเป็นเลขคี่หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isOdd = NumberHelper::isOdd(3); // ผลลัพธ์: true
     * ```
     * returns bool ผลลัพธ์ว่าเป็นเลขคี่หรือไม่
     * 
     * @param int $number
     * @return bool
     */
    public static function isOdd(int $number): bool
    {
        return $number % 2 !== 0;
    }

    /**
     * ตรวจสอบว่าอยู่ในช่วง
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าตัวเลขที่กำหนดอยู่ในช่วงที่ระบุหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $inRange = NumberHelper::inRange(5, 1, 10); // ผลลัพธ์: true
     * ```
     * 
     * returns bool ผลลัพธ์ว่าอยู่ในช่วงหรือไม่
     * 
     * @param float $number
     * @param float $min
     * @param float $max
     * @return bool
     */
    public static function inRange(float $number, float $min, float $max): bool
    {
        return $number >= $min && $number <= $max;
    }

    /**
     * จำกัดค่าให้อยู่ในช่วง
     * จุดประสงค์: ใช้เพื่อจำกัดค่าตัวเลขให้อยู่ในช่วงที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $clamped = NumberHelper::clamp(15, 0, 10); // ผลลัพธ์: 10
     * ```
     * 
     * returns float ตัวเลขที่ถูกจำกัดให้อยู่ในช่วง
     * 
     * @param float $number
     * @param float $min
     * @param float $max
     * @return float
     */
    public static function clamp(float $number, float $min, float $max): float
    {
        return max($min, min($max, $number));
    }

    /**
     * แปลงเป็นเลขโรมัน
     * จุดประสงค์: ใช้เพื่อแปลงตัวเลขอารบิกเป็นเลขโรมัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $roman = NumberHelper::toRoman(1990); // ผลลัพธ์: MCMXC
     * ```
     * 
     * returns string ตัวเลขในรูปแบบโรมัน
     * 
     * @param int $number
     * @return string
     */
    public static function toRoman(int $number): string
    {
        $map = [
            'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
            'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
            'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1
        ];
        
        $result = '';
        
        foreach ($map as $roman => $value) {
            $matches = intval($number / $value);
            $result .= str_repeat($roman, $matches);
            $number = $number % $value;
        }
        
        return $result;
    }

    /**
     * สร้างตัวเลขสุ่ม
     * จุดประสงค์: ใช้เพื่อสร้างตัวเลขสุ่มในช่วงที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $randomNumber = NumberHelper::random(1, 10); // ผลลัพธ์: ตัวเลขสุ่มระหว่าง 1 ถึง 10
     * ```
     * 
     * returns int ตัวเลขสุ่มที่ถูกสร้างขึ้น
     * 
     * @param int $min
     * @param int $max
     * @return int
     */
    public static function random(int $min = 0, int $max = 100): int
    {
        return random_int($min, $max);
    }

    /**
     * คำนวณ VAT
     * จุดประสงค์: ใช้เพื่อคำนวณภาษีมูลค่าเพิ่ม (VAT) จากราคาที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $vat = NumberHelper::vat(1000, 7); // ผลลัพธ์: 70
     * ```
     * 
     * returns float จำนวน VAT ที่คำนวณได้
     * 
     * @param float $price
     * @param float $vatRate
     * @return float
     */
    public static function vat(float $price, float $vatRate = 7): float
    {
        return $price * ($vatRate / 100);
    }

    /**
     * คำนวณราคารวม VAT
     * จุดประสงค์: ใช้เพื่อคำนวณราคารวมภาษีมูลค่าเพิ่ม (VAT) จากราคาที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $priceWithVat = NumberHelper::priceWithVat(1000, 7); // ผลลัพธ์: 1070
     * ```
     * 
     * returns float ราคารวม VAT
     * 
     * @param float $price
     * @param float $vatRate
     * @return float
     */
    public static function priceWithVat(float $price, float $vatRate = 7): float
    {
        return $price + self::vat($price, $vatRate);
    }

    /**
     * คำนวณราคาก่อน VAT
     * จุดประสงค์: ใช้เพื่อคำนวณราคาก่อนภาษีมูลค่าเพิ่ม (VAT) จากราคารวม VAT ที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $priceBeforeVat = NumberHelper::priceBeforeVat(1070, 7); // ผลลัพธ์: 1000
     * ```
     * 
     * returns float ราคาก่อน VAT
     * 
     * @param float $priceWithVat
     * @param float $vatRate
     * @return float
     */
    public static function priceBeforeVat(float $priceWithVat, float $vatRate = 7): float
    {
        return $priceWithVat / (1 + ($vatRate / 100));
    }

    /**
     * คำนวณส่วนลด
     * จุดประสงค์: ใช้เพื่อคำนวณจำนวนเงินส่วนลดจากราคาที่กำหนดและอัตราส่วนลด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $discountAmount = NumberHelper::discount(1000, 10); // ผลลัพธ์: 100
     * ```
     * 
     * returns float จำนวนเงินส่วนลด
     * 
     * @param float $price
     * @param float $discount
     * @return float
     */
    public static function discount(float $price, float $discount): float
    {
        return $price * ($discount / 100);
    }

    /**
     * คำนวณราคาหลังส่วนลด
     * จุดประสงค์: ใช้เพื่อคำนวณราคาหลังจากหักส่วนลดจากราคาที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $priceAfterDiscount = NumberHelper::priceAfterDiscount(1000, 10); // ผลลัพธ์: 900
     * ```
     * 
     * returns float ราคาหลังส่วนลด
     * 
     * @param float $price
     * @param float $discount
     * @return float
     */
    public static function priceAfterDiscount(float $price, float $discount): float
    {
        return $price - self::discount($price, $discount);
    }

    /**
     * แปลงเป็น Binary
     * จุดประสงค์: ใช้เพื่อแปลงตัวเลขจำนวนเต็มเป็นเลขฐานสอง (Binary)
     * ตัวอย่างการใช้งาน:
     * ```php
     * $binary = NumberHelper::toBinary(10); // ผลลัพธ์: "1010"
     * ```
     * 
     * returns string ตัวเลขในรูปแบบ Binary
     * 
     * @param int $number
     * @return string
     */
    public static function toBinary(int $number): string
    {
        return decbin($number);
    }

    /**
     * แปลงเป็น Hexadecimal
     * จุดประสงค์: ใช้เพื่อแปลงตัวเลขจำนวนเต็มเป็นเลขฐานสิบหก (Hexadecimal)
     * ตัวอย่างการใช้งาน:
     * ```php
     * $hex = NumberHelper::toHex(255); // ผลลัพธ์: "ff"
     * ```
     * 
     * returns string ตัวเลขในรูปแบบ Hexadecimal
     * 
     * @param int $number
     * @return string
     */
    public static function toHex(int $number): string
    {
        return dechex($number);
    }

    /**
     * แปลงเป็น Octal
     * จุดประสงค์: ใช้เพื่อแปลงตัวเลขจำนวนเต็มเป็นเลขฐานแปด (Octal)
     * ตัวอย่างการใช้งาน:
     * ```php
     * $octal = NumberHelper::toOctal(64); // ผลลัพธ์: "100"
     * ```
     * 
     * returns string ตัวเลขในรูปแบบ Octal
     * 
     * @param int $number
     * @return string
     */
    public static function toOctal(int $number): string
    {
        return decoct($number);
    }

    /**
     * แปลงจาก Binary
     * จุดประสงค์: ใช้เพื่อแปลงตัวเลขในรูปแบบเลขฐานสอง (Binary) เป็นจำนวนเต็ม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $number = NumberHelper::fromBinary("1010"); // ผลลัพธ์: 10
     * ```
     * 
     * returns int จำนวนเต็มที่แปลงจาก Binary
     * 
     * @param string $binary
     * @return int
     */
    public static function fromBinary(string $binary): int
    {
        return bindec($binary);
    }

    /**
     * แปลงจาก Hexadecimal
     * จุดประสงค์: ใช้เพื่อแปลงตัวเลขในรูปแบบเลขฐานสิบหก (Hexadecimal) เป็นจำนวนเต็ม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $number = NumberHelper::fromHex("ff"); // ผลลัพธ์: 255
     * ```
     * 
     * returns int จำนวนเต็มที่แปลงจาก Hexadecimal
     * 
     * @param string $hex
     * @return int
     */
    public static function fromHex(string $hex): int
    {
        return hexdec($hex);
    }

    /**
     * แปลงจาก Octal
     * จุดประสงค์: ใช้เพื่อแปลงตัวเลขในรูปแบบเลขฐานแปด (Octal) เป็นจำนวนเต็ม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $number = NumberHelper::fromOctal("100"); // ผลลัพธ์: 64
     * ```
     * 
     * @param string $octal กำหนดตัวเลขในรูปแบบ Octal ที่ต้องการแปลง
     * @return int จำนวนเต็มที่แปลงจาก Octal
     */
    public static function fromOctal(string $octal): int
    {
        return octdec($octal);
    }

    /**
     * สร้างรหัสตัวเลข
     * จุดประสงค์: ใช้เพื่อสร้างรหัสตัวเลขที่มีรูปแบบตามที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $code = NumberHelper::generateCode('INV', 1, 5); // ผลลัพธ์: "INV00001"
     * $code = NumberHelper::generateCode(null, null, 5, 'INV00001'); // ผลลัพธ์: "INV00002"
     * ```
     * 
     * @param string|null $prefix กำหนดคำนำหน้าของรหัส (เช่น "INV")
     * @param int|null $number กำหนดตัวเลขที่ต้องการใช้ในรหัส
     * @param int $digit กำหนดจำนวนหลักของตัวเลขในรหัส (ค่าเริ่มต้นคือ 3)
     * @param string|null $lastCode กำหนดรหัสล่าสุดเพื่อให้ระบบสามารถสร้างรหัสถัดไปได้โดยอัตโนมัติ
     * @return string|false รหัสตัวเลขที่ถูกสร้างขึ้น หรือ false หากพารามิเตอร์ไม่ครบ
     */
    public static function generateCode($prefix = null, $number = null, $digit = 3, $lastCode = null) {

        // โหมดรันต่อจากรหัสล่าสุด
        if ($lastCode !== null) {
            $numberPart = preg_replace('/[^0-9]/', '', $lastCode);
            $prefixPart = preg_replace('/[0-9]/', '', $lastCode);

            $newNumber = (int)$numberPart + 1;
            return $prefixPart . str_pad($newNumber, $digit, "0", STR_PAD_LEFT);
        }

        // โหมดกำหนดเลขเอง
        if ($prefix !== null && $number !== null) {
            return $prefix . str_pad($number, $digit, "0", STR_PAD_LEFT);
        }

        return false; // กรณีพารามิเตอร์ไม่ครบ
    }
}
