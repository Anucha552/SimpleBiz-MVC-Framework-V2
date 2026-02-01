<?php
/**
 * class นี้เป็นฟังก์ชันช่วยเหลือสำหรับจัดการ array
 * 
 * จุดประสงค์: ฟังก์ชันช่วยเหลือสำหรับจัดการ array
 * ArrayHelper ควรใช้กับอะไร: array ที่ต้องการจัดการ
 * 
 * ฟีเจอร์:
 * - จัดการ array ขั้นสูง
 * - Pluck, flatten, group
 * - Filter และ transform
 * - ตรวจสอบและแก้ไข array
 * 
 * ตัวอย่างการใช้งานโดยรวม:
 * ```php
 * use App\Helpers\ArrayHelper;
 * $array = ['user' => ['profile' => ['name' => 'John']]];
 * $name = ArrayHelper::get($array, 'user.profile.name', 'Default Name');
 * ```
 */

namespace App\Helpers;

class ArrayHelper
{
    /**
     * ดึงค่าจาก array โดยใช้ dot notation
     * จุดประสงค์: ใช้เพื่อดึงค่าจาก array ที่ซับซ้อนโดยใช้คีย์แบบ dot notation
     * get() ควรใช้กับอะไร: array ที่ต้องการดึงค่า, คีย์แบบ dot notation, ค่าเริ่มต้นถ้าไม่มีค่า
     * ตัวอย่างการใช้งาน:
     * ```php
     * $value = ArrayHelper::get($array, 'user.profile.name', 'Default Name');
     * ```
     * 
     * @param array $array กำหนด array ที่ต้องการดึงค่า
     * @param string $key กำหนดคีย์แบบ dot notation
     * @param mixed $default กำหนดค่าเริ่มต้นถ้าไม่มีค่า
     * @return mixed คืนค่าที่ดึงมาหรือค่าเริ่มต้นถ้าไม่มีค่า
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
     * ตั้งค่าค่าใน array โดยใช้ dot notation
     * จุดประสงค์: ใช้เพื่อตั้งค่าค่าใน array ที่ซับซ้อนโดยใช้คีย์แบบ dot notation
     * set() ควรใช้กับอะไร: array ที่ต้องการตั้งค่า, คีย์แบบ dot notation, ค่าใหม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * ArrayHelper::set($array, 'user.profile.name', 'John Doe');
     * ```
     * 
     * @param array &$array กำหนด array ที่ต้องการตั้งค่า
     * @param string $key กำหนดคีย์แบบ dot notation
     * @param mixed $value กำหนดค่าใหม่
     * @return void ไม่มีค่าที่ส่งกลับ
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
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่ามีคีย์ใน array ที่ซับซ้อนโดยใช้คีย์แบบ dot notation
     * has() ควรใช้กับอะไร: array ที่ต้องการตรวจสอบ, คีย์แบบ dot notation
     * ตัวอย่างการใช้งาน:
     * ```php
     * if (ArrayHelper::has($array, 'user.profile.name')) {
     *     // มีคีย์ 'user.profile.name'
     * }
     * ```
     * 
     * @param array $array กำหนด array ที่ต้องการตรวจสอบ
     * @param string $key กำหนดคีย์แบบ dot notation
     * @return bool คืนค่า true ถ้ามีคีย์ใน array, false ถ้าไม่มี
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
     * จุดประสงค์: ใช้เพื่อลบคีย์จาก array ที่ซับซ้อนโดยใช้คีย์แบบ dot notation
     * forget() ควรใช้กับอะไร: array ที่ต้องการลบคีย์, คีย์แบบ dot notation
     * ตัวอย่างการใช้งาน:
     * ```php
     * ArrayHelper::forget($array, 'user.profile.name');
     * ```
     * 
     * @param array &$array กำหนด array ที่ต้องการลบคีย์
     * @param string $key กำหนดคีย์แบบ dot notation
     * @return void ไม่มีค่าที่ส่งกลับ
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
     * จุดประสงค์: ใช้เพื่อดึงค่าจาก array ของ arrays หรือ objects โดยสามารถระบุคีย์สำหรับค่าที่ต้องการและคีย์สำหรับผลลัพธ์ได้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $names = ArrayHelper::pluck($users, 'name');
     * $namesById = ArrayHelper::pluck($users, 'name', 'id');
     * ```
     * 
     * returns array ผลลัพธ์เป็น array ของค่าที่ดึงมา
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
     * จุดประสงค์: ใช้เพื่อแปลง array ที่มีหลายมิติให้เป็น array มิติเดียว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $flattened = ArrayHelper::flatten([[1, 2], [3, 4]]);
     * ```
     * 
     * returns array ผลลัพธ์เป็น array มิติเดียว
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
     * จุดประสงค์: ใช้เพื่อแบ่งกลุ่ม array ตามคีย์ที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $grouped = ArrayHelper::groupBy($users, 'role');
     * ```
     * 
     * returns array ผลลัพธ์เป็น array ที่ถูกแบ่งกลุ่ม
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
     * จุดประสงค์: ใช้เพื่อเรียงลำดับ array ของ arrays หรือ objects ตามคีย์ที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $sorted = ArrayHelper::sortBy($users, 'name');
     * $sortedDesc = ArrayHelper::sortBy($users, 'name', 'desc');
     * ```
     * 
     * returns array ผลลัพธ์เป็น array ที่ถูกเรียงลำดับ
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
     * จุดประสงค์: ใช้เพื่อกรองค่าใน array ตามเงื่อนไขที่กำหนดใน callback
     * ตัวอย่างการใช้งาน:
     * ```php
     * $filtered = ArrayHelper::filter($array, function($value) {
     *     return $value > 10;
     * });
     * ```
     * 
     * returns array ผลลัพธ์เป็น array ที่ผ่านการกรอง
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
     * จุดประสงค์: ใช้เพื่อแปลงค่าของ array ตามฟังก์ชัน callback ที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $mapped = ArrayHelper::map([1, 2, 3], function($value) {
     *     return $value * 2;
     * });
     * ```
     * 
     * returns array ผลลัพธ์เป็น array ที่ถูกแปลงค่า
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
     * จุดประสงค์: ใช้เพื่อรับเฉพาะคีย์ที่ต้องการจาก array
     * ตัวอย่างการใช้งาน:
     * ```php
     * $only = ArrayHelper::only($array, ['id', 'name']);
     * ```
     * 
     * returns array ผลลัพธ์เป็น array ที่มีเฉพาะคีย์ที่ระบุ
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
     * จุดประสงค์: ใช้เพื่อรับทุกอย่างยกเว้นคีย์ที่ระบุจาก array
     * ตัวอย่างการใช้งาน:
     * ```php
     * $except = ArrayHelper::except($array, ['password', 'secret']);
     * ```
     * 
     * returns array ผลลัพธ์เป็น array ที่ไม่มีคีย์ที่ระบุ
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
     * จุดประสงค์: ใช้เพื่อแบ่ง array ใหญ่เป็น array ย่อยๆ ตามขนาดที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $chunks = ArrayHelper::chunk([1, 2, 3, 4, 5], 2);
     * ```
     * 
     * returns array ผลลัพธ์เป็น array ของ array ย่อย
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
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าทุกค่าภายใน array ผ่านเงื่อนไขที่กำหนดใน callback หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $allPositive = ArrayHelper::every([1, 2, 3], function($value) {
     *     return $value > 0;
     * });
     * ```
     * 
     * returns bool ผลลัพธ์เป็น true ถ้าทุกค่าผ่านเงื่อนไข, false ถ้าไม่
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
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่ามีอย่างน้อยหนึ่งค่าภายใน array ผ่านเงื่อนไขที่กำหนดใน callback หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $hasNegative = ArrayHelper::some([1, -2, 3], function($value) {
     *     return $value < 0;
     * });
     * ```
     * 
     * returns bool ผลลัพธ์เป็น true ถ้ามีอย่างน้อยหนึ่งค่าผ่านเงื่อนไข, false ถ้าไม่
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
     * จุดประสงค์: ใช้เพื่อหาค่าแรกใน array ที่ผ่านเงื่อนไขที่กำหนดใน callback
     * ตัวอย่างการใช้งาน:
     * ```php
     * $firstEven = ArrayHelper::first([1, 2, 3, 4], function($value) {
     *     return $value % 2 === 0;
     * });
     * ```
     * 
     * returns mixed ผลลัพธ์เป็นค่าตัวแรกที่ผ่านเงื่อนไข หรือค่า default ถ้าไม่มีค่าใดผ่านเงื่อนไข
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
     * จุดประสงค์: ใช้เพื่อหาค่าสุดท้ายใน array ที่ผ่านเงื่อนไขที่กำหนดใน callback
     * ตัวอย่างการใช้งาน:
     * ```php
     * $lastEven = ArrayHelper::last([1, 2, 3, 4], function($value) {
     *     return $value % 2 === 0;
     * });
     * ```
     * 
     * returns mixed ผลลัพธ์เป็นค่าสุดท้ายที่ผ่านเงื่อนไข หรือค่า default ถ้าไม่มีค่าใดผ่านเงื่อนไข
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
     * จุดประสงค์: ใช้เพื่อห่อหุ้มค่าที่ไม่ใช่ array ให้อยู่ใน array
     * ตัวอย่างการใช้งาน:
     * ```php
     * $wrapped = ArrayHelper::wrap('value'); // ผลลัพธ์: ['value']
     * $wrappedArray = ArrayHelper::wrap(['a', 'b']); // ผลลัพธ์: ['a', 'b']
     * ```
     * 
     * returns array ผลลัพธ์เป็น array ที่ห่อหุ้มค่าหรือค่าเดิมถ้าเป็น array อยู่แล้ว
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
     * จุดประสงค์: ใช้เพื่อลบค่าที่เป็น null ออกจาก array
     * ตัวอย่างการใช้งาน:
     * ```php
     * $cleaned = ArrayHelper::removeNull(['a', null, 'b', null]); // ผลลัพธ์: ['a', 'b']
     * ```
     * returns array ผลลัพธ์เป็น array ที่ไม่มีค่า null
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
     * จุดประสงค์: ใช้เพื่อลบค่าที่ว่างเปล่าหรือเท่ากับ false ออกจาก array               
     * ตัวอย่างการใช้งาน:
     * ```php
     * $cleaned = ArrayHelper::removeEmpty(['a', '', 'b', false, 0, '0']); // ผลลัพธ์: ['a', 'b', 0, '0']
     * ```
     * 
     * returns array ผลลัพธ์เป็น array ที่ไม่มีค่าว่าง
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
     * จุดประสงค์: ใช้เพื่อลบค่าที่ซ้ำกันออกจาก array
     * ตัวอย่างการใช้งาน:
     * ```php
     * $unique = ArrayHelper::unique([1, 2, 2, 3, 1]); // ผลลัพธ์: [1, 2, 3]
     * ```
     * 
     * returns array ผลลัพธ์เป็น array ที่ไม่มีค่าซ้ำ
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
     * จุดประสงค์: ใช้เพื่อสุ่มค่าจาก array โดยสามารถระบุจำนวนค่าที่ต้องการสุ่มได้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $randomValue = ArrayHelper::random([1, 2, 3, 4]); // ผลลัพธ์: ค่าสุ่มหนึ่งค่า เช่น 2
     * $randomValues = ArrayHelper::random([1, 2, 3, 4], 2); // ผลลัพธ์: ค่าสุ่มสองค่า เช่น [3, 1]
     * ```
     * returns mixed ค่าสุ่มหนึ่งค่าหรือ array ของค่าสุ่ม
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
     * จุดประสงค์: ใช้เพื่อสับเปลี่ยนค่าภายใน array ให้เรียงลำดับแบบสุ่ม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $shuffled = ArrayHelper::shuffle([1, 2, 3, 4]); // ผลลัพธ์: [3, 1, 4, 2] (ตัวอย่าง)
     * ```
     * returns array ผลลัพธ์เป็น array ที่ถูกสับเปลี่ยนค่าแล้ว
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
     * จุดประสงค์: ใช้เพื่อรวม array หลายๆ ตัวเข้าด้วยกันแบบ recursive
     * ตัวอย่างการใช้งาน:
     * ```php
     * $merged = ArrayHelper::merge(['a' => 1, 'b' => ['x' => 10]], ['b' => ['y' => 20], 'c' => 3]);
     * // ผลลัพธ์: ['a' => 1, 'b' => ['x' => 10, 'y' => 20], 'c' => 3]
     * ```
     * returns array ผลลัพธ์เป็น array ที่ถูกรวมกัน
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
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่า array ที่ส่งเข้ามาเป็น associative array หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isAssoc = ArrayHelper::isAssoc(['a' => 1, 'b' => 2]); // ผลลัพธ์: true
     * $isAssoc = ArrayHelper::isAssoc([1, 2, 3]); // ผลลัพธ์: false
     * ```
     * returns bool ผลลัพธ์เป็น boolean
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
     * จุดประสงค์: ใช้เพื่อแปลง object เป็น array
     * ตัวอย่างการใช้งาน:
     * ```php
     * $array = ArrayHelper::fromObject($object);
     * ```
     * 
     * returns array ผลลัพธ์เป็น array ที่แปลงมาจาก object
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
     * จุดประสงค์: ใช้เพื่อเพิ่มค่าไว้ด้านหน้า array โดยสามารถระบุคีย์ได้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $prepended = ArrayHelper::prepend([2, 3], 1); // ผลลัพธ์: [1, 2, 3]
     * ```
     * 
     * returns array ผลลัพธ์เป็น array ที่มีค่าถูกเพิ่มไว้ด้านหน้า
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
     * จุดประสงค์: ใช้เพื่อรับค่าแรกของ array โดยสามารถกำหนดค่าเริ่มต้นได้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $first = ArrayHelper::firstValue([1, 2, 3]); // ผลลัพธ์: 1
     * $first = ArrayHelper::firstValue([], 'default'); // ผลลัพธ์: 'default'
     * ```
     * returns mixed ผลลัพธ์เป็นค่าที่อยู่ตำแหน่งแรกของ array หรือค่าเริ่มต้นถ้า array ว่าง
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
     * จุดประสงค์: ใช้เพื่อรับค่าสุดท้ายของ array โดยสามารถกำหนดค่าเริ่มต้นได้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $last = ArrayHelper::lastValue([1, 2, 3]); // ผลลัพธ์: 3
     * $last = ArrayHelper::lastValue([], 'default'); // ผลลัพธ์: 'default'
     * ```
     * returns mixed ผลลัพธ์เป็นค่าที่อยู่ตำแหน่งสุดท้ายของ array หรือค่าเริ่มต้นถ้า array ว่าง
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
     * จุดประสงค์: ใช้เพื่อดึง n ค่าแรกจาก array
     * ตัวอย่างการใช้งาน:
     * ```php
     * $firstThree = ArrayHelper::take([1, 2, 3, 4, 5], 3); // ผลลัพธ์: [1, 2, 3]
     * ```
     * returns array ผลลัพธ์เป็น array ที่มี n ค่าแรก
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
     * จุดประสงค์: ใช้เพื่อข้าม n ค่าแรกจาก array
     * ตัวอย่างการใช้งาน:
     * ```php
     * $skipped = ArrayHelper::skip([1, 2, 3, 4, 5], 2); // ผลลัพธ์: [3, 4, 5]
     * ```
     * 
     * returns array ผลลัพธ์เป็น array ที่ข้าม n ค่าแรก
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
     * จุดประสงค์: ใช้เพื่อแบ่ง array เป็นหน้าตามหมายเลขหน้าและจำนวนต่อหน้า
     * ตัวอย่างการใช้งาน:
     * ```php
     * $pageItems = ArrayHelper::paginate([1, 2, 3, 4, 5], 2, 2); // ผลลัพธ์: [3, 4]
     * ```
     * 
     * returns array ผลลัพธ์เป็น array ของค่าที่อยู่ในหน้าที่ระบุ
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
     * จุดประสงค์: ใช้เพื่อนับจำนวนครั้งที่ค่าต่างๆ ปรากฏใน array
     * ตัวอย่างการใช้งาน:
     * ```php
     * $counts = ArrayHelper::countValues([1, 2, 2, 3, 3, 3]); // ผลลัพธ์: [1 => 1, 2 => 2, 3 => 3]
     * ```
     * 
     * returns array ผลลัพธ์เป็น associative array ที่มีค่ารวมกับจำนวนครั้งที่ปรากฏ
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
     * จุดประสงค์: ใช้เพื่อรวมหลายๆ array เข้าด้วยกันโดยจับค่าที่ตำแหน่งเดียวกันมาเป็นกลุ่ม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $zipped = ArrayHelper::zip([1, 2], ['a', 'b']); // ผลลัพธ์: [[1, 'a'], [2, 'b']]
     * ```
     * 
     * returns array ผลลัพธ์เป็น array ของกลุ่มค่าที่จับคู่กัน
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
