<?php
/**
 * Base Test Case
 * 
 * คลาสพื้นฐานสำหรับการทดสอบทั้งหมด
 */

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use function tests_reset_doubles;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup ก่อนการทดสอบแต่ละครั้ง
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (function_exists('tests_reset_doubles')) {
            tests_reset_doubles();
        }

        // เริ่ม session สำหรับการทดสอบ
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

    /**
     * Cleanup หลังการทดสอบแต่ละครั้ง
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Assert ว่า array มีคีย์ที่กำหนด
     */
    protected function assertArrayHasKeys(array $keys, array $array): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, "Array does not have key: {$key}");
        }
    }
}
