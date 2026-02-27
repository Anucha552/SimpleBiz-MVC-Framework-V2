<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Core\ModelQueryBuilder;
use App\Core\RawExpression;
use Tests\TestCase;

class ModelQueryBuilderTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    public function test_constructor_applies_soft_delete_condition_when_model_uses_soft_deletes()
    {
        $db = $this->createMock(Database::class);
        $qb = new ModelQueryBuilder($db, 'users', DummySoftModel::class, true);
        $sql = $qb->toSql();
        $this->assertStringContainsString('`deleted_at` IS NULL', $sql);
    }

    public function test_insert_calls_model_prepare_and_delegates_to_db_execute()
    {
        $db = $this->createMock(Database::class);

        $db->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($sql) {
                return is_string($sql) && stripos($sql, 'INSERT INTO') !== false;
            }), $this->isType('array'))
            ->willReturn(1);

        DummyPrepareModel::$called = false;
        $qb = new ModelQueryBuilder($db, 'items', DummyPrepareModel::class, false);
        $ok = $qb->insert(['name' => 'x']);
        $this->assertTrue($ok);
        $this->assertTrue(DummyPrepareModel::$called, 'prepareInsertData should be called');
    }

    public function test_insertGetId_returns_last_insert_id_after_prepare()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())->method('execute')->willReturn(1);
        $db->expects($this->once())->method('lastInsertId')->willReturn('42');

        $qb = new ModelQueryBuilder($db, 'items', DummyPrepareModel::class, false);
        $id = $qb->insertGetId(['name' => 'y']);
        $this->assertSame(42, $id);
    }

    public function test_update_calls_prepare_and_returns_count()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())->method('execute')->with($this->callback(function ($sql) {
            return is_string($sql) && stripos($sql, 'UPDATE') !== false;
        }), $this->isType('array'))->willReturn(2);

        $qb = new ModelQueryBuilder($db, 'items', DummyPrepareModel::class, false);
        $qb->where('id', '=', 1);
        $count = $qb->update(['name' => 'z']);
        $this->assertSame(2, $count);
    }

    public function test_delete_soft_delete_updates_deleted_at()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())->method('execute')->with($this->callback(function ($sql) {
            return is_string($sql) && stripos($sql, 'UPDATE') !== false && stripos($sql, 'deleted_at') !== false;
        }), $this->isType('array'))->willReturn(1);

        $qb = new ModelQueryBuilder($db, 'items', DummySoftModel::class, true);
        $qb->where('id', '=', 5);
        $count = $qb->delete();
        $this->assertSame(1, $count);
    }

    public function test_restore_updates_deleted_at_null_and_returns_zero_when_not_using_soft_deletes()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())->method('execute')->with($this->callback(function ($sql) {
            return is_string($sql) && stripos($sql, 'UPDATE') !== false && stripos($sql, 'deleted_at') !== false;
        }), $this->isType('array'))->willReturn(1);

        $qb = new ModelQueryBuilder($db, 'items', DummySoftModel::class, true);
        $qb->where('id', '=', 6);
        $count = $qb->restore();
        $this->assertSame(1, $count);

        $qb2 = new ModelQueryBuilder($db, 'items', DummyNoSoftModel::class, false);
        $this->assertSame(0, $qb2->restore());
    }

    public function test_withTrashed_and_onlyTrashed_behavior()
    {
        $db = $this->createMock(Database::class);
        $qb = new ModelQueryBuilder($db, 'users', DummySoftModel::class, true);
        // initially has deleted_at IS NULL condition
        $this->assertStringContainsString('`deleted_at` IS NULL', $qb->toSql());

        // withTrashed removes constraint
        $qb->withTrashed();
        $this->assertStringNotContainsString('`deleted_at` IS NULL', $qb->toSql());

        // onlyTrashed adds IS NOT NULL
        $qb2 = new ModelQueryBuilder($db, 'users', DummySoftModel::class, true);
        $qb2->onlyTrashed();
        $this->assertStringContainsString('`deleted_at` IS NOT NULL', $qb2->toSql());
    }

    public function test_forceDelete_calls_parent_delete()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())->method('execute')->with($this->callback(function ($sql) {
            return is_string($sql) && stripos($sql, 'DELETE FROM') !== false;
        }), $this->isType('array'))->willReturn(3);

        $qb = new ModelQueryBuilder($db, 'items', DummyNoSoftModel::class, false);
        $qb->where('id', '=', 7);
        $count = $qb->forceDelete();
        $this->assertSame(3, $count);
    }

    public function test_constructor_does_not_apply_soft_delete_when_model_does_not_use_soft_deletes()
    {
        $db = $this->createMock(Database::class);
        $qb = new ModelQueryBuilder($db, 'users', DummyNoSoftModel::class, true);
        $this->assertStringNotContainsString('`deleted_at` IS NULL', $qb->toSql());
    }

    public function test_constructor_respects_applySoftDelete_flag_false()
    {
        $db = $this->createMock(Database::class);
        $qb = new ModelQueryBuilder($db, 'users', DummySoftModel::class, false);
        $this->assertStringNotContainsString('`deleted_at` IS NULL', $qb->toSql());
    }

    public function test_delete_without_soft_deletes_calls_parent_delete()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())->method('execute')->with($this->callback(function ($sql) {
            return is_string($sql) && stripos($sql, 'DELETE FROM') !== false;
        }), $this->isType('array'))->willReturn(4);

        $qb = new ModelQueryBuilder($db, 'items', DummyNoSoftModel::class, false);
        $qb->where('id', '=', 8);
        $count = $qb->delete();
        $this->assertSame(4, $count);
    }

    public function test_removeDeletedAtConstraint_preserves_other_wheres()
    {
        $db = $this->createMock(Database::class);
        $qb = new ModelQueryBuilder($db, 'users', DummySoftModel::class, true);
        $qb->where('name', '=', 'bob');
        $this->assertStringContainsString('`deleted_at` IS NULL', $qb->toSql());
        $this->assertStringContainsString('`name`', $qb->toSql());

        $qb->withTrashed();
        $this->assertStringNotContainsString('`deleted_at` IS NULL', $qb->toSql());
        $this->assertStringContainsString('`name`', $qb->toSql());
    }

    public function test_insert_without_model_does_not_call_prepare()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())->method('execute')->willReturn(1);

        DummyPrepareModel::$called = false;
        $qb = new ModelQueryBuilder($db, 'items', '', false);
        $ok = $qb->insert(['name' => 'no_model']);
        $this->assertTrue($ok);
        $this->assertFalse(DummyPrepareModel::$called, 'prepareInsertData should not be called when modelClass is empty');
    }

    public function test_update_without_model_does_not_call_prepare()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())->method('execute')->willReturn(1);

        DummyPrepareModel::$called = false;
        $qb = new ModelQueryBuilder($db, 'items', '', false);
        $qb->where('id', '=', 9);
        $count = $qb->update(['name' => 'no_model']);
        $this->assertSame(1, $count);
        $this->assertFalse(DummyPrepareModel::$called, 'prepareUpdateData should not be called when modelClass is empty');
    }
}

// Dummy model stubs used by tests
class DummySoftModel
{
    public static function usesSoftDeletes(): bool
    {
        return true;
    }

    public static function prepareInsertData(array $data): array
    {
        return $data;
    }

    public static function prepareUpdateData(array $data): array
    {
        return $data;
    }
}

class DummyNoSoftModel
{
    public static function usesSoftDeletes(): bool
    {
        return false;
    }

    public static function prepareInsertData(array $data): array
    {
        return $data;
    }

    public static function prepareUpdateData(array $data): array
    {
        return $data;
    }
}

class DummyPrepareModel
{
    public static bool $called = false;

    public static function usesSoftDeletes(): bool
    {
        return false;
    }

    public static function prepareInsertData(array $data): array
    {
        self::$called = true;
        $data['prepared'] = true;
        return $data;
    }

    public static function prepareUpdateData(array $data): array
    {
        self::$called = true;
        $data['prepared_update'] = true;
        return $data;
    }
}
