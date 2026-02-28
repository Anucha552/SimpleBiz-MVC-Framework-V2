<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Core\Database;

class TestModel extends \App\Core\Model
{
    protected static string $table = 'test_models';
    protected static array $fillable = ['name', 'value'];
    protected static bool $timestamps = true;
    protected static bool $softDeletes = true;
}

class NoTimestampModel extends \App\Core\Model
{
    protected static string $table = 'no_ts';
    protected static array $fillable = ['name'];
    protected static bool $timestamps = false;
}

class NoPropModel extends \App\Core\Model
{
    protected static string $table = 'noprop';
}

class ModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // ensure DB connection is set by bootstrap
        $db = Database::getInstance();
        // recreate test table
        $db->execRaw('DROP TABLE IF EXISTS `test_models`');
        $db->execRaw(<<<SQL
CREATE TABLE `test_models` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` TEXT,
  `value` TEXT,
  `created_at` TEXT,
  `updated_at` TEXT,
  `deleted_at` TEXT
);
SQL
        );
    }

    public function testQueryAddsSoftDeleteConstraint()
    {
        $sql = TestModel::query()->toSql();
        $this->assertStringContainsString('`deleted_at` IS NULL', $sql);
    }

    public function testCreateAndFindAndTimestampsAndFillable()
    {
        $id = TestModel::create(['name' => 'Alice', 'value' => 'V1', 'forbidden' => 'x', 'id' => 999]);
        $this->assertGreaterThan(0, $id);

        $row = TestModel::withTrashed()->find($id);
        $this->assertNotNull($row);
        $this->assertArrayHasKey('name', $row);
        $this->assertEquals('Alice', $row['name']);
        // fillable should prevent forbidden/id override
        $this->assertArrayNotHasKey('forbidden', $row);
        $this->assertNotEquals(999, $row['id']);
        $this->assertArrayHasKey('created_at', $row);
        $this->assertArrayHasKey('updated_at', $row);
    }

    public function testWhereInEmptyGeneratesFalseCondition()
    {
        $sql = TestModel::query()->whereIn('id', [])->toSql();
        $this->assertStringContainsString('0 = 1', $sql);
    }

    public function testUpdateUpdatesAndSetsUpdatedAt()
    {
        $id = TestModel::create(['name' => 'Before', 'value' => 'v']);
        $before = TestModel::withTrashed()->find($id);
        $this->assertNotNull($before);

        sleep(1);
        $affected = TestModel::where('id', '=', $id)->update(['name' => 'After']);
        $this->assertGreaterThanOrEqual(0, $affected);

        $after = TestModel::withTrashed()->find($id);
        $this->assertEquals('After', $after['name']);
        $this->assertNotEquals($before['updated_at'], $after['updated_at']);
    }

// Method นี้มองหา Chain ของ Model ไม่เจอ เป็น IDE ไม่รู้จัก restore() กับ forceDelete() เลยขอ comment ไว้ก่อนนะครับ
    public function testSoftDeleteWithTrashedOnlyTrashedRestoreAndForceDelete()
{
    $id = TestModel::create(['name' => 'ToDelete', 'value' => 'v']);

    // soft delete
    $deleted = TestModel::where('id', '=', $id)->delete();
    $this->assertGreaterThanOrEqual(0, $deleted);

    // normal find should not return (soft deleted)
    $this->assertNull(TestModel::find($id));

    // withTrashed should return
    $this->assertNotNull(TestModel::withTrashed()->find($id));

    // onlyTrashed should include it
    $rows = TestModel::onlyTrashed()->where('id', '=', $id)->get();
    $this->assertCount(1, $rows);

    // restore (ต้องเรียก restore())
    $restored = TestModel::withTrashed()
        ->where('id', '=', $id)
        ->restore();

    $this->assertGreaterThanOrEqual(0, $restored);
    $this->assertNotNull(TestModel::find($id));

    // force delete (ต้องเรียก forceDelete())
    $force = TestModel::withTrashed()
        ->where('id', '=', $id)
        ->forceDelete();

    $this->assertGreaterThanOrEqual(0, $force);
    $this->assertNull(TestModel::withTrashed()->find($id));
}

    public function testSelectOrderLimitOffsetProduceSql()
    {
        $sql = TestModel::select(['name', 'value'])
            ->orderBy('name', 'DESC')
            ->limit(5)
            ->offset(2)
            ->toSql();

        $this->assertStringContainsString('SELECT', $sql);
        $this->assertStringContainsString('`name`, `value`', $sql);
        $this->assertStringContainsString('ORDER BY', $sql);
        $this->assertStringContainsString('LIMIT 5', $sql);
        $this->assertStringContainsString('OFFSET 2', $sql);
    }

    public function testWhereOrWhereAndNullChecksInSql()
    {
        $sql = TestModel::where('name', '=', 'Alice')
            ->orWhere('value', '=', 'V1')
            ->whereNull('deleted_at')
            ->whereNotNull('created_at')
            ->toSql();

        $this->assertStringContainsString('`deleted_at` IS NULL', $sql);
        $this->assertStringContainsString('`created_at` IS NOT NULL', $sql);
        $this->assertStringContainsString('OR', $sql);
    }

    public function testPrepareInsertAndUpdateDataAddsTimestampsAndRespectsFillable()
    {
        $data = ['name' => 'Bob', 'value' => 'X', 'id' => 42, 'forbidden' => 'y'];

        $prepared = TestModel::prepareInsertData($data);
        $this->assertArrayHasKey('created_at', $prepared);
        $this->assertArrayHasKey('updated_at', $prepared);
        $this->assertArrayNotHasKey('forbidden', $prepared);
        $this->assertArrayNotHasKey('id', $prepared);

        $upd = TestModel::prepareUpdateData(['name' => 'Bobby']);
        $this->assertArrayHasKey('updated_at', $upd);
    }

    public function testPrepareInsertRespectsNoTimestampsWhenDisabled()
    {
        $p = NoTimestampModel::prepareInsertData(['name' => 'NT', 'extra' => 'x']);
        $this->assertArrayNotHasKey('created_at', $p);
        $this->assertArrayNotHasKey('updated_at', $p);
    }

    public function testUsesSoftDeletesDetectsSubclassDefault()
    {
        // class without overriding softDeletes should use parent's default (false)
        if (!class_exists('NoPropModel')) {
            eval(<<<'PHP'
            class NoPropModel extends \App\Core\Model
            {
                protected static string $table = 'noprop';
            }
            PHP
            );
        }

        $this->assertFalse(NoPropModel::usesSoftDeletes());
        $this->assertTrue(TestModel::usesSoftDeletes());
    }

    public function testQueryThrowsWhenTableNotSet()
    {
        // `BadModel` is provided as a dedicated test class in tests/Unit/BadModel.php

        $this->expectException(\RuntimeException::class);
        \Tests\Unit\BadModel::query();
    }

    public function testCreateThrowsWhenNoDbConnection()
    {
        // `NoDbModel` is provided as a dedicated test class in tests/Unit/NoDbModel.php

        $this->expectException(\RuntimeException::class);
        \Tests\Unit\NoDbModel::create(['name' => 'X', 'value' => 'v']);
    }

    public function testFindReturnsNullWhenNotFound()
    {
        $this->assertNull(TestModel::find(999999));
    }

    public function testPrepareUpdateRespectsNoTimestampsWhenDisabled()
    {
        $p = NoTimestampModel::prepareUpdateData(['name' => 'NTU']);
        $this->assertArrayNotHasKey('updated_at', $p);
    }

    public function testPrepareInsertRespectsGuardedWhenFillableEmpty()
    {
        // `GuardedModel` is provided as a dedicated test class in tests/Unit/GuardedModel.php

        $prepared = \Tests\Unit\GuardedModel::prepareInsertData(['id' => 5, 'secret' => 's', 'ok' => 1]);
        $this->assertArrayNotHasKey('id', $prepared);
        $this->assertArrayNotHasKey('secret', $prepared);
        $this->assertArrayHasKey('ok', $prepared);
    }
}
