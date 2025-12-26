<?php
/**
 * คลาส Request
 * 
 * จุดประสงค์: จัดการคำขอ HTTP ทั้งหมดและให้วิธีการเข้าถึงข้อมูลคำขอที่สะดวก
 * ฟีเจอร์: รองรับ GET, POST, PUT, DELETE, JSON, Headers, Files
 * 
 * คลาสนี้ห่อหุ้มตัวแปรสุดยอดของ PHP ($_GET, $_POST, ฯลฯ)
 * และให้ API ที่สะอาดกว่าสำหรับการเข้าถึงข้อมูลคำขอ
 * 
 * ตัวอย่างการใช้งาน:
 * - $request->get('id') → รับพารามิเตอร์ GET
 * - $request->post('username') → รับข้อมูล POST
 * - $request->input('email') → รับจาก POST, PUT, DELETE, หรือ JSON
 * - $request->all() → รับข้อมูลคำขอทั้งหมด
 * - $request->method() → รับเมธอด HTTP
 * - $request->header('Authorization') → รับค่า header
 */

namespace App\Core;

class Request
{
    /**
     * ข้อมูล GET parameters
     */
    private array $get;

    /**
     * ข้อมูล POST parameters
     */
    private array $post;

    /**
     * ข้อมูล Server/Headers
     */
    private array $server;

    /**
     * ข้อมูลไฟล์ที่อัปโหลด
     */
    private array $files;

    /**
     * ข้อมูล Cookies
     */
    private array $cookies;

    /**
     * ข้อมูล JSON หรือ raw input
     */
    private ?array $json = null;

    /**
     * สร้างอินสแตนซ์ Request ใหม่
     */
    public function __construct()
    {
        $this->get = $_GET ?? [];
        $this->post = $_POST ?? [];
        $this->server = $_SERVER ?? [];
        $this->files = $_FILES ?? [];
        $this->cookies = $_COOKIE ?? [];

        // ตรวจสอบว่ามีข้อมูล JSON หรือไม่
        if ($this->isJson()) {
            $this->json = json_decode(file_get_contents('php://input'), true) ?? [];
        }
    }

    /**
     * รับพารามิเตอร์ GET
     * 
     * @param string|null $key คีย์ที่ต้องการดึง (null = ทั้งหมด)
     * @param mixed $default ค่าเริ่มต้นถ้าไม่พบ
     * @return mixed
     */
    public function get(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->get;
        }

