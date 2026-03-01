<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

class TmpChainModel extends \App\Core\Model
{
    protected static string $table = 'tmp_chain_test_table';
}

class TmpChainSoftModel extends \App\Core\Model
{
    protected static string $table = 'tmp_chain_test_table';
    protected static bool $softDeletes = true;
}

class TmpChainTest extends TestCase
{
    public function test_withTrashed_returns_chainable_object(): void
    {
        // Model::setConnection is configured in tests bootstrap

        // Basic chainability assertions
        $qb = TmpChainModel::withTrashed();
        $this->assertIsObject($qb);
        $this->assertTrue(method_exists($qb, 'where'), 'Returned object should have method "where"');
        $this->assertTrue(method_exists($qb, 'forceDelete'), 'Returned object should have method "forceDelete"');

        // Ensure withTrashed returns a ModelQueryBuilder specifically
        $this->assertInstanceOf(\App\Core\ModelQueryBuilder::class, $qb);

        // Ensure chaining retains same type
        $chained = $qb->where('id', '=', 1);
        $this->assertInstanceOf(\App\Core\ModelQueryBuilder::class, $chained);

        // If model uses soft deletes, the default query should include deleted_at constraint
        // model that uses soft deletes is declared at file scope
        $qbSoft = \Tests\Unit\TmpChainSoftModel::query();
        $sqlWith = $qbSoft->toSql();
        $this->assertStringContainsString('deleted_at', $sqlWith);

        $qbSoftTrashed = \Tests\Unit\TmpChainSoftModel::withTrashed();
        $sqlWithout = $qbSoftTrashed->toSql();
        $this->assertStringNotContainsString('deleted_at IS NULL', $sqlWithout);
    }
}
