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
 */

namespace App\Helpers;

use App\Core\Response;

class UrlHelper
{
    /**
     * รับ base URL ของแอปพลิเคชัน
     * 
     * @return string
     */
    public static function base(): string
    {
        $protocol = self::isSecure() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        return "{$protocol}://{$host}";
    }

    /**
     * สร้าง URL เต็ม
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
     * 
     * @param string $path
     * @return string
     */
    public static function asset(string $path): string
    {
        $path = ltrim($path, '/');
        return self::base() . '/' . $path;
    }

    /**
     * Encode URL
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
     * 
     * @param string $path
     * @param array $params
     * @param string $version
     * @return string
     */
    public static function api(string $path, array $params = [], string $version = 'v1'): string
    {
        $path = ltrim($path, '/');
        return self::to("api/{$version}/{$path}", $params);
    }

    /**
     * Join URL segments
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
