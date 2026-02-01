<?php
/**
 * คลาส Cache สำหรับจัดการ caching ในแอปพลิเคชัน
 * 
 * จุดประสงค์: จัดการ caching เพื่อเพิ่มความเร็วแอปพลิเคชัน เช่น การเก็บผลลัพธ์จากการคำนวณหรือการดึงข้อมูลจากฐานข้อมูล
 * Cache ควรใช้กับอะไร: การเก็บข้อมูลที่ต้องการเข้าถึงบ่อย ๆ เพื่อลดภาระการประมวลผลซ้ำ เช่น ผลลัพธ์จากการคิวรีฐานข้อมูล, การตั้งค่าระบบ, หรือข้อมูลที่คำนวณได้
 * 
 * ฟีเจอร์หลัก:
 * - บันทึกและดึงข้อมูลจาก cache
 * - ตั้งเวลาหมดอายุของ cache (TTL)
 * - ลบ cache ทีละรายการหรือทั้งหมด
 * - Cache tags สำหรับการจัดกลุ่ม
 * - ตรวจสอบสถานะ cache
 * - รองรับการเพิ่มและลดค่าของ cache (สำหรับตัวเลข)
 * 
 * ตัวอย่างการใช้งานโดยรวม:
 * ```php
 * // ตั้งค่า cache
 * Cache::set('user_1', ['name' => 'John', 'age' => 30], 600);
 * 
 * // ดึงค่า cache
 * $user = Cache::get('user_1', ['name' => 'Default', 'age' => 0]);
 * ```
 */

namespace App\Core;

class Cache
{
    /**
     * โฟลเดอร์เก็บ cache สำหรับเก็บไฟล์ cache
     */
    private static string $cacheDir = 'storage/cache';
    /**
     * บอกว่า $cacheDir ถูกแปลงเป็น absolute แล้วหรือยัง
     */
    private static bool $cacheDirNormalized = false;

    /**
     * นามสกุลไฟล์ cache สำหรับระบุไฟล์ cache
     */
    private const CACHE_EXTENSION = '.cache';

    /**
     * TTL เริ่มต้น (1 ชั่วโมง) สำหรับ cache ที่ไม่มีการระบุเวลา
     */
    private const DEFAULT_TTL = 3600;

    /**
     * ตั้งค่าโฟลเดอร์ cache สำหรับเก็บไฟล์ cache
     * จุดประสงค์: ให้ผู้ใช้กำหนดโฟลเดอร์เก็บ cache เองได้
     * setCacheDirectory() ควรใช้กับอะไร: การตั้งค่าโฟลเดอร์เก็บ cache ที่ต้องการใช้ในแอปพลิเคชัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * Cache::setCacheDirectory('path/to/custom/cache');
     * ```
     * 
     * @param string $dir กำหนดโฟลเดอร์เก็บ cache
     * @return void ไม่คืนค่าอะไร
     */
    public static function setCacheDirectory(string $dir): void
    {
        // Accept both forward and back slashes when trimming (Windows/Unix)
        $trimmed = rtrim($dir, '/\\');
        self::$cacheDir = self::resolvePath($trimmed);
        self::$cacheDirNormalized = true;
    }

    /**
     * เลือกโฟลเดอร์เก็บ cache แบบรวดเร็ว
     * จุดประสงค์: ใช้โฟลเดอร์ public/storage/cache สำหรับการเก็บ cache เพื่อให้เข้าถึงได้ง่ายจากเว็บเซิร์ฟเวอร์
     * usePublicStorage() ควรใช้กับอะไร: เมื่อคุณต้องการให้ cache ถูกเก็บในโฟลเดอร์ที่สามารถเข้าถึงได้จากเว็บเซิร์ฟเวอร์ เช่น public/storage/cache
     * ตัวอย่างการใช้งาน:
     * ```php
     * Cache::usePublicStorage(true);
     * ```
     *
     * @param bool $usePublic กำหนดว่าจะใช้โฟลเดอร์ public/storage/cache หรือไม่
     * @return void ไม่คืนค่าอะไร
     */
    public static function usePublicStorage(bool $usePublic = true): void
    {
        $path = $usePublic ? 'public/storage/cache' : 'storage/cache';
        self::$cacheDir = self::resolvePath(rtrim($path, '/\\'));
        self::$cacheDirNormalized = true;
    }

