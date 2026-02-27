<?php
/**
 * QueryBuilder สำหรับสร้างและจัดการคำสั่ง SQL โดยมีฟีเจอร์หลักๆ เช่น การสร้างเงื่อนไข where, การ join ตาราง, การจัดการการลบแบบ soft delete และอื่นๆ
 *
 * จุดประสงค์: เพื่อให้สามารถสร้างคำสั่ง SQL ได้อย่างสะดวกและปลอดภัย โดยใช้วิธีการ binding parameter เพื่อลดความเสี่ยงจาก SQL injection และมีฟีเจอร์ที่ช่วยในการจัดการกับข้อมูลที่ถูกลบแบบ soft delete ได้อย่างง่ายดาย
 * 
 * ตัวอย่างการใช้งาน:
 * ```php
 * $db = App\Core\Database::getInstance();
 * $query = new QueryBuilder($db, 'users');
 * ```
 */
declare(strict_types=1);

namespace App\Core;

use App\Core\Database;
use App\Core\RawExpression;
use App\Core\Logger;

class QueryBuilder
{
    /**
     * db: ตัวแปรที่เก็บ instance ของ Database เพื่อใช้ในการเชื่อมต่อและดำเนินการกับฐานข้อมูล
     */
    protected Database $db;

    /**
     * table: ชื่อตารางในฐานข้อมูลที่ QueryBuilder นี้จะทำงานด้วย
     */
    protected string $table;

    /**
     * logger: ตัวแปรที่เก็บ instance ของ Logger เพื่อใช้ในการบันทึกข้อมูลการดีบักหรือข้อผิดพลาดที่เกิดขึ้นใน QueryBuilder
     */
    protected Logger $logger;

    /**
     * สวิตช์เปิด/ปิดการบันทึกล็อกจาก QueryBuilder
     */
    protected bool $loggingEnabled = true;

    /**
     * columns: รายชื่อคอลัมน์ที่ต้องการเลือกในคำสั่ง SELECT โดยสามารถเป็น array หรือ string ที่คั่นด้วยเครื่องหมายจุลภาค (,) และรองรับการใช้ RawExpression เพื่อใส่คำสั่ง SQL ดิบได้
     */
    protected array|string $columns = ['*'];

    /**
     * wheres: รายการเงื่อนไขที่ใช้ในคำสั่ง WHERE โดยเก็บเป็น array ของ associative array ที่มีคีย์ 'boolean' (เช่น 'AND', 'OR') และ 'sql' (คำสั่ง SQL ของเงื่อนไขนั้น)
     */
    protected array $wheres = [];

    /**
     * joins: รายการการ join ตาราง โดยเก็บเป็น array ของ associative array ที่มีคีย์ 'type' (เช่น 'INNER', 'LEFT') และ 'table' (ชื่อตารางที่ join) และ 'on' (เงื่อนไขการ join)
     */
    protected array $joins = [];

    /**
     * groups: รายการคอลัมน์ที่ใช้ในการจัดกลุ่มข้อมูล (GROUP BY)
     */
    protected array $groups = [];

    /**
     * havings: รายการเงื่อนไขที่ใช้ในคำสั่ง HAVING โดยเก็บเป็น array ของ associative array ที่มีคีย์ 'boolean' (เช่น 'AND', 'OR') และ 'sql' (คำสั่ง SQL ของเงื่อนไขนั้น)
     */
    protected array $havings = [];

    /**
     * orders: รายการคอลัมน์ที่ใช้ในการจัดเรียงข้อมูล (ORDER BY) โดยเก็บเป็น array ของ string ที่มีรูปแบบ "column direction" เช่น "name ASC" หรือ "created_at DESC"
     */
    protected array $orders = [];

    /**
     * limit: จำนวนแถวที่ต้องการดึงมาในคำสั่ง SELECT (LIMIT)
     */
    protected ?int $limit = null;

    /**
     * offset: จำนวนแถวที่ต้องการข้ามในคำสั่ง SELECT (OFFSET)
     */
    protected ?int $offset = null;

    /**
     * bindings: รายการค่าที่ใช้ในการ binding parameter ในคำสั่ง SQL โดยเก็บเป็น associative array ที่มีคีย์เป็นชื่อ placeholder (เช่น 'p1', 'p2') และค่าที่จะถูก bind ไปยัง placeholder นั้น
     */
    protected array $bindings = [];

    /**
     * paramCounter: ตัวนับที่ใช้ในการสร้างชื่อ placeholder สำหรับ binding parameter โดยจะเริ่มต้นที่ 1 และเพิ่มขึ้นทุกครั้งที่มีการสร้าง placeholder ใหม่
     */
    protected int $paramCounter = 1;

    /**
     * Constructor ของ QueryBuilder ที่รับ instance ของ Database และชื่อตารางที่ต้องการทำงานด้วย และสร้าง instance ของ Logger เพื่อใช้ในการบันทึกข้อมูลการดีบักหรือข้อผิดพลาดที่เกิดขึ้นใน QueryBuilder
     * จุดประสงค์: เพื่อให้สามารถสร้าง QueryBuilder ได้อย่างสะดวก โดยการส่ง instance ของ Database และชื่อตารางที่ต้องการทำงานด้วยเข้ามาใน constructor และมี Logger ที่พร้อมใช้งานสำหรับบันทึกข้อมูลการดีบักหรือข้อผิดพลาดที่เกิดขึ้นใน QueryBuilder
     * ตัวอย่างการใช้งาน:
     * ```php
     * $db = App\Core\Database::getInstance();
     * $query = new QueryBuilder($db, 'users');
     * ```
     * 
     * @param Database $db instance ของ Database ที่ใช้ในการเชื่อมต่อและดำเนินการกับฐานข้อมูล
     * @param string $table ชื่อตารางในฐานข้อมูลที่ QueryBuilder นี้จะทำงานด้วย
     * @param Logger|null $logger instance ของ Logger ที่ใช้ในการบันทึกข้อมูลการดีบักหรือข้อผิดพลาดที่เกิดขึ้นใน QueryBuilder (ถ้าไม่ส่งเข้ามาจะสร้าง instance ใหม่)
     * @param bool $enableLogging สวิตช์เปิด/ปิดการบันทึกล็อกจาก QueryBuilder (ค่าเริ่มต้นเป็น true)
     */
    public function __construct(Database $db, string $table, ?Logger $logger = null, bool $enableLogging = true)
    {
        $this->db = $db;
        $this->table = $table;
        $this->logger = $logger ?? new Logger();
        $this->loggingEnabled = $enableLogging;
    }

    /**
     * ฟังก์ชัน select ที่ใช้กำหนดคอลัมน์ที่ต้องการเลือกในคำสั่ง SELECT โดยสามารถรับค่าเป็น array หรือ string ที่คั่นด้วยเครื่องหมายจุลภาค (,) และรองรับการใช้ RawExpression เพื่อใส่คำสั่ง SQL ดิบได้
     * จุดประสงค์: เพื่อให้สามารถกำหนดคอลัมน์ที่ต้องการเลือกในคำสั่ง SELECT ได้อย่างสะดวก โดยสามารถรับค่าเป็น array หรือ string ที่คั่นด้วยเครื่องหมายจุลภาค (,) และรองรับการใช้ RawExpression เพื่อใส่คำสั่ง SQL ดิบได้ เช่น การนับจำนวนแถวด้วย COUNT(*) หรือการใช้ฟังก์ชันอื่นๆ ใน SQL
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->select('id, name, email')->get(); // ผลลัพธ์: SELECT `id`, `name`, `email` FROM `table`
     * $query->select(['id', 'name', 'email'])->get(); // ผลลัพธ์: SELECT `id`, `name`, `email` FROM `table`
     * $query->select(new RawExpression('COUNT(*)'))->get(); // ผลลัพธ์: SELECT COUNT(*) FROM `table`
     * ```
     * 
     * @param array|string|RawExpression $columns คอลัมน์ที่ต้องการเลือก
     * @return static คืนค่า instance ของ QueryBuilder เพื่อให้สามารถเรียกใช้เมธอดอื่นๆ ต่อได้
     */
    public function select($columns = '*'): static
    {
        if ($columns === '*') {
            $this->columns = ['*'];
        } elseif (is_array($columns)) {
            $this->columns = $columns;
        } elseif ($columns instanceof RawExpression) {
            $this->columns = [$columns];
        } else {
            // แยก string ที่คั่นด้วยเครื่องหมายจุลภาคและ trim แต่ละคอลัมน์
            $this->columns = array_map('trim', explode(',', (string)$columns));
        }
        return $this;
    }

