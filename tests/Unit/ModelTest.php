<?php
/**
 * Model Test
 * 
 * ทดสอบการทำงานพื้นฐานของ Model
 */

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\TestModel;

class ModelTest extends TestCase
{
    public function testModelHasTable(): void
    {
        $model = new TestModel();
        
        $this->assertNotEmpty($model->getTable());
        $this->assertEquals('test_table', $model->getTable());
    }

    public function testModelHasPrimaryKey(): void
    {
        $model = new TestModel();
        
        $this->assertEquals('id', $model->getPrimaryKey());
    }

    public function testModelHasFillable(): void
    {
        $model = new TestModel();
        
        $this->assertIsArray($model->getFillable());
        $this->assertNotEmpty($model->getFillable());
    }

    public function testModelCanSetAndGetAttributes(): void
    {
        $model = new TestModel();
        $model->name = 'Test Name';
        
        $this->assertEquals('Test Name', $model->name);
    }

    public function testModelHasGuarded(): void
    {
        $model = new TestModel();
        
        $this->assertIsArray($model->getGuarded());
        $this->assertContains('id', $model->getGuarded());
    }
}
