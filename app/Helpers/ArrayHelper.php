<?php
/**
 * Array Helper
 * 
 * จุดประสงค์: ฟังก์ชันช่วยเหลือสำหรับจัดการ array
 * 
 * ฟีเจอร์:
 * - จัดการ array ขั้นสูง
 * - Pluck, flatten, group
 * - Filter และ transform
 */

namespace App\Helpers;

class ArrayHelper
{
    /**
     * ดึงค่าจาก array ด้วย dot notation
     * 
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(array $array, string $key, $default = null)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }
        
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            
            $array = $array[$segment];
        }
        
        return $array;
    }

    /**
     * ตั้งค่าใน array ด้วย dot notation
     * 
     * @param array &$array
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        
        while (count($keys) > 1) {
            $key = array_shift($keys);
            
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            
            $array = &$array[$key];
        }
        
        $array[array_shift($keys)] = $value;
    }

    /**
     * ตรวจสอบว่ามีคีย์ใน array หรือไม่ (รองรับ dot notation)
     * 
     * @param array $array
     * @param string $key
     * @return bool
     */
    public static function has(array $array, string $key): bool
    {
        if (isset($array[$key])) {
            return true;
        }
        
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }
            
            $array = $array[$segment];
        }
        
        return true;
    }

    /**
     * ลบคีย์จาก array (รองรับ dot notation)
     * 
     * @param array &$array
     * @param string $key
     * @return void
     */
    public static function forget(array &$array, string $key): void
    {
        $keys = explode('.', $key);
        
        while (count($keys) > 1) {
            $key = array_shift($keys);
            
            if (!isset($array[$key]) || !is_array($array[$key])) {
                return;
            }
            
            $array = &$array[$key];
        }
        
        unset($array[array_shift($keys)]);
    }

    /**
     * Pluck - ดึงค่าจาก array ของ arrays/objects
     * 
     * @param array $array
     * @param string $value
     * @param string|null $key
     * @return array
     */
    public static function pluck(array $array, string $value, ?string $key = null): array
    {
        $results = [];
        
        foreach ($array as $item) {
            $itemValue = is_object($item) ? $item->$value : $item[$value] ?? null;
            
            if ($key === null) {
                $results[] = $itemValue;
            } else {
                $itemKey = is_object($item) ? $item->$key : $item[$key] ?? null;
                $results[$itemKey] = $itemValue;
            }
        }
        
        return $results;
    }

    /**
     * Flatten - แปลง multi-dimensional array เป็น single-dimensional
     * 
     * @param array $array
     * @param int $depth
     * @return array
     */
    public static function flatten(array $array, int $depth = INF): array
    {
        $result = [];
        
        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } else {
                $values = $depth === 1
                    ? array_values($item)
                    : self::flatten($item, $depth - 1);
                
                foreach ($values as $value) {
                    $result[] = $value;
                }
            }
        }
        
        return $result;
    }

    /**
     * แบ่งกลุ่ม array ตาม key
     * 
     * @param array $array
     * @param string $groupBy
     * @return array
     */
    public static function groupBy(array $array, string $groupBy): array
    {
        $result = [];
        
        foreach ($array as $item) {
            $key = is_object($item) ? $item->$groupBy : $item[$groupBy] ?? null;
            
            if ($key !== null) {
                if (!isset($result[$key])) {
                    $result[$key] = [];
                }
                $result[$key][] = $item;
            }
        }
        
        return $result;
    }

    /**
     * เรียงลำดับ array ตาม key
     * 
     * @param array $array
     * @param string $key
     * @param string $direction 'asc' or 'desc'
     * @return array
     */
    public static function sortBy(array $array, string $key, string $direction = 'asc'): array
    {
        usort($array, function($a, $b) use ($key, $direction) {
            $aValue = is_object($a) ? $a->$key : $a[$key] ?? null;
            $bValue = is_object($b) ? $b->$key : $b[$key] ?? null;
            
            if ($aValue == $bValue) {
                return 0;
            }
            
            $result = $aValue < $bValue ? -1 : 1;
            
            return $direction === 'desc' ? -$result : $result;
        });
        
        return $array;
    }

    /**
     * Filter array โดยใช้ callback
     * 
     * @param array $array
     * @param callable $callback
     * @return array
     */
    public static function filter(array $array, callable $callback): array
    {
        return array_filter($array, $callback);
    }

    /**
     * Map array โดยใช้ callback
     * 
     * @param array $array
     * @param callable $callback
     * @return array
     */
    public static function map(array $array, callable $callback): array
    {
        return array_map($callback, $array);
    }

    /**
     * รับเฉพาะคีย์ที่ระบุ
     * 
     * @param array $array
     * @param array $keys
     * @return array
     */
    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * รับทุกอย่างยกเว้นคีย์ที่ระบุ
     * 
     * @param array $array
     * @param array $keys
     * @return array
     */
    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }

    /**
     * แบ่ง array เป็นชิ้นๆ
     * 
     * @param array $array
     * @param int $size
     * @return array
     */
    public static function chunk(array $array, int $size): array
    {
        return array_chunk($array, $size);
    }

    /**
     * ตรวจสอบว่าทุกค่าผ่านเงื่อนไขหรือไม่
     * 
     * @param array $array
     * @param callable $callback
     * @return bool
     */
    public static function every(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if (!$callback($value, $key)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * ตรวจสอบว่ามีอย่างน้อยหนึ่งค่าผ่านเงื่อนไข
     * 
     * @param array $array
     * @param callable $callback
     * @return bool
     */
    public static function some(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * หาค่าแรกที่ผ่านเงื่อนไข
     * 
     * @param array $array
     * @param callable $callback
     * @param mixed $default
     * @return mixed
     */
    public static function first(array $array, callable $callback, $default = null)
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }
        
        return $default;
    }

    /**
     * หาค่าสุดท้ายที่ผ่านเงื่อนไข
     * 
     * @param array $array
     * @param callable $callback
     * @param mixed $default
     * @return mixed
     */
    public static function last(array $array, callable $callback, $default = null)
    {
        return self::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * Wrap ค่าใน array ถ้ายังไม่ใช่ array
     * 
     * @param mixed $value
     * @return array
     */
    public static function wrap($value): array
    {
        if (is_null($value)) {
            return [];
        }
        
        return is_array($value) ? $value : [$value];
    }

    /**
     * ลบค่า null จาก array
     * 
     * @param array $array
     * @return array
     */
    public static function removeNull(array $array): array
    {
        return array_filter($array, function($value) {
            return !is_null($value);
        });
    }

    /**
     * ลบค่าว่างจาก array
     * 
     * @param array $array
     * @return array
     */
    public static function removeEmpty(array $array): array
    {
        return array_filter($array, function($value) {
            return !empty($value) || $value === 0 || $value === '0';
        });
    }

    /**
     * Unique array (ลบค่าซ้ำ)
     * 
     * @param array $array
     * @return array
     */
    public static function unique(array $array): array
    {
        return array_values(array_unique($array));
    }

    /**
     * สุ่มค่าจาก array
     * 
     * @param array $array
     * @param int $count
     * @return mixed
     */
    public static function random(array $array, int $count = 1)
    {
        if (empty($array)) {
            return $count === 1 ? null : [];
        }
        
        $keys = array_rand($array, min($count, count($array)));
        
        if ($count === 1) {
            return $array[$keys];
        }
        
        $result = [];
        foreach ((array)$keys as $key) {
            $result[] = $array[$key];
        }
        
        return $result;
    }

    /**
     * สับเปลี่ยนค่าใน array
     * 
     * @param array $array
     * @return array
     */
    public static function shuffle(array $array): array
    {
        shuffle($array);
        return $array;
    }

    /**
     * Merge arrays แบบ recursive
     * 
     * @param array ...$arrays
     * @return array
     */
    public static function merge(...$arrays): array
    {
        $result = [];
        
        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                if (is_int($key)) {
                    $result[] = $value;
                } elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
                    $result[$key] = self::merge($result[$key], $value);
                } else {
                    $result[$key] = $value;
                }
            }
        }
        
        return $result;
    }

    /**
     * ตรวจสอบว่าเป็น associative array หรือไม่
     * 
     * @param array $array
     * @return bool
     */
    public static function isAssoc(array $array): bool
    {
        if ([] === $array) {
            return false;
        }
        
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * แปลง object เป็น array
     * 
     * @param mixed $object
     * @return array
     */
    public static function fromObject($object): array
    {
        return json_decode(json_encode($object), true);
    }

    /**
     * Prepend - เพิ่มค่าไว้ด้านหน้า array
     * 
     * @param array $array
     * @param mixed $value
     * @param mixed $key
     * @return array
     */
    public static function prepend(array $array, $value, $key = null): array
    {
        if ($key === null) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }
        
        return $array;
    }

    /**
     * รับค่าแรกของ array
     * 
     * @param array $array
     * @param mixed $default
     * @return mixed
     */
    public static function firstValue(array $array, $default = null)
    {
        return empty($array) ? $default : reset($array);
    }

    /**
     * รับค่าสุดท้ายของ array
     * 
     * @param array $array
     * @param mixed $default
     * @return mixed
     */
    public static function lastValue(array $array, $default = null)
    {
        return empty($array) ? $default : end($array);
    }

    /**
     * ดึง n ค่าแรก
     * 
     * @param array $array
     * @param int $count
     * @return array
     */
    public static function take(array $array, int $count): array
    {
        return array_slice($array, 0, $count);
    }

    /**
     * ข้าม n ค่าแรก
     * 
     * @param array $array
     * @param int $count
     * @return array
     */
    public static function skip(array $array, int $count): array
    {
        return array_slice($array, $count);
    }

    /**
     * แบ่งหน้า array
     * 
     * @param array $array
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function paginate(array $array, int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        return array_slice($array, $offset, $perPage);
    }

    /**
     * นับค่าที่ปรากฏใน array
     * 
     * @param array $array
     * @return array
     */
    public static function countValues(array $array): array
    {
        return array_count_values($array);
    }

    /**
     * Zip arrays เข้าด้วยกัน
     * 
     * @param array ...$arrays
     * @return array
     */
    public static function zip(...$arrays): array
    {
        $result = [];
        $maxLength = max(array_map('count', $arrays));
        
        for ($i = 0; $i < $maxLength; $i++) {
            $result[] = array_map(function($array) use ($i) {
                return $array[$i] ?? null;
            }, $arrays);
        }
        
        return $result;
    }
}
