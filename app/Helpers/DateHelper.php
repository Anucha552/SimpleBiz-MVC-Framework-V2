<?php
/**
 * Date Helper
 * 
 * จุดประสงค์: ฟังก์ชันช่วยเหลือสำหรับจัดการวันที่และเวลา
 * 
 * ฟีเจอร์:
 * - แปลงวันที่เป็นภาษาไทย
 * - คำนวณความแตกต่างของเวลา
 * - Format วันที่รูปแบบต่างๆ
 * - แปลง timezone
 * - ตรวจสอบวันที่ (เช่น วันนี้, เมื่อวาน, พรุ่งนี้)
 * - ฟังก์ชันช่วยเหลือสำหรับการจัดการวันที่และเวลาในแบบแอปพลิเคชันเว็บ
 */

namespace App\Helpers;

class DateHelper
{
    /**
     * เดือนภาษาไทยแบบเต็ม
     */
    private const THAI_MONTHS_FULL = [
        1 => 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];

    /**
     * เดือนภาษาไทยแบบย่อ
     */
    private const THAI_MONTHS_SHORT = [
        1 => 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
        'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'
    ];

    /**
     * วันภาษาไทยแบบเต็ม
     */
    private const THAI_DAYS_FULL = [
        'Sunday' => 'วันอาทิตย์',
        'Monday' => 'วันจันทร์',
        'Tuesday' => 'วันอังคาร',
        'Wednesday' => 'วันพุธ',
        'Thursday' => 'วันพฤหัสบดี',
        'Friday' => 'วันศุกร์',
        'Saturday' => 'วันเสาร์'
    ];

    /**
     * วันภาษาไทยแบบย่อ
     */
    private const THAI_DAYS_SHORT = [
        'Sunday' => 'อา.',
        'Monday' => 'จ.',
        'Tuesday' => 'อ.',
        'Wednesday' => 'พ.',
        'Thursday' => 'พฤ.',
        'Friday' => 'ศ.',
        'Saturday' => 'ส.'
    ];

    /**
     * แปลงวันที่เป็นภาษาไทย
     * จุดประสงค์: ใช้เพื่อแปลงวันที่เป็นรูปแบบวันที่ภาษาไทย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $date = DateHelper::thaiDate('2023-10-05'); // ผลลัพธ์: '5 ตุลาคม 2566'
     * ```
     * 
     * returns string วันที่ในรูปแบบภาษาไทย
     * 
     * @param string $date วันที่ในรูปแบบ Y-m-d หรือ timestamp
     * @param bool $shortMonth ใช้ชื่อเดือนแบบย่อหรือไม่
     * @param bool $buddhistEra ใช้พ.ศ. หรือ ค.ศ.
     * @return string
     */
    public static function thaiDate(string $date, bool $shortMonth = false, bool $buddhistEra = true): string
    {
        $timestamp = is_numeric($date) ? (int)$date : strtotime($date);
        
        if (!$timestamp) {
            return '';
        }
        
        $day = date('j', $timestamp);
        $month = (int)date('n', $timestamp);
        $year = (int)date('Y', $timestamp);
        
        if ($buddhistEra) {
            $year += 543;
        }
        
        $monthName = $shortMonth ? self::THAI_MONTHS_SHORT[$month] : self::THAI_MONTHS_FULL[$month];
        
        return "{$day} {$monthName} {$year}";
    }

    /**
     * แปลงวันที่และเวลาเป็นภาษาไทย
     * จุดประสงค์: ใช้เพื่อแปลงวันที่และเวลาเป็นรูปแบบวันที่และเวลาภาษาไทย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $dateTime = DateHelper::thaiDateTime('2023-10-05 14:30:00'); // ผลลัพธ์: '5 ตุลาคม 2566 เวลา 14:30 น.'
     * ```
     * 
     * returns string วันที่และเวลาในรูปแบบภาษาไทย
     * 
     * @param string $datetime
     * @param bool $shortMonth
     * @param bool $buddhistEra
     * @return string
     */
    public static function thaiDateTime(string $datetime, bool $shortMonth = false, bool $buddhistEra = true): string
    {
        $timestamp = is_numeric($datetime) ? (int)$datetime : strtotime($datetime);
        
        if (!$timestamp) {
            return '';
        }
        
        $date = self::thaiDate($datetime, $shortMonth, $buddhistEra);
        $time = date('H:i', $timestamp);
        
        return "{$date} เวลา {$time} น.";
    }

