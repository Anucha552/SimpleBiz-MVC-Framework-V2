<?php
/**
 * URL Helper
 * 
 * จุดประสงค์: ฟังก์ชันช่วยเหลือสำหรับจัดการ URL
 * 
 * ฟีเจอร์:
 * - สร้าง URL
 * - Redirect
 * - Query string manipulation
 * - รับข้อมูล URL ปัจจุบัน
 * - ตรวจสอบความปลอดภัยของ URL
 * - ฟังก์ชันเสริมอื่นๆ ที่เกี่ยวข้องกับ URL
 * 
 */

namespace App\Helpers;

use App\Core\Response;

class UrlHelper
{
    /**
     * รับ base URL ของแอปพลิเคชัน
     * จุดประสงค์: ใช้เพื่อรับ base URL ของแอปพลิเคชัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $baseUrl = UrlHelper::base();
     * ```
     * 
     * ผลลัพธ์: คืนค่า base URL เช่น http://example.com
     * 
     * returns string base URL
     * 
     * @return string
     */
    public static function base(): string
    {
        $protocol = self::isSecure() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost'; // ใช้ localhost เป็นค่าเริ่มต้นถ้าไม่มี HTTP_HOST
        
        return "{$protocol}://{$host}";
    }

    /**
     * สร้าง URL เต็ม
     * จุดประสงค์: ใช้เพื่อสร้าง URL เต็มจาก path และ query parameters
     * ตัวอย่างการใช้งาน:
     * ```php
     * $url = UrlHelper::to('posts/view', ['id' => 123]);
     * ```
     * 
     * ผลลัพธ์: คืนค่า URL เช่น http://example.com/posts/view?id=123
     * 
     * returns string URL เต็ม
     * 
     * @param string $path
     * @param array $params
     * @return string
     */
    public static function to(string $path = '', array $params = []): string
    {
        $base = self::base();
        $path = ltrim($path, '/');
        $url = $base . '/' . $path;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }

    /**
     * รับ URL ปัจจุบัน
     * จุดประสงค์: ใช้เพื่อรับ URL ปัจจุบันของคำขอ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $currentUrl = UrlHelper::current();
     * ```
     * 
     * ผลลัพธ์: คืนค่า URL ปัจจุบัน เช่น http://example.com/current/path?query=string
     * 
     * returns string URL ปัจจุบัน
     * 
     * @param bool $withQueryString
     * @return string
     */
    public static function current(bool $withQueryString = true): string
    {
        $protocol = self::isSecure() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        if (!$withQueryString) {
            $uri = strtok($uri, '?');
        }
        
        return "{$protocol}://{$host}{$uri}";
    }

    /**
     * รับ URL ก่อนหน้า (referrer)
     * จุดประสงค์: ใช้เพื่อรับ URL ก่อนหน้าของคำขอ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $previousUrl = UrlHelper::previous();
     * ```
     * 
     * ผลลัพธ์: คืนค่า URL ก่อนหน้า หรือ null ถ้าไม่มี
     * 
     * returns string|null URL ก่อนหน้า หรือ null ถ้าไม่มี
     * 
     * @param string|null $default
     * @return string|null
     */
    public static function previous(?string $default = null): ?string
    {
        return $_SERVER['HTTP_REFERER'] ?? $default;
    }

    /**
     * ตรวจสอบว่าเป็น HTTPS หรือไม่
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่า URL ปัจจุบันใช้โปรโตคอล HTTPS หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isSecure = UrlHelper::isSecure();
     * ```
     * 
     * ผลลัพธ์: คืนค่า true ถ้าเป็น HTTPS, false ถ้าไม่ใช่
     * 
     * returns bool true ถ้าเป็น HTTPS, false ถ้าไม่ใช่
     * 
     * @return bool
     */
    public static function isSecure(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        
        if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            return true;
        }
        
