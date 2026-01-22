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
     * @param mixed $data
     */
    public static function json($data, int $statusCode = 200, int $jsonFlags = 0): self
    {
        $body = json_encode($data, $jsonFlags);
        if ($body === false) {
            // Fallback that still returns a valid JSON payload
            $body = json_encode([
                'success' => false,
                'message' => 'JSON encode failed',
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

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }

        echo $this->body;
    }
}
