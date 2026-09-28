<?php

namespace App\Core;

/**
 * คลาสสำหรับกำหนดเงื่อนไขของพารามิเตอร์ในเส้นทาง
 *
 * จุดประสงค์: เพิ่ม regex constraint ให้ route parameter หลังจากลงทะเบียนเส้นทางแล้ว
 * RouteDefinition ควรใช้กับอะไร: เมื่อคุณต้องการจำกัดรูปแบบค่าที่ URL ของพารามิเตอร์จะรับได้
 *
 * ตัวอย่างการใช้งาน:
 * ```php
 * $router->get('/users/{id}', 'UserController@show')->whereNumber('id');
 * ```
 */
final class RouteDefinition
{
    /**
     * สร้างนิยามสำหรับกำหนดเงื่อนไขของเส้นทาง
     * จุดประสงค์: ผูกนิยามนี้กับ Router, HTTP method และ path ที่ลงทะเบียนไว้
     *
     * @param Router $router Router ที่ใช้จัดการเส้นทาง
     * @param string $method HTTP method ของเส้นทาง เช่น GET หรือ POST
     * @param string $path รูปแบบ path ของเส้นทาง เช่น /users/{id}
     */
    public function __construct(
        private Router $router,
        private string $method,
        private string $path
    ) {
    }

    /**
     * กำหนด regex constraint ให้พารามิเตอร์หนึ่งตัวหรือหลายตัว
     * จุดประสงค์: ให้ Router จับคู่ route เฉพาะเมื่อค่าพารามิเตอร์ตรงกับรูปแบบที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $route->where('slug', '[a-z0-9-]+');
     * $route->where(['id' => '[0-9]+', 'slug' => '[a-z0-9-]+']);
     * ```
     *
     * @param string|array $parameter ชื่อพารามิเตอร์ หรืออาร์เรย์รูปแบบ [ชื่อพารามิเตอร์ => regex]
     * @param string|null $expression regex เมื่อกำหนดพารามิเตอร์เป็น string
     * @return self นิยามเส้นทางเดิมสำหรับ chain method
     * @throws \InvalidArgumentException เมื่อกำหนดพารามิเตอร์เป็น string แต่ไม่ได้ส่ง regex
     */
    public function where(string|array $parameter, ?string $expression = null): self
    {
        if (is_array($parameter)) {
            foreach ($parameter as $name => $pattern) {
                $this->router->setRouteConstraint($this->method, $this->path, (string) $name, $pattern);
            }

            return $this;
        }

        if ($expression === null) {
            throw new \InvalidArgumentException('A regex expression is required');
        }

        $this->router->setRouteConstraint($this->method, $this->path, $parameter, $expression);

        return $this;
    }

    /**
     * จำกัดพารามิเตอร์ให้รับเฉพาะตัวเลข 0-9 อย่างน้อยหนึ่งหลัก
     * @param string $parameter ชื่อพารามิเตอร์ใน path
     * @return self นิยามเส้นทางเดิมสำหรับ chain method
     */
    public function whereNumber(string $parameter): self
    {
        return $this->where($parameter, '[0-9]+');
    }

    /**
     * จำกัดพารามิเตอร์ให้รับเฉพาะตัวอักษรภาษาอังกฤษ
     * @param string $parameter ชื่อพารามิเตอร์ใน path
     * @return self นิยามเส้นทางเดิมสำหรับ chain method
     */
    public function whereAlpha(string $parameter): self
    {
        return $this->where($parameter, '[a-zA-Z]+');
    }

    /**
     * จำกัดพารามิเตอร์ให้รับเฉพาะตัวอักษรภาษาอังกฤษและตัวเลข
     * @param string $parameter ชื่อพารามิเตอร์ใน path
     * @return self นิยามเส้นทางเดิมสำหรับ chain method
     */
    public function whereAlphaNumeric(string $parameter): self
    {
        return $this->where($parameter, '[a-zA-Z0-9]+');
    }

    /**
     * จำกัดพารามิเตอร์ให้มีรูปแบบ UUID 8-4-4-4-12 ตัวอักษร
     * @param string $parameter ชื่อพารามิเตอร์ใน path
     * @return self นิยามเส้นทางเดิมสำหรับ chain method
     */
    public function whereUuid(string $parameter): self
    {
        return $this->where($parameter, '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}');
    }

    /**
     * จำกัดพารามิเตอร์ให้มีรูปแบบ ULID จำนวน 26 ตัวอักษร
     * @param string $parameter ชื่อพารามิเตอร์ใน path
     * @return self นิยามเส้นทางเดิมสำหรับ chain method
     */
    public function whereUlid(string $parameter): self
    {
        return $this->where($parameter, '[0-7][0-9A-HJKMNP-TV-Z]{25}');
    }

    /**
     * จำกัดพารามิเตอร์ให้ตรงกับค่าหนึ่งค่าในรายการที่อนุญาต
     * @param string $parameter ชื่อพารามิเตอร์ใน path
     * @param array $values รายการค่าที่อนุญาต
     * @return self นิยามเส้นทางเดิมสำหรับ chain method
     * @throws \InvalidArgumentException เมื่อรายการค่าที่อนุญาตว่าง
     */
    public function whereIn(string $parameter, array $values): self
    {
        if ($values === []) {
            throw new \InvalidArgumentException('At least one allowed value is required');
        }

        $alternatives = array_map(static fn ($value): string => preg_quote((string) $value, '#'), $values);

        return $this->where($parameter, '(?:' . implode('|', $alternatives) . ')');
    }
}