        return $this->get[$key] ?? $default;
    }

    /**
     * รับข้อมูล POST
     * 
     * @param string|null $key คีย์ที่ต้องการดึง (null = ทั้งหมด)
     * @param mixed $default ค่าเริ่มต้นถ้าไม่พบ
     * @return mixed
     */
    public function post(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }

        return $this->post[$key] ?? $default;
    }

    /**
     * รับข้อมูลจาก POST, JSON, หรือ raw input
     * ใช้สำหรับ POST, PUT, DELETE requests
     * 
     * @param string|null $key คีย์ที่ต้องการดึง (null = ทั้งหมด)
     * @param mixed $default ค่าเริ่มต้นถ้าไม่พบ
     * @return mixed
     */
    public function input(?string $key = null, $default = null)
    {
        $data = array_merge($this->post, $this->json ?? []);

        if ($key === null) {
            return $data;
        }

        return $data[$key] ?? $default;
    }

    /**
     * รับข้อมูลทั้งหมด (GET + POST + JSON)
     * 
     * @return array
     */
    public function all(): array
    {
        return array_merge($this->get, $this->post, $this->json ?? []);
    }

    /**
     * ตรวจสอบว่ามีคีย์อยู่ในคำขอหรือไม่
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $all = $this->all();
        return isset($all[$key]);
    }

    /**
     * ตรวจสอบว่าคีย์หลายตัวมีอยู่หรือไม่
     * 
     * @param array $keys
     * @return bool
     */
    public function hasAll(array $keys): bool
    {
        $all = $this->all();
        foreach ($keys as $key) {
            if (!isset($all[$key])) {
                return false;
            }
        }
        return true;
    }

    /**
     * รับเฉพาะคีย์ที่ระบุ
     * 
     * @param array $keys
     * @return array
     */
    public function only(array $keys): array
    {
        $all = $this->all();
        $result = [];
        foreach ($keys as $key) {
            if (isset($all[$key])) {
                $result[$key] = $all[$key];
            }
        }
        return $result;
    }

    /**
     * รับทุกอย่างยกเว้นคีย์ที่ระบุ
     * 
     * @param array $keys
     * @return array
     */
    public function except(array $keys): array
    {
        $all = $this->all();
        foreach ($keys as $key) {
            unset($all[$key]);
        }
        return $all;
    }

    /**
     * รับข้อมูลไฟล์ที่อัปโหลด
     * 
     * @param string|null $key
     * @return mixed
     */
    public function file(?string $key = null)
    {
        if ($key === null) {
            return $this->files;
        }

        return $this->files[$key] ?? null;
    }

    /**
     * ตรวจสอบว่ามีไฟล์อัปโหลดหรือไม่
     * 
     * @param string $key
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * รับเมธอด HTTP
     * 
     * @return string
     */
    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * ตรวจสอบว่าเป็นเมธอดที่ระบุหรือไม่
     * 
     * @param string $method
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    /**
     * ตรวจสอบว่าเป็น GET request หรือไม่
     * 
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    /**
     * ตรวจสอบว่าเป็น POST request หรือไม่
     * 
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    /**
     * ตรวจสอบว่าเป็น PUT request หรือไม่
     * 
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    /**
     * ตรวจสอบว่าเป็น DELETE request หรือไม่
     * 
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    /**
     * ตรวจสอบว่าเป็น AJAX request หรือไม่
     * 
     * @return bool
     */
    public function isAjax(): bool
    {
        return !empty($this->server['HTTP_X_REQUESTED_WITH']) &&
               strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * ตรวจสอบว่าเป็น JSON request หรือไม่
     * 
     * @return bool
     */
    public function isJson(): bool
    {
        $contentType = $this->header('Content-Type');
        return $contentType && stripos($contentType, 'application/json') !== false;
    }

    /**
     * รับค่า header
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function header(string $key, $default = null)
    {
        // แปลงชื่อ header เป็นรูปแบบ SERVER
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        
        return $this->server[$key] ?? $default;
    }

    /**
     * รับ Authorization header
     * 
     * @return string|null
     */
    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization');
        if ($header && preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * รับ URI ปัจจุบัน
     * 
     * @return string
     */
    public function uri(): string
    {
        return strtok($this->server['REQUEST_URI'] ?? '/', '?');
    }

    /**
     * รับ URL เต็ม
     * 
     * @return string
     */
    public function url(): string
    {
        $protocol = $this->isSecure() ? 'https' : 'http';
        $host = $this->server['HTTP_HOST'] ?? 'localhost';
        $uri = $this->server['REQUEST_URI'] ?? '/';

        return "{$protocol}://{$host}{$uri}";
    }

    /**
     * ตรวจสอบว่าเป็น HTTPS หรือไม่
     * 
     * @return bool
     */
    public function isSecure(): bool
    {
        return !empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off';
    }

    /**
     * รับ IP address ของผู้ใช้
     * 
     * @return string
     */
    public function ip(): string
    {
        // ตรวจสอบ proxy headers ก่อน
        if (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            return $this->server['HTTP_X_FORWARDED_FOR'];
        }

        if (!empty($this->server['HTTP_CLIENT_IP'])) {
            return $this->server['HTTP_CLIENT_IP'];
        }

        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * รับ User Agent
     * 
     * @return string
     */
    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * รับ cookie
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function cookie(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->cookies;
        }

        return $this->cookies[$key] ?? $default;
    }

    /**
     * รับข้อมูล JSON ที่ถูก decode แล้ว
     * 
     * @return array|null
     */
    public function json(): ?array
    {
        return $this->json;
    }

    /**
     * รับ raw input body
     * 
     * @return string
     */
    public function raw(): string
    {
        return file_get_contents('php://input');
    }
}
