<?php

namespace App\Core;

/**
 * Lightweight HTTP Response.
 *
 * Design goals:
 * - Minimal surface area (good for small freelance projects)
 * - Easy to return from controllers/middleware
 * - Can still be used alongside existing echo/exit patterns
 */
class Response
{
    private int $statusCode;

    /** @var array<string, string> */
    private array $headers = [];

    private string $body;

    /**
     * @var array<int, array{name:string,value:string,options:array}>
     */
    private array $cookies = [];

    /**
     * Test/debug helper: headers sent by the last Response::send() call.
     *
     * @var array<int, string>
     */
    private static array $lastSentHeaders = [];

    /**
     * @return array<int, string>
     */
    public static function getLastSentHeaders(): array
    {
        return self::$lastSentHeaders;
    }

    public static function clearLastSentHeaders(): void
    {
        self::$lastSentHeaders = [];
    }

    public function __construct(string $body = '', int $statusCode = 200, array $headers = [])
    {
        $this->body = $body;
        $this->statusCode = $statusCode;

        foreach ($headers as $name => $value) {
            $this->headers[(string) $name] = (string) $value;
        }
    }

    public static function html(string $html, int $statusCode = 200): self
    {
        return (new self($html, $statusCode))
            ->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Standard API success response.
     *
     * Shape:
     * - success: true
     * - data: mixed
     * - message: string
     * - errors: []
     * - meta: optional
     *
     * @param mixed $data
     */
    public static function apiSuccess($data = null, string $message = 'Success', array $meta = [], int $statusCode = 200): self
    {
        $payload = [
            'success' => true,
            'data' => $data,
            'message' => $message,
            'errors' => [],
        ];

        if (!empty($meta)) {
            $payload['meta'] = $meta;
        }

        return self::json($payload, $statusCode, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Standard API error response.
     *
     * Shape:
     * - success: false
     * - data: null
     * - message: string
     * - errors: array
     * - meta: optional
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

    public static function noContent(): self
    {
        return new self('', 204);
    }

    /**
     * @param mixed $data
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

    public static function redirect(string $location, int $statusCode = 302): self
    {
        return (new self('', $statusCode))
            ->withHeader('Location', $location);
    }

    /**
     * Add a cookie to be sent with this response.
     *
     * Options map to PHP's setcookie options: expires, path, domain, secure, httponly, samesite.
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

    public function withStatus(int $statusCode): self
    {
        $clone = clone $this;
        $clone->statusCode = $statusCode;
        return $clone;
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    /**
     * Add multiple headers.
     *
     * @param array<string, string> $headers
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

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /** @return array<string, string> */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return array<int, array{name:string,value:string,options:array}>
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

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
                setcookie($cookie['name'], $cookie['value'], $cookie['options']);
            }
        }

        echo $this->body;
    }
}
