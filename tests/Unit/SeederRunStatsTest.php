<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Seeder;
use Tests\TestCase;

final class SeederRunStatsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Seeder::resetRunStats();
    }

    public function testRunStatsCountNestedSeederFilesAndMissingSeeders(): void
    {
        ob_start();
        try {
            (new SeederRunStatsRoot())->run();
            Seeder::recordSeederRun();
        } finally {
            ob_end_clean();
        }

        self::assertSame(['ran' => 3, 'skipped' => 1], Seeder::getRunStats());
    }
}

final class SeederRunStatsRoot extends Seeder
{
    public function __construct()
    {
    }

    public function run(): void
    {
        $this->call([
            SeederRunStatsChildOne::class,
            SeederRunStatsMissing::class,
            SeederRunStatsChildTwo::class,
        ]);
    }
}

final class SeederRunStatsChildOne extends Seeder
{
    public function __construct()
    {
    }

    public function run(): void
    {
    }
}

final class SeederRunStatsChildTwo extends Seeder
{
    public function __construct()
    {
    }

    public function run(): void
    {
    }
}
