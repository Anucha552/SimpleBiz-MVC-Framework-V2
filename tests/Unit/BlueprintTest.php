<?php

use PHPUnit\Framework\TestCase;
use App\Core\Blueprint;
use App\Core\ColumnDefinition;
use App\Core\ForeignKeyDefinition;

class BlueprintTest extends TestCase
{
    public function testIncrements()
    {
        $bp = new Blueprint('users');
        $col = $bp->increments();
        $this->assertInstanceOf(ColumnDefinition::class, $col);
        $this->assertEquals('id', $col->getName());
        $this->assertTrue($col->isPrimary());
        $this->assertTrue($col->isAutoIncrement());
    }

    public function testIntegerTypes()
    {
        $bp = new Blueprint('t');
        $int = $bp->integer('age', true, 10);
        $this->assertTrue($int->isNullable());
        $this->assertEquals(10, $int->getDefault());
        $small = $bp->smallInteger('score');
        $this->assertEquals('score', $small->getName());
        $big = $bp->bigInteger('big', false, 99);
        $this->assertEquals(99, $big->getDefault());
        $uint = $bp->unsignedInteger('u', true, 1);
        $this->assertTrue($uint->isUnsigned());
        $ubig = $bp->unsignedBigInteger('ub', false, 2);
        $this->assertTrue($ubig->isUnsigned());
        $tiny = $bp->tinyInteger('flag', false, 0);
        $this->assertEquals(0, $tiny->getDefault());
    }

    public function testStringAndTextTypes()
    {
        $bp = new Blueprint('t');
        $str = $bp->string('name', 100, true, 'a');
        $this->assertEquals('name', $str->getName());
        $this->assertEquals('a', $str->getDefault());
        $this->assertTrue($str->isNullable());
        $text = $bp->text('desc', true);
        $this->assertTrue($text->isNullable());
        $tiny = $bp->tinyText('t', false);
        $this->assertEquals('t', $tiny->getName());
        $med = $bp->mediumText('m', true);
        $this->assertTrue($med->isNullable());
        $long = $bp->longText('l', false);
        $this->assertEquals('l', $long->getName());
        $char = $bp->char('c', 2, true, 'x');
        $this->assertEquals('x', $char->getDefault());
    }

    public function testBooleanDecimalFloatDouble()
    {
        $bp = new Blueprint('t');
        $bool = $bp->boolean('b', true, 1);
        $this->assertEquals(1, $bool->getDefault());
        $this->assertTrue($bool->isNullable());
        $dec = $bp->decimal('d', 8, 3, false, 0.1);
        $this->assertEquals(0.1, $dec->getDefault());
        $float = $bp->float('f', true, 2.2);
        $this->assertEquals(2.2, $float->getDefault());
        $dbl = $bp->double('db', false, 3.3);
        $this->assertEquals(3.3, $dbl->getDefault());
    }

    public function testDateTimeTypes()
    {
        $bp = new Blueprint('t');
        $ts = $bp->timestamp('created', true, 'CURRENT_TIMESTAMP');
        $this->assertEquals('CURRENT_TIMESTAMP', $ts->getDefault());
        $date = $bp->date('d', true, '2020-01-01');
        $this->assertEquals('2020-01-01', $date->getDefault());
        $dt = $bp->dateTime('dt', false, 'CURRENT_TIMESTAMP');
        $this->assertEquals('CURRENT_TIMESTAMP', $dt->getDefault());
        $time = $bp->time('t', true, '00:00:00');
        $this->assertEquals('00:00:00', $time->getDefault());
    }

    public function testJsonUuidBinaryEnum()
    {
        $bp = new Blueprint('t');
        $json = $bp->json('j', true, '{}');
        $this->assertEquals('{}', $json->getDefault());
        $uuid = $bp->uuid('uuid', false, '');
        $this->assertEquals('', $uuid->getDefault());
        $bin = $bp->binary('b', true);
        $this->assertTrue($bin->isNullable());
        $enum = $bp->enum('status', ['a', 'b'], true, 'a');
        $this->assertEquals('a', $enum->getDefault());
    }

    public function testTimestampsAndSoftDeletes()
    {
        $bp = new Blueprint('t');
        $bp->timestamps();
        $cols = (new \ReflectionObject($bp))->getProperty('columns');
        $cols->setAccessible(true);
        $arr = $cols->getValue($bp);
        $this->assertGreaterThanOrEqual(2, count($arr));
        $bp->softDeletes();
        $arr = $cols->getValue($bp);
        $this->assertGreaterThanOrEqual(3, count($arr));
    }

    public function testUniqueIndexPrimary()
    {
        $bp = new Blueprint('t');
        $bp->string('email')->unique();
        $bp->index('name');
        $bp->primary(['id', 'email']);
        $indexes = (new \ReflectionObject($bp))->getProperty('indexes');
        $indexes->setAccessible(true);
        $ix = $indexes->getValue($bp);
        $this->assertNotEmpty($ix);
        $primary = (new \ReflectionObject($bp))->getProperty('primary');
        $primary->setAccessible(true);
        $p = $primary->getValue($bp);
        $this->assertContains('email', $p);
    }

    public function testForeignIdAndForeign()
    {
        $bp = new Blueprint('t');
        $fk = $bp->foreignId('user_id');
        $this->assertInstanceOf(ForeignKeyDefinition::class, $fk);
        $fk2 = $bp->foreign(['col1', 'col2']);
        $this->assertInstanceOf(ForeignKeyDefinition::class, $fk2);
    }

    public function testEscapeName()
    {
        $bp = new Blueprint('t');
        $method = (new \ReflectionObject($bp))->getMethod('escapeName');
        $method->setAccessible(true);
        $this->assertEquals('`col`', $method->invoke($bp, 'col'));
        $this->assertEquals('`a\\`b`', $method->invoke($bp, 'a`b'));
    }

    public function testToCreateSqlAndToAlterAddSql()
    {
        $bp = new Blueprint('t');
        $bp->increments('id');
        $bp->string('name');
        $bp->unique('name');
        $sql = $bp->toCreateSql();
        $this->assertIsString($sql);
        $stmts = $bp->toAlterAddSql();
        $this->assertIsArray($stmts);
        $this->assertNotEmpty($stmts);
    }
}
