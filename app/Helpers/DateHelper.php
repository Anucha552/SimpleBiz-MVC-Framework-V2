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
     * 
     * @param string $date
     * @return bool
     */
    public static function isWeekday(string $date): bool
    {
        return !self::isWeekend($date);
    }
}
