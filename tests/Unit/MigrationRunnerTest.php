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
        $result = $runner->run();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);

        // Rollback should not throw and returns array
        $rb = $runner->rollback(1);
        $this->assertIsArray($rb);
    }
}
