<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Response;
use Tests\TestCase;

final class ResponseTest extends TestCase
{
    public function testJsonResponseHasContentTypeAndBody(): void
    {
        $response = Response::json(['ok' => true], 201, JSON_UNESCAPED_UNICODE);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertArrayHasKey('Content-Type', $response->getHeaders());
        $this->assertStringContainsString('application/json', $response->getHeaders()['Content-Type']);
        $this->assertStringContainsString('{"ok":true}', $response->getBody());
    }

    public function testNoContentResponse(): void
    {
        $response = Response::noContent();

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', $response->getBody());
    }

    public function testWithCookieStoresCookieSpec(): void
    {
        $response = (new Response('hi'))
            ->withCookie('sid', 'abc', ['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);

        $cookies = $response->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertSame('sid', $cookies[0]['name']);
        $this->assertSame('abc', $cookies[0]['value']);
        $this->assertSame('/', $cookies[0]['options']['path']);
    }

    public function testApiSuccessHasStandardShape(): void
    {
        $response = Response::apiSuccess(['id' => 1], 'OK');

        $payload = json_decode($response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertSame(true, $payload['success']);
        $this->assertSame(['id' => 1], $payload['data']);
        $this->assertSame('OK', $payload['message']);
        $this->assertSame([], $payload['errors']);
    }

    public function testApiErrorHasStandardShape(): void
    {
        $response = Response::apiError('Bad', ['x'], 400);

        $payload = json_decode($response->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertSame(false, $payload['success']);
        $this->assertNull($payload['data']);
        $this->assertSame('Bad', $payload['message']);
        $this->assertSame(['x'], $payload['errors']);
    }

    public function testApiErrorCanKeepAssociativeErrors(): void
    {
        $errors = [
            'email' => ['Email is required'],
            'password' => ['Password too short'],
        ];

        $response = Response::apiError('Validation failed', $errors, 422);
        $payload = json_decode($response->getBody(), true);

        $this->assertSame($errors, $payload['errors']);
    }
}