    /**
     * แปลงวันเป็นภาษาไทย (วันจันทร์, วันอังคาร)
     * จุดประสงค์: ใช้เพื่อแปลงวันในสัปดาห์เป็นชื่อวันภาษาไทย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $day = DateHelper::thaiDay('2023-10-05'); // ผลลัพธ์: 'วันพฤหัสบดี'
     * $shortDay = DateHelper::thaiDay('2023-10-05', true); // ผลลัพธ์: 'พฤ.'
     * ```
     * 
     * returns string ชื่อวันในรูปแบบภาษาไทย
     * 
     * @param string $date
     * @param bool $short ใช้ชื่อย่อหรือไม่
     * @return string
     */
    public static function thaiDay(string $date, bool $short = false): string
    {
        $timestamp = is_numeric($date) ? (int)$date : strtotime($date);
        
        if (!$timestamp) {
            return '';
        }
        
        $dayName = date('l', $timestamp);
        
        return $short ? self::THAI_DAYS_SHORT[$dayName] : self::THAI_DAYS_FULL[$dayName];
    }

    /**
     * แปลงวันที่เป็นรูปแบบ "วันนี้", "เมื่อวาน", "พรุ่งนี้" หรือวันที่
     * จุดประสงค์: ใช้เพื่อแปลงวันที่เป็นรูปแบบที่มนุษย์อ่านง่าย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $humanDate = DateHelper::humanDate('2023-10-05 14:30:00'); // ผลลัพธ์: '5 ตุลาคม 2566 เวลา 14:30 น.'
     * ```
     * 
     * returns string วันที่ในรูปแบบที่มนุษย์อ่านง่าย
     * 
     * @param string $date
     * @return string
     */
    public static function humanDate(string $date): string
    {
        $timestamp = is_numeric($date) ? (int)$date : strtotime($date);
        
        if (!$timestamp) {
            return '';
        }
        
        $today = strtotime('today');
        $yesterday = strtotime('yesterday');
        $tomorrow = strtotime('tomorrow');
        $dateOnly = strtotime(date('Y-m-d', $timestamp));
        
        if ($dateOnly == $today) {
            return 'วันนี้ ' . date('H:i', $timestamp) . ' น.';
        } elseif ($dateOnly == $yesterday) {
            return 'เมื่อวาน ' . date('H:i', $timestamp) . ' น.';
        } elseif ($dateOnly == $tomorrow) {
            return 'พรุ่งนี้ ' . date('H:i', $timestamp) . ' น.';
        }
        
        return self::thaiDate($date, true);
    }

