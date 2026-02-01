<?php
/**
 * คลาสนี้เป็นตัวแทนของการตอบสนอง HTTP ที่ส่งกลับไปยังไคลเอนต์
 * 
 * จุดประสงค์: จัดการการสร้างและส่งการตอบสนอง HTTP
 * Response() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างและส่งการตอบสนอง HTTP
 * 
 * ฟีเจอร์หลัก:
 * - สร้างการตอบสนองด้วยสถานะ, หัวข้อ, และเนื้อหา
 * - ส่งการตอบสนองไปยังไคลเอนต์
 * 
 * ตัวอย่างการใช้งานโดยรวม:
 * ```php
 * $response = new Response('Hello, World!', 200, ['Content-Type' => 'text/plain']);
 * $response->send();
 * ```
 */

namespace App\Core;

class Response
{
    /**
     * สถานะรหัส HTTP ของการตอบสนอง
     */
    private int $statusCode;

    /**
     * หัวข้อของการตอบสนอง
     *
     */
    private array $headers = [];

    /**
     * เนื้อหาของการตอบสนอง
     */
    private string $body;

    /**
     * คุกกี้ของการตอบสนอง
     */
    private array $cookies = [];

    /**
     * บันทึกหัวข้อที่ถูกส่งล่าสุด (สำหรับการทดสอบ)
     */
    private static array $lastSentHeaders = [];

    /**
     * สร้างอินสแตนซ์ใหม่ของ Response
     * จุดประสงค์: สร้างการตอบสนอง HTTP ใหม่
     * Response() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างการตอบสนอง HTTP ใหม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $response = new Response('Hello, World!', 200, ['Content-Type' => 'text/plain']);
     * ```
     *
     * @param string $body เนื้อหาของการตอบสนอง
     * @param int $statusCode รหัสสถานะ HTTP (ค่าเริ่มต้น: 200)
     * @param array<string, string> $headers หัวข้อเพิ่มเติมสำหรับการตอบสนอง
     */
    public function __construct(string $body = '', int $statusCode = 200, array $headers = [])
    {
        $this->body = $body;
        $this->statusCode = $statusCode;

        foreach ($headers as $name => $value) {
            $this->headers[(string) $name] = (string) $value;
        }
    }

    /**
     * รับหัวข้อที่ถูกส่งล่าสุด (สำหรับการทดสอบ)
     * จุดประสงค์: ดึงหัวข้อที่ถูกส่งล่าสุด
     * getLastSentHeaders() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $headers = Response::getLastSentHeaders();
     * ```
     * 
     * @return array<int, string> คืนค่าหัวข้อที่ถูกส่งล่าสุด
     */
    public static function getLastSentHeaders(): array
    {
        return self::$lastSentHeaders;
    }

