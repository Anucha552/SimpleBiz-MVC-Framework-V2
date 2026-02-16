<?php
/**
 * Unit Test สำหรับ DemoUnit
 *
 * จุดประสงค์: ทดสอบฟังก์ชันต่างๆ ใน DemoUnit class
 * สร้างเมื่อ: " . date('Y-m-d H:i:s') . "
 */

namespace Tests\Unit;

use Tests\TestCase;

class DemoUnitTest extends TestCase
{
    /**
     * Setup ก่อนการทดสอบแต่ละครั้ง
     */
    protected function setUp(): void
    {
        parent::setUp();
        // เตรียมข้อมูลที่ต้องใช้
    }

    /**
     * @test
     * ทดสอบว่าระบบทำงานได้ปกติ
     */
    public function it_works()
    {
        $this->assertTrue(true);
    }

    /**
     * @test
     * ตัวอย่างการทดสอบ assertion ต่างๆ
     */
    public function it_demonstrates_assertions()
    {
        // Equality
        $this->assertEquals(4, 2 + 2);
        $this->assertNotEquals(5, 2 + 2);

        // Boolean
        $this->assertTrue(true);
        $this->assertFalse(false);

        // Null
        $this->assertNull(null);
        $this->assertNotNull('value');

        // Array
        $this->assertArrayHasKey('key', ['key' => 'value']);
        $this->assertCount(3, [1, 2, 3]);

        // String
        $this->assertStringContainsString('world', 'hello world');
        $this->assertStringStartsWith('hello', 'hello world');
        $this->assertStringEndsWith('world', 'hello world');
    }

    /**
     * Cleanup หลังการทดสอบแต่ละครั้ง
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        // ทำความสะอาด
    }
}