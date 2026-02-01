<?php
/**
 * คลาส Request สำหรับจัดการคำขอ HTTP
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
     * Response headers ที่ middleware ต้องการแนบไปกับ response สุดท้าย
     *
     * @var array<string, string>
     */
    private array $responseHeaders = [];

    /**
     * Correlation ID for this request.
     */
    private string $requestId;

    /**
     * สร้างอินสแตนซ์ Request ใหม่
     * จุดประสงค์: เตรียมข้อมูลคำขอจากตัวแปรสุดยอดของ PHP
     * ตัวอย่างการใช้งาน:
     * ```php
     * $request = new Request();
     * ```
     * 
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function __construct()
    {
        $this->get = $_GET ?? [];
        $this->post = $_POST ?? [];
        $this->server = $_SERVER ?? [];
        $this->files = $_FILES ?? [];
        $this->cookies = $_COOKIE ?? [];

        $this->requestId = $this->initRequestId();
        // Always expose request id to the client
        $this->setResponseHeader('X-Request-Id', $this->requestId);
        // Make it available to legacy code/loggers that read from $_SERVER
        if (!isset($_SERVER['HTTP_X_REQUEST_ID'])) {
            $_SERVER['HTTP_X_REQUEST_ID'] = $this->requestId;
        }

        // ตรวจสอบว่ามีข้อมูล JSON หรือไม่
        if ($this->isJson()) {
            $this->json = json_decode(file_get_contents('php://input'), true) ?? [];
        }
    }

    /**
     * รับ Correlation ID ของคำขอ
     * จุดประสงค์: ดึง Correlation ID ที่ใช้ติดตามคำขอ
     * getRequestId() ควรใช้กับอะไร: การติดตามคำขอในระบบล็อกหรือการดีบัก
     * ตัวอย่างการใช้งาน:
     * ```php
     * $requestId = $request->getRequestId();
     * ```
     * 
     * @return string คืนค่า Correlation ID
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }

    /**
     * สร้าง Correlation ID ใหม่ถ้าไม่มีใน header
     * จุดประสงค์: สร้างหรือดึง Correlation ID สำหรับคำขอ
     * initRequestId() ควรใช้กับอะไร: เมื่อคุณต้องการระบุคำขอด้วย ID เฉพาะ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $requestId = $this->initRequestId();
     * ```
     * 
     * @return string คืนค่า Correlation ID
     */
    private function initRequestId(): string
    {
        $candidate = $this->server['HTTP_X_REQUEST_ID'] ?? $this->server['HTTP_X_CORRELATION_ID'] ?? null;
        if (is_string($candidate)) {
            $candidate = trim($candidate);
            // allow common ID formats; avoid logging/control chars
            if ($candidate !== '' && strlen($candidate) <= 128 && preg_match('/^[A-Za-z0-9._\-]+$/', $candidate) === 1) {
                return $candidate;
            }
        }

        return bin2hex(random_bytes(16));
    }

    /**
     * เพิ่ม/อัปเดต header สำหรับ response สุดท้าย
     * จุดประสงค์: ตั้งค่า header ที่จะถูกส่งกับ response
     * setResponseHeader() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่ม header เฉพาะให้กับ response
     * ตัวอย่างการใช้งาน:
     * ```php
     * $request->setResponseHeader('X-Custom-Header', 'Value');
     * ```
     * 
     * @param string $name ชื่อ header
     * @param string $value ค่า header
     * @return self คืนค่าอินสแตนซ์ปัจจุบันเพื่อการเชนริ่ง
     */
    public function setResponseHeader(string $name, string $value): self
    {
        $this->responseHeaders[$name] = $value;
        return $this;
    }

    /**
     * เพิ่ม headers หลายตัวสำหรับ response สุดท้าย
     * จุดประสงค์: ตั้งค่าหลาย header ที่จะถูกส่งกับ response
     * addResponseHeaders() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มหลาย header เฉพาะให้กับ response
     * ตัวอย่างการใช้งาน:
     * ```php
     * $request->addResponseHeaders([
     *     'X-Custom-Header-1' => 'Value1',
     *     'X-Custom-Header-2' => 'Value2',
     * ]);
     * ```
     *
     * @param array<string, string> $headers กำหนดชื่อและค่าของ headers
     * @return self คืนค่าอินสแตนซ์ปัจจุบันเพื่อการเชนริ่ง
     */
    public function addResponseHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->responseHeaders[(string) $name] = (string) $value;
        }
        return $this;
    }

    /**
     * ดึง response headers ที่ถูกสะสมไว้
     * จุดประสงค์: ดึง headers ที่จะถูกส่งกับ response
     * getResponseHeaders() ควรใช้กับอะไร: เมื่อคุณต้องการดู headers ที่ถูกตั้งค่าไว้สำหรับ response
     * ตัวอย่างการใช้งาน:
     * ```php
     * $headers = $request->getResponseHeaders();
     * ```
     * 
     * @return array<string, string> คืนค่าอาร์เรย์ของ headers
     */
    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    /**
     * รับพารามิเตอร์ GET
     * จุดประสงค์: ดึงค่าพารามิเตอร์จาก URL query string
     * get() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่าจาก query string
     * ตัวอย่างการใช้งาน:
     * ```php
     * $value = $request->get('key', 'default');
     * ```
     * 
     * @param string|null $key กำหนดคีย์ที่ต้องการดึง (null = ทั้งหมด)
     * @param mixed $default กำหนดค่าเริ่มต้นถ้าไม่พบ
     * @return mixed คืนค่าพารามิเตอร์ที่ร้องขอหรือค่าเริ่มต้น
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
     * จุดประสงค์: ดึงค่าพารามิเตอร์จากข้อมูล POST
     * post() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่าจากข้อมูล POST
     * ตัวอย่างการใช้งาน:
     * ```php
     * $value = $request->post('key', 'default');
     * ```
     * 
     * @param string|null $key กำหนดคีย์ที่ต้องการดึง (null = ทั้งหมด)
     * @param mixed $default กำหนดค่าเริ่มต้นถ้าไม่พบ
     * @return mixed คืนค่าพารามิเตอร์ที่ร้องขอหรือค่าเริ่มต้น
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
     * จุดประสงค์: ดึงค่าพารามิเตอร์จากข้อมูลคำขอไม่ว่าจะเป็น POST หรือ JSON
     * input() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่าจากข้อมูลคำขอไม่ว่าจะเป็น POST หรือ JSON
     * ตัวอย่างการใช้งาน:
     * ```php
     * $value = $request->input('key', 'default');
     * ```
     * 
     * @param string|null $key กำหนดคีย์ที่ต้องการดึง (null = ทั้งหมด)
     * @param mixed $default กำหนดค่าเริ่มต้นถ้าไม่พบ
     * @return mixed คืนค่าพารามิเตอร์ที่ร้องขอหรือค่าเริ่มต้น
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
     * จุดประสงค์: ดึงค่าพารามิเตอร์ทั้งหมดจากคำขอไม่ว่าจะเป็น GET, POST หรือ JSON
     * all() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่าทั้งหมดจากคำขอ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $allData = $request->all();
     * ```
     * 
     * @return array คืนค่าอาร์เรย์ของพารามิเตอร์ทั้งหมด
     */
    public function all(): array
    {
        return array_merge($this->get, $this->post, $this->json ?? []);
    }

    /**
     * ตรวจสอบว่ามีคีย์อยู่ในคำขอหรือไม่
     * จุดประสงค์: ตรวจสอบว่าคีย์ที่ระบุมีอยู่ในคำขอหรือไม่
     * has() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบการมีอยู่ของคีย์ในคำขอ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $exists = $request->has('key');
     * ```
     * 
     * @param string $key กำหนดคีย์ที่ต้องการตรวจสอบ
     * @return bool คืนค่าจริงถ้าคีย์มีอยู่ในคำขอ
     */
    public function has(string $key): bool
    {
        $all = $this->all();
        return isset($all[$key]);
    }

    /**
     * ตรวจสอบว่าคีย์หลายตัวมีอยู่หรือไม่
     * จุดประสงค์: ตรวจสอบว่าคีย์ที่ระบุทั้งหมดมีอยู่ในคำขอหรือไม่
     * hasAll() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบการมีอยู่ของหลายคีย์ในคำขอ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $allExist = $request->hasAll(['key1', 'key2']);
     * ```
     * 
     * @param array $keys กำหนดคีย์ที่ต้องการตรวจสอบ
     * @return bool คืนค่าจริงถ้าคีย์ทั้งหมดมีอยู่ในคำขอ
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
     * จุดประสงค์: ดึงค่าพารามิเตอร์เฉพาะคีย์ที่ระบุจากคำขอ
     * only() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่าพารามิเตอร์เฉพาะบางคีย์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $subset = $request->only(['key1', 'key2']);
     * ```
     * 
     * @param array $keys กำหนดคีย์ที่ต้องการดึง
     * @return array คืนค่าอาร์เรย์ของพารามิเตอร์ที่ระบุ
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
     * จุดประสงค์: ดึงค่าพารามิเตอร์ทั้งหมดจากคำขอยกเว้นคีย์ที่ระบุ
     * except() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่าพารามิเตอร์ทั้งหมดยกเว้นบางคีย์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $data = $request->except(['key1', 'key2']);
     * ```
     * 
     * @param array $keys กำหนดคีย์ที่ต้องการยกเว้น
     * @return array คืนค่าอาร์เรย์ของพารามิเตอร์ที่เหลือ
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
     * จุดประสงค์: ดึงข้อมูลไฟล์ที่อัปโหลดจากคำขอ
     * file() ควรใช้กับอะไร: เมื่อคุณต้องการดึงข้อมูลไฟล์ที่อัปโหลด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $file = $request->file('upload');
     * ```
     * 
     * @param string|null $key กำหนดคีย์ของไฟล์ที่ต้องการดึง (null = ทั้งหมด)
     * @return mixed คืนค่าข้อมูลไฟล์ที่ร้องขอหรือ null
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
     * จุดประสงค์: ตรวจสอบว่ามีไฟล์อัปโหลดที่ระบุอยู่ในคำขอหรือไม่
     * hasFile() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบการมีอยู่ของไฟล์อัปโหลด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $hasFile = $request->hasFile('upload');
     * ```
     * 
     * @param string $key กำหนดคีย์ของไฟล์ที่ต้องการตรวจสอบ
     * @return bool คืนค่าจริงถ้ามีไฟล์อัปโหลดที่ระบุ
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * รับเมธอด HTTP
     * จุดประสงค์: ดึงเมธอด HTTP ของคำขอ
     * method() ควรใช้กับอะไร: เมื่อคุณต้องการทราบเมธอด HTTP ของคำขอ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $method = $request->method();
     * ```
     * 
     * @return string คืนค่าเมธอด HTTP
     */
    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * ตรวจสอบว่าเป็นเมธอดที่ระบุหรือไม่
     * จุดประสงค์: ตรวจสอบว่าเมธอด HTTP ของคำขอตรงกับที่ระบุหรือไม่
     * isMethod() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบเมธอด HTTP ของคำขอ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isPost = $request->isMethod('POST');
     * ```
     * 
     * @param string $method กำหนดเมธอดที่ต้องการตรวจสอบ
     * @return bool คืนค่าจริงถ้าเมธอดตรงกับที่ระบุ
     */
    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    /**
     * ตรวจสอบว่าเป็น GET request หรือไม่
     * จุดประสงค์: ตรวจสอบว่าเมธอด HTTP ของคำขอเป็น GET หรือไม่
     * isGet() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าเป็น GET request
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isGet = $request->isGet();
     * ```
     * 
     * @return bool คืนค่าจริงถ้าเป็น GET request
     */
    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    /**
     * ตรวจสอบว่าเป็น POST request หรือไม่
     * จุดประสงค์: ตรวจสอบว่าเมธอด HTTP ของคำขอเป็น POST หรือไม่
     * isPost() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าเป็น POST request
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isPost = $request->isPost();
     * ```
     * 
     * @return bool คืนค่าจริงถ้าเป็น POST request
     */
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    /**
     * ตรวจสอบว่าเป็น PUT request หรือไม่
     * จุดประสงค์: ตรวจสอบว่าเมธอด HTTP ของคำขอเป็น PUT หรือไม่
     * isPut() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าเป็น PUT request
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isPut = $request->isPut();
     * ```
     * 
     * @return bool คืนค่าจริงถ้าเป็น PUT request
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
     * จุดประสงค์: ตรวจสอบว่าเป็นคำขอแบบ AJAX หรือไม่
     * isAjax() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าเป็น AJAX request
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isAjax = $request->isAjax();
     * ```
     * 
     * @return bool คืนค่าจริงถ้าเป็น AJAX request
     */
    public function isAjax(): bool
    {
        return !empty($this->server['HTTP_X_REQUESTED_WITH']) &&
               strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * ตรวจสอบว่าเป็น JSON request หรือไม่
     * จุดประสงค์: ตรวจสอบว่าเมธอด HTTP ของคำขอเป็น JSON หรือไม่
     * isJson() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าเป็น JSON request
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isJson = $request->isJson();
     * ```
     * 
     * @return bool คืนค่าจริงถ้าเป็น JSON request
     */
    public function isJson(): bool
    {
        $contentType = $this->header('Content-Type');
        return $contentType && stripos($contentType, 'application/json') !== false;
    }

    /**
     * รับค่า header
     * จุดประสงค์: ดึงค่าของ header ที่ระบุจากคำขอ
     * header() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่าของ header จากคำขอ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $auth = $request->header('Authorization', 'default');
     * ```
     * 
     * @param string $key กำหนดชื่อ header ที่ต้องการดึง
     * @param mixed $default กำหนดค่าเริ่มต้นถ้าไม่พบ
     * @return mixed คืนค่าของ header ที่ร้องขอหรือค่าเริ่มต้น
     */
    public function header(string $key, $default = null)
    {
        // Try multiple server keys to be robust across server configurations
        $serverKeyHttp = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        $serverKeyRaw = strtoupper(str_replace('-', '_', $key));

        if (isset($this->server[$serverKeyHttp])) {
            return $this->server[$serverKeyHttp];
        }

        if (isset($this->server[$serverKeyRaw])) {
            return $this->server[$serverKeyRaw];
        }

        // Fallback to getallheaders if available (preserve original casing)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $lookup = str_replace(' ', '-', ucwords(strtolower(str_replace('-', ' ', $key))));
            if (isset($headers[$lookup])) {
                return $headers[$lookup];
            }
            // case-insensitive search
            $lowerLookup = strtolower($lookup);
            foreach ($headers as $hname => $hvalue) {
                if (strtolower($hname) === $lowerLookup) {
                    return $hvalue;
                }
            }
        }

        return $default;
    }

    /**
     * รับ Authorization header
     * จุดประสงค์: ดึงค่า Authorization header จากคำขอ
     * bearerToken() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่า Bearer token จาก Authorization header
     * ตัวอย่างการใช้งาน:
     * ```php
     * $token = $request->bearerToken();
     * ```
     * 
     * @return string|null คืนค่า Bearer token หรือ null ถ้าไม่มี
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
     * จุดประสงค์: ดึง URI ของคำขอปัจจุบัน
     * uri() ควรใช้กับอะไร: เมื่อคุณต้องการทราบ URI ของคำขอปัจจุบัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $uri = $request->uri();
     * ```
     * 
     * @return string คืนค่า URI ปัจจุบัน
     */
    public function uri(): string
    {
        return strtok($this->server['REQUEST_URI'] ?? '/', '?');
    }

    /**
     * รับ URL เต็ม
     * จุดประสงค์: ดึง URL เต็มของคำขอปัจจุบัน
     * url() ควรใช้กับอะไร: เมื่อคุณต้องการทราบ URL เต็มของคำขอปัจจุบัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $url = $request->url();
     * ```
     * 
     * @return string คืนค่า URL เต็มของคำขอปัจจุบัน
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
     * จุดประสงค์: ตรวจสอบว่าเป็นการเชื่อมต่อแบบ HTTPS หรือไม่
     * isSecure() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าเชื่อมต่อผ่าน HTTPS หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * if ($request->isSecure()) {
     *     // ทำบางอย่างเมื่อเป็น HTTPS
     * }
     * ```
     * 
     * @return bool คืนค่าจริงถ้าเป็นการเชื่อมต่อแบบ HTTPS
     */
    public function isSecure(): bool
    {
        return !empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off';
    }

    /**
     * รับ IP address ของผู้ใช้
     * จุดประสงค์: ดึง IP address ของผู้ใช้จากคำขอ
     * ip() ควรใช้กับอะไร: เมื่อคุณต้องการทราบ IP address ของผู้ใช้ที่ส่งคำขอ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $ip = $request->ip();
     * ```
     * 
     * @return string คืนค่า IP address ของผู้ใช้
     */
    public function ip(): string
    {
        // ตรวจสอบ proxy headers ก่อน
        if (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            return $this->server['HTTP_X_FORWARDED_FOR'];
        }

        // ตรวจสอบ HTTP_CLIENT_IP
        if (!empty($this->server['HTTP_CLIENT_IP'])) {
            return $this->server['HTTP_CLIENT_IP'];
        }

        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * รับ User Agent
     * จุดประสงค์: ดึง User Agent ของคำขอ
     * userAgent() ควรใช้กับอะไร: เมื่อคุณต้องการทราบ User Agent ของคำขอ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $userAgent = $request->userAgent();
     * ```
     * 
     * @return string คืนค่า User Agent ของคำขอ
     */
    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * รับ cookie
     * จุดประสงค์: ดึงค่าคุกกี้จากคำขอ
     * cookie() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่าคุกกี้จากคำขอ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $cookieValue = $request->cookie('cookie_name');
     * ```
     * 
     * @param string|null $key กำหนดชื่อคุกกี้ที่ต้องการดึง (null = ทั้งหมด)
     * @param mixed $default กำหนดค่าเริ่มต้นถ้าไม่พบ
     * @return mixed คืนค่าคุกกี้ที่ร้องขอหรือค่าเริ่มต้น
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
     * จุดประสงค์: ดึงข้อมูล JSON ที่ถูก decode จากคำขอ
     * json() ควรใช้กับอะไร: เมื่อคุณต้องการดึงข้อมูล JSON ที่ถูก decode จากคำขอ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $data = $request->json();
     * ```
     * 
     * @return array|null คืนค่าอาร์เรย์ของข้อมูล JSON หรือ null ถ้าไม่มี
     */
    public function json(): ?array
    {
        return $this->json;
    }

    /**
     * รับ raw input body
     * จุดประสงค์: ดึง raw input body ของคำขอ
     * raw() ควรใช้กับอะไร: เมื่อคุณต้องการดึง raw input body ของคำขอ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $rawBody = $request->raw();
     * ```
     * 
     * @return string คืนค่า raw input body ของคำขอ
     */
    public function raw(): string
    {
        return file_get_contents('php://input');
    }
}