    /**
     * ล้างหัวข้อที่ถูกส่งล่าสุด (สำหรับการทดสอบ)
     * จุดประสงค์: ล้างบันทึกหัวข้อที่ถูกส่งล่าสุด
     * clearLastSentHeaders() ควรใช้กับอะไร: เมื่อคุณต้องการล้างบันทึกหัวข้อที่ถูกส่งล่าสุด
     * ตัวอย่างการใช้งาน:
     * ```php
     * Response::clearLastSentHeaders();
     * ```
     * 
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public static function clearLastSentHeaders(): void
    {
        self::$lastSentHeaders = [];
    }

    /**
     * สร้างการตอบสนอง HTML
     * จุดประสงค์: สร้างการตอบสนอง HTML
     * html() ควรใช้กับอะไร: เมื่อคุณต้องการส่งการตอบสนอง HTML
     * ตัวอย่างการใช้งาน:
     * ```php
     * $response = Response::html('<h1>Hello, World!</h1>');
     * ```
     * 
     * @param string $html เนื้อหา HTML ของการตอบสนอง
     * @param int $statusCode รหัสสถานะ HTTP (ค่าเริ่มต้น: 200)
     * @return self คืนค่าอินสแตนซ์ของ Response
     */
    public static function html(string $html, int $statusCode = 200): self
    {
        return (new self($html, $statusCode))
            ->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * สร้างการตอบสนอง API สำเร็จรูป
     * จุดประสงค์: สร้างการตอบสนอง API ที่แสดงถึงความสำเร็จ
     * apiSuccess() ควรใช้กับอะไร: เมื่อคุณต้องการส่งการตอบสนอง API ที่แสดงถึงความสำเร็จ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $response = Response::apiSuccess($data, 'Operation successful', ['page' => 1], 200);
     * ```
     *
     * @param mixed $data กำหนดข้อมูลที่ส่งกลับ
     * @param string $message กำหนดข้อความที่เกี่ยวข้องกับการตอบสนอง
     * @param array $meta กำหนดข้อมูลเมตาเพิ่มเติม (ถ้ามี)
     * @param int $statusCode รหัสสถานะ HTTP (ค่าเริ่มต้น: 200)
     * @return self คืนค่าอินสแตนซ์ของ Response
     */
    public static function apiSuccess($data = null, string $message = 'Success', array $meta = [], int $statusCode = 200): self
    {
        $payload = [
            'success' => true,
            'data' => $data,
            'message' => $message,
            'errors' => [],
        ];

        // เพิ่ม meta ถ้ามี
        if (!empty($meta)) {
            $payload['meta'] = $meta;
        }

        return self::json($payload, $statusCode, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * สร้างการตอบสนอง API ที่แสดงถึงข้อผิดพลาด
     * จุดประสงค์: สร้างการตอบสนอง API ที่แสดงถึงข้อผิดพลาด
     * apiError() ควรใช้กับอะไร: เมื่อคุณต้องการส่งการตอบสนอง API ที่แสดงถึงข้อผิดพลาด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $response = Response::apiError('An error occurred', ['field' => 'Invalid value'], 400);
     * ```
     * 
     * @param string $message กำหนดข้อความที่อธิบายข้อผิดพลาด
     * @param array $errors กำหนดรายละเอียดข้อผิดพลาดเพิ่มเติม
     * @param int $statusCode รหัสสถานะ HTTP (ค่าเริ่มต้น: 400)
     * @param array $meta กำหนดข้อมูลเมตาเพิ่มเติม (ถ้ามี)
     * @return self คืนค่าอินสแตนซ์ของ Response
     */
    public static function apiError(string $message, array $errors = [], int $statusCode = 400, array $meta = []): self
    {
        $payload = [
            'success' => false,
            'data' => null,
            'message' => $message,
            'errors' => $errors,
        ];

        if (!empty($meta)) {
            $payload['meta'] = $meta;
        }

        return self::json($payload, $statusCode, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * สร้างการตอบสนองไม่มีเนื้อหา (204 No Content)
     * จุดประสงค์: สร้างการตอบสนองที่ไม่มีเนื้อหา
     * noContent() ควรใช้กับอะไร: เมื่อคุณต้องการส่งการตอบสนองที่ไม่มีเนื้อหา
     * ตัวอย่างการใช้งาน:
     * ```php
     * $response = Response::noContent();
     * ```
     * 
     * @return self คืนค่าอินสแตนซ์ของ Response
     */
    public static function noContent(): self
    {
        return new self('', 204);
    }

    /**
     * สร้างการตอบสนอง JSON
     * จุดประสงค์: สร้างการตอบสนอง JSON
     * json() ควรใช้กับอะไร: เมื่อคุณต้องการส่งการตอบสนอง JSON
     * ตัวอย่างการใช้งาน:
     * ```php
     * $response = Response::json($data, 200);
     * ```
     * 
     * @param mixed $data กำหนดข้อมูลที่จะถูกเข้ารหัสเป็น JSON
     * @param int $statusCode รหัสสถานะ HTTP (ค่าเริ่มต้น: 200)
     * @param int $jsonFlags ธงสำหรับ json_encode (ค่าเริ่มต้น: 0)
     * @return self คืนค่าอินสแตนซ์ของ Response
     */
    public static function json($data, int $statusCode = 200, int $jsonFlags = 0): self
    {
        $flags = $jsonFlags;
        // Make sure slashes/unicode are readable unless caller overrides
        if ($flags === 0) {
            $flags = JSON_UNESCAPED_UNICODE;
        }

        $body = json_encode($data, $flags);
        if ($body === false) {
            // Fallback that still returns a valid JSON payload
            $body = json_encode([
                'success' => false,
                'message' => 'JSON encode failed',
                'errors' => [],
            ], JSON_UNESCAPED_UNICODE);

            return (new self($body ?: '{"success":false}', 500))
                ->withHeader('Content-Type', 'application/json; charset=UTF-8');
        }

        return (new self($body, $statusCode))
            ->withHeader('Content-Type', 'application/json; charset=UTF-8');
    }

    /**
     * สร้างการตอบสนองเปลี่ยนเส้นทาง
     * จุดประสงค์: สร้างการตอบสนองที่เปลี่ยนเส้นทางไปยัง URL ใหม่
     * redirect() ควรใช้กับอะไร: เมื่อคุณต้องการส่งการตอบสนองที่เปลี่ยนเส้นทาง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $response = Response::redirect('https://example.com', 302);
     * ```
     * 
     * @param string $location กำหนด URL ที่จะเปลี่ยนเส้นทางไป
     * @param int $statusCode รหัสสถานะ HTTP (ค่าเริ่มต้น: 302)
     * @return self คืนค่าอินสแตนซ์ของ Response
     */
    public static function redirect(string $location, int $statusCode = 302): self
    {
        return (new self('', $statusCode))
            ->withHeader('Location', $location);
    }

    /**
     * เพิ่มคุกกี้ที่จะถูกส่งพร้อมกับการตอบสนองนี้
     * จุดประสงค์: เพิ่มคุกกี้ในการตอบสนอง HTTP
     * withCookie() ควรใช้กับอะไร: เมื่อคุณต้องการตั้งค่าคุกกี้ที่จะถูกส่งกับการตอบสนอง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $response = $response->withCookie('session_id', 'abc123', ['path' => '/', 'httponly' => true]);
     * ```
     * 
     * @param string $name กำหนดชื่อของคุกกี้
     * @param string $value กำหนดค่าของคุกกี้
     * @param array $options กำหนดตัวเลือกเพิ่มเติมสำหรับคุกกี้ (เช่น expires, path, domain, secure, httponly)
     * @return self คืนค่าอินสแตนซ์ของ Response ที่มีคุกกี้เพิ่มขึ้น
     */
    public function withCookie(string $name, string $value, array $options = []): self
    {
        $clone = clone $this;
        $clone->cookies[] = [
            'name' => $name,
            'value' => $value,
            'options' => $options,
        ];
        return $clone;
    }

    /**
     * เพิ่มรหัสสถานะให้กับการตอบสนองนี้
     * จุดประสงค์: ตั้งค่ารหัสสถานะ HTTP ของการตอบสนอง
     * withStatus() ควรใช้กับอะไร: เมื่อคุณต้องการตั้งค่ารหัสสถานะ HTTP ของการตอบสนอง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $response = $response->withStatus(404);
     * ```
     * 
     * @param int $statusCode กำหนดรหัสสถานะ HTTP
     * @return self คืนค่าอินสแตนซ์ของ Response ที่มีรหัสสถานะที่ตั้งค่าใหม่
     */
    public function withStatus(int $statusCode): self
    {
        $clone = clone $this;
        $clone->statusCode = $statusCode;
        return $clone;
    }

    /**
     * เพิ่มหัวข้อเดียว
     * จุดประสงค์: เพิ่มหัวข้อ HTTP ให้กับการตอบสนอง
     * withHeader() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มหัวข้อ HTTP ให้กับการตอบสนอง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $response = $response->withHeader('Content-Type', 'application/json');
     * ```
     * 
     * @param string $name กำหนดชื่อของหัวข้อ
     * @param string $value กำหนดค่าของหัวข้อ
     * @return self คืนค่าอินสแตนซ์ของ Response ที่มีหัวข้อเพิ่มขึ้น
     */
    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    /**
     * เพิ่มหลายหัวข้อ
     * จุดประสงค์: เพิ่มหลายหัวข้อ HTTP ให้กับการตอบสนอง
     * withHeaders() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มหลายหัวข้อ HTTP ให้กับการตอบสนอง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $response = $response->withHeaders(['Content-Type' => 'application/json', 'Cache-Control' => 'no-cache'], false);
     * ```
     * 
     * @param array<string, string> $headers กำหนดหัวข้อที่ต้องการเพิ่ม
     * @param bool $overwrite กำหนดว่าควรเขียนทับ
     * @return self คืนค่าอินสแตนซ์ของ Response ที่มีหัวข้อเพิ่มขึ้น
     */
    public function withHeaders(array $headers, bool $overwrite = true): self
    {
        $clone = clone $this;
        foreach ($headers as $name => $value) {
            $key = (string) $name;
            if (!$overwrite && array_key_exists($key, $clone->headers)) {
                continue;
            }
            $clone->headers[$key] = (string) $value;
        }
        return $clone;
    }

    public function write(string $chunk): self
    {
        $clone = clone $this;
        $clone->body .= $chunk;
        return $clone;
    }

    /**
     * ส่งคืนรหัสสถานะของการตอบสนอง
     * จุดประสงค์: ดึงรหัสสถานะ HTTP ของการตอบสนอง
     * getStatusCode() ควรใช้กับอะไร: เมื่อคุณต้องการทราบรหัสสถานะ HTTP ของการตอบสนอง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $statusCode = $response->getStatusCode();
     * ```
     * 
     * @return int คืนค่ารหัสสถานะ HTTP ของการตอบสนอง
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /** 
     * ส่งคืนหัวข้อของการตอบสนอง
     * จุดประสงค์: ดึงหัวข้อ HTTP ของการตอบสนอง
     * getHeaders() ควรใช้กับอะไร: เมื่อคุณต้องการทราบหัวข้อ HTTP ของการตอบสนอง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $headers = $response->getHeaders();
     * ```
     * 
     * @return array<string, string> คืนค่าหัวข้อของการตอบสนอง
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * ส่งคืนคุกกี้ของการตอบสนอง
     * จุดประสงค์: ดึงคุกกี้ที่ถูกตั้งค่าใน การตอบสนอง
     * getCookies() ควรใช้กับอะไร: เมื่อคุณต้องการทราบคุกกี้ที่ถูกตั้งค่าใน การตอบสนอง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $cookies = $response->getCookies();
     * ```
     * 
     * @return array คืนค่าคุกกี้ของการตอบสนอง
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * ส่งการตอบสนองไปยังไคลเอนต์
     * จุดประสงค์: ส่งการตอบสนอง HTTP ไปยังไคลเอนต์
     * send() ควรใช้กับอะไร: เมื่อคุณต้องการส่งการตอบสนอง HTTP ไปยังไคลเอนต์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $response->send();
     * ```
     * 
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function send(): void
    {
        // Reset per-send record (useful for tests)
        self::$lastSentHeaders = [];

        foreach ($this->headers as $name => $value) {
            self::$lastSentHeaders[] = $name . ': ' . $value;
        }

        if (!headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }

            foreach ($this->cookies as $cookie) {
                $options = $cookie['options'] ?? [];
                if (is_array($options) && defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
                    // PHP 7.3+ supports passing options array
                    setcookie($cookie['name'], $cookie['value'], $options);
                } else {
                    // Backward compatible setcookie call
                    $expires = $options['expires'] ?? 0;
                    $path = $options['path'] ?? '/';
                    $domain = $options['domain'] ?? '';
                    $secure = $options['secure'] ?? false;
                    $httponly = $options['httponly'] ?? false;
                    setcookie($cookie['name'], $cookie['value'], $expires, $path, $domain, $secure, $httponly);
                }
            }
        }

        echo $this->body;
    }
}
