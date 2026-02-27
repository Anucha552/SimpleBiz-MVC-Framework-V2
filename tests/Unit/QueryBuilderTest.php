<?php

namespace Tests\Unit;

use App\Core\QueryBuilder;
use App\Core\RawExpression;
use App\Core\Database;
use Tests\TestCase;

class QueryBuilderTest extends TestCase
{
    public function testSelectWhereOrderLimitOffsetToSql()
    {
        $db = $this->createMock(Database::class);
        $qb = new QueryBuilder($db, 'users');

        $sql = $qb->select('id, name')
            ->where('id', '=', 1)
            ->orderBy('name', 'ASC')
            ->limit(10)
            ->offset(5)
            ->toSql();

        $this->assertStringContainsString('SELECT `id`, `name` FROM `users`', $sql);
        $this->assertStringContainsString('WHERE `id` = :p1', $sql);
        $this->assertStringContainsString('ORDER BY `name` ASC', $sql);
        $this->assertStringContainsString('LIMIT 10', $sql);
        $this->assertStringContainsString('OFFSET 5', $sql);
    }

    public function testWhereNullAndNotNull()
    {
        $db = $this->createMock(Database::class);
        $qb = new QueryBuilder($db, 'items');

        $sql1 = $qb->whereNull('deleted_at')->toSql();
        $this->assertStringContainsString('WHERE `deleted_at` IS NULL', $sql1);

        $qb = new QueryBuilder($db, 'items');
        $sql2 = $qb->whereNotNull('deleted_at')->toSql();
        $this->assertStringContainsString('WHERE `deleted_at` IS NOT NULL', $sql2);
    }

    public function testWhereInEmptyCreatesFalseCondition()
    {
        $db = $this->createMock(Database::class);
        $qb = new QueryBuilder($db, 't');

        $sql = $qb->whereIn('id', [])->toSql();
        $this->assertStringContainsString('WHERE 0 = 1', $sql);
    }