    /**
     * บันทึกข้อมูลใน cache
     * จุดประสงค์: เก็บข้อมูลใน cache เพื่อเพิ่มความเร็วในการเข้าถึงข้อมูล
     * set() ควรใช้กับอะไร: เมื่อคุณต้องการเก็บข้อมูลใน cache เพื่อเพิ่มประสิทธิภาพการเข้าถึงข้อมูลที่ใช้บ่อย
     * วิธีกำหนด: 1 นาที = 60 วินาที
     * ตัวอย่างการใช้งาน:
     * ```php
     * Cache::set('user_1', ['name' => 'John', 'age' => 30], 600);
     * ```
     * 
     * @param string $key กำหนดคีย์สำหรับเก็บข้อมูลใน cache
     * @param mixed $value กำหนดค่าที่จะเก็บใน cache
     * @param int $ttl กำหนดเวลาในการหมดอายุของ cache (วินาที), 0 หมายถึงไม่หมดอายุ
     * @return bool คืนค่า true หากบันทึกสำเร็จ, false หากไม่สำเร็จ
     */
    public static function set(string $key, $value, int $ttl = self::DEFAULT_TTL): bool
    {
        $filePath = self::getCacheFilePath($key);
        
        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!self::ensureCacheDirectory()) {
            return false;
        }

        // สร้างข้อมูล cache
        $cacheData = [
            'key' => $key,
            'value' => $value,
            'expires_at' => $ttl > 0 ? time() + $ttl : 0,
            'created_at' => time(),
        ];

        // บันทึกลงไฟล์
        $serialized = serialize($cacheData);
        $result = file_put_contents($filePath, $serialized, LOCK_EX);

