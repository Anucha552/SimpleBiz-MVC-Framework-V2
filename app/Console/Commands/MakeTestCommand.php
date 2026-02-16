<?php

declare(strict_types=1);

namespace App\Console\Commands;

class MakeTestCommand extends BaseCommand
{
    public function name(): string
    {
        return 'make:test';
    }

    protected function execute(array $args): void
    {
        if (empty($args)) {
            $this->error("กรุณาระบุชื่อ test");
            $this->info("วิธีใช้: php console make:test TestName [--unit|--feature]");
            $this->info("ตัวอย่าง:");
            $this->info("  php console make:test StringHelperTest --unit");
            $this->info("  php console make:test UserRegistrationTest --feature");
            return;
        }

        $name = $args[0];
        if (!str_ends_with($name, 'Test')) {
            $name .= 'Test';
        }

        $isFeature = in_array('--feature', $args, true) || in_array('-f', $args, true);
        $isUnit = in_array('--unit', $args, true) || in_array('-u', $args, true);

        if (!$isFeature && !$isUnit) {
            $isUnit = true;
        }

        $type = $isFeature ? 'Feature' : 'Unit';
        $path = $this->path("tests/{$type}/{$name}.php");

        if (file_exists($path)) {
            $this->error("Test นี้มีอยู่แล้ว: tests/{$type}/{$name}.php");
            return;
        }

        try {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $template = $isFeature ? $this->getFeatureTestTemplate($name) : $this->getUnitTestTemplate($name);

            if (file_put_contents($path, $template) === false) {
                throw new \Exception("ไม่สามารถเขียนไฟล์ได้");
            }

            $this->success("สร้าง {$type} Test สำเร็จ: tests/{$type}/{$name}.php");
            $this->info("รันคำสั่งนี้เพื่อทดสอบ:");
            $this->info("  php console test tests/{$type}/{$name}.php");
            $this->info("  php console test --filter {$name}");
        } catch (\Exception $e) {
            $this->error("เกิดข้อผิดพลาด: " . $e->getMessage());
        }
    }

    private function getUnitTestTemplate(string $name): string
    {
        $className = str_replace('Test', '', $name);

        return <<<PHP
<?php
/**
 * Unit Test สำหรับ {$className}
 *
 * จุดประสงค์: ทดสอบฟังก์ชันต่างๆ ใน {$className} class
 * สร้างเมื่อ: " . date('Y-m-d H:i:s') . "
 */

namespace Tests\Unit;

use Tests\TestCase;

class {$name} extends TestCase
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
        \$this->assertTrue(true);
    }

    /**
     * @test
     * ตัวอย่างการทดสอบ assertion ต่างๆ
     */
    public function it_demonstrates_assertions()
    {
        // Equality
        \$this->assertEquals(4, 2 + 2);
        \$this->assertNotEquals(5, 2 + 2);

        // Boolean
        \$this->assertTrue(true);
        \$this->assertFalse(false);

        // Null
        \$this->assertNull(null);
        \$this->assertNotNull('value');

        // Array
        \$this->assertArrayHasKey('key', ['key' => 'value']);
        \$this->assertCount(3, [1, 2, 3]);

        // String
        \$this->assertStringContainsString('world', 'hello world');
        \$this->assertStringStartsWith('hello', 'hello world');
        \$this->assertStringEndsWith('world', 'hello world');
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
PHP;
    }

    private function getFeatureTestTemplate(string $name): string
    {
        $feature = str_replace('Test', '', $name);

        return <<<PHP
<?php
/**
 * Feature Test สำหรับ {$feature}
 *
 * จุดประสงค์: ทดสอบ flow การทำงานแบบครบวงจร
 * สร้างเมื่อ: " . date('Y-m-d H:i:s') . "
 */

namespace Tests\Feature;

use Tests\TestCase;

class {$name} extends TestCase
{
    /**
     * Setup ก่อนการทดสอบแต่ละครั้ง
     */
    protected function setUp(): void
    {
        parent::setUp();
        // เตรียมข้อมูลที่ต้องใช้
        // เช่น: สร้างผู้ใช้ทดสอบ, เชื่อมต่อฐานข้อมูล
    }

    /**
     * @test
     * ทดสอบว่าระบบทำงานได้ปกติ
     */
    public function it_works()
    {
        \$this->assertTrue(true);
    }

    /**
     * @test
     * ตัวอย่าง Feature test ที่ทดสอบ flow การทำงาน
     */
    public function user_can_complete_workflow()
    {
        // Arrange - เตรียมข้อมูล
        \$testData = [
            'username' => 'testuser_' . time(),
            'email' => 'test_' . time() . '@example.com',
        ];

        // Act - ทำการทดสอบ
        // เช่น: เรียก API, เรียก method ใน Model
        \$result = true; // เปลี่ยนเป็นการเรียกฟังก์ชันจริง

        // Assert - ตรวจสอบผลลัพธ์
        \$this->assertTrue(\$result);
    }

    /**
     * @test
     * ทดสอบกรณีที่เกิด error
     */
    public function it_handles_errors_gracefully()
    {
        // ทดสอบว่าระบบจัดการ error ได้อย่างเหมาะสม
        \$this->assertTrue(true);
    }

    /**
     * Cleanup หลังการทดสอบแต่ละครั้ง
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        // ทำความสะอาด เช่น: ลบข้อมูลทดสอบ
    }
}
PHP;
    }
}
