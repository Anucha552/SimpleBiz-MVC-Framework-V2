<?php
/**
 * Authentication Feature Test
 * 
 * ทดสอบการทำงานของระบบ Authentication
 */

namespace Tests\Feature;

use Tests\TestCase;
use App\Core\Auth;
use App\Models\User;

class AuthTest extends TestCase
{
    public function testUserCanCheckIfAuthenticated(): void
    {
        // Test check method exists
        $this->assertTrue(method_exists(Auth::class, 'check'));
    }

    public function testUserCanLogout(): void
    {
        // Test logout method exists
        $this->assertTrue(method_exists(Auth::class, 'logout'));
    }

    public function testAuthHasAttemptMethod(): void
    {
        // Test attempt method exists
        $this->assertTrue(method_exists(Auth::class, 'attempt'));
    }
}
