<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\View;
use App\Core\Cache;
use InvalidArgumentException;
use Tests\TestCase;

final class ViewTest extends TestCase
{
    public function testRenderWithLayoutAndSections(): void
    {
        $view = (new View('_test/hello', [
            'name' => 'World',
            'title' => 'Hello Title',
        ]))
            ->layout('_test_layout');

        $html = $view->render();

        $this->assertStringContainsString('LAYOUT-BEGIN', $html);
        $this->assertStringContainsString('TITLE:Hello Title', $html);
        $this->assertStringContainsString('CONTENT:Hello, World!', $html);
        $this->assertStringContainsString('LAYOUT-END', $html);
    }

     public function testRenderIgnoresCachedHtmlEvenWhenCacheIsRequested(): void
    {
        $data = ['name' => 'Cache', 'title' => 'Cache Title'];
        $cacheKey = 'view_' . md5('_test/hello' . md5(json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR)));
        Cache::set($cacheKey, ['html' => 'STALE CACHE CONTENT', 'deps' => []], 3600);

        $wasDebug = View::isDebug();
        View::setDebug(false);

        try {
            $html = (new View('_test/hello', $data))
                ->layout('_test_layout')
                ->cache(3600)
                ->render();
        } finally {
            View::setDebug($wasDebug);
        }

        $this->assertStringNotContainsString('STALE CACHE CONTENT', $html);
        $this->assertStringContainsString('Hello, Cache!', $html);
    }

    public function testInvalidViewNameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new View('../secret');
    }

    public function testInvalidLayoutNameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $view = new View('_test/hello');
        $view->layout('../bad');
    }
}
