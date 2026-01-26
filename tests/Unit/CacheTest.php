<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Cache;
use Tests\TestCase;

final class CacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Use test cache dir inside storage to avoid collisions
        Cache::setCacheDirectory(__DIR__ . '/../../storage/cache');
        @mkdir(__DIR__ . '/../../storage/cache', 0777, true);
        Cache::flush();
    }

    public function testSetGetHasForget(): void
    {
        $key = 'test:key';
        $this->assertFalse(Cache::has($key));

        $ok = Cache::set($key, 'value', 2);
        $this->assertTrue($ok);
        $this->assertTrue(Cache::has($key));
        $this->assertSame('value', Cache::get($key));

        $this->assertTrue(Cache::forget($key) || !Cache::has($key));
    }

    public function testTTLExpiration(): void
    {
        $key = 'ttl:key';
        Cache::set($key, 'v', 1);
        $this->assertTrue(Cache::has($key));
        sleep(2);
        $this->assertFalse(Cache::has($key));
    }
}
