<?php
/**
 * คลาส Cache
 * 
 * จุดประสงค์: จัดการ caching เพื่อเพิ่มความเร็วแอปพลิเคชัน
 * ฟีเจอร์: File-based cache, TTL (Time To Live), Cache invalidation
 * 
 * ฟีเจอร์หลัก:
 * - บันทึกและดึงข้อมูลจาก cache
 * - ตั้งเวลาหมดอายุของ cache (TTL)
 * - ลบ cache ทีละรายการหรือทั้งหมด
 * - Cache tags สำหรับการจัดกลุ่ม
 * 
 * ตัวอย่างการใช้งาน:
 * ```php
 * // บันทึก
 * Cache::set('products', $products, 3600); // cache 1 ชั่วโมง
 * 
 * // ดึงข้อมูล
 * $products = Cache::get('products');
 * 
 * // ดึงหรือสร้างใหม่
 * $products = Cache::remember('products', 3600, function() {
 *     return Product::all();
 * });
 * 
 * // ลบ
 * Cache::forget('products');
 * 
 * // ลบทั้งหมด
 * Cache::flush();
 * ```
 */

namespace App\Core;

class Cache
{
    /**
     * โฟลเดอร์เก็บ cache
     */
    private static string $cacheDir = 'storage/cache';

    /**
     * นามสกุลไฟล์ cache
     */
    private const CACHE_EXTENSION = '.cache';

    /**
     * TTL เริ่มต้น (1 ชั่วโมง)
     */
    private const DEFAULT_TTL = 3600;

    /**
     * ตั้งค่าโฟลเดอร์ cache
     * 
     * @param string $dir
     */
    public static function setCacheDirectory(string $dir): void
    {
        self::$cacheDir = rtrim($dir, '/');
    }

    /**
     * บันทึกข้อมูลใน cache
     * 
     * @param string $key
     * @param mixed $value
     * @param int $ttl เวลาหมดอายุ (วินาที), 0 = ไม่หมดอายุ
     * @return bool
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
     * 
     * @param string $key
     * @param mixed $default ค่าเริ่มต้นถ้าไม่มีใน cache
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $filePath = self::getCacheFilePath($key);

        if (!file_exists($filePath)) {
            return $default;
        }

        // อ่านไฟล์
        $content = file_get_contents($filePath);
        if ($content === false) {
            return $default;
        }

        // Unserialize
        $cacheData = @unserialize($content);
        if ($cacheData === false || !is_array($cacheData)) {
            self::forget($key);
            return $default;
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
     * 
     * @param string $key
     * @return bool
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

        if ($cacheData === false || !is_array($cacheData)) {
            self::forget($key);
            return false;
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
     * 
     * @param string $key
     * @return bool
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
     * 
     * @param string $key
     * @param int $ttl
     * @param callable $callback
     * @return mixed
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
     * 
     * @param string $key
     * @param callable $callback
     * @return mixed
     */
    public static function rememberForever(string $key, callable $callback)
    {
        return self::remember($key, 0, $callback);
    }

    /**
     * รับค่าและลบ
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function pull(string $key, $default = null)
    {
        $value = self::get($key, $default);
        self::forget($key);
        return $value;
    }

    /**
     * บันทึกแบบไม่หมดอายุ
     * 
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function forever(string $key, $value): bool
    {
        return self::set($key, $value, 0);
    }

    /**
     * เพิ่มค่า (สำหรับตัวเลข)
     * 
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public static function increment(string $key, int $value = 1)
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
     * 
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public static function decrement(string $key, int $value = 1)
    {
        return self::increment($key, -$value);
    }

    /**
     * ลบ cache ทั้งหมด
     * 
     * @return bool
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
     * 
     * @param string $pattern
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
     * 
     * @return array
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
     * 
     * @param string $key
     * @return string
     */
    private static function getCacheFilePath(string $key): string
    {
        $hashedKey = md5($key);
        return self::$cacheDir . '/' . $hashedKey . self::CACHE_EXTENSION;
    }

    /**
     * สร้างโฟลเดอร์ cache
     * 
     * @return bool
     */
    private static function ensureCacheDirectory(): bool
    {
        if (!is_dir(self::$cacheDir)) {
            if (!mkdir(self::$cacheDir, 0755, true)) {
                return false;
            }
        }

        return is_writable(self::$cacheDir);
    }

    /**
     * แปลงขนาดไฟล์เป็นรูปแบบที่อ่านง่าย
     * 
     * @param int $bytes
     * @return string
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
     * 
     * @return array
     */
    public static function all(): array
    {
        if (!is_dir(self::$cacheDir)) {
            return [];
        }

        $files = glob(self::$cacheDir . '/*' . self::CACHE_EXTENSION);
        if ($files === false) {
            return [];
        }

        $allCache = [];

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
     * 
     * @param int $seconds
     * @return int จำนวนไฟล์ที่ลบ
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
