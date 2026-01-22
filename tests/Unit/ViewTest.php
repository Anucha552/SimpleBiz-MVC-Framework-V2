<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\View;
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