        return $result !== false;
    }

    /**
     * ดึงข้อมูลจาก cache
     * จุดประสงค์: เข้าถึงข้อมูลที่ถูกเก็บไว้ใน cache
     * get() ควรใช้กับอะไร: เมื่อคุณต้องการดึงข้อมูลที่ถูกเก็บไว้ใน cache เพื่อลดเวลาการประมวลผลซ้ำ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $user = Cache::get('user_1', ['name' => 'Default', 'age' => 0]);
     * ```
     * 
     * @param string $key กำหนดคีย์สำหรับดึงข้อมูลจาก cache
     * @param mixed $default ค่าเริ่มต้นถ้าไม่มีใน cache
     * @return mixed คืนค่าข้อมูลจาก cache หรือค่าเริ่มต้นถ้าไม่มี
     */
    public static function get(string $key, $default = null)
    {
        // รับ path ของไฟล์ cache
        $filePath = self::getCacheFilePath($key);

        // ตรวจสอบว่าไฟล์มีอยู่หรือไม่
        if (!file_exists($filePath)) {
            return $default;
        }

        // อ่านไฟล์
        $content = file_get_contents($filePath);
        if ($content === false) {
            return $default;
        }

        // วิเคราะห์ข้อมูล cache
        $cacheData = @unserialize($content);

        // ตรวจสอบการวิเคราะห์ข้อมูล
        if ($cacheData === false || !is_array($cacheData)) {
            $json = @json_decode($content, true);
            if (is_array($json) && array_key_exists('value', $json)) {
                $cacheData = $json;
            } else {
                return $default;
            }
        }

        // ตรวจสอบว่าหมดอายุหรือยัง
        if (isset($cacheData['expires_at']) && $cacheData['expires_at'] > 0) {
            if (time() > $cacheData['expires_at']) {
                self::forget($key);
                return $default;
            }
        }

        return $cacheData['value'] ?? $default;
    }

    /**
     * ตรวจสอบว่ามี cache หรือไม่
     * จุดประสงค์: ตรวจสอบว่ามีข้อมูลใน cache หรือไม่
     * has() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่ามีข้อมูลใน cache หรือไม่ ก่อนที่จะพยายามดึงข้อมูลนั้น
     * ตัวอย่างการใช้งาน:
     * ```php
     * if (Cache::has('user_1')) {
     *     // มี cache
     * }
     * ```
     * 
     * @param string $key กำหนดคีย์สำหรับตรวจสอบใน cache
     * @return bool คืนค่า true ถ้ามีข้อมูลใน cache และยังไม่หมดอายุ, คืนค่า false ถ้าไม่มีหรือหมดอายุ
     */
    public static function has(string $key): bool
    {
        $filePath = self::getCacheFilePath($key);

        if (!file_exists($filePath)) {
            return false;
        }

        // อ่านและตรวจสอบว่าหมดอายุหรือยัง
        $content = file_get_contents($filePath);
        $cacheData = @unserialize($content);

        // JSON fallback similar to get(). Don't delete file here on unserialize
        // failure; just consider the key missing.
        if ($cacheData === false || !is_array($cacheData)) {
            $json = @json_decode($content, true);
            if (is_array($json) && array_key_exists('value', $json)) {
                $cacheData = $json;
            } else {
                return false;
            }
        }

        // ตรวจสอบว่าหมดอายุหรือยัง
        if (isset($cacheData['expires_at']) && $cacheData['expires_at'] > 0) {
            if (time() > $cacheData['expires_at']) {
                self::forget($key);
                return false;
            }
        }

        return true;
    }

    /**
     * ลบ cache
     * จุดประสงค์: ลบข้อมูลใน cache ที่ไม่ต้องการใช้งานแล้ว
     * forget() ควรใช้กับอะไร: เมื่อคุณต้องการลบข้อมูล cache ที่ไม่จำเป็นต้องใช้งานอีกต่อไป
     * ตัวอย่างการใช้งาน:
     * ```php
     * Cache::forget('user_1');
     * ```
     * 
     * @param string $key กำหนดคีย์สำหรับลบข้อมูลใน cache
     * @return bool คืนค่า true หากลบสำเร็จ, false หากไม่สำเร็จ
     */
    public static function forget(string $key): bool
    {
        $filePath = self::getCacheFilePath($key);

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    /**
     * ดึงข้อมูล ถ้าไม่มีให้สร้างใหม่
     * จุดประสงค์: ดึงข้อมูลจาก cache ถ้าไม่มีข้อมูลจะใช้ callback สร้างข้อมูลใหม่และเก็บใน cache
     * remember() ควรใช้กับอะไร: เมื่อคุณต้องการดึงข้อมูลจาก cache หรือสร้างข้อมูลใหม่ถ้าไม่มีอยู่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $user = Cache::remember('user_1', 3600, function() {
     *     return ['name' => 'John', 'age' => 30];
     * });
     * ```
     * 
     * @param string $key กำหนดคีย์สำหรับดึงข้อมูลจาก cache
     * @param int $ttl กำหนดเวลาในการหมดอายุของ cache (วินาที), 0 หมายถึงไม่หมดอายุ
     * @param callable $callback ฟังก์ชัน callback สำหรับสร้างข้อมูลใหม่เมื่อไม่มีข้อมูลใน cache
     * @return mixed คืนค่าข้อมูลจาก cache หรือค่าที่สร้างใหม่
     */
    public static function remember(string $key, int $ttl, callable $callback)
    {
        // ตรวจสอบว่ามี cache หรือไม่
        if (self::has($key)) {
            return self::get($key);
        }

        // สร้างข้อมูลใหม่
        $value = $callback();

        // บันทึกใน cache
        self::set($key, $value, $ttl);

        return $value;
    }

    /**
     * ดึงข้อมูล ถ้าไม่มีให้สร้างใหม่แบบไม่หมดอายุ
     * จุดประสงค์: ดึงข้อมูลจาก cache ถ้าไม่มีข้อมูลจะใช้ callback สร้างข้อมูลใหม่และเก็บใน cache แบบไม่หมดอายุ
     * rememberForever() ควรใช้กับอะไร: เมื่อคุณต้องการดึงข้อมูลจาก cache หรือสร้างข้อมูลใหม่ถ้าไม่มีอยู่ และต้องการเก็บข้อมูลนั้นแบบไม่หมดอายุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $settings = Cache::rememberForever('app_settings', function() {
     *    return ['theme' => 'dark', 'language' => 'en'];
     * });
     * ```
     * 
     * @param string $key กำหนดคีย์สำหรับดึงข้อมูลจาก cache
     * @param callable $callback ฟังก์ชัน callback สำหรับสร้างข้อมูลใหม่เมื่อไม่มีข้อมูลใน cache
     * @return mixed คืนค่าข้อมูลจาก cache หรือค่าที่สร้างใหม่
     */
    public static function rememberForever(string $key, callable $callback)
    {
        return self::remember($key, 0, $callback);
    }

    /**
     * รับค่าและลบ
     * จุดประสงค์: ดึงข้อมูลจาก cache และลบข้อมูลนั้นออกจาก cache
     * pull() ควรใช้กับอะไร: เมื่อคุณต้องการดึงข้อมูลจาก cache และลบข้อมูลนั้นออกจาก cache ทันทีหลังจากดึง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $value = Cache::pull('session_token', 'default_token');
     * ```
     * 
     * @param string $key กำหนดคีย์สำหรับดึงข้อมูลจาก cache
     * @param mixed $default ค่าที่จะคืนถ้าไม่มีข้อมูลใน cache
     * @return mixed คืนค่าข้อมูลจาก cache หรือค่าเริ่มต้นถ้าไม่มีข้อมูล
     */
    public static function pull(string $key, $default = null)
    {
        $value = self::get($key, $default);
        self::forget($key);
        return $value;
    }

    /**
     * บันทึกแบบไม่หมดอายุ
     * จุดประสงค์: บันทึกข้อมูลใน cache โดยไม่ตั้งเวลาหมดอายุ
     * forever() ควรใช้กับอะไร: เมื่อคุณต้องการเก็บข้อมูลใน cache โดยไม่ต้องการให้ข้อมูลนั้นหมดอายุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * Cache::forever('user_1', ['name' => 'John', 'age' => 30]);
     * ```
     * 
     * @param string $key กำหนดคีย์สำหรับบันทึกข้อมูลใน cache
     * @param mixed $value ข้อมูลที่ต้องการบันทึกใน cache
     * @return bool คืนค่า true หากบันทึกสำเร็จ, false หากไม่สำเร็จ
     */
    public static function forever(string $key, $value): bool
    {
        return self::set($key, $value, 0);
    }

    /**
     * เพิ่มค่า (สำหรับตัวเลข)
     * จุดประสงค์: เพิ่มค่าตัวเลขใน cache
     * increment() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มค่าตัวเลขที่เก็บใน cache
     * ตัวอย่างการใช้งาน:
     * ```php
     * Cache::increment('counter');
     * ```
     * 
     * @param string $key กำหนดคีย์สำหรับเพิ่มค่าใน cache
     * @param int $value จำนวนที่ต้องการเพิ่ม
     * @return int|bool คืนค่าค่าที่เพิ่มแล้ว หรือ false หากไม่สำเร็จ
     */
    public static function increment(string $key, int $value = 1): int|bool
    {
        $current = self::get($key, 0);
        if (!is_numeric($current)) {
            return false;
        }

        $newValue = (int)$current + $value;
        self::forever($key, $newValue);

        return $newValue;
    }

    /**
     * ลดค่า (สำหรับตัวเลข)
     * จุดประสงค์: ลดค่าตัวเลขใน cache
     * decrement() ควรใช้กับอะไร: เมื่อคุณต้องการลดค่าตัวเลขที่เก็บใน cache
     * ตัวอย่างการใช้งาน:
     * ```php
     * Cache::decrement('counter');
     * ```
     * 
     * @param string $key กำหนดคีย์สำหรับลดค่าใน cache
     * @param int $value จำนวนที่ต้องการลด
     * @return int|bool คืนค่าค่าที่ลดแล้ว หรือ false หากไม่สำเร็จ
     */
    public static function decrement(string $key, int $value = 1): int|bool
    {
        return self::increment($key, -$value);
    }

    /**
     * ลบ cache ทั้งหมด
     * จุดประสงค์: ลบข้อมูลทั้งหมดใน cache
     * flush() ควรใช้กับอะไร: เมื่อคุณต้องการลบข้อมูล cache ทั้งหมดออกจากระบบ
     * ตัวอย่างการใช้งาน:
     * ```php
     * Cache::flush();
     * ```
     * 
     * @return bool คืนค่า true หากลบสำเร็จทั้งหมด, false หากมีข้อผิดพลาดเกิดขึ้น
     */
    public static function flush(): bool
    {
        if (!is_dir(self::$cacheDir)) {
            return true;
        }

        $files = glob(self::$cacheDir . '/*' . self::CACHE_EXTENSION);
        if ($files === false) {
            return false;
        }

        $success = true;
        foreach ($files as $file) {
            if (!unlink($file)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * ลบ cache ที่หมดอายุ
     * จุดประสงค์: ลบข้อมูล cache ที่หมดอายุออกจากระบบ
     * clearExpired() ควรใช้กับอะไร: เมื่อคุณต้องการลบข้อมูล cache ที่หมดอายุเพื่อประหยัดพื้นที่จัดเก็บ
     * ตัวอย่างการใช้งาน:
     * ```php
     * Cache::clearExpired();
     * ```
     * 
     * @return int จำนวนไฟล์ที่ลบ
     */
    public static function clearExpired(): int
    {
        if (!is_dir(self::$cacheDir)) {
            return 0;
        }

        $files = glob(self::$cacheDir . '/*' . self::CACHE_EXTENSION);
        if ($files === false) {
            return 0;
        }

        $count = 0;
        foreach ($files as $file) {
            $content = @file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $cacheData = @unserialize($content);
            if ($cacheData === false || !is_array($cacheData)) {
                unlink($file);
                $count++;
                continue;
            }

            // ตรวจสอบว่าหมดอายุหรือยัง
            if (isset($cacheData['expires_at']) && $cacheData['expires_at'] > 0) {
                if (time() > $cacheData['expires_at']) {
                    unlink($file);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * ลบ cache ตาม pattern
     * จุดประสงค์: ลบข้อมูล cache ที่ตรงกับรูปแบบที่กำหนด
     * forgetPattern() ควรใช้กับอะไร: เมื่อคุณต้องการลบข้อมูล cache ที่ตรงกับรูปแบบเฉพาะ เช่น ลบ cache ของผู้ใช้ทั้งหมดที่มีคีย์ขึ้นต้นด้วย "user_"
     * ตัวอย่างการใช้งาน:
     * ```php
     * Cache::forgetPattern('user_*');
     * ```
     * 
     * @param string $pattern กำหนดรูปแบบของคีย์ที่ต้องการลบ
     * @return int จำนวนไฟล์ที่ลบ
     */
    public static function forgetPattern(string $pattern): int
    {
        if (!is_dir(self::$cacheDir)) {
            return 0;
        }

        $files = glob(self::$cacheDir . '/*' . self::CACHE_EXTENSION);
        if ($files === false) {
            return 0;
        }

        $count = 0;
        foreach ($files as $file) {
            $content = @file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $cacheData = @unserialize($content);
            if ($cacheData === false || !is_array($cacheData)) {
                continue;
            }

            $key = $cacheData['key'] ?? '';
            if (fnmatch($pattern, $key)) {
                unlink($file);
                $count++;
            }
        }

        return $count;
    }

    /**
     * รับสถิติของ cache
     * จุดประสงค์: ให้ข้อมูลสถิติเกี่ยวกับ cache ที่เก็บอยู่
     * stats() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบสถานะและขนาดของ cache ที่เก็บอยู่ในระบบ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $stats = Cache::stats();
     * ```
     * 
     * @return array คืนค่าสถิติของ cache ประกอบด้วย:
     * - 'total_files': จำนวนไฟล์ cache ทั้งหมด
     * - 'total_size': ขนาดรวมของไฟล์ cache (ไบต์)
     * - 'total_size_formatted': ขนาดรวมของไฟล์ cache ในรูปแบบที่อ่านง่าย
     * - 'expired_files': จำนวนไฟล์ cache ที่หมดอายุ
     * 
     */
    public static function stats(): array
    {
        if (!is_dir(self::$cacheDir)) {
            return [
                'total_files' => 0,
                'total_size' => 0,
                'expired_files' => 0,
            ];
        }

        $files = glob(self::$cacheDir . '/*' . self::CACHE_EXTENSION);
        if ($files === false) {
            return [
                'total_files' => 0,
                'total_size' => 0,
                'expired_files' => 0,
            ];
        }

        $totalSize = 0;
        $expiredCount = 0;

        foreach ($files as $file) {
            $totalSize += filesize($file);

            $content = @file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $cacheData = @unserialize($content);
            if ($cacheData === false || !is_array($cacheData)) {
                $expiredCount++;
                continue;
            }

            // ตรวจสอบว่าหมดอายุหรือยัง
            if (isset($cacheData['expires_at']) && $cacheData['expires_at'] > 0) {
                if (time() > $cacheData['expires_at']) {
                    $expiredCount++;
                }
            }
        }

        return [
            'total_files' => count($files),
            'total_size' => $totalSize,
            'total_size_formatted' => self::formatBytes($totalSize),
            'expired_files' => $expiredCount,
        ];
    }

    // ========== Helper Methods ==========

    /**
     * รับ path ของไฟล์ cache
     * จุดประสงค์: สร้าง path ของไฟล์ cache จากคีย์ที่ระบุ
     * getCacheFilePath() ควรใช้กับอะไร: เมื่อคุณต้องการทราบตำแหน่งไฟล์ cache ที่สอดคล้องกับคีย์เฉพาะ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $path = Cache::getCacheFilePath('user_1');
     * ```
     * 
     * @param string $key กำหนดคีย์สำหรับสร้าง path ของไฟล์ cache
     * @return string คืนค่า path ของไฟล์ cache ที่สอดคล้องกับคีย์
     */
    private static function getCacheFilePath(string $key): string
    {
        if (!self::$cacheDirNormalized) {
            self::$cacheDir = self::resolvePath(self::$cacheDir);
            self::$cacheDirNormalized = true;
        }

        $hashedKey = md5($key);
        return self::$cacheDir . DIRECTORY_SEPARATOR . $hashedKey . self::CACHE_EXTENSION;
    }

    /**
     * สร้างโฟลเดอร์ cache
     * จุดประสงค์: ตรวจสอบและสร้างโฟลเดอร์เก็บ cache ถ้ายังไม่มี
     * ensureCacheDirectory() ควรใช้กับอะไร: เมื่อคุณต้องการให้แน่ใจว่าโฟลเดอร์เก็บ cache มีอยู่และเขียนได้ก่อนที่จะบันทึกข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * Cache::ensureCacheDirectory();
     * ```
     * 
     * @return bool คืนค่า true หากโฟลเดอร์มีอยู่และเขียนได้, false หากสร้างไม่สำเร็จ
     */
    private static function ensureCacheDirectory(): bool
    {
        if (!self::$cacheDirNormalized) {
            self::$cacheDir = self::resolvePath(self::$cacheDir);
            self::$cacheDirNormalized = true;
        }

        if (!is_dir(self::$cacheDir)) {
            if (!mkdir(self::$cacheDir, 0755, true)) {
                return false;
            }
        }

        return is_writable(self::$cacheDir);
    }

    /**
     * ตรวจสอบว่า path ที่ให้มาเป็น absolute หรือไม่
     * จุดประสงค์: ใช้ตรวจสอบว่า path ที่ระบุเป็น absolute path หรือ relative path
     * isAbsolutePath() ควรใช้กับอะไร: เมื่อต้องการแยกแยะระหว่าง absolute path และ relative path
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isAbsolute = Cache::isAbsolutePath('/var/www/html');
     * ```
     * 
     * @param string $path กำหนด path ที่ต้องการตรวจสอบ
     * @return bool คืนค่า true หากเป็น absolute path, false หากเป็น relative path
     */
    private static function isAbsolutePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        // Unix absolute (/...) or Windows drive (C:\...) or UNC \\\server\share
        return (bool) preg_match('#^([a-zA-Z]:\\\\|[a-zA-Z]:/|/|\\\\\\\\)#', $path);
    }

    /**
     * แปลง path ที่เป็น relative ให้เป็น absolute โดยอิงจาก project root
     * Project root ถูกตั้งเป็นสองระดับขึ้นจากโฟลเดอร์ปัจจุบันของไฟล์นี้ (app/Core)
     * จุดประสงค์: ใช้แปลง path ที่ระบุเป็น relative path ให้เป็น absolute path
     * resolvePath() ควรใช้กับอะไร: เมื่อต้องการแปลง relative path เป็น absolute path โดยอิงจาก root ของโปรเจกต์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $absolutePath = Cache::resolvePath('storage/cache');
     * ```
     * 
     * @param string $path กำหนด path ที่ต้องการแปลง
     * @return string คืนค่า path ที่เป็น absolute
     */
    private static function resolvePath(string $path): string
    {
        if (self::isAbsolutePath($path)) {
            return rtrim($path, '/\\');
        }

        $projectRoot = dirname(__DIR__, 2);
        $combined = $projectRoot . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
        return rtrim($combined, '/\\');
    }

    /**
     * แปลงขนาดไฟล์เป็นรูปแบบที่อ่านง่าย
     * จุดประสงค์: แปลงขนาดไฟล์จากไบต์เป็นหน่วยที่อ่านง่าย เช่น KB, MB
     * formatBytes() ควรใช้กับอะไร: เมื่อต้องการแสดงขนาดไฟล์ในรูปแบบที่เข้าใจง่ายสำหรับผู้ใช้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $readableSize = Cache::formatBytes(2048);
     * ```
     * 
     * @param int $bytes กำหนดขนาดไฟล์เป็นไบต์
     * @return string คืนค่าขนาดไฟล์ในรูปแบบที่อ่านง่าย
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * รับข้อมูลทั้งหมดใน cache (สำหรับ debugging)
     * จุดประสงค์: ดึงข้อมูลทั้งหมดที่เก็บอยู่ใน cache เพื่อการตรวจสอบหรือ debugging
     * all() ควรใช้กับอะไร: เมื่อต้องการดูข้อมูลทั้งหมดที่เก็บอยู่ใน cache เพื่อวิเคราะห์หรือแก้ไขปัญหา
     * ตัวอย่างการใช้งาน:
     * ```php
     * $allCache = Cache::all();
     * ```
     * 
     * @return array คืนค่าข้อมูลทั้งหมดใน cache ในรูปแบบอาร์เรย์
     */
    public static function all(): array
    {
        // ตรวจสอบว่าโฟลเดอร์ cache มีอยู่หรือไม่
        if (!is_dir(self::$cacheDir)) {
            return [];
        }

        // ดึงไฟล์ทั้งหมดในโฟลเดอร์ cache
        $files = glob(self::$cacheDir . '/*' . self::CACHE_EXTENSION);
        if ($files === false) {
            return [];
        }

        $allCache = [];

        // วนลูปผ่านไฟล์ทั้งหมดและวิเคราะห์ข้อมูล
        foreach ($files as $file) {
            $content = @file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $cacheData = @unserialize($content);
            if ($cacheData === false || !is_array($cacheData)) {
                continue;
            }

            $key = $cacheData['key'] ?? '';
            $isExpired = false;

            if (isset($cacheData['expires_at']) && $cacheData['expires_at'] > 0) {
                if (time() > $cacheData['expires_at']) {
                    $isExpired = true;
                }
            }

            $allCache[$key] = [
                'key' => $key,
                'expires_at' => $cacheData['expires_at'] ?? 0,
                'created_at' => $cacheData['created_at'] ?? 0,
                'is_expired' => $isExpired,
                'file' => basename($file),
            ];
        }

        return $allCache;
    }

    /**
     * ลบ cache ที่เก่ากว่า X วินาที
     * จุดประสงค์: ลบข้อมูล cache ที่ถูกสร้างมานานกว่าระยะเวลาที่กำหนด ออกจากระบบ
     * clearOlderThan() ควรใช้กับอะไร: เมื่อคุณต้องการลบข้อมูล cache ที่เก่ากว่าเวลาที่กำหนด เช่น ลบ cache ที่เก่ากว่า 1 ชั่วโมง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $deletedCount = Cache::clearOlderThan(3600); // ลบ cache ที่เก่ากว่า 1 ชั่วโมง
     * ```
     * 
     * @param int $seconds กำหนดระยะเวลาเป็นวินาที
     * @return int คืนค่าจำนวนไฟล์ที่ถูกลบ
     */
    public static function clearOlderThan(int $seconds): int
    {
        if (!is_dir(self::$cacheDir)) {
            return 0;
        }

        $files = glob(self::$cacheDir . '/*' . self::CACHE_EXTENSION);
        if ($files === false) {
            return 0;
        }

        $cutoffTime = time() - $seconds;
        $count = 0;

        foreach ($files as $file) {
            $content = @file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $cacheData = @unserialize($content);
            if ($cacheData === false || !is_array($cacheData)) {
                continue;
            }

            $createdAt = $cacheData['created_at'] ?? 0;
            if ($createdAt > 0 && $createdAt < $cutoffTime) {
                unlink($file);
                $count++;
            }
        }

        return $count;
    }
}