    /**
     * แสดงเวลาแบบมนุษย์อ่านง่าย (5 นาทีที่แล้ว, 2 ชั่วโมงที่แล้ว)
     * จุดประสงค์: ใช้เพื่อแสดงเวลาที่ผ่านมาในรูปแบบที่มนุษย์อ่านง่าย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $timeAgo = DateHelper::timeAgo('2023-10-05 14:30:00'); // ผลลัพธ์: '2 ชั่วโมงที่แล้ว'
     * ```
     * 
     * returns string เวลาที่ผ่านมาในรูปแบบที่มนุษย์อ่านง่าย
     * 
     * @param string $datetime
     * @return string
     */
    public static function timeAgo(string $datetime): string
    {
        $timestamp = is_numeric($datetime) ? (int)$datetime : strtotime($datetime);
        
        if (!$timestamp) {
            return '';
        }
        
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return 'เมื่อสักครู่';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return "{$minutes} นาทีที่แล้ว";
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return "{$hours} ชั่วโมงที่แล้ว";
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return "{$days} วันที่แล้ว";
        } elseif ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return "{$weeks} สัปดาห์ที่แล้ว";
        } elseif ($diff < 31536000) {
            $months = floor($diff / 2592000);
            return "{$months} เดือนที่แล้ว";
        } else {
            $years = floor($diff / 31536000);
            return "{$years} ปีที่แล้ว";
        }
    }

    /**
     * แปลงวันที่เป็นรูปแบบที่กำหนด
     * จุดประสงค์: ใช้เพื่อแปลงวันที่เป็นรูปแบบที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $formattedDate = DateHelper::format('2023-10-05', 'd/m/Y'); // ผลลัพธ์: '05/10/2023'
     * ```
     * 
     * returns string วันที่ในรูปแบบที่กำหนด
     * 
     * @param string $date
     * @param string $format
     * @return string
     */
    public static function format(string $date, string $format = 'Y-m-d'): string
    {
        $timestamp = is_numeric($date) ? (int)$date : strtotime($date);
        
        if (!$timestamp) {
            return '';
        }
        
        return date($format, $timestamp);
    }

    /**
     * คำนวณความแตกต่างระหว่าง 2 วันที่
     * จุดประสงค์: ใช้เพื่อคำนวณความแตกต่างระหว่าง 2 วันที่ในหน่วยที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $diffDays = DateHelper::diff('2023-10-01', '2023-10-05', 'days'); // ผลลัพธ์: 4
     * ```
     * 
     * returns int ความแตกต่างระหว่าง 2 วันที่ในหน่วยที่กำหนด
     * 
     * @param string $date1
     * @param string $date2
     * @param string $unit ('days', 'hours', 'minutes', 'seconds')
     * @return int
     */
    public static function diff(string $date1, string $date2, string $unit = 'days'): int
    {
        $timestamp1 = is_numeric($date1) ? (int)$date1 : strtotime($date1);
        $timestamp2 = is_numeric($date2) ? (int)$date2 : strtotime($date2);
        
        if (!$timestamp1 || !$timestamp2) {
            return 0;
        }
        
        $diff = abs($timestamp1 - $timestamp2);
        
        switch ($unit) {
            case 'seconds':
                return $diff;
            case 'minutes':
                return floor($diff / 60);
            case 'hours':
                return floor($diff / 3600);
            case 'days':
            default:
                return floor($diff / 86400);
        }
    }

    /**
     * เพิ่มจำนวนวัน
     * จุดประสงค์: ใช้เพื่อเพิ่มจำนวนวันให้กับวันที่ที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $newDate = DateHelper::addDays('2023-10-05', 10); // ผลลัพธ์: '2023-10-15'
     * ```
     * 
     * returns string วันที่ใหม่หลังจากเพิ่มวัน
     * 
     * @param string $date
     * @param int $days
     * @param string $format
     * @return string
     */
    public static function addDays(string $date, int $days, string $format = 'Y-m-d'): string
    {
        $timestamp = is_numeric($date) ? (int)$date : strtotime($date);
        
        if (!$timestamp) {
            return '';
        }
        
        return date($format, strtotime("+{$days} days", $timestamp));
    }

    /**
     * ลดจำนวนวัน
     * จุดประสงค์: ใช้เพื่อลดจำนวนวันให้กับวันที่ที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $newDate = DateHelper::subDays('2023-10-15', 10); // ผลลัพธ์: '2023-10-05'
     * ```
     * 
     * returns string วันที่ใหม่หลังจากลดวัน
     * 
     * @param string $date
     * @param int $days
     * @param string $format
     * @return string
     */
    public static function subDays(string $date, int $days, string $format = 'Y-m-d'): string
    {
        return self::addDays($date, -$days, $format);
    }

    /**
     * เพิ่มเดือน
     * จุดประสงค์: ใช้เพื่อเพิ่มจำนวนเดือนให้กับวันที่ที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $newDate = DateHelper::addMonths('2023-10-05', 2); // ผลลัพธ์: '2023-12-05'
     * ```
     * 
     * returns string วันที่ใหม่หลังจากเพิ่มเดือน
     * 
     * @param string $date
     * @param int $months
     * @param string $format
     * @return string
     */
    public static function addMonths(string $date, int $months, string $format = 'Y-m-d'): string
    {
        $timestamp = is_numeric($date) ? (int)$date : strtotime($date);
        
        if (!$timestamp) {
            return '';
        }
        
        return date($format, strtotime("+{$months} months", $timestamp));
    }

    /**
     * เพิ่มปี
     * จุดประสงค์: ใช้เพื่อเพิ่มจำนวนปีให้กับวันที่ที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $newDate = DateHelper::addYears('2023-10-05', 3); // ผลลัพธ์: '2026-10-05'
     * ```
     * 
     * returns string วันที่ใหม่หลังจากเพิ่มปี
     * 
     * @param string $date
     * @param int $years
     * @param string $format
     * @return string
     */
    public static function addYears(string $date, int $years, string $format = 'Y-m-d'): string
    {
        $timestamp = is_numeric($date) ? (int)$date : strtotime($date);
        
        if (!$timestamp) {
            return '';
        }
        
        return date($format, strtotime("+{$years} years", $timestamp));
    }

    /**
     * ตรวจสอบว่าเป็นวันนี้หรือไม่
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าค่าวันที่ที่ระบุเป็นวันปัจจุบันหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isToday = DateHelper::isToday('2023-10-05'); // ผลลัพธ์: true หรือ false
     * ```
     * 
     * returns bool true ถ้าวันที่เป็นวันนี้, false ถ้าไม่ใช่
     * 
     * @param string $date
     * @return bool
     */
    public static function isToday(string $date): bool
    {
        $timestamp = is_numeric($date) ? (int)$date : strtotime($date);
        
        if (!$timestamp) {
            return false;
        }
        
        return date('Y-m-d', $timestamp) === date('Y-m-d');
    }

    /**
     * ตรวจสอบว่าเป็นเมื่อวานหรือไม่
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าค่าวันที่ที่ระบุเป็นวันเมื่อวานหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isYesterday = DateHelper::isYesterday('2023-10-04'); // ผลลัพธ์: true หรือ false
     * ```
     * 
     * returns bool true ถ้าวันที่เป็นเมื่อวาน, false ถ้าไม่ใช่
     * 
     * @param string $date
     * @return bool
     */
    public static function isYesterday(string $date): bool
    {
        $timestamp = is_numeric($date) ? (int)$date : strtotime($date);
        
        if (!$timestamp) {
            return false;
        }
        
        return date('Y-m-d', $timestamp) === date('Y-m-d', strtotime('yesterday'));
    }

    /**
     * ตรวจสอบว่าเป็นพรุ่งนี้หรือไม่
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าค่าวันที่ที่ระบุเป็นวันพรุ่งนี้หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isTomorrow = DateHelper::isTomorrow('2023-10-06'); // ผลลัพธ์: true หรือ false
     * ```
     * 
     * returns bool true ถ้าวันที่เป็นพรุ่งนี้, false ถ้าไม่ใช่
     * 
     * @param string $date
     * @return bool
     */
    public static function isTomorrow(string $date): bool
    {
        $timestamp = is_numeric($date) ? (int)$date : strtotime($date);
        
        if (!$timestamp) {
            return false;
        }
        
        return date('Y-m-d', $timestamp) === date('Y-m-d', strtotime('tomorrow'));
    }

    /**
     * ตรวจสอบว่าเป็นอดีตหรือไม่
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าค่าวันที่ที่ระบุเป็นอดีตหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isPast = DateHelper::isPast('2023-10-04'); // ผลลัพธ์: true หรือ false
     * ```
     * 
     * returns bool true ถ้าวันที่เป็นอดีต, false ถ้าไม่ใช่
     * 
     * @param string $date
     * @return bool
     */
    public static function isPast(string $date): bool
    {
        $timestamp = is_numeric($date) ? (int)$date : strtotime($date);
        
        if (!$timestamp) {
            return false;
        }
        
        return $timestamp < time();
    }

    /**
     * ตรวจสอบว่าเป็นอนาคตหรือไม่
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าค่าวันที่ที่ระบุเป็นอนาคตหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isFuture = DateHelper::isFuture('2023-10-06'); // ผลลัพธ์: true หรือ false
     * ```
     * 
     * returns bool true ถ้าวันที่เป็นอนาคต, false ถ้าไม่ใช่
     * 
     * @param string $date
     * @return bool
     */
    public static function isFuture(string $date): bool
    {
        $timestamp = is_numeric($date) ? (int)$date : strtotime($date);
        
        if (!$timestamp) {
            return false;
        }
        
        return $timestamp > time();
    }

    /**
     * วันแรกของเดือน
     * จุดประสงค์: ใช้เพื่อรับวันที่แรกของเดือนจากวันที่ที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $startOfMonth = DateHelper::startOfMonth('2023-10-05'); // ผลลัพธ์: 2023-10-01
     * ```
     * 
     * returns string วันที่แรกของเดือน
     * 
     * @param string|null $date
     * @param string $format
     * @return string
     */
    public static function startOfMonth(?string $date = null, string $format = 'Y-m-d'): string
    {
        $timestamp = $date ? (is_numeric($date) ? (int)$date : strtotime($date)) : time();
        return date($format, strtotime('first day of this month', $timestamp));
    }

    /**
     * วันสุดท้ายของเดือน
     * จุดประสงค์: ใช้เพื่อรับวันที่สุดท้ายของเดือนจากวันที่ที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $endOfMonth = DateHelper::endOfMonth('2023-10-05'); // ผลลัพธ์: 2023-10-31
     * ```
     * 
     * returns string วันที่สุดท้ายของเดือน
     * 
     * @param string|null $date
     * @param string $format
     * @return string
     */
    public static function endOfMonth(?string $date = null, string $format = 'Y-m-d'): string
    {
        $timestamp = $date ? (is_numeric($date) ? (int)$date : strtotime($date)) : time();
        return date($format, strtotime('last day of this month', $timestamp));
    }

    /**
     * แปลง timestamp เป็นวันที่
     * จุดประสงค์: ใช้เพื่อแปลง timestamp เป็นวันที่ในรูปแบบที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $date = DateHelper::fromTimestamp(1696502400); // ผลลัพธ์: 2023-10-05 00:00:00
     * ```
     * 
     * returns string วันที่ที่แปลงจาก timestamp
     * 
     * @param int $timestamp
     * @param string $format
     * @return string
     */
    public static function fromTimestamp(int $timestamp, string $format = 'Y-m-d H:i:s'): string
    {
        return date($format, $timestamp);
    }

    /**
     * แปลงวันที่เป็น timestamp
     * จุดประสงค์: ใช้เพื่อแปลงวันที่ในรูปแบบที่กำหนดเป็น timestamp
     * ตัวอย่างการใช้งาน:
     * ```php
     * $timestamp = DateHelper::toTimestamp('2023-10-05 00:00:00'); // ผลลัพธ์: 1696502400
     * ```
     * 
     * returns int timestamp ที่แปลงจากวันที่
     * 
     * @param string $date
     * @return int
     */
    public static function toTimestamp(string $date): int
    {
        return strtotime($date);
    }

    /**
     * รับวันที่ปัจจุบัน
     * จุดประสงค์: ใช้เพื่อรับวันที่และเวลาปัจจุบันในรูปแบบที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $currentDateTime = DateHelper::now(); // ผลลัพธ์: '2023-10-05 14:30:00'
     * ```
     * 
     * returns string วันที่และเวลาปัจจุบัน
     * 
     * @param string $format
     * @return string
     */
    public static function now(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format);
    }

    /**
     * รับวันที่วันนี้
     * จุดประสงค์: ใช้เพื่อรับวันที่วันนี้ในรูปแบบที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $today = DateHelper::today(); // ผลลัพธ์: '2023-10-05'
     * ```
     * 
     * returns string วันที่วันนี้
     * 
     * @param string $format
     * @return string
     */
    public static function today(string $format = 'Y-m-d'): string
    {
        return date($format);
    }

    /**
     * รับวันที่เมื่อวาน
     * จุดประสงค์: ใช้เพื่อรับวันที่เมื่อวานในรูปแบบที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $yesterday = DateHelper::yesterday(); // ผลลัพธ์: '2023-10-04'
     * ```
     * 
     * returns string วันที่เมื่อวาน
     * 
     * @param string $format
     * @return string
     */
    public static function yesterday(string $format = 'Y-m-d'): string
    {
        return date($format, strtotime('yesterday'));
    }

    /**
     * รับวันที่พรุ่งนี้
     * จุดประสงค์: ใช้เพื่อรับวันที่พรุ่งนี้ในรูปแบบที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $tomorrow = DateHelper::tomorrow(); // ผลลัพธ์: '2023-10-06'
     * ```
     * 
     * returns string วันที่พรุ่งนี้
     * 
     * @param string $format
     * @return string
     */
    public static function tomorrow(string $format = 'Y-m-d'): string
    {
        return date($format, strtotime('tomorrow'));
    }

    /**
     * ตรวจสอบว่าเป็นวันหยุดสุดสัปดาห์หรือไม่
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าวันที่ที่กำหนดเป็นวันหยุดสุดสัปดาห์หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isWeekend = DateHelper::isWeekend('2023-10-07'); // ผลลัพธ์: true (ถ้าวันที่เป็นวันเสาร์หรืออาทิตย์)
     * ```
     * 
     * returns bool ผลลัพธ์ว่าเป็นวันหยุดสุดสัปดาห์หรือไม่
     * 
     * @param string $date
     * @return bool
     */
    public static function isWeekend(string $date): bool
    {
        $timestamp = is_numeric($date) ? (int)$date : strtotime($date);
        
        if (!$timestamp) {
            return false;
        }
        
        $dayOfWeek = (int)date('N', $timestamp);
        return $dayOfWeek >= 6; // 6=Saturday, 7=Sunday
    }

    /**
     * ตรวจสอบว่าเป็นวันทำงานหรือไม่
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าวันที่ที่กำหนดเป็นวันทำงานหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isWeekday = DateHelper::isWeekday('2023-10-05'); // ผลลัพธ์: true (ถ้าวันที่ไม่ใช่วันเสาร์หรืออาทิตย์)
     * ```
     * 
     * returns bool ผลลัพธ์ว่าเป็นวันทำงานหรือไม่
     * 
     * @param string $date
     * @return bool
     */
    public static function isWeekday(string $date): bool
    {
        return !self::isWeekend($date);
    }
}