    /**
     * ฟังก์ชัน where ที่ใช้สร้างเงื่อนไขในคำสั่ง WHERE โดยสามารถรับค่าเป็น callable เพื่อสร้างเงื่อนไขแบบ nested หรือรับค่าเป็น column, operator, value เพื่อสร้างเงื่อนไขแบบปกติ และรองรับการจัดการกับค่า NULL ได้อย่างถูกต้อง
     * จุดประสงค์: เพื่อให้สามารถสร้างเงื่อนไขในคำสั่ง WHERE ได้อย่างยืดหยุ่น โดยสามารถใช้ callable เพื่อสร้างเงื่อนไขแบบ nested หรือใช้ column, operator, value เพื่อสร้างเงื่อนไขแบบปกติ และรองรับการจัดการกับค่า NULL ได้อย่างถูกต้อง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->where('id', '=', 1)->get(); // ผลลัพธ์: SELECT * FROM `table` WHERE `id` = 1
     * $query->where(function($q) {
     *     $q->where('id', '=', 1)->orWhere('name', '=', 'John');
     * })->get(); // ผลลัพธ์: SELECT * FROM `table` WHERE (`id` = 1 OR `name` = 'John')
     * ```
     * 
     * @param callable|string $column ชื่อคอลัมน์หรือ callable สำหรับสร้างเงื่อนไขแบบ nested
     * @param string|null $operator ตัวดำเนินการเปรียบเทียบ (เช่น '=', '!=', '<', '>')
     * @param mixed $value ค่าที่ใช้ในการเปรียบเทียบ
     * @return static คืนค่า instance ของ QueryBuilder เพื่อให้สามารถเรียกใช้เมธอดอื่นๆ ต่อได้
     */
    public function where(callable|string $column, ?string $operator = null, mixed $value = null): static
    {
        // ถ้า $column เป็น callable ให้สร้างเงื่อนไขแบบ nested โดยสร้าง instance ใหม่ของ QueryBuilder และส่งต่อไปยัง callable นั้น จากนั้นนำเงื่อนไขที่ได้จาก nested มารวมกับเงื่อนไขหลักโดยใช้ AND
        if (is_callable($column)) {
            $nested = new static($this->db, $this->table, $this->logger, $this->loggingEnabled);
            // ส่งต่อ paramCounter ไปยัง nested เพื่อให้สามารถสร้าง placeholder ได้อย่างต่อเนื่อง
            $nested->paramCounter = $this->paramCounter;
            $column($nested);
            $nestedWhere = $nested->buildWhereClause();
            if ($nestedWhere === '') {
                return $this;
            }
            // รวม bindings จาก nested ไปยัง bindings หลัก
            foreach ($nested->bindings as $k => $v) {
                $this->bindings[$k] = $v;
            }
            $this->paramCounter = $nested->paramCounter;
            $this->wheres[] = ['boolean' => 'AND', 'sql' => '(' . $nestedWhere . ')'];
            return $this;
        }

        // ถ้า $column เป็น string ให้สร้างเงื่อนไขแบบปกติ
        $col = $column;

        // ถ้าผู้เรียกใช้ส่งมาเป็น where('col', 'value') (operator ถูกละไว้)
        // ให้ถือว่า operator เป็น '=' และ value คือ $operator
        if ($value === null && $operator !== null) {
            $op = '=';
            $value = $operator;
        } else {
            $op = $operator ?? '=';
        }

        // จัดการกับค่า NULL โดยถ้า value เป็น NULL และ operator เป็น '=' ให้สร้างเงื่อนไข IS NULL
        // และถ้า operator เป็น '!=' หรือ '<>' ให้สร้างเงื่อนไข IS NOT NULL
        if ($value === null) {
            if ($op === '=') {
                $sql = $this->formatIdentifierOrRaw($col) . ' IS NULL';
                $this->wheres[] = ['boolean' => 'AND', 'sql' => $sql];
                return $this;
            }
            if ($op === '!=' || $op === '<>') {
                $sql = $this->formatIdentifierOrRaw($col) . ' IS NOT NULL';
                $this->wheres[] = ['boolean' => 'AND', 'sql' => $sql];
                return $this;
            }
        }

        // การสร้าง binding ปกติ
        $placeholder = $this->makePlaceholder();
        $left = $this->formatIdentifierOrRaw($col);
        $this->wheres[] = ['boolean' => 'AND', 'sql' => sprintf('%s %s :%s', $left, $op, $placeholder)];
        $this->bindings[$placeholder] = $value;
        return $this;
    }

    /**
     * ฟังก์ชัน orWhere ที่ใช้สร้างเงื่อนไขในคำสั่ง WHERE โดยใช้ OR แทน AND โดยสามารถรับค่าเป็น callable เพื่อสร้างเงื่อนไขแบบ nested หรือรับค่าเป็น column, operator, value เพื่อสร้างเงื่อนไขแบบปกติ และรองรับการจัดการกับค่า NULL ได้อย่างถูกต้อง
     * จุดประสงค์: เพื่อให้สามารถสร้างเงื่อนไขในคำสั่ง WHERE ได้อย่างยืดหยุ่น โดยสามารถใช้ callable เพื่อสร้างเงื่อนไขแบบ nested หรือใช้ column, operator, value เพื่อสร้างเงื่อนไขแบบปกติ และรองรับการจัดการกับค่า NULL ได้อย่างถูกต้อง โดยใช้ OR แทน AND ในการเชื่อมโยงเงื่อนไข
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->orWhere('id', '=', 1)->get(); // ผลลัพธ์: SELECT * FROM `table` WHERE `id` = 1
     * $query->orWhere(function($q) {
     *     $q->where('id', '=', 1)->orWhere('name', '=', 'John');
     * })->get(); // ผลลัพธ์: SELECT * FROM `table` WHERE (`id` = 1 OR `name` = 'John')
     * ```
     * 
     * @param callable|string $column ชื่อคอลัมน์หรือ callable สำหรับสร้างเงื่อนไขแบบ nested
     * @param string|null $operator ตัวดำเนินการเปรียบเทียบ (เช่น '=', '!=', '<', '>')
     * @param mixed $value ค่าที่ใช้ในการเปรียบเทียบ
     * @return static คืนค่า instance ของ QueryBuilder เพื่อให้สามารถเรียกใช้เมธอดอื่นๆ ต่อได้
     */
    public function orWhere(callable|string $column, ?string $operator = null, mixed $value = null): static
    {
        // รองรับ nested callable เหมือน where(), แต่ใช้ OR เป็นตัวเชื่อม
        if (is_callable($column)) {
            $nested = new static($this->db, $this->table, $this->logger, $this->loggingEnabled);
            $nested->paramCounter = $this->paramCounter;
            $column($nested);
            $nestedWhere = $nested->buildWhereClause();
            if ($nestedWhere === '') {
                return $this;
            }
            foreach ($nested->bindings as $k => $v) {
                $this->bindings[$k] = $v;
            }
            $this->paramCounter = $nested->paramCounter;
            $this->wheres[] = ['boolean' => 'OR', 'sql' => '(' . $nestedWhere . ')'];
            return $this;
        }

        $col = $column;

        // where('col', 'value') -> operator '=' and value = $operator
        if ($value === null && $operator !== null) {
            $op = '=';
            $value = $operator;
        } else {
            $op = $operator ?? '=';
        }

        // NULL handling for OR
        if ($value === null) {
            if ($op === '=') {
                $sql = $this->formatIdentifierOrRaw($col) . ' IS NULL';
                $this->wheres[] = ['boolean' => 'OR', 'sql' => $sql];
                return $this;
            }
            if ($op === '!=' || $op === '<>') {
                $sql = $this->formatIdentifierOrRaw($col) . ' IS NOT NULL';
                $this->wheres[] = ['boolean' => 'OR', 'sql' => $sql];
                return $this;
            }
        }

        $placeholder = $this->makePlaceholder();
        $left = $this->formatIdentifierOrRaw($col);
        $this->wheres[] = ['boolean' => 'OR', 'sql' => sprintf('%s %s :%s', $left, $op, $placeholder)];
        $this->bindings[$placeholder] = $value;
        return $this;
    }

    /** ฟังก์ชัน whereIn ที่ใช้สร้างเงื่อนไขในคำสั่ง WHERE โดยใช้ IN แทนการเปรียบเทียบปกติ โดยรับค่าเป็น column และ array ของ values ที่ต้องการตรวจสอบ และรองรับการจัดการกับ array ว่างได้อย่างถูกต้อง
     * จุดประสงค์: เพื่อให้สามารถสร้างเงื่อนไขในคำสั่ง WHERE ได้อย่างสะดวก โดยการใช้ IN แทนการเปรียบเทียบปกติ โดยรับค่าเป็น column และ array ของ values ที่ต้องการตรวจสอบ และรองรับการจัดการกับ array ว่างได้อย่างถูกต้อง โดยถ้า array ว่างจะสร้างเงื่อนไขที่เป็น false เสมอ เช่น $query->whereIn('id', [1, 2, 3])->get() หรือ $query->whereIn('id', [])->get()
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->whereIn('id', [1, 2, 3])->get(); // ผลลัพธ์: SELECT * FROM `table` WHERE `id` IN (1, 2, 3)
     * $query->whereIn('id', [])->get(); // ผลลัพธ์: SELECT * FROM `table` WHERE 0 = 1 (เงื่อนไขที่เป็น false เสมอ)
     * ```
     * 
     * @param string $column ชื่อคอลัมน์ที่ต้องการตรวจสอบ
     * @param array $values array ของค่าที่ต้องการตรวจสอบ
     * @return static คืนค่า instance ของ QueryBuilder เพื่อให้สามารถเรียกใช้เมธอดอื่นๆ ต่อได้
     */
    public function whereIn(string $column, array $values): static
    {
        if (empty($values)) {
            // no values => always-false condition
            $this->wheres[] = ['boolean' => 'AND', 'sql' => '0 = 1'];
            return $this;
        }
        $placeholders = [];
        foreach ($values as $v) {
            $ph = $this->makePlaceholder();
            $placeholders[] = ":$ph";
            $this->bindings[$ph] = $v;
        }
        $this->wheres[] = ['boolean' => 'AND', 'sql' => sprintf("%s IN (%s)", $this->formatIdentifierOrRaw($column), implode(', ', $placeholders))];
        return $this;
    }

