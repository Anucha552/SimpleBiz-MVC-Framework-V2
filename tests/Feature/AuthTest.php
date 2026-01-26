<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use Tests\TestCase;

final class AuthTest extends TestCase
{
    public function testLoginTemporaryAndIdCheck(): void
    {
        // Use temporary login helper for tests
        Auth::loginTemporary(['id' => 123, 'username' => 'tester']);

        // loginTemporary sets the in-memory user without touching session
        $this->assertSame(123, Auth::id());

        // Ensure user returns array with id
        $user = Auth::user();
        $this->assertIsArray($user);
        $this->assertArrayHasKey('id', $user);
        $this->assertSame(123, $user['id']);
    }
}
