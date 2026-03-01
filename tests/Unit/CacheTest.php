<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Cache;
use Tests\TestCase;

final class CacheTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = __DIR__ . '/_tmp_cache_' . uniqid();
        @mkdir($this->cacheDir, 0777, true);
        Cache::setCacheDirectory($this->cacheDir);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_dir($this->cacheDir)) {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->cacheDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($it as $file) {
                if ($file->isDir()) {
                    @rmdir($file->getPathname());
                } else {
                    @unlink($file->getPathname());
                }
            }

            @rmdir($this->cacheDir);
        }
    }

    private function cacheFilePath(string $key): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . md5($key) . '.cache';
    }

    private function writeRawCache(string $key, $data, bool $asJson = false): void
    {
        $path = $this->cacheFilePath($key);
        $content = $asJson ? json_encode($data) : serialize($data);
        file_put_contents($path, $content, LOCK_EX);
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

    public function testRememberAndRememberForever(): void
    {
        $counter = 0;
        $value = Cache::remember('remember:key', 60, function () use (&$counter) {
            $counter++;
            return ['a' => 1];
        });

        $this->assertSame(['a' => 1], $value);
        $this->assertSame(1, $counter);

        // calling remember again should not call callback
        $value2 = Cache::remember('remember:key', 60, function () use (&$counter) {
            $counter++;
            return ['a' => 2];
        });

        $this->assertSame(['a' => 1], $value2);
        $this->assertSame(1, $counter);

        // rememberForever stores without expiration
        $counter = 0;
        $val = Cache::rememberForever('forever:key', function () use (&$counter) {
            $counter++;
            return 'forever';
        });

        $this->assertSame('forever', $val);
        $this->assertSame('forever', Cache::get('forever:key'));
        $this->assertSame(1, $counter);
    }

    public function testPullForeverAndHas(): void
    {
        Cache::forever('pull:key', 'x');
        $this->assertTrue(Cache::has('pull:key'));
        $pulled = Cache::pull('pull:key', 'def');
        $this->assertSame('x', $pulled);
        $this->assertFalse(Cache::has('pull:key'));
    }

    public function testIncrementDecrementAndNonNumeric(): void
    {
        // increment when not exist returns the increment value
        $r = Cache::increment('counter:key', 5);
        $this->assertSame(5, $r);

        $r2 = Cache::increment('counter:key', 2);
        $this->assertSame(7, $r2);

        $d = Cache::decrement('counter:key', 3);
        $this->assertSame(4, $d);

        // set non-numeric and attempt increment
        Cache::set('nonnum', 'abc');
        $res = Cache::increment('nonnum');
        $this->assertFalse($res);
    }

    public function testClearExpiredAndClearOlderThanAndGarbageFile(): void
    {
        // create expired file by writing data with past expires_at
        $expiredKey = 'expired:key';
        $this->writeRawCache($expiredKey, [
            'key' => $expiredKey,
            'value' => 'v',
            'expires_at' => time() - 100,
            'created_at' => time() - 200,
        ]);

        $deleted = Cache::clearExpired();
        $this->assertGreaterThanOrEqual(1, $deleted);

        // create old file for clearOlderThan
        $oldKey = 'old:key';
        $this->writeRawCache($oldKey, [
            'key' => $oldKey,
            'value' => 'v',
            'expires_at' => 0,
            'created_at' => time() - 7200,
        ]);

        $count = Cache::clearOlderThan(3600);
        $this->assertGreaterThanOrEqual(1, $count);

        // create a garbage (non-serialized and non-json) file and ensure clearExpired removes it
        $badKey = 'bad:key';
        file_put_contents($this->cacheFilePath($badKey), 'garbage content', LOCK_EX);
        $removed = Cache::clearExpired();
        $this->assertGreaterThanOrEqual(1, $removed);
    }

    public function testFlushAndForgetPatternAndAllStats(): void
    {
        Cache::set('user_1', 'a', 0);
        Cache::set('user_2', 'b', 0);
        Cache::set('other', 'c', 0);

        // forget pattern
        $deleted = Cache::forgetPattern('user_*');
        $this->assertSame(2, $deleted);

        // set some files for stats and all()
        Cache::set('s1', 'x', 0);
        Cache::set('s2', 'y', 0);

        $stats = Cache::stats();
        $this->assertArrayHasKey('total_files', $stats);
        $this->assertArrayHasKey('total_size', $stats);
        $this->assertArrayHasKey('total_size_formatted', $stats);
        $this->assertArrayHasKey('expired_files', $stats);

        $all = Cache::all();
        $this->assertIsArray($all);
        $this->assertArrayHasKey('s1', $all);

        // flush removes all
        $this->assertTrue(Cache::flush());
        $this->assertSame(0, Cache::stats()['total_files']);
    }

    public function testFormatBytesRepresentation(): void
    {
        // indirectly check formatted size contains unit
        Cache::set('f1', str_repeat('x', 2048), 0);
        $stats = Cache::stats();
        $this->assertStringMatchesFormat('%s %s', $stats['total_size_formatted']);
    }
}