    /** ฟังก์ชัน whereNull ที่ใช้สร้างเงื่อนไขในคำสั่ง WHERE โดยตรวจสอบว่า column มีค่าเป็น NULL หรือไม่ โดยจะสร้างเงื่อนไข SQL ที่ใช้ IS NULL และเพิ่มเข้าไปในรายการเงื่อนไขของ QueryBuilder
     * จุดประสงค์: เพื่อให้สามารถสร้างเงื่อนไขในคำสั่ง WHERE ได้อย่างสะดวก โดยการตรวจสอบว่า column มีค่าเป็น NULL หรือไม่ โดยจะสร้างเงื่อนไข SQL ที่ใช้ IS NULL และเพิ่มเข้าไปในรายการเงื่อนไขของ QueryBuilder เช่น $query->whereNull('deleted_at')->get()
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->whereNull('deleted_at')->get(); // ผลลัพธ์: SELECT * FROM `table` WHERE `deleted_at` IS NULL
     * ```
     * 
     * @param string $column ชื่อคอลัมน์ที่ต้องการตรวจสอบว่าเป็น NULL หรือไม่
     * @return static คืนค่า instance ของ QueryBuilder เพื่อให้สามารถเรียกใช้เมธอดอื่นๆ ต่อได้
     */
    public function whereNull(string $column): static
    {
        $this->wheres[] = ['boolean' => 'AND', 'sql' => $this->formatIdentifierOrRaw($column) . " IS NULL"];
        return $this;
    }

    /** 
     * ฟังก์ชัน whereNotNull ที่ใช้สร้างเงื่อนไขในคำสั่ง WHERE โดยตรวจสอบว่า column ไม่มีค่าเป็น NULL หรือไม่ โดยจะสร้างเงื่อนไข SQL ที่ใช้ IS NOT NULL และเพิ่มเข้าไปในรายการเงื่อนไขของ QueryBuilder
     * จุดประสงค์: เพื่อให้สามารถสร้างเงื่อนไขในคำสั่ง WHERE ได้อย่างสะดวก โดยการตรวจสอบว่า column ไม่มีค่าเป็น NULL หรือไม่ โดยจะสร้างเงื่อนไข SQL ที่ใช้ IS NOT NULL และเพิ่มเข้าไปในรายการเงื่อนไขของ QueryBuilder เช่น $query->whereNotNull('deleted_at')->get()
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->whereNotNull('deleted_at')->get(); // ผลลัพธ์: SELECT * FROM `table` WHERE `deleted_at` IS NOT NULL
     * ```
     * 
     * @param string $column ชื่อคอลัมน์ที่ต้องการตรวจสอบว่าไม่มีค่าเป็น NULL หรือไม่
     * @return static คืนค่า instance ของ QueryBuilder เพื่อให้สามารถเรียกใช้เมธอดอื่นๆ ต่อได้
     */
    public function whereNotNull(string $column): static
    {
        $this->wheres[] = ['boolean' => 'AND', 'sql' => $this->formatIdentifierOrRaw($column) . " IS NOT NULL"];
        return $this;
    }

    /**
     * ฟังก์ชัน join ที่ใช้สร้างการ join ตารางในคำสั่ง SQL โดยรับค่าเป็นชื่อ table ที่ต้องการ join และเงื่อนไขการ join ที่ประกอบด้วย column แรก, operator, และ column ที่สอง โดยจะสร้างคำสั่ง SQL สำหรับการ join และเพิ่มเข้าไปในรายการ joins ของ QueryBuilder
     * จุดประสงค์: เพื่อให้สามารถสร้างการ join ตารางในคำสั่ง SQL ได้อย่างสะดวก โดยการรับค่าเป็นชื่อ table ที่ต้องการ join และเงื่อนไขการ join ที่ประกอบด้วย column แรก, operator, และ column ที่สอง โดยจะสร้างคำสั่ง SQL สำหรับการ join และเพิ่มเข้าไปในรายการ joins ของ QueryBuilder เช่น $query->join('posts', 'users.id', '=', 'posts.user_id')->get()
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->join('posts', 'users.id', '=', 'posts.user_id')->get(); // ผลลัพธ์: SELECT * FROM `users` JOIN `posts` ON `users`.`id` = `posts`.`user_id`
     * ```
     * 
     * @param string $table ชื่อ table ที่ต้องการ join
     * @param string $first column แรกในเงื่อนไขการ join
     * @param string $operator ตัวดำเนินการในการเปรียบเทียบ (เช่น '=', '<>', '>', '<', '>=', '<=')
     * @param string $second column ที่สองในเงื่อนไขการ join
     * @return static คืนค่า instance ของ QueryBuilder เพื่อให้สามารถเรียกใช้เมธอดอื่นๆ ต่อได้
     */
    public function join(string $table, string $first, string $operator, string $second): static
    {
        $tbl = $this->escapeIdentifier($table);
        $firstSql = $this->formatIdentifierOrRaw($first);
        $secondSql = $this->formatIdentifierOrRaw($second);
        $this->joins[] = ['type' => 'INNER', 'sql' => "JOIN $tbl ON $firstSql $operator $secondSql"];
        return $this;
    }

    /**
     * ฟังก์ชัน leftJoin ที่ใช้สร้างการ join ตารางแบบ LEFT JOIN ในคำสั่ง SQL โดยรับค่าเป็นชื่อ table ที่ต้องการ join และเงื่อนไขการ join ที่ประกอบด้วย column แรก, operator, และ column ที่สอง โดยจะสร้างคำสั่ง SQL สำหรับการ join แบบ LEFT JOIN และเพิ่มเข้าไปในรายการ joins ของ QueryBuilder
     * จุดประสงค์: เพื่อให้สามารถสร้างการ join ตารางแบบ LEFT JOIN ในคำสั่ง SQL ได้อย่างสะดวก โดยการรับค่าเป็นชื่อ table ที่ต้องการ join และเงื่อนไขการ join ที่ประกอบด้วย column แรก, operator, และ column ที่สอง โดยจะสร้างคำสั่ง SQL สำหรับการ join แบบ LEFT JOIN และเพิ่มเข้าไปในรายการ joins ของ QueryBuilder เช่น $query->leftJoin('posts', 'users.id', '=', 'posts.user_id')->get()
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->leftJoin('posts', 'users.id', '=', 'posts.user_id')->get(); // ผลลัพธ์: SELECT * FROM `users` LEFT JOIN `posts` ON `users`.`id` = `posts`.`user_id`
     * ```
     * 
     * @param string $table ชื่อ table ที่ต้องการ join
     * @param string $first column แรกในเงื่อนไขการ join
     * @param string $operator ตัวดำเนินการในการเปรียบเทียบ (เช่น '=', '<>', '>', '<', '>=', '<=')
     * @param string $second column ที่สองในเงื่อนไขการ join
     * @return static คืนค่า instance ของ QueryBuilder เพื่อให้สามารถเรียกใช้เมธอดอื่นๆ ต่อได้
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        $tbl = $this->escapeIdentifier($table);
        $firstSql = $this->formatIdentifierOrRaw($first);
        $secondSql = $this->formatIdentifierOrRaw($second);
        $this->joins[] = ['type' => 'LEFT', 'sql' => "LEFT JOIN $tbl ON $firstSql $operator $secondSql"];
        return $this;
    }

    /**
     * ฟังก์ชัน groupBy ที่ใช้กำหนดคอลัมน์ที่ต้องการจัดกลุ่มข้อมูลในคำสั่ง SQL โดยรับค่าเป็น array หรือ string ที่คั่นด้วยเครื่องหมายจุลภาค (,) และเพิ่มเข้าไปในรายการ groups ของ QueryBuilder
     * จุดประสงค์: เพื่อให้สามารถกำหนดคอลัมน์ที่ต้องการจัดกลุ่มข้อมูลในคำสั่ง SQL ได้อย่างสะดวก โดยการรับค่าเป็น array หรือ string ที่คั่นด้วยเครื่องหมายจุลภาค (,) และเพิ่มเข้าไปในรายการ groups ของ QueryBuilder เช่น $query->groupBy('category')->get() หรือ $query->groupBy('category, type')->get()
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->groupBy('category')->get(); // ผลลัพธ์: SELECT * FROM `table` GROUP BY `category`
     * $query->groupBy(['category', 'type'])->get(); // ผลลัพธ์: SELECT * FROM `table` GROUP BY `category`, `type`
     * ```
     * 
     * @param array|string $columns รายชื่อคอลัมน์ที่ต้องการจัดกลุ่มข้อมูลในคำสั่ง SQL โดยสามารถเป็น array หรือ string ที่คั่นด้วยเครื่องหมายจุลภาค (,)
     * @return static คืนค่า instance ของ QueryBuilder เพื่อให้สามารถเรียกใช้เมธอดอื่นๆ ต่อได้
     */
    public function groupBy(array|string $columns): static
    {
        $this->groups = is_array($columns) ? $columns : explode(',', (string)$columns);
        return $this;
    }

