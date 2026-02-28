<?php
declare(strict_types=1);

use Tests\TestCase;
use App\Core\Authorization;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Database;

final class AuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (function_exists('tests_reset_doubles')) {
            tests_reset_doubles();
        }
        // clear any static user in Auth
        $rc = new ReflectionClass(App\Core\Auth::class);
        $prop = $rc->getProperty('user');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    public function testNormalizePermissions(): void
    {
        $this->assertSame(['a','b'], Authorization::normalizePermissions(['a','b']));
        $this->assertSame(['a','b'], Authorization::normalizePermissions('["a","b"]'));
        $this->assertSame(['a','b'], Authorization::normalizePermissions('a,b'));
        $this->assertSame(['single'], Authorization::normalizePermissions('single'));
        $this->assertSame([], Authorization::normalizePermissions(null));
    }

    public function testCanReturnsFalseWhenNotAuthenticated(): void
    {
        // ensure no user
        $this->assertNull(Auth::user());
        $this->assertFalse(Authorization::can('edit_posts'));
    }

    public function testCanReturnsTrueForAdmin(): void
    {
        // set private static user in Auth
        $this->setAuthUser(['id' => 1, 'is_admin' => true]);
        $this->assertTrue(Authorization::can('anything'));
    }

    public function testCanUsesSessionCache(): void
    {
        $this->setAuthUser(['id' => 2]);
        Session::set('_auth_permissions', ['perms' => ['edit_posts'], 'ts' => time()]);
        $this->assertTrue(Authorization::can('edit_posts'));
        $this->assertFalse(Authorization::can('delete_posts'));
    }

    public function testCanReadsPermissionsFromUserRecordAndCaches(): void
    {
        $user = ['id' => 3, 'permissions' => '["x","y"]'];
        $this->setAuthUser($user);
        // ensure no preexisting session cache
        Session::remove('_auth_permissions');
        $this->assertTrue(Authorization::can('x'));
        $cached = Session::get('_auth_permissions');
        $this->assertIsArray($cached);
        $this->assertArrayHasKey('perms', $cached);
        $this->assertContains('x', $cached['perms']);
    }

    public function testCanChecksDatabaseUserPermissionWhenNoLocalPerms(): void
    {
        $this->setAuthUser(['id' => 4]);

        // create a DB instance that responds to fetchColumn for user_permissions
        $db = new class extends Database {
            public function fetchColumn(string $sql, array $params = []) {
                if (stripos($sql, 'user_permissions') !== false && ($params['uid'] ?? null) === 4 && ($params['perm'] ?? null) === 'db_perm') {
                    return 1;
                }
                return false;
            }
        };

        // inject into Database::$instance
        $rc = new ReflectionClass(Database::class);
        $prop = $rc->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, $db);

        $this->assertTrue(Authorization::can('db_perm'));
        $this->assertFalse(Authorization::can('other_perm'));
    }

    public function testInvalidatePermissionCacheRemovesSessionEntry(): void
    {
        $this->setAuthUser(['id' => 5]);
        Session::set('_auth_permissions', ['perms' => ['a'], 'ts' => time()]);
        Authorization::invalidatePermissionCache(5);
        $this->assertNull(Session::get('_auth_permissions'));
    }

    public function testHasRoleFromUserRecordArrayAndStringFormats(): void
    {
        $this->setAuthUser(['id' => 6, 'roles' => ['editor','moderator']]);
        $this->assertTrue(Authorization::hasRole('editor'));

        $this->setAuthUser(['id' => 7, 'roles' => 'editor,visitor']);
        $this->assertTrue(Authorization::hasRole('visitor'));
    }

    public function testHasRoleChecksDatabaseWhenNecessary(): void
    {
        $this->setAuthUser(['id' => 8, 'role' => null]);

        $db = new class extends Database {
            public function query(string $sql, array $params = []) {
                return new class {
                    public function fetchColumn() { return 1; }
                };
            }
            public function fetch(string $sql, array $params = []) { return false; }
        };

        $rc = new ReflectionClass(Database::class);
        $prop = $rc->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, $db);

        $this->assertTrue(Authorization::hasRole('some_role'));
    }

    private function setAuthUser(array $user): void
    {
        $rc = new ReflectionClass(App\Core\Auth::class);
        $prop = $rc->getProperty('user');
        $prop->setAccessible(true);
        $prop->setValue(null, $user);
    }

    public function testLoadAllPermissionsMergesUserAndDbAndRoles(): void
    {
        $this->setAuthUser(['id' => 9, 'permissions' => '["p1"]', 'role_id' => 20]);

        $db = new class extends Database {
            public function fetchAll(string $sql, array $params = []) {
                if (stripos($sql, 'user_permissions') !== false) {
                    return [ ['permission' => 'db1'], ['permission' => 'p1'] ];
                }
                if (stripos($sql, 'user_roles') !== false) {
                    return [ ['id' => 20] ];
                }
                if (stripos($sql, 'role_permissions') !== false) {
                    return [ ['permission' => 'role_perm'] ];
                }
                return [];
            }

            public function fetch(string $sql, array $params = []) {
                return false;
            }
        };

        $rc = new ReflectionClass(Database::class);
        $prop = $rc->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, $db);

        $perms = Authorization::loadAllPermissions(Auth::user());
        $this->assertContains('p1', $perms);
        $this->assertContains('db1', $perms);
        $this->assertContains('role_perm', $perms);

        $cached = Session::get('_auth_permissions');
        $this->assertIsArray($cached);
        $this->assertArrayHasKey('perms', $cached);
        $this->assertContains('role_perm', $cached['perms']);
    }

    public function testLoadAllPermissionsReturnsStarForAdmin(): void
    {
        $this->setAuthUser(['id' => 10, 'is_admin' => true]);
        $perms = Authorization::loadAllPermissions(Auth::user());
        $this->assertSame(['*'], $perms);
    }

    public function testInvalidatePermissionCacheWithNullClearsCurrentUser(): void
    {
        $this->setAuthUser(['id' => 11]);
        Session::set('_auth_permissions', ['perms' => ['a'], 'ts' => time()]);
        Authorization::invalidatePermissionCache(null);
        $this->assertNull(Session::get('_auth_permissions'));
    }

    public function testLogOnceDeduplicatesEntries(): void
    {
        $rc = new ReflectionClass(App\Core\Authorization::class);
        $seen = $rc->getProperty('seenLogs');
        $seen->setAccessible(true);
        $seen->setValue(null, []);

        $rm = $rc->getMethod('logOnce');
        $rm->setAccessible(true);
        $rm->invoke(null, 'info', 'ev.test', ['a' => 1]);
        $rm->invoke(null, 'info', 'ev.test', ['a' => 1]);

        $s = $seen->getValue(null);
        $this->assertCount(1, $s);
    }
}
