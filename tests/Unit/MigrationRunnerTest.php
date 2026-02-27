<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\MigrationRunner;
use App\Core\Database;
use Tests\TestCase;

final class MigrationRunnerTest extends TestCase
{
    public function testRunAndRollbackNoMigrationsIsSafe(): void
    {
        try {
            $runner = new MigrationRunner();
        } catch (\PDOException $e) {
            $this->markTestSkipped('Database not configured for migrations: ' . $e->getMessage());
            return;
        }

        // Ensure no pending migrations by running then rolling back
        // Buffer any output to avoid PHPUnit marking the test as risky
        ob_start();
        $result = $runner->run();
        $out = (string) ob_get_clean();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);

        // Rollback should not throw and returns array; also buffer output
        ob_start();
        $rb = $runner->rollback(1);
        $out2 = (string) ob_get_clean();
        $this->assertIsArray($rb);
    }
}