    /** 
     * ฟังก์ชัน having ที่ใช้สร้างเงื่อนไขในคำสั่ง HAVING โดยรับค่าเป็น column, operator, value และรองรับการจัดการกับค่า NULL ได้อย่างถูกต้อง โดยจะสร้างคำสั่ง SQL สำหรับเงื่อนไข HAVING และเพิ่มเข้าไปในรายการ havings ของ QueryBuilder
     * จุดประสงค์: เพื่อให้สามารถสร้างเงื่อนไขในคำสั่ง HAVING ได้อย่างสะดวก โดยการรับค่าเป็น column, operator, value และรองรับการจัดการกับค่า NULL ได้อย่างถูกต้อง โดยจะสร้างคำสั่ง SQL สำหรับเงื่อนไข HAVING และเพิ่มเข้าไปในรายการ havings ของ QueryBuilder เช่น $query->having('total', '>', 100)->get() หรือ $query->having('total', '=', null)->get()
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->having('total', '>', 100)->get(); // ผลลัพธ์: SELECT * FROM `table` HAVING `total` > 100
     * $query->having('total', '=', null)->get(); // ผลลัพธ์: SELECT * FROM `table` HAVING `total` IS NULL
     * ```
     * 
     * @param string $column ชื่อคอลัมน์ที่ต้องการตรวจสอบในเงื่อนไข HAVING
     * @param string $operator ตัวดำเนินการในการเปรียบเทียบ (เช่น '=', '!=', '<', '>')
     * @param mixed $value ค่าที่ใช้ในการเปรียบเทียบ
     * @return static คืนค่า instance ของ QueryBuilder เพื่อให้สามารถเรียกใช้เมธอดอื่นๆ ต่อได้
     */
    public function having(string $column, string $operator, mixed $value): static
    {
        $placeholder = $this->makePlaceholder();
        $left = $this->formatIdentifierOrRaw($column);
        $this->havings[] = ['boolean' => 'AND', 'sql' => sprintf('%s %s :%s', $left, $operator, $placeholder)];
        $this->bindings[$placeholder] = $value;
        return $this;
    }

    /**
     * ฟังก์ชัน orHaving ที่ใช้สร้างเงื่อนไขในคำสั่ง HAVING โดยใช้ OR แทน AND
     * รองรับการจัดการกับค่า NULL เช่นเดียวกับ where/orWhere
     * จุดประสงค์: เพื่อให้สามารถสร้างเงื่อนไขในคำสั่ง HAVING ได้อย่างสะดวก โดยการใช้ OR แทน AND และรองรับการจัดการกับค่า NULL เช่นเดียวกับ where/orWhere โดยจะสร้างคำสั่ง SQL สำหรับเงื่อนไข HAVING และเพิ่มเข้าไปในรายการ havings ของ QueryBuilder เช่น $query->orHaving('total', '>', 100)->get() หรือ $query->orHaving('total', '=', null)->get()
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->orHaving('total', '>', 100)->get(); // ผลลัพธ์: SELECT * FROM `table` HAVING `total` > 100
     * $query->orHaving('total', '=', null)->get(); // ผลลัพธ์: SELECT * FROM `table` HAVING `total` IS NULL
     * ```
     * 
     * @param string $column ชื่อคอลัมน์ที่ต้องการตรวจสอบในเงื่อนไข HAVING
     * @param string $operator ตัวดำเนินการในการเปรียบเทียบ (เช่น '=', '!=', '<', '>')
     * @param mixed $value ค่าที่ใช้ในการเปรียบเทียบ
     * @return static คืนค่า instance ของ QueryBuilder เพื่อให้สามารถเรียกใช้เมธอดอื่นๆ ต่อได้
     */
    public function orHaving(string $column, string $operator, mixed $value): static
    {
        // NULL handling
        if ($value === null) {
            if ($operator === '=') {
                $sql = $this->formatIdentifierOrRaw($column) . ' IS NULL';
                $this->havings[] = ['boolean' => 'OR', 'sql' => $sql];
                return $this;
            }
            if ($operator === '!=' || $operator === '<>') {
                $sql = $this->formatIdentifierOrRaw($column) . ' IS NOT NULL';
                $this->havings[] = ['boolean' => 'OR', 'sql' => $sql];
                return $this;
            }
        }

        $placeholder = $this->makePlaceholder();
        $left = $this->formatIdentifierOrRaw($column);
        $this->havings[] = ['boolean' => 'OR', 'sql' => sprintf('%s %s :%s', $left, $operator, $placeholder)];
        $this->bindings[$placeholder] = $value;
        return $this;
    }

    /**
     * ฟังก์ชัน orderBy ที่ใช้กำหนดคอลัมน์ที่ต้องการจัดเรียงข้อมูลในคำสั่ง SQL โดยรับค่าเป็น column และ direction (ASC หรือ DESC) และเพิ่มเข้าไปในรายการ orders ของ QueryBuilder
     * จุดประสงค์: เพื่อให้สามารถกำหนดคอลัมน์ที่ต้องการจัดเรียงข้อมูลในคำสั่ง SQL ได้อย่างสะดวก โดยการรับค่าเป็น column และ direction (ASC หรือ DESC) และเพิ่มเข้าไปในรายการ orders ของ QueryBuilder เช่น $query->orderBy('name', 'ASC')->get() หรือ $query->orderBy('created_at', 'DESC')->get()
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->orderBy('name', 'ASC')->get(); // ผลลัพธ์: SELECT * FROM `table` ORDER BY `name` ASC
     * $query->orderBy('created_at', 'DESC')->get(); // ผลลัพธ์: SELECT * FROM `table` ORDER BY `created_at` DESC
     * ```
     * 
     * @param string $column ชื่อคอลัมน์ที่ต้องการจัดเรียงข้อมูลในคำสั่ง SQL
     * @param string $direction ทิศทางในการจัดเรียงข้อมูล (ASC หรือ DESC)
     * @return static คืนค่า instance ของ QueryBuilder เพื่อให้สามารถเรียกใช้เมธอดอื่นๆ ต่อได้
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = $this->formatIdentifierOrRaw($column) . " $direction";
        return $this;
    }

    /**
     * ฟังก์ชัน limit ที่ใช้กำหนดจำนวนแถวที่ต้องการดึงมาในคำสั่ง SELECT โดยรับค่าเป็นจำนวนแถวที่ต้องการ และเพิ่มเข้าไปในตัวแปร limit ของ QueryBuilder
     * จุดประสงค์: เพื่อให้สามารถกำหนดจำนวนแถวที่ต้องการดึงมาในคำสั่ง SELECT ได้อย่างสะดวก โดยการรับค่าเป็นจำนวนแถวที่ต้องการ และเพิ่มเข้าไปในตัวแปร limit ของ QueryBuilder เช่น $query->limit(10)->get()
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->limit(10)->get(); // ผลลัพธ์: SELECT * FROM `table` LIMIT 10
     * ```
     * 
     * @param int $limit จำนวนแถวที่ต้องการดึงมาในคำสั่ง SELECT
     * @return static คืนค่า instance ของ QueryBuilder เพื่อให้สามารถเรียกใช้เมธอดอื่นๆ ต่อได้
     */
    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * ฟังก์ชัน offset ที่ใช้กำหนดจำนวนแถวที่ต้องการข้ามในคำสั่ง SELECT โดยรับค่าเป็นจำนวนแถวที่ต้องการข้าม และเพิ่มเข้าไปในตัวแปร offset ของ QueryBuilder
     * จุดประสงค์: เพื่อให้สามารถกำหนดจำนวนแถวที่ต้องการข้ามในคำสั่ง SELECT ได้อย่างสะดวก โดยการรับค่าเป็นจำนวนแถวที่ต้องการข้าม และเพิ่มเข้าไปในตัวแปร offset ของ QueryBuilder เช่น $query->offset(20)->get()
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->offset(20)->get(); // ผลลัพธ์: SELECT * FROM `table` OFFSET 20
     * ```
     * 
     * @param int $offset จำนวนแถวที่ต้องการข้ามในคำสั่ง SELECT
     * @return static คืนค่า instance ของ QueryBuilder เพื่อให้สามารถเรียกใช้เมธอดอื่นๆ ต่อได้
     */
    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * ฟังก์ชัน get ที่ใช้ดึงข้อมูลจากฐานข้อมูลตามเงื่อนไขที่กำหนดใน QueryBuilder โดยจะสร้างคำสั่ง SQL จากเงื่อนไขต่างๆ ที่ถูกกำหนดไว้ และใช้ Database instance เพื่อทำการ query ข้อมูล จากนั้นจะคืนค่าผลลัพธ์เป็น array ของแถวข้อมูลที่ได้จากฐานข้อมูล
     * จุดประสงค์: เพื่อให้สามารถดึงข้อมูลจากฐานข้อมูลตามเงื่อนไขที่กำหนดใน QueryBuilder ได้อย่างสะดวก โดยการเรียกใช้เมธอด get() ซึ่งจะสร้างคำสั่ง SQL จากเงื่อนไขต่างๆ ที่ถูกกำหนดไว้ และใช้ Database instance เพื่อทำการ query ข้อมูล จากนั้นจะคืนค่าผลลัพธ์เป็น array ของแถวข้อมูลที่ได้จากฐานข้อมูล เช่น $query->where('id', '>', 10)->get()
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->where('id', '>', 10)->get(); // ผลลัพธ์: SELECT * FROM `table` WHERE `id` > 10
     * ```
     * 
     * @return array คืนค่าผลลัพธ์เป็น array ของแถวข้อมูลที่ได้จากฐานข้อมูล
     */
    public function get(): array
    {
        $sql = $this->toSql();
        // บันทึกการทำงานของคำสั่ง SQL (เฉพาะกรณีที่เปิดใช้งาน)

        // validate placeholders vs bindings and execute
        $this->validateSqlBindings($sql);
        try {
            $result = $this->db->fetchAll($sql, $this->bindings) ?: [];
        } catch (\Throwable $e) {
            $this->clear();
            throw $e;
        }
        $this->clear();
        return $result;
    }