        return false;
    }

    /**
     * Redirect ไปยัง URL
     * จุดประสงค์: ใช้เพื่อส่งการตอบกลับ redirect ไปยัง URL ที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * UrlHelper::redirect('http://example.com');
     * ```
     * 
     * ผลลัพธ์: ส่งการตอบกลับ redirect ไปยัง URL ที่ระบุ
     * 
     * returns Response การตอบกลับ redirect
     * 
     * @param string $url
     * @param int $statusCode
     * @return Response
     */
    public static function redirect(string $url, int $statusCode = 302): Response
    {
        return Response::redirect($url, $statusCode);
    }

    /**
     * Redirect กลับไปหน้าก่อนหน้า
     * จุดประสงค์: ใช้เพื่อส่งการตอบกลับ redirect กลับไปยัง URL ก่อนหน้า
     * ตัวอย่างการใช้งาน:
     * ```php
     * UrlHelper::back();
     * ```
     * 
     * ผลลัพธ์: ส่งการตอบกลับ redirect กลับไปยัง URL ก่อนหน้า หรือไปยัง URL เริ่มต้นถ้าไม่มี URL ก่อนหน้า
     * 
     * returns Response การตอบกลับ redirect
     * 
     * @param string|null $default
     * @return Response
     */
    public static function back(?string $default = null): Response
    {
        $url = self::previous($default ?: '/');
        return self::redirect($url);
    }

    /**
     * เพิ่ม query parameters เข้า URL
     * จุดประสงค์: ใช้เพื่อเพิ่ม query parameters เข้าไปใน URL ที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $newUrl = UrlHelper::addQuery('http://example.com', ['key' => 'value']);
     * ```
     * 
     * ผลลัพธ์: คืนค่า URL ที่มี query parameters ที่เพิ่มเข้าไป
     * 
     * returns string URL ที่มี query parameters ที่เพิ่มเข้าไป
     * 
     * @param string $url
     * @param array $params
     * @return string
     */
    public static function addQuery(string $url, array $params): string
    {
        $parts = parse_url($url);
        $query = [];
        
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        
        $query = array_merge($query, $params);
        
        $result = $parts['scheme'] . '://' . $parts['host'];
        
        if (isset($parts['port'])) {
            $result .= ':' . $parts['port'];
        }
        
        if (isset($parts['path'])) {
            $result .= $parts['path'];
        }
        
        if (!empty($query)) {
            $result .= '?' . http_build_query($query);
        }
        
        if (isset($parts['fragment'])) {
            $result .= '#' . $parts['fragment'];
        }
        
        return $result;
    }

    /**
     * ลบ query parameters จาก URL
     * จุดประสงค์: ใช้เพื่อลบ query parameters ออกจาก URL ที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $newUrl = UrlHelper::removeQuery('http://example.com?key=value', ['key']);
     * ```
     * 
     * ผลลัพธ์: คืนค่า URL ที่ไม่มี query parameters ที่ถูกลบออก
     * 
     * returns string URL ที่ไม่มี query parameters ที่ถูกลบออก
     * 
     * @param string $url
     * @param array $keys
     * @return string
     */
    public static function removeQuery(string $url, array $keys): string
    {
        $parts = parse_url($url);
        $query = [];
        
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        
        foreach ($keys as $key) {
            unset($query[$key]);
        }
        
        $result = $parts['scheme'] . '://' . $parts['host'];
        
        if (isset($parts['port'])) {
            $result .= ':' . $parts['port'];
        }
        
        if (isset($parts['path'])) {
            $result .= $parts['path'];
        }
        
        if (!empty($query)) {
            $result .= '?' . http_build_query($query);
        }
        
        if (isset($parts['fragment'])) {
            $result .= '#' . $parts['fragment'];
        }
        
        return $result;
    }

    /**
     * รับ query parameter จาก URL ปัจจุบัน
     * จุดประสงค์: ใช้เพื่อรับค่า query parameter จาก URL ปัจจุบัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $value = UrlHelper::query('key', 'default');
     * ```
     * 
     * ผลลัพธ์: คืนค่าของ query parameter ที่ระบุ หรือค่าเริ่มต้นถ้าไม่มี
     * 
     * returns mixed ค่า query parameter หรือค่าเริ่มต้น
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public static function query(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        
        return $_GET[$key] ?? $default;
    }

    /**
     * ตรวจสอบว่า URL ตรงกับ pattern หรือไม่
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่า URL ตรงกับ pattern ที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isMatch = UrlHelper::is('/posts/*');
     * ```
     * ผลลัพธ์: คืนค่า true ถ้า URL ตรงกับ pattern, false ถ้าไม่ตรง
     * 
     * returns bool true ถ้า URL ตรงกับ pattern, false ถ้าไม่ตรง
     * 
     * @param string $pattern
     * @param string|null $url
     * @return bool
     */
    public static function is(string $pattern, ?string $url = null): bool
    {
        if ($url === null) {
            $url = $_SERVER['REQUEST_URI'] ?? '/';
            $url = strtok($url, '?');
        }
        
        $pattern = str_replace('*', '.*', $pattern);
        $pattern = '/^' . str_replace('/', '\/', $pattern) . '$/';
        
        return preg_match($pattern, $url) === 1;
    }

    /**
     * สร้าง URL สำหรับ asset (css, js, images)
     * จุดประสงค์: ใช้เพื่อสร้าง URL สำหรับไฟล์ asset เช่น CSS, JavaScript, หรือรูปภาพ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $assetUrl = UrlHelper::asset('css/style.css');
     * ```
     * 
     * ผลลัพธ์: คืนค่า URL ของ asset เช่น http://example.com/css/style.css
     * 
     * returns string URL ของ asset
     * 
     * @param string $path
     * @return string
     */
    public static function asset(string $path): string
    {
        $path = ltrim($path, '/'); // ลบ slash นำหน้าออกถ้ามี
        return self::base() . '/' . $path; // สร้าง URL เต็มสำหรับ asset 
    }

    /**
     * Encode URL
     * จุดประสงค์: ใช้เพื่อเข้ารหัส URL
     * ตัวอย่างการใช้งาน:
     * ```php
     * $encodedUrl = UrlHelper::encode('http://example.com?key=value');
     * ```
     * 
     * ผลลัพธ์: คืนค่า URL ที่ถูกเข้ารหัส ตัวอย่าง: http%3A%2F%2Fexample.com%3Fkey%3Dvalue
     * 
     * returns string URL ที่ถูกเข้ารหัส
     * 
     * @param string $url
     * @return string
     */
    public static function encode(string $url): string
    {
        return urlencode($url);
    }

    /**
     * Decode URL
     * จุดประสงค์: ใช้เพื่อถอดรหัส URL
     * ตัวอย่างการใช้งาน:
     * ```php
     * $decodedUrl = UrlHelper::decode('http%3A%2F%2Fexample.com%3Fkey%3Dvalue');
     * ```  
     * 
     * ผลลัพธ์: คืนค่า URL ที่ถูกถอดรหัส เช่น http://example.com?key=value
     * 
     * returns string URL ที่ถูกถอดรหัส
     * 
     * @param string $url
     * @return string
     */
    public static function decode(string $url): string
    {
        return urldecode($url);
    }

    /**
     * ตรวจสอบว่า URL ถูกต้องหรือไม่
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่า URL ที่ระบุเป็น URL ที่ถูกต้องตามรูปแบบหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isValid = UrlHelper::isValid('http://example.com');
     * ```
     * 
     * ผลลัพธ์: คืนค่า true ถ้า URL ถูกต้อง, false ถ้าไม่ถูกต้อง
     * 
     * returns bool true ถ้า URL ถูกต้อง, false ถ้าไม่ถูกต้อง
     * 
     * @param string $url
     * @return bool
     */
    public static function isValid(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Parse URL เป็น components
     * จุดประสงค์: ใช้เพื่อแยก URL เป็นส่วนประกอบต่างๆ เช่น scheme, host, path, query, fragment
     * ตัวอย่างการใช้งาน:
     * ```php
     * $components = UrlHelper::parse('http://example.com:8080/path?query=string#fragment');
     * ```
     * 
     * ผลลัพธ์: คืนค่าอาร์เรย์ที่มีส่วนประกอบของ URL
     * 
     * returns array ส่วนประกอบของ URL
     * 
     * @param string $url
     * @return array
     */
    public static function parse(string $url): array
    {
        $parts = parse_url($url);
        
        return [
            'scheme' => $parts['scheme'] ?? null,
            'host' => $parts['host'] ?? null,
            'port' => $parts['port'] ?? null,
            'user' => $parts['user'] ?? null,
            'pass' => $parts['pass'] ?? null,
            'path' => $parts['path'] ?? null,
            'query' => $parts['query'] ?? null,
            'fragment' => $parts['fragment'] ?? null,
        ];
    }

    /**
     * รับ domain จาก URL
     * จุดประสงค์: ใช้เพื่อรับ domain จาก URL ที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $domain = UrlHelper::domain('http://example.com/path');
     * ```
     * 
     * ผลลัพธ์: คืนค่า domain เช่น example.com
     * 
     * returns string|null domain หรือ null ถ้าไม่พบ
     * 
     * @param string|null $url
     * @return string|null
     */
    public static function domain(?string $url = null): ?string
    {
        if ($url === null) {
            return $_SERVER['HTTP_HOST'] ?? null;
        }
        
        $parts = parse_url($url);
        return $parts['host'] ?? null;
    }

    /**
     * รับ path จาก URL
     * จุดประสงค์: ใช้เพื่อรับ path จาก URL ที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $path = UrlHelper::path('http://example.com/path?query=string');
     * ```
     * 
     * ผลลัพธ์: คืนค่า path เช่น /path
     * 
     * returns string path
     * 
     * @param string|null $url
     * @return string
     */
    public static function path(?string $url = null): string
    {
        if ($url === null) {
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            return strtok($uri, '?');
        }
        
        $parts = parse_url($url);
        return $parts['path'] ?? '/';
    }

    /**
     * สร้าง URL พร้อม timestamp (สำหรับ cache busting)
     * จุดประสงค์: ใช้เพื่อสร้าง URL ที่มี timestamp ต่อท้ายเพื่อป้องกันการแคช
     * ตัวอย่างการใช้งาน:
     * ```php
     * $cacheBustedUrl = UrlHelper::cacheBust('http://example.com/asset.js');
     * ```
     * 
     * ผลลัพธ์: คืนค่า URL ที่มี timestamp ต่อท้าย เช่น http://example.com/asset.js?v=1627890123
     * 
     * returns string URL ที่มี timestamp ต่อท้าย
     * 
     * @param string $url
     * @return string
     */
    public static function cacheBust(string $url): string
    {
        $separator = strpos($url, '?') === false ? '?' : '&';
        return $url . $separator . 'v=' . time();
    }

    /**
     * สร้าง URL สำหรับ pagination
     * จุดประสงค์: ใช้เพื่อสร้าง URL สำหรับหน้าต่างๆ ในการแบ่งหน้า
     * ตัวอย่างการใช้งาน:
     * ```php
     * $pageUrl = UrlHelper::page(2);
     * ```
     * 
     * ผลลัพธ์: คืนค่า URL สำหรับหน้าที่ระบุ เช่น /current/path?page=2
     * 
     * returns string URL สำหรับ pagination
     * 
     * @param int $page
     * @param array $additionalParams
     * @return string
     */
    public static function page(int $page, array $additionalParams = []): string
    {
        $params = array_merge($_GET, $additionalParams, ['page' => $page]);
        $path = strtok($_SERVER['REQUEST_URI'], '?');
        
        return $path . '?' . http_build_query($params);
    }

    /**
     * ลบ slash ท้าย URL
     * จุดประสงค์: ใช้เพื่อลบ slash (/) ที่ท้าย URL
     * ตัวอย่างการใช้งาน:
     * ```php
     * $cleanUrl = UrlHelper::removeTrailingSlash('http://example.com/path/');
     * ```
     * 
     * ผลลัพธ์: คืนค่า URL ที่ไม่มี slash ท้าย เช่น http://example.com/path
     * 
     * returns string URL ที่ไม่มี slash ท้าย
     * 
     * @param string $url
     * @return string
     */
    public static function removeTrailingSlash(string $url): string
    {
        return rtrim($url, '/');
    }

    /**
     * เพิ่ม slash ท้าย URL
     * จุดประสงค์: ใช้เพื่อเพิ่ม slash (/) ที่ท้าย URL หากยังไม่มี
     * ตัวอย่างการใช้งาน:
     * ```php
     * $urlWithSlash = UrlHelper::addTrailingSlash('http://example.com/path');
     * ```  
     * 
     * ผลลัพธ์: คืนค่า URL ที่มี slash ท้าย เช่น http://example.com/path/
     * 
     * returns string URL ที่มี slash ท้าย
     * 
     * @param string $url
     * @return string
     */
    public static function addTrailingSlash(string $url): string
    {
        return rtrim($url, '/') . '/';
    }

    /**
     * สร้าง signed URL (URL ที่มี signature)
     * จุดประสงค์: ใช้เพื่อสร้าง signed URL ที่มี signature สำหรับความปลอดภัย   
     * ตัวอย่างการใช้งาน:
     * ```php
     * $signedUrl = UrlHelper::signed('http://example.com/resource', 'secret_key', time() + 3600);
     * ```
     * 
     * ผลลัพธ์: คืนค่า signed URL ที่มี signature และ expiration timestamp 
     * ต่อท้าย เช่น http://example.com/resource?expires=1627890123&signature=abcdef123456
     * 
     * returns string signed URL
     * 
     * @param string $url
     * @param string $secret
     * @param int|null $expiration เวลาหมดอายุ (timestamp)
     * @return string
     */
    public static function signed(string $url, string $secret, ?int $expiration = null): string
    {
        $params = ['expires' => $expiration];
        
        if ($expiration) {
            $url = self::addQuery($url, $params);
        }
        
        $signature = hash_hmac('sha256', $url, $secret);
        
        return self::addQuery($url, ['signature' => $signature]);
    }

    /**
     * ตรวจสอบ signed URL
     * จุดประสงค์: ใช้เพื่อตรวจสอบความถูกต้องของ signed URL
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isValid = UrlHelper::verifySignature($signedUrl, 'secret_key');
     * ```
     * 
     * ผลลัพธ์: คืนค่า true ถ้า signed URL ถูกต้องและไม่หมดอายุ, false ถ้าไม่ถูกต้องหรือหมดอายุ
     * 
     * returns bool true ถ้า signed URL ถูกต้องและไม่หมดอายุ, false ถ้าไม่ถูกต้องหรือหมดอายุ
     * 
     * @param string $url
     * @param string $secret
     * @return bool
     */
    public static function verifySignature(string $url, string $secret): bool
    {
        $parts = parse_url($url);
        $query = [];
        
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        
        if (!isset($query['signature'])) {
            return false;
        }
        
        $signature = $query['signature'];
        unset($query['signature']);
        
        // ตรวจสอบ expiration
        if (isset($query['expires']) && time() > $query['expires']) {
            return false;
        }
        
        // สร้าง URL ใหม่โดยไม่มี signature
        $urlWithoutSignature = self::removeQuery($url, ['signature']);
        
        $expectedSignature = hash_hmac('sha256', $urlWithoutSignature, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * สร้าง URL สำหรับ API
     * จุดประสงค์: ใช้เพื่อสร้าง URL สำหรับเรียกใช้งาน API
     * ตัวอย่างการใช้งาน:
     * ```php
     * $apiUrl = UrlHelper::api('users/list', ['page' => 2]);
     * ```
     * 
     * ผลลัพธ์: คืนค่า URL สำหรับ API เช่น http://example.com/api/users/list?page=2
     * 
     * returns string URL สำหรับ API
     * 
     * @param string $path
     * @param array $params
     * @return string
     */
    public static function api(string $path, array $params = []): string
    {
        $path = ltrim($path, '/');
        return self::to("api/{$path}", $params);
    }

    /**
     * Join URL segments
     * จุดประสงค์: ใช้เพื่อรวม segments ของ URL เข้าด้วยกันอย่างถูกต้อง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $fullUrl = UrlHelper::join('http://example.com/', '/path/', '/to/', 'resource');
     * ```
     * 
     * ผลลัพธ์: คืนค่า URL ที่รวม segments เข้าด้วยกัน เช่น http://example.com/path/to/resource
     * 
     * returns string URL ที่รวม segments เข้าด้วยกัน
     * 
     * @param string ...$segments
     * @return string
     */
    public static function join(...$segments): string
    {
        $segments = array_map(function($segment) {
            return trim($segment, '/');
        }, $segments);
        
        return implode('/', array_filter($segments));
    }

}
