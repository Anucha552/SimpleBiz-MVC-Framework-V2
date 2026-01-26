<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Session;
use Tests\TestCase;

final class SessionTest extends TestCase
{
    public function testSetGetHasAndRemove(): void
    {
        Session::start();
        Session::set('foo', 'bar');

        $this->assertTrue(Session::has('foo'));
        $this->assertSame('bar', Session::get('foo'));

        Session::remove('foo');
        $this->assertFalse(Session::has('foo'));
    }

    public function testFlashAndOldInput(): void
    {
        Session::start();
        Session::flash('notice', 'Hello');

        // Flash data is stored under _flash.new until the next request.
        $this->assertSame('Hello', $_SESSION['_flash']['new']['notice'] ?? null);

        // Simulate aging (next request) by moving new -> old
        $_SESSION['_flash']['old'] = $_SESSION['_flash']['new'];
        unset($_SESSION['_flash']['new']);

        $this->assertTrue(Session::hasFlash('notice'));
        $this->assertSame('Hello', Session::getFlash('notice'));

        // Old input
        Session::flashInput(['name' => 'tester']);
        $_SESSION['_flash']['old'] = $_SESSION['_flash']['new'];
        unset($_SESSION['_flash']['new']);
        $this->assertSame(['name' => 'tester'], Session::old());
    }
}