    /**
     * ฟังก์ชัน first ที่ใช้ดึงข้อมูลแถวแรกจากฐานข้อมูลตามเงื่อนไขที่กำหนดใน QueryBuilder โดยจะเรียกใช้เมธอด limit(1) เพื่อจำกัดผลลัพธ์ให้มีเพียงแถวเดียว และเรียกใช้เมธอด get() เพื่อดึงข้อมูล จากนั้นจะคืนค่าผลลัพธ์เป็นแถวข้อมูลแรกที่ได้จากฐานข้อมูล หรือ null ถ้าไม่มีข้อมูล
     * จุดประสงค์: เพื่อให้สามารถดึงข้อมูลแถวแรกจากฐานข้อมูลตามเงื่อนไขที่กำหนดใน QueryBuilder ได้อย่างสะดวก โดยการเรียกใช้เมธอด first() ซึ่งจะเรียกใช้เมธอด limit(1) เพื่อจำกัดผลลัพธ์ให้มีเพียงแถวเดียว และเรียกใช้เมธอด get() เพื่อดึงข้อมูล จากนั้นจะคืนค่าผลลัพธ์เป็นแถวข้อมูลแรกที่ได้จากฐานข้อมูล หรือ null ถ้าไม่มีข้อมูล เช่น $query->where('id', '>', 10)->first()
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->where('id', '>', 10)->first(); // ผลลัพธ์: SELECT * FROM `table` WHERE `id` > 10 LIMIT 1
     * ```
     * 
     * @return array|null คืนค่าผลลัพธ์เป็นแถวข้อมูลแรกที่ได้จากฐานข้อมูล หรือ null ถ้าไม่มีข้อมูล
     */
    public function first(): ?array
    {
        $this->limit(1);
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    /**
     * ฟังก์ชัน find ที่ใช้ดึงข้อมูลแถวเดียวจากฐานข้อมูลตามค่า id โดยจะสร้างเงื่อนไข where สำหรับคอลัมน์ id และเรียกใช้เมธอด first() เพื่อดึงข้อมูล จากนั้นจะคืนค่าผลลัพธ์เป็นแถวข้อมูลที่มี id ตรงกับค่าที่กำหนด หรือ null ถ้าไม่มีข้อมูล
     * จุดประสงค์: เพื่อให้สามารถดึงข้อมูลแถวเดียวจากฐานข้อมูลตามค่า id ได้อย่างสะดวก โดยการเรียกใช้เมธอด find() ซึ่งจะสร้างเงื่อนไข where สำหรับคอลัมน์ id และเรียกใช้เมธอด first() เพื่อดึงข้อมูล จากนั้นจะคืนค่าผลลัพธ์เป็นแถวข้อมูลที่มี id ตรงกับค่าที่กำหนด หรือ null ถ้าไม่มีข้อมูล เช่น $query->find(5)
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->find(5); // ผลลัพธ์: SELECT * FROM `table` WHERE `id` = 5 LIMIT 1
     * ```
     * 
     * @param int $id ค่าของ id ที่ต้องการค้นหา
     * @return array|null คืนค่าผลลัพธ์เป็นแถวข้อมูลที่มี id ตรงกับค่าที่กำหนด หรือ null ถ้าไม่มีข้อมูล
     */
    public function find(int $id): ?array
    {
        $this->where('id', '=', $id);
        return $this->first();
    }

    /**
     * ฟังก์ชัน insert ที่ใช้แทรกข้อมูลใหม่ลงในฐานข้อมูล โดยรับค่าเป็น array ของข้อมูลที่ต้องการแทรก โดยจะสร้างคำสั่ง SQL สำหรับการแทรกข้อมูล และใช้ Database instance เพื่อดำเนินการแทรกข้อมูล จากนั้นจะคืนค่าผลลัพธ์เป็น boolean ที่บอกว่าการแทรกข้อมูลสำเร็จหรือไม่
     * จุดประสงค์: เพื่อให้สามารถแทรกข้อมูลใหม่ลงในฐานข้อมูลได้อย่างสะดวก โดยการเรียกใช้เมธอด insert() ซึ่งจะรับค่าเป็น array ของข้อมูลที่ต้องการแทรก โดยจะสร้างคำสั่ง SQL สำหรับการแทรกข้อมูล และใช้ Database instance เพื่อดำเนินการแทรกข้อมูล จากนั้นจะคืนค่าผลลัพธ์เป็น boolean ที่บอกว่าการแทรกข้อมูลสำเร็จหรือไม่ เช่น $query->insert(['name' => 'John', 'email' => 'john@example.com'])
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->insert(['name' => 'John', 'email' => 'john@example.com']); // ผลลัพธ์: INSERT INTO `table` (`name`, `email`) VALUES ('John', 'john@example.com')
     * ```
     * 
     * @param array $data ข้อมูลที่ต้องการแทรก
     * @return bool คืนค่าผลลัพธ์เป็น boolean ที่บอกว่าการแทรกข้อมูลสำเร็จหรือไม่
     */
    public function insert(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $cols = array_keys($data);
        $escapedCols = array_map([$this, 'escapeIdentifier'], $cols);
        $placeholders = [];
        foreach ($data as $val) {
            if ($val instanceof RawExpression) {
                $placeholders[] = $val->getValue();
                continue;
            }
            $ph = $this->makePlaceholder();
            $placeholders[] = ":$ph";
            $this->bindings[$ph] = $val;
        }
        $sql = sprintf("INSERT INTO %s (%s) VALUES (%s)", $this->escapeIdentifier($this->table), implode(', ', $escapedCols), implode(', ', $placeholders));

        // validate and execute with logging on error
        $this->validateSqlBindings($sql);
        try {
            $affected = $this->db->execute($sql, $this->bindings);
            $ok = $affected !== false && $affected >= 0;
        } catch (\Throwable $e) {
            $ok = false;
        }
        $this->clear();
        return (bool)$ok;
    }

    /**
     * ฟังก์ชัน insertGetId ที่ใช้แทรกข้อมูลใหม่ลงในฐานข้อมูลและคืนค่า id ของแถวที่ถูกแทรก โดยรับค่าเป็น array ของข้อมูลที่ต้องการแทรก โดยจะเรียกใช้เมธอด insert() เพื่อแทรกข้อมูล และถ้าแทรกสำเร็จจะใช้ Database instance เพื่อดึงค่า id ของแถวที่ถูกแทรกมา จากนั้นจะคืนค่าผลลัพธ์เป็น id ของแถวที่ถูกแทรก หรือ 0 ถ้าไม่สามารถดึงค่า id ได้
     * จุดประสงค์: เพื่อให้สามารถแทรกข้อมูลใหม่ลงในฐานข้อมูลและคืนค่า id ของแถวที่ถูกแทรกได้อย่างสะดวก โดยการเรียกใช้เมธอด insertGetId() ซึ่งจะรับค่าเป็น array ของข้อมูลที่ต้องการแทรก โดยจะเรียกใช้เมธอด insert() เพื่อแทรกข้อมูล และถ้าแทรกสำเร็จจะใช้ Database instance เพื่อดึงค่า id ของแถวที่ถูกแทรกมา จากนั้นจะคืนค่าผลลัพธ์เป็น id ของแถวที่ถูกแทรก หรือ 0 ถ้าไม่สามารถดึงค่า id ได้ เช่น $query->insertGetId(['name' => 'John', 'email' => 'john@example.com'])
     * ตัวอย่างการใช้งาน:
     * ```php
     * $id = $query->insertGetId(['name' => 'John', 'email' => 'john@example.com']); // ผลลัพธ์: INSERT INTO `table` (`name`, `email`) VALUES ('John', 'john@example.com')
     * ```
     * 
     * @param array $data ข้อมูลที่ต้องการแทรก
     * @return int คืนค่าผลลัพธ์เป็น id ของแถวที่ถูกแทรก หรือ 0 ถ้าไม่สามารถดึงค่า id ได้
     */
    public function insertGetId(array $data): int
    {
        $ok = $this->insert($data); // เรียกใช้เมธอด insert() เพื่อแทรกข้อมูลและตรวจสอบความสำเร็จ
        $id = 0;
        if ($ok) {
            $id = (int)$this->db->lastInsertId();
        }
        // insert() already calls clear(); ensure cleared
        $this->clear();
        return $id;
    }

    /**
     * ฟังก์ชัน update ที่ใช้ปรับปรุงข้อมูลในฐานข้อมูลตามเงื่อนไขที่กำหนดใน QueryBuilder โดยรับค่าเป็น array ของข้อมูลที่ต้องการปรับปรุง โดยจะสร้างคำสั่ง SQL สำหรับการปรับปรุงข้อมูล และใช้ Database instance เพื่อดำเนินการปรับปรุงข้อมูล จากนั้นจะคืนค่าผลลัพธ์เป็นจำนวนแถวที่ถูกปรับปรุง
     * จุดประสงค์: เพื่อให้สามารถปรับปรุงข้อมูลในฐานข้อมูลตามเงื่อนไขที่กำหนดใน QueryBuilder ได้อย่างสะดวก โดยการเรียกใช้เมธอด update() ซึ่งจะรับค่าเป็น array ของข้อมูลที่ต้องการปรับปรุง โดยจะสร้างคำสั่ง SQL สำหรับการปรับปรุงข้อมูล และใช้ Database instance เพื่อดำเนินการปรับปรุงข้อมูล จากนั้นจะคืนค่าผลลัพธ์เป็นจำนวนแถวที่ถูกปรับปรุง เช่น $query->where('id', '=', 1)->update(['name' => 'John'])
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->where('id', '=', 1)->update(['name' => 'John']); // ผลลัพธ์: UPDATE `table` SET `name` = 'John' WHERE `id` = 1
     * ```
     * 
     * @param array $data ข้อมูลที่ต้องการปรับปรุง
     * @return int คืนค่าผลลัพธ์เป็นจำนวนแถวที่ถูกปรับปรุง
     */
    public function update(array $data): int
    {
        if (empty($data)) {
            return 0;
        }
        $sets = [];
        foreach ($data as $col => $val) {
            $escaped = $this->escapeIdentifier($col);
            if ($val instanceof RawExpression) {
                $sets[] = sprintf('%s = %s', $escaped, $val->getValue());
                continue;
            }
            $ph = $this->makePlaceholder();
            $sets[] = sprintf('%s = :%s', $escaped, $ph);
            $this->bindings[$ph] = $val;
        }

        $sql = sprintf('UPDATE %s SET %s', $this->escapeIdentifier($this->table), implode(', ', $sets));
        $whereSql = $this->buildWhereClause();

        // ป้องกันการรันคำสั่ง UPDATE ที่ไม่มี WHERE ซึ่งอาจทำให้ข้อมูลทั้งหมดในตารางถูกปรับปรุงได้
        if ($whereSql === '') {
            if ($this->loggingEnabled) {
                try {
                    $this->logger->error('sql.unsafe', [
                        'operation' => 'update',
                        'table' => $this->table,
                        'sql' => $sql,
                        'bindings' => $this->bindings,
                    ]);
                } catch (\Throwable $_) {
                }
            }
            $this->clear();
            throw new \RuntimeException('Unsafe query: UPDATE without WHERE clause.');
        }
        if ($whereSql !== '') {
            $sql .= ' WHERE ' . $whereSql;
        }

        $this->validateSqlBindings($sql);

        try {
            $count = $this->db->execute($sql, $this->bindings);
        } catch (\Throwable $e) {
            $this->clear();
            throw $e;
        }

        $this->clear();
        return $count;
    }

    /**
     * ฟังก์ชัน delete ที่ใช้ลบข้อมูลจากฐานข้อมูลตามเงื่อนไขที่กำหนดใน QueryBuilder โดยจะสร้างคำสั่ง SQL สำหรับการลบข้อมูล และใช้ Database instance เพื่อดำเนินการลบข้อมูล จากนั้นจะคืนค่าผลลัพธ์เป็นจำนวนแถวที่ถูกลบ
     * จุดประสงค์: เพื่อให้สามารถลบข้อมูลจากฐานข้อมูลตามเงื่อนไขที่กำหนดใน QueryBuilder ได้อย่างสะดวก โดยการเรียกใช้เมธอด delete() ซึ่งจะสร้างคำสั่ง SQL สำหรับการลบข้อมูล และใช้ Database instance เพื่อดำเนินการลบข้อมูล จากนั้นจะคืนค่าผลลัพธ์เป็นจำนวนแถวที่ถูกลบ เช่น $query->where('id', '=', 1)->delete()
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->where('id', '=', 1)->delete(); // ผลลัพธ์: DELETE FROM `table` WHERE `id` = 1
     * ```
     * 
     * @return int คืนค่าผลลัพธ์เป็นจำนวนแถวที่ถูกลบ
     */
    public function delete(): int
    {
        $sql = sprintf("DELETE FROM %s", $this->escapeIdentifier($this->table));
        $whereSql = $this->buildWhereClause();
        
        // ป้องกันการรันคำสั่ง DELETE ที่ไม่มี WHERE ซึ่งอาจทำให้ข้อมูลทั้งหมดในตารางถูกลบได้
        if ($whereSql === '') {
            if ($this->loggingEnabled) {
                try {
                    $this->logger->error('sql.unsafe', [
                        'operation' => 'delete',
                        'table' => $this->table,
                        'sql' => $sql,
                        'bindings' => $this->bindings,
                    ]);
                } catch (\Throwable $_) {
                }
            }

            $this->clear();
            throw new \RuntimeException('Unsafe query: DELETE without WHERE clause.');
        }

        if ($whereSql !== '') {
            $sql .= " WHERE " . $whereSql;
        }

        try {
            $count = $this->db->execute($sql, $this->bindings);
        } catch (\Throwable $e) {
            $count = 0;
        }
        $this->clear();
        return $count;
    }

    /**
     * ฟังก์ชัน beginTransaction ที่ใช้เริ่มต้นการทำงานในรูปแบบ transaction โดยจะเรียกใช้เมธอด beginTransaction() ของ Database instance เพื่อเริ่มต้น transaction และคืนค่าผลลัพธ์เป็น boolean ที่บอกว่าการเริ่มต้น transaction สำเร็จหรือไม่
     * จุดประสงค์: เพื่อให้สามารถเริ่มต้นการทำงานในรูปแบบ transaction ได้อย่างสะดวก โดยการเรียกใช้เมธอด beginTransaction() ซึ่งจะเรียกใช้เมธอด beginTransaction() ของ Database instance เพื่อเริ่มต้น transaction และคืนค่าผลลัพธ์เป็น boolean ที่บอกว่าการเริ่มต้น transaction สำเร็จหรือไม่ เช่น $query->beginTransaction()
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->beginTransaction(); // ผลลัพธ์: เริ่มต้น transaction
     * ```
     * 
     * @return bool คืนค่าผลลัพธ์เป็น boolean ที่บอกว่าการเริ่มต้น transaction สำเร็จหรือไม่
     */
    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    /**
     * ฟังก์ชัน commit ที่ใช้ยืนยันการทำงานในรูปแบบ transaction โดยจะเรียกใช้เมธอด commit() ของ Database instance เพื่อยืนยัน transaction และคืนค่าผลลัพธ์เป็น boolean ที่บอกว่าการยืนยัน transaction สำเร็จหรือไม่
     * จุดประสงค์: เพื่อให้สามารถยืนยันการทำงานในรูปแบบ transaction ได้อย่างสะดวก โดยการเรียกใช้เมธอด commit() ซึ่งจะเรียกใช้เมธอด commit() ของ Database instance เพื่อยืนยัน transaction และคืนค่าผลลัพธ์เป็น boolean ที่บอกว่าการยืนยัน transaction สำเร็จหรือไม่ เช่น $query->commit()
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->commit(); // ผลลัพธ์: ยืนยัน transaction
     * ```
     * 
     * @return bool คืนค่าผลลัพธ์เป็น boolean ที่บอกว่าการยืนยัน transaction สำเร็จหรือไม่
     */
    public function commit(): bool
    {
        return $this->db->commit();
    }

    /**
     * ฟังก์ชัน rollback ที่ใช้ยกเลิกการทำงานในรูปแบบ transaction โดยจะเรียกใช้เมธอด rollBack() ของ Database instance เพื่อยกเลิก transaction และคืนค่าผลลัพธ์เป็น boolean ที่บอกว่าการยกเลิก transaction สำเร็จหรือไม่
     * จุดประสงค์: เพื่อให้สามารถยกเลิกการทำงานในรูปแบบ transaction ได้อย่างสะดวก โดยการเรียกใช้เมธอด rollback() ซึ่งจะเรียกใช้เมธอด rollBack() ของ Database instance เพื่อยกเลิก transaction และคืนค่าผลลัพธ์เป็น boolean ที่บอกว่าการยกเลิก transaction สำเร็จหรือไม่ เช่น $query->rollback()
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->rollback(); // ผลลัพธ์: ยกเลิก transaction
     * ```
     * 
     * @return bool คืนค่าผลลัพธ์เป็น boolean ที่บอกว่าการยกเลิก transaction สำเร็จหรือไม่
     */
    public function rollback(): bool
    {
        return $this->db->rollBack();
    }

    /**
     * ฟังก์ชัน toSql ที่ใช้สร้างคำสั่ง SQL จากเงื่อนไขต่างๆ ที่ถูกกำหนดไว้ใน QueryBuilder โดยจะประกอบด้วยส่วนของ SELECT, FROM, JOIN, WHERE, GROUP BY, HAVING, ORDER BY, LIMIT และ OFFSET ตามลำดับ โดยจะคืนค่าผลลัพธ์เป็น string ของคำสั่ง SQL ที่ถูกสร้างขึ้น
     * จุดประสงค์: เพื่อให้สามารถสร้างคำสั่ง SQL จากเงื่อนไขต่างๆ ที่ถูกกำหนดไว้ใน QueryBuilder ได้อย่างสะดวก โดยการเรียกใช้เมธอด toSql() ซึ่งจะประกอบด้วยส่วนของ SELECT, FROM, JOIN, WHERE, GROUP BY, HAVING, ORDER BY, LIMIT และ OFFSET ตามลำดับ โดยจะคืนค่าผลลัพธ์เป็น string ของคำสั่ง SQL ที่ถูกสร้างขึ้น เช่น $query->where('id', '>', 10)->toSql()
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->where('id', '>', 10)->toSql(); // ผลลัพธ์: "SELECT * FROM `table` WHERE `id` > :p1"
     * ```
     * 
     * @return string คืนค่าผลลัพธ์เป็น string ของคำสั่ง SQL ที่ถูกสร้างขึ้น
     */
    public function toSql(): string
    {
        $cols = [];
        foreach ($this->columns as $c) {
            if ($c instanceof RawExpression) {
                $cols[] = $c->getValue();
                continue;
            }
            if ($c === '*') {
                $cols[] = '*';
                continue;
            }
            $cols[] = $this->escapeIdentifier($c);
        }
        $sql = sprintf('SELECT %s FROM %s', implode(', ', $cols), $this->escapeIdentifier($this->table));

        if (!empty($this->joins)) {
            foreach ($this->joins as $j) {
                $sql .= ' ' . $j['sql'];
            }
        }

        $whereSql = $this->buildWhereClause();
        if ($whereSql !== '') {
            $sql .= ' WHERE ' . $whereSql;
        }

        if (!empty($this->groups)) {
            $grp = array_map([$this, 'formatIdentifierOrRaw'], $this->groups);
            $sql .= ' GROUP BY ' . implode(', ', $grp);
        }

        if (!empty($this->havings)) {
            $haveSql = $this->buildHavingClause();
            if ($haveSql !== '') {
                $sql .= ' HAVING ' . $haveSql;
            }
        }

        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        return $sql;
    }

    /**
     * ฟังก์ชัน clear ที่ใช้รีเซ็ตสถานะของ QueryBuilder โดยจะล้างข้อมูลต่างๆ ที่ถูกกำหนดไว้ใน QueryBuilder เช่น columns, wheres, joins, groups, havings, orders, limit, offset และ bindings เพื่อให้พร้อมสำหรับการสร้างคำสั่ง SQL ใหม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->where('id', '>', 10)->clear()->where('name', 'LIKE', '%John%')->get();
     * ```
     * 
     * @return void ไม่มีค่าที่คืนกลับ
     */
    public function clear(): void
    {
        $this->columns = ['*'];
        $this->wheres = [];
        $this->joins = [];
        $this->groups = [];
        $this->havings = [];
        $this->orders = [];
        $this->limit = null;
        $this->offset = null;
        $this->bindings = [];
        $this->paramCounter = 1;
    }

    /* ---------- Helpers ---------- */
    /**
     * ฟังก์ชัน makePlaceholder ที่ใช้สร้างชื่อ placeholder สำหรับการ bind ค่าในคำสั่ง SQL โดยจะใช้ตัวอักษร 'p' ตามด้วยตัวเลขที่เพิ่มขึ้นเรื่อยๆ เพื่อให้ได้ชื่อ placeholder ที่ไม่ซ้ำกัน เช่น :p1, :p2, :p3 เป็นต้น
     * จุดประสงค์: เพื่อให้สามารถสร้างชื่อ placeholder สำหรับการ bind ค่าในคำสั่ง SQL ได้อย่างสะดวก โดยการเรียกใช้เมธอด makePlaceholder() ซึ่งจะใช้ตัวอักษร 'p' ตามด้วยตัวเลขที่เพิ่มขึ้นเรื่อยๆ เพื่อให้ได้ชื่อ placeholder ที่ไม่ซ้ำกัน เช่น :p1, :p2, :p3 เป็นต้น เช่น $query->where('id', '=', 10) จะใช้ makePlaceholder() เพื่อสร้าง placeholder :p1 และ bind ค่า 10 กับ :p1
     * ตัวอย่างการใช้งาน:
     * // ในเมธอด where()
     * $placeholder = $this->makePlaceholder(); // ผลลัพธ์: "p1"
     * $this->bindings[$placeholder] = 10; // bind ค่า 10 กับ :p1
     * ```
     * 
     * @return string คืนค่าผลลัพธ์เป็น string ของชื่อ placeholder ที่ถูกสร้างขึ้น
     */
    protected function makePlaceholder(): string
    {
        return 'p' . $this->paramCounter++;
    }

    /**
     * ฟังก์ชัน setLoggingEnabled ที่ใช้เปิดหรือปิดการบันทึก log ของ QueryBuilder โดยรับค่าเป็น boolean ที่บอกว่าต้องการเปิดหรือปิดการบันทึก log และตั้งค่าตัวแปร loggingEnabled ตามค่าที่รับเข้ามา
     * จุดประสงค์: เพื่อให้สามารถเปิดหรือปิดการบันทึก logของ QueryBuilder ได้อย่างสะดวก โดยการเรียกใช้เมธอด setLoggingEnabled() ซึ่งจะรับค่าเป็น boolean ที่บอกว่าต้องการเปิดหรือปิดการบันทึก log และตั้งค่าตัวแปร loggingEnabled ตามค่าที่รับเข้ามา เช่น $query->setLoggingEnabled(true) จะเปิดการบันทึก log และ $query->setLoggingEnabled(false) จะปิดการบันทึก log
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->setLoggingEnabled(true); // ผลลัพธ์: เปิดการบันทึก log
     * $query->setLoggingEnabled(false); // ผลลัพธ์: ปิดการบันทึก log
     * ```
     * 
     * @param bool $enable ค่าที่บอกว่าต้องการเปิดหรือปิดการบันทึก log (true สำหรับเปิด, false สำหรับปิด)
     * @return void ไม่มีค่าที่คืนกลับ
     */
    public function setLoggingEnabled(bool $enable): void
    {
        $this->loggingEnabled = (bool)$enable;
    }

    /**
     * ฟังก์ชัน extractPlaceholders ที่ใช้ดึงชื่อ placeholders จากคำสั่ง SQL โดยจะใช้ regular expression เพื่อค้นหาชื่อ placeholders ที่อยู่ในรูปแบบ :name ในคำสั่ง SQL และคืนค่าผลลัพธ์เป็น array ของชื่อ placeholders ที่พบในคำสั่ง SQL
     * จุดประสงค์: เพื่อให้สามารถดึงชื่อ placeholders จากคำสั่ง SQL ได้อย่างสะดวก โดยการเรียกใช้เมธอด extractPlaceholders() ซึ่งจะใช้ regular expression เพื่อค้นหาชื่อ placeholders ที่อยู่ในรูปแบบ :name ในคำสั่ง SQL และคืนค่าผลลัพธ์เป็น array ของชื่อ placeholders ที่พบในคำสั่ง SQL เช่น $query->where('id', '=', 10)->toSql() จะดึงชื่อ placeholder :p1 จากคำสั่ง SQL "SELECT * FROM `table` WHERE `id` = :p1"
     * ตัวอย่างการใช้งาน:
     * ```php
     * $sql = "SELECT * FROM `table` WHERE `id` = :p1 AND `name` = :p2";
     * $placeholders = $query->extractPlaceholders($sql); // ผลลัพธ์: ['p1', 'p2']
     * ```
     * 
     * @param string $sql คำสั่ง SQL ที่ต้องการดึงชื่อ placeholders
     * @return array คืนค่าผลลัพธ์เป็น array ของชื่อ placeholders ที่พบในคำสั่ง SQL
     */
    protected function extractPlaceholders(string $sql): array
    {
        if (preg_match_all('/:([a-zA-Z0-9_]+)/', $sql, $m)) {
            return array_values(array_unique($m[1]));
        }
        return [];
    }

    /**
     * ฟังก์ชัน validateSqlBindings ที่ใช้ตรวจสอบความสอดคล้องระหว่าง placeholders ในคำสั่ง SQL กับ keys ใน bindings โดยจะดึงชื่อ placeholders จาก SQL และเปรียบเทียบกับ keys ใน bindings เพื่อหาว่ามี placeholders ที่ไม่มีการ bind หรือมี bindings ที่ไม่มี placeholders ใน SQL หรือไม่ ถ้ามีความไม่สอดคล้องกันจะทำการบันทึก log warning (ถ้าเปิดใช้งาน logging) โดยระบุรายละเอียดของ SQL, placeholders, bindings, missing และ extra เพื่อช่วยในการ debug และตรวจสอบปัญหาเกี่ยวกับการ bind ค่าในคำสั่ง SQL
     * จุดประสงค์: เพื่อให้สามารถตรวจสอบความสอดคล้องระหว่าง placeholders ในคำสั่ง SQL กับ keys ใน bindings ได้อย่างสะดวก โดยการเรียกใช้เมธอด validateSqlBindings() ซึ่งจะดึงชื่อ placeholders จาก SQL และเปรียบเทียบกับ keys ใน bindings เพื่อหาว่ามี placeholders ที่ไม่มีการ bind หรือมี bindings ที่ไม่มี placeholders ใน SQL หรือไม่ ถ้ามีความไม่สอดคล้องกันจะทำการบันทึก log warning (ถ้าเปิดใช้งาน logging) โดยระบุรายละเอียดของ SQL, placeholders, bindings, missing และ extra เพื่อช่วยในการ debug และตรวจสอบปัญหาเกี่ยวกับการ bind ค่าในคำสั่ง SQL เช่น $query->where('id', '=', 10)->get() จะตรวจสอบว่า :p1 มีการ bind กับค่า 10 หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->where('id', '=', 10)->get(); // ผลลัพธ์: ตรวจสอบว่า :p1 มีการ bind กับค่า 10 หรือไม่ ถ้าไม่จะบันทึก log warning
     * ```
     * 
     * @param string $sql คำสั่ง SQL ที่ต้องการตรวจสอบ
     * @return void ไม่มีค่าที่คืนกลับ
     */
    protected function validateSqlBindings(string $sql): void
    {
        $placeholders = $this->extractPlaceholders($sql);
        $bindingKeys = array_keys($this->bindings);
        sort($placeholders);
        sort($bindingKeys);
        $missing = array_values(array_diff($placeholders, $bindingKeys));
        $extra = array_values(array_diff($bindingKeys, $placeholders));
        
        // ตรวจสอบว่ามี placeholders ที่ไม่มีการ bind หรือมี bindings ที่ไม่มี placeholders ใน SQL หรือไม่ ถ้ามีความไม่สอดคล้องกันจะทำการบันทึก log warning (ถ้าเปิดใช้งาน logging) โดยระบุรายละเอียดของ SQL, placeholders, bindings, missing และ extra เพื่อช่วยในการ debug และตรวจสอบปัญหาเกี่ยวกับการ bind ค่าในคำสั่ง SQL
        if (!empty($missing) || !empty($extra)) {
            // log warning with details
        }
    }

    /**
     * ฟังก์ชัน formatIdentifierOrRaw ที่ใช้ตรวจสอบและจัดรูปแบบค่าที่เป็น identifier หรือ RawExpression โดยถ้าค่าเป็น instance ของ RawExpression จะคืนค่าของ expression นั้นโดยตรง แต่ถ้าเป็น string จะทำการ escape identifier นั้นด้วยเมธอด escapeIdentifier
     * จุดประสงค์: เพื่อให้สามารถตรวจสอบและจัดรูปแบบค่าที่เป็น identifier หรือ RawExpression ได้อย่างสะดวก โดยการเรียกใช้เมธอด formatIdentifierOrRaw() ซึ่งจะตรวจสอบว่าค่าที่รับเข้ามาเป็น instance ของ RawExpression หรือไม่ ถ้าใช่จะคืนค่าของ expression นั้นโดยตรง แต่ถ้าเป็น string จะทำการ escape identifier นั้นด้วยเมธอด escapeIdentifier เช่น $query->select(new RawExpression('COUNT(*) as total'))->get()
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->select(new RawExpression('COUNT(*) as total'))->get(); // ผลลัพธ์: SELECT COUNT(*) as total FROM `table`
     * ```
     * 
     * @param string|RawExpression $value ค่าที่ต้องการตรวจสอบและจัดรูปแบบ ซึ่งสามารถเป็น string หรือ instance ของ RawExpression
     * @return string คืนค่าผลลัพธ์เป็น string ที่ถูกจัดรูปแบบแล้ว
     */
    protected function formatIdentifierOrRaw(string|RawExpression $value): string
    {
        if ($value instanceof RawExpression) {
            return $value->getValue();
        }
        return $this->escapeIdentifier($value);
    }

    /**
     * ฟังก์ชัน escapeIdentifier ที่ใช้สำหรับ escape identifiers (table/column) สำหรับ MySQL โดยใช้ backticks
     * จุดประสงค์: เพื่อให้สามารถ escape identifiers (table/column) สำหรับ MySQL โดยใช้ backticks ได้อย่างสะดวก โดยการเรียกใช้เมธอด escapeIdentifier() ซึ่งจะจัดการกับ dot-separated identifiers และ simple aliases เช่น `column as alias`
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->select('users.id as user_id')->get(); // ผลลัพธ์: SELECT `users`.`id` AS `user_id` FROM `table`
     * ```
     * 
     * @param string $name ชื่อ identifier ที่ต้องการ escape
     * @return string คืนค่าผลลัพธ์เป็น string ของ identifier ที่ถูก escape แล้ว
     */
    protected function escapeIdentifier(string $name): string
    {
        $name = trim($name);
        // preserve expressions wrapped as RawExpression elsewhere
        // handle alias with AS (case-insensitive)
        if (preg_match('/\s+AS\s+/i', $name)) {
            [$left, $alias] = preg_split('/\s+AS\s+/i', $name, 2);
            return $this->escapeIdentifier($left) . ' AS ' . $this->escapeIdentifier($alias);
        }
        // handle space alias like `col alias`
        if (preg_match('/\s+/', $name)) {
            [$left, $alias] = preg_split('/\s+/', $name, 2);
            return $this->escapeIdentifier($left) . ' AS ' . $this->escapeIdentifier($alias);
        }
        // wildcard
        if ($name === '*') {
            return '*';
        }
        // dot notation
        if (str_contains($name, '.')) {
            $parts = explode('.', $name);
            $parts = array_map(function ($p) {
                return '`' . str_replace('`', '``', $p) . '`';
            }, $parts);
            return implode('.', $parts);
        }
        return '`' . str_replace('`', '``', $name) . '`';
    }

    /**
     * ฟังก์ชัน buildWhereClause ที่ใช้สร้างส่วนของ WHERE clause จากเงื่อนไขที่ถูกกำหนดไว้ใน QueryBuilder โดยจะรวมเงื่อนไขต่างๆ ที่อยู่ในรายการ wheres เข้าด้วยกันโดยใช้ตัวเชื่อม boolean (AND/OR) และคืนค่าผลลัพธ์เป็น string ของ WHERE clause ที่ถูกสร้างขึ้น
     * จุดประสงค์: เพื่อให้สามารถสร้างส่วนของ WHERE clause จากเงื่อนไขที่ถูกกำหนดไว้ใน QueryBuilder ได้อย่างสะดวก โดยการเรียกใช้เมธอด buildWhereClause() ซึ่งจะรวมเงื่อนไขต่างๆ ที่อยู่ในรายการ wheres เข้าด้วยกันโดยใช้ตัวเชื่อม boolean (AND/OR) และคืนค่าผลลัพธ์เป็น string ของ WHERE clause ที่ถูกสร้างขึ้น เช่น $query->where('id', '>', 10)->orWhere('name', 'LIKE', '%John%') จะสร้าง WHERE clause เป็น "`id` > :p1 OR `name` LIKE :p2"
     * ตัวอย่างการใช้งาน:
     * ```php
     * // ในเมธอด toSql()
     * $whereSql = $this->buildWhereClause(); // ผลลัพธ์: "`id` > :p1 OR `name` LIKE :p2"
     * ```
     * 
     * @return string คืนค่าผลลัพธ์เป็น string ของ WHERE clause ที่ถูกสร้างขึ้น
     */
    protected function buildWhereClause(): string
    {
        if (empty($this->wheres)) {
            return '';
        }
        $parts = [];
        foreach ($this->wheres as $i => $w) {
            $prefix = $i === 0 ? '' : ' ' . $w['boolean'] . ' ';
            $parts[] = $prefix . $w['sql'];
        }
        return implode('', $parts);
    }

    /**
     * ฟังก์ชัน buildHavingClause ที่ใช้สร้างส่วนของ HAVING clause จากเงื่อนไขที่ถูกกำหนดไว้ใน QueryBuilder โดยจะรวมเงื่อนไขต่างๆ ที่อยู่ในรายการ havings เข้าด้วยกันโดยใช้ตัวเชื่อม boolean (AND/OR) และคืนค่าผลลัพธ์เป็น string ของ HAVING clause ที่ถูกสร้างขึ้น
     * จุดประสงค์: เพื่อให้สามารถสร้างส่วนของ HAVING clause จากเงื่อนไขที่ถูกกำหนดไว้ใน QueryBuilder ได้อย่างสะดวก โดยการเรียกใช้เมธอด buildHavingClause() ซึ่งจะรวมเงื่อนไขต่างๆ ที่อยู่ในรายการ havings เข้าด้วยกันโดยใช้ตัวเชื่อม boolean (AND/OR) และคืนค่าผลลัพธ์เป็น string ของ HAVING clause ที่ถูกสร้างขึ้น เช่น $query->having('total', '>', 100)->orHaving('avg', '<', 50) จะสร้าง HAVING clause เป็น "`total` > :p1 OR `avg` < :p2"
     * ตัวอย่างการใช้งาน:
     * ```php
     * // ในเมธอด toSql()
     * $haveSql = $this->buildHavingClause(); // ผลลัพธ์: "`total` > :p1 OR `avg` < :p2"
     * ```
     * 
     * @return string คืนค่าผลลัพธ์เป็น string ของ HAVING clause ที่ถูกสร้างขึ้น
     */
    protected function buildHavingClause(): string
    {
        if (empty($this->havings)) {
            return '';
        }
        $parts = [];
        foreach ($this->havings as $i => $h) {
            $prefix = $i === 0 ? '' : ' ' . $h['boolean'] . ' ';
            $parts[] = $prefix . $h['sql'];
        }
        return implode('', $parts);
    }
}

// Example usage (with Database wrapper)
// $db = App\Core\Database::getInstance();
// $builder = new QueryBuilder($db, 'users');
// $users = $builder->where(function($q) {
//         $q->where('age', '>', 18)
//             ->orWhere('role', '=', 'admin');
// })->orderBy('name')->limit(10)->get();

// $id = $builder->insertGetId(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

// $affected = $builder->where('id', '=', $id)->update(['name' => 'Jane Q. Doe']);

// $rawNow = new RawExpression('NOW()');
// $builder->insert(['name' => 'Raw Time', 'created_at' => $rawNow]);