    public function testWhereInCreatesPlaceholdersAndBindingsOnGet()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())
            ->method('fetchAll')
            ->with($this->callback(function ($sql) {
                return strpos($sql, 'IN (') !== false;
            }), $this->callback(function ($bindings) {
                // expect two bindings p1 and p2
                return is_array($bindings) && count($bindings) === 2 && isset($bindings['p1']) && isset($bindings['p2']);
            }))
            ->willReturn([]);

        $qb = new QueryBuilder($db, 't');
        $qb->whereIn('id', [10, 20])->get();
    }

    public function testJoinAndLeftJoinToSql()
    {
        $db = $this->createMock(Database::class);
        $qb = new QueryBuilder($db, 'users');

        $sql = $qb->join('posts', 'users.id', '=', 'posts.user_id')
            ->leftJoin('comments', 'posts.id', '=', 'comments.post_id')
            ->toSql();

        $this->assertStringContainsString('JOIN `posts` ON `users`.`id` = `posts`.`user_id`', $sql);
        $this->assertStringContainsString('LEFT JOIN `comments` ON `posts`.`id` = `comments`.`post_id`', $sql);
    }

    public function testInsertAndInsertGetIdUsesLastInsertId()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())
            ->method('execute')
            ->willReturn(1);
        $db->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('123');

        $qb = new QueryBuilder($db, 'users');
        $id = $qb->insertGetId(['name' => 'Alice', 'created_at' => new RawExpression('NOW()')]);

        $this->assertSame(123, $id);
    }

    public function testUpdateThrowsWithoutWhere()
    {
        $this->expectException(\RuntimeException::class);

        $db = $this->createMock(Database::class);
        $qb = new QueryBuilder($db, 'users');
        $qb->update(['name' => 'Bob']);
    }

    public function testDeleteThrowsWithoutWhere()
    {
        $this->expectException(\RuntimeException::class);

        $db = $this->createMock(Database::class);
        $qb = new QueryBuilder($db, 'users');
        $qb->delete();
    }

    public function testNestedWhereMergesBindingsOnGet()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())
            ->method('fetchAll')
            ->with($this->callback(function ($sql) {
                return strpos($sql, '(') !== false && strpos($sql, 'OR') !== false;
            }), $this->callback(function ($bindings) {
                return is_array($bindings) && count($bindings) === 2 && $bindings['p1'] === 1 && $bindings['p2'] === 2;
            }))
            ->willReturn([]);

        $qb = new QueryBuilder($db, 't');
        $qb->where(function ($q) {
            $q->where('a', '=', 1)->orWhere('b', '=', 2);
        })->get();
    }

    public function testSelectWithRawExpression()
    {
        $db = $this->createMock(Database::class);
        $qb = new QueryBuilder($db, 't');

        $sql = $qb->select(new RawExpression('COUNT(*) as total'))->toSql();
        $this->assertStringContainsString('SELECT COUNT(*) as total FROM `t`', $sql);
    }

    public function testClearResetsPlaceholderCounter()
    {
        $db = $this->createMock(Database::class);
        $qb = new QueryBuilder($db, 't');

        $sql1 = $qb->where('a', '=', 1)->toSql();
        $this->assertStringContainsString(':p1', $sql1);

        $qb->clear();
        $sql2 = $qb->where('b', '=', 2)->toSql();
        $this->assertStringContainsString(':p1', $sql2);
        $this->assertStringNotContainsString(':p2', $sql2);
    }

    public function testUpdateWithWhereExecutes()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($sql) {
                return strpos($sql, 'UPDATE `users` SET') !== false && strpos($sql, 'WHERE `id` = :p1') !== false;
            }), $this->callback(function ($bindings) {
                return isset($bindings['p1']) && $bindings['p1'] === 5;
            }))
            ->willReturn(1);

        $qb = new QueryBuilder($db, 'users');
        $count = $qb->where('id', '=', 5)->update(['name' => 'Bob']);
        $this->assertSame(1, $count);
    }

    public function testDeleteWithWhereExecutes()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($sql) {
                return strpos($sql, 'DELETE FROM `users`') !== false && strpos($sql, 'WHERE `id` = :p1') !== false;
            }), $this->callback(function ($bindings) {
                return isset($bindings['p1']) && $bindings['p1'] === 7;
            }))
            ->willReturn(1);

        $qb = new QueryBuilder($db, 'users');
        $count = $qb->where('id', '=', 7)->delete();
        $this->assertSame(1, $count);
    }

    public function testTransactionMethodsDelegateToDb()
    {
        $db = $this->createMock(Database::class);
        $db->method('beginTransaction')->willReturn(true);
        $db->method('commit')->willReturn(true);
        $db->method('rollBack')->willReturn(true);

        $qb = new QueryBuilder($db, 'x');
        $this->assertTrue($qb->beginTransaction());
        $this->assertTrue($qb->commit());
        $this->assertTrue($qb->rollback());
    }

    public function testEscapeIdentifierWithAlias()
    {
        $db = $this->createMock(Database::class);
        $qb = new QueryBuilder($db, 'users');

        $sql = $qb->select('users.id as user_id')->toSql();
        $this->assertStringContainsString('SELECT `users`.`id` AS `user_id` FROM `users`', $sql);
    }

    public function testHavingAndGroupByToSql()
    {
        $db = $this->createMock(Database::class);
        $qb = new QueryBuilder($db, 'orders');

        $sql = $qb->groupBy('category')->having('total', '>', 100)->toSql();
        $this->assertStringContainsString('GROUP BY `category`', $sql);
        $this->assertStringContainsString('HAVING `total` > :p1', $sql);
    }

    public function testInsertRespectsRawExpressionValue()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($sql) {
                // created_at should be the raw NOW(), not a placeholder
                return strpos($sql, 'NOW()') !== false && strpos($sql, ':p2') === false;
            }), $this->callback(function ($bindings) {
                // only one binding for name
                return is_array($bindings) && count($bindings) === 1 && isset($bindings['p1']) && $bindings['p1'] === 'Alice';
            }))
            ->willReturn(1);

        $qb = new QueryBuilder($db, 'users');
        $ok = $qb->insert(['name' => 'Alice', 'created_at' => new RawExpression('NOW()')]);
        $this->assertTrue($ok);
    }

    public function testGetClearsStateSoPlaceholdersRestart()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        $qb = new QueryBuilder($db, 't');
        $qb->where('a', '=', 1)->get(); // get() should call clear()

        $sql = $qb->where('b', '=', 2)->toSql();
        $this->assertStringContainsString(':p1', $sql);
        $this->assertStringNotContainsString(':p2', $sql);
    }

    public function testEscapeIdentifierHandlesBackticksAndSpaceAlias()
    {
        $db = $this->createMock(Database::class);
        $qb = new QueryBuilder($db, 't');

        $sql = $qb->select('weird`name as alias')->toSql();
        $this->assertStringContainsString('`weird``name` AS `alias`', $sql);

        $sql2 = $qb->select('col alias_no_as')->toSql();
        $this->assertStringContainsString('AS `alias_no_as`', $sql2);
    }

    public function testWhereShortSignatureGeneratesExpectedSql()
    {
        $db = $this->createMock(Database::class);
        $qb = new QueryBuilder($db, 'users');
        $qb->where('name', 'John');
        $sql = $qb->toSql();
        $this->assertStringContainsString('`name` = :p1', $sql);
    }

    public function testOrWhereCallableNestedGeneratesExpectedSql()
    {
        $db = $this->createMock(Database::class);
        $qb = new QueryBuilder($db, 'users');
        $qb->where('id', '=', 1)
            ->orWhere(function($q) {
                $q->where('name', '=', 'John');
            });
        $sql = $qb->toSql();
        $this->assertStringContainsString('`id` = :p1', $sql);
        $this->assertStringContainsString('OR (', $sql);
        $this->assertStringContainsString('`name` = :p2', $sql);
    }

    public function testOrHavingAddsOrClause()
    {
        $db = $this->createMock(Database::class);
        $qb = new QueryBuilder($db, 'users');
        $qb->groupBy('category')
            ->having('total', '>', 100)
            ->orHaving('avg', '<', 50);
        $sql = $qb->toSql();
        $this->assertStringContainsString('HAVING', $sql);
        $this->assertStringContainsString('`total` > :p1', $sql);
        $this->assertStringContainsString('OR `avg` < :p2', $sql);
    }

    public function testSelectWithArrayColumns()
    {
        $db = $this->createMock(Database::class);
        $qb = new QueryBuilder($db, 'users');

        $sql = $qb->select(['id', 'email'])->toSql();
        $this->assertStringContainsString('`id`', $sql);
        $this->assertStringContainsString('`email`', $sql);
    }

    public function testGroupByWithArrayProducesMultipleColumns()
    {
        $db = $this->createMock(Database::class);
        $qb = new QueryBuilder($db, 'orders');

        $sql = $qb->groupBy(['category', 'type'])->toSql();
        $this->assertStringContainsString('GROUP BY `category`, `type`', $sql);
    }

    public function testFirstReturnsNullAndRow()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        $qb = new QueryBuilder($db, 't');
        $this->assertNull($qb->first());

        $db2 = $this->createMock(Database::class);
        $db2->expects($this->once())
            ->method('fetchAll')
            ->willReturn([['id' => 1, 'name' => 'x']]);

        $qb2 = new QueryBuilder($db2, 't');
        $row = $qb2->first();
        $this->assertIsArray($row);
        $this->assertSame(1, $row['id']);
    }

    public function testFindGeneratesWhereAndLimit()
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())
            ->method('fetchAll')
            ->with($this->callback(function ($sql) {
                return strpos($sql, 'WHERE `id` = :p1') !== false && strpos($sql, 'LIMIT 1') !== false;
            }), $this->callback(function ($bindings) {
                return isset($bindings['p1']) && $bindings['p1'] === 9;
            }))
            ->willReturn([['id' => 9]]);

        $qb = new QueryBuilder($db, 'users');
        $res = $qb->find(9);
        $this->assertIsArray($res);
        $this->assertSame(9, $res['id']);
    }

    public function testInsertEmptyReturnsFalse()
    {
        $db = $this->createMock(Database::class);
        $qb = new QueryBuilder($db, 'users');
        $this->assertFalse($qb->insert([]));
    }

    public function testProtectedHelpersViaReflection()
    {
        $db = $this->createMock(Database::class);
        $qb = new QueryBuilder($db, 't');

        $rc = new \ReflectionClass(QueryBuilder::class);

        $mFormat = $rc->getMethod('formatIdentifierOrRaw');
        $mFormat->setAccessible(true);
        $resRaw = $mFormat->invokeArgs($qb, [new RawExpression('COUNT(*)')]);
        $this->assertSame('COUNT(*)', $resRaw);
        $resIdent = $mFormat->invokeArgs($qb, ['col']);
        $this->assertStringContainsString('`col`', $resIdent);

        $mExtract = $rc->getMethod('extractPlaceholders');
        $mExtract->setAccessible(true);
        $place = $mExtract->invokeArgs($qb, ['SELECT :p1, :p2']);
        $this->assertEquals(['p1', 'p2'], $place);

        $mValidate = $rc->getMethod('validateSqlBindings');
        $mValidate->setAccessible(true);
        $pBindings = $rc->getProperty('bindings');
        $pBindings->setAccessible(true);
        // set a bindings mismatch and ensure validateSqlBindings does not throw
        $pBindings->setValue($qb, ['p1' => 'x', 'p3' => 'y']);
        $mValidate->invokeArgs($qb, ['SELECT :p1, :p2']);
        $this->assertTrue(true); // reached without exception
    }
}
