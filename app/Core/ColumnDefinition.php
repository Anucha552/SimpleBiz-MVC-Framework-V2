<?php
/**
 * คลาสสำหรับการกำหนดคอลัมน์ในฐานข้อมูล
 * 
 * จุดประสงค์: ใช้เพื่อสร้างและจัดการการกำหนดคอลัมน์ในตารางฐานข้อมูล
 * ColumnDefinition ควรใช้กับอะไร: เมื่อคุณต้องการสร้างหรือตั้งค่าคอลัมน์ในตารางฐานข้อมูล เช่น การกำหนดประเภทข้อมูล, การตั้งค่า NULLABLE, DEFAULT, และคุณสมบัติพิเศษอื่นๆ
 * 
 * ฟีเจอร์:
 * - กำหนดชื่อและประเภทของคอลัมน์
 * - ตั้งค่าคุณสมบัติต่างๆ เช่น NULLABLE, DEFAULT, UNSIGNED, COMMENT, AUTO_INCREMENT
 * - กำหนดคีย์พิเศษ เช่น UNIQUE, INDEX, PRIMARY KEY
 * - สร้างนิยาม SQL สำหรับคอลัมน์
 * 
 * ตัวอย่างการใช้งานโดยรวม:
 * ```php
 * $column = new ColumnDefinition('age', 'INT');
 * $column->unsigned()->nullable()->default(0)->comment('Age of the user');
 * $sqlDefinition = $column->toSqlDefinition();
 * ```
 */

namespace App\Core;

class ColumnDefinition
{
    /**
     * คุณสมบัติของคอลัมน์
     */
    protected string $name;

    /**
     * ประเภทของคอลัมน์
     */
    protected string $type;

    /**
     * คุณสมบัติ NULLABLE
     */
    protected bool $nullable = false;

    /**
     * ค่าเริ่มต้นของคอลัมน์
     */
    protected $default = null;

    /**
     * ค่าดิบของค่าเริ่มต้น
     */
    protected bool $defaultIsRaw = false;

    /**
     * คุณสมบัติพิเศษ
     */
    protected bool $unsigned = false;

    /**
     * ความคิดเห็นของคอลัมน์
     */
    protected ?string $comment = null;

    /**
     * คุณสมบัติ AUTO_INCREMENT
     */
    protected bool $autoIncrement = false;

    /**
     * คุณสมบัติ UNIQUE
     */
    protected bool $unique = false;

    /**
     * คุณสมบัติ INDEX
     */
    protected bool $index = false;

    /**
     * คุณสมบัติ PRIMARY KEY
     */
    protected bool $primary = false;

    /**
     * สร้างอินสแตนซ์ของ ColumnDefinition
     * จุดประสงค์: กำหนดชื่อและประเภทของคอลัมน์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $column = new ColumnDefinition('age', 'INT');
     * ```
     * 
     * @param string $name กำหนดชื่อของคอลัมน์
     * @param string $type กำหนดประเภทของคอลัมน์
     */
    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * ตั้งค่าคอลัมน์ให้เป็น NULLABLE หรือ NOT NULL
     * จุดประสงค์: กำหนดว่าคอลัมน์สามารถรับค่า NULL ได้หรือไม่
     * nullable() ควรใช้กับอะไร: เมื่อคุณต้องการกำหนดว่าคอลัมน์ในฐานข้อมูลสามารถรับค่า NULL ได้หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $column->nullable(true); // ตั้งค่าเป็น NULLABLE
     * $column->nullable(false); // ตั้งค่าเป็น NOT NULL
     * ```
     * 
     * @param bool $value กำหนดว่าคอลัมน์เป็น NULLABLE หรือ NOT NULL
     * @return App\Core\ColumnDefinition
     */
    public function nullable(bool $value = true): self
    {
        $this->nullable = $value;
        return $this;
    }

    /**
     * ตั้งค่าค่าเริ่มต้นของคอลัมน์
     * จุดประสงค์: กำหนดค่าเริ่มต้นสำหรับคอลัมน์
     * default() ควรใช้กับอะไร: เมื่อคุณต้องการกำหนดค่าเริ่มต้นให้กับคอลัมน์ในฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $column->default(0); // ตั้งค่าเริ่มต้นเป็น 0
     * $column->default('CURRENT_TIMESTAMP', true); // ตั้งค่าเริ่มต้นเป็น CURRENT_TIMESTAMP (ค่าดิบ)
     * ```
     * 
     * @param mixed $value กำหนดค่าเริ่มต้นของคอลัมน์
     * @param bool $raw กำหนดว่าค่าที่ระบุเป็นค่าดิบหรือไม่
     * @return App\Core\ColumnDefinition
     */
    public function default($value, bool $raw = false): self
    {
        $this->default = $value;
        $this->defaultIsRaw = $raw;
        return $this;
    }

    /**
     * ตั้งค่าคอลัมน์ให้เป็น UNSIGNED
     * จุดประสงค์: กำหนดว่าคอลัมน์เป็นประเภท UNSIGNED หรือไม่
     * unsigned() ควรใช้กับอะไร: เมื่อคุณต้องการกำหนดว่าคอลัมน์ในฐานข้อมูลเป็นประเภท UNSIGNED
     * ตัวอย่างการใช้งาน:
     * ```php
     * $column->unsigned(); // ตั้งค่าเป็น UNSIGNED
     * $column->unsigned(false); // ตั้งค่าไม่เป็น UNSIGNED
     * ```
     * 
     * @param bool $value กำหนดว่าคอลัมน์เป็น UNSIGNED หรือไม่
     * @return App\Core\ColumnDefinition
     */
    public function unsigned(bool $value = true): self
    {
        $this->unsigned = $value;
        return $this;
    }

    /**
     * เพิ่มความคิดเห็นให้กับคอลัมน์
     * จุดประสงค์: กำหนดข้อความความคิดเห็นสำหรับคอลัมน์
     * comment() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มความคิดเห็นหรือคำอธิบายให้กับคอลัมน์ในฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $column->comment('Age of the user'); // เพิ่มความคิดเห็น
     * ```
     * 
     * @param string $text กำหนดข้อความความคิดเห็นสำหรับคอลัมน์
     * @return App\Core\ColumnDefinition
     */
    public function comment(string $text): self
    {
        $this->comment = $text;
        return $this;
    }

    /**
     * ตั้งค่าคอลัมน์ให้เป็น AUTO_INCREMENT
     * จุดประสงค์: กำหนดว่าคอลัมน์เป็น AUTO_INCREMENT หรือไม่
     * autoIncrement() ควรใช้กับอะไร: เมื่อคุณต้องการกำหนดว่าคอลัมน์ในฐานข้อมูลเป็น AUTO_INCREMENT
     * ตัวอย่างการใช้งาน:
     * ```php
     * $column->autoIncrement(); // ตั้งค่าเป็น AUTO_INCREMENT
     * $column->autoIncrement(false); // ตั้งค่าไม่เป็น AUTO_INCREMENT
     * ```
     * 
     * @param bool $value กำหนดว่าคอลัมน์เป็น AUTO_INCREMENT หรือไม่
     * @return App\Core\ColumnDefinition
     */
    public function autoIncrement(bool $value = true): self
    {
        $this->autoIncrement = $value;
        return $this;
    }

    /**
     * ตั้งค่าคอลัมน์ให้เป็น UNIQUE
     * จุดประสงค์: กำหนดว่าคอลัมน์มีคุณสมบัติ UNIQUE หรือไม่
     * unique() ควรใช้กับอะไร: เมื่อคุณต้องการกำหนดว่าคอลัมน์ในฐานข้อมูลมีคุณสมบัติ UNIQUE
     * ตัวอย่างการใช้งาน:
     * ```php
     * $column->unique(); // ตั้งค่าเป็น UNIQUE
     * $column->unique(false); // ตั้งค่าไม่เป็น UNIQUE
     * ```
     * 
     * @param bool $value กำหนดว่าคอลัมน์มีคุณสมบัติ UNIQUE หรือไม่
     * @return App\Core\ColumnDefinition
     */
    public function unique(bool $value = true): self
    {
        $this->unique = $value;
        return $this;
    }

    /**
     * ตั้งค่าคอลัมน์ให้เป็น INDEX
     * จุดประสงค์: กำหนดว่าคอลัมน์มีคุณสมบัติ INDEX หรือไม่
     * index() ควรใช้กับอะไร: เมื่อคุณต้องการกำหนดว่าคอลัมน์ในฐานข้อมูลมีคุณสมบัติ INDEX
     * ตัวอย่างการใช้งาน:
     * ```php
     * $column->index(); // ตั้งค่าเป็น INDEX
     * $column->index(false); // ตั้งค่าไม่เป็น INDEX
     * ```
     * 
     * @param bool $value กำหนดว่าคอลัมน์มีคุณสมบัติ INDEX หรือไม่
     * @return App\Core\ColumnDefinition
     */
    public function index(bool $value = true): self
    {
        $this->index = $value;
        return $this;
    }

    /**
     * ตั้งค่าคอลัมน์ให้เป็น PRIMARY KEY
     * จุดประสงค์: กำหนดว่าคอลัมน์มีคุณสมบัติ PRIMARY KEY หรือไม่
     * primary() ควรใช้กับอะไร: เมื่อคุณต้องการกำหนดว่าคอลัมน์ในฐานข้อมูลมีคุณสมบัติ PRIMARY KEY
     * ตัวอย่างการใช้งาน:
     * ```php
     * $column->primary(); // ตั้งค่าเป็น PRIMARY KEY
     * $column->primary(false); // ตั้งค่าไม่เป็น PRIMARY KEY
     * ```
     * 
     * @param bool $value กำหนดว่าคอลัมน์มีคุณสมบัติ PRIMARY KEY หรือไม่
     * @return App\Core\ColumnDefinition
     */
    public function primary(bool $value = true): self
    {
        $this->primary = $value;
        return $this;
    }

    /**
     * ดึงชื่อของคอลัมน์
     * จุดประสงค์: คืนค่าชื่อของคอลัมน์ที่กำหนดไว้
     * getName() ควรใช้กับอะไร: เมื่อคุณต้องการดึงชื่อของคอลัมน์ที่ถูกกำหนดใน ColumnDefinition
     * ตัวอย่างการใช้งาน:
     * ```php
     * $columnName = $column->getName(); // คืนค่าชื่อคอลัมน์
     * ```
     * 
     * @return string คืนค่าชื่อของคอลัมน์
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * ตรวจสอบว่าคอลัมน์เป็น UNIQUE หรือไม่
     * จุดประสงค์: คืนค่าคุณสมบัติ UNIQUE ของคอลัมน์
     * isUnique() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าคอลัมน์ในฐานข้อมูลมีคุณสมบัติ UNIQUE หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isUnique = $column->isUnique(); // ตรวจสอบว่าเป็น UNIQUE หรือไม่
     * ```
     * 
     * @return bool คืนค่า true หากคอลัมน์เป็น UNIQUE, false หากไม่ใช่
     */
    public function isUnique(): bool
    {
        return $this->unique;
    }

    /**
     * ตรวจสอบว่าคอลัมน์เป็น INDEX หรือไม่
     * จุดประสงค์: คืนค่าคุณสมบัติ INDEX ของคอลัมน์
     * isIndex() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าคอลัมน์ในฐานข้อมูลมีคุณสมบัติ INDEX หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isIndex = $column->isIndex(); // ตรวจสอบว่าเป็น INDEX หรือไม่
     * ```
     * 
     * @return bool คืนค่า true หากคอลัมน์เป็น INDEX, false หากไม่ใช่
     */
    public function isIndex(): bool
    {
        return $this->index;
    }

    /**
     * ตรวจสอบว่าคอลัมน์เป็น PRIMARY KEY หรือไม่
     * จุดประสงค์: คืนค่าคุณสมบัติ PRIMARY KEY ของคอลัมน์
     * isPrimary() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าคอลัมน์ในฐานข้อมูลมีคุณสมบัติ PRIMARY KEY หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isPrimary = $column->isPrimary(); // ตรวจสอบว่าเป็น PRIMARY KEY หรือไม่
     * ```
     * 
     * @return bool คืนค่า true หากคอลัมน์เป็น PRIMARY KEY, false หากไม่ใช่
     */
    public function isPrimary(): bool
    {
        return $this->primary;
    }

    /** 
     * สร้างนิยาม SQL สำหรับคอลัมน์
     * จุดประสงค์: สร้างสตริง SQL ที่แสดงถึงการกำหนดคอลัมน์
     * toSqlDefinition() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างนิยาม SQL สำหรับคอลัมน์ในตารางฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $sqlDefinition = $column->toSqlDefinition(); // สร้างนิยาม SQL
     * ```
     * 
     * @return string คือคอลัมน์ SQL ของคอลัมน์
     */
    protected function escapeName(string $name): string
    {
        return "`" . str_replace("`", "\\`", $name) . "`";
    }

    /**
     * สร้างนิยาม SQL สำหรับคอลัมน์
     * จุดประสงค์: สร้างสตริง SQL ที่แสดงถึงการกำหนดคอลัมน์
     * toSqlDefinition() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างนิยาม SQL สำหรับคอลัมน์ในตารางฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $sqlDefinition = $column->toSqlDefinition(); // สร้างนิยาม SQL
     * ```
     * 
     * @return string คือคอลัมน์ SQL ของคอลัมน์
     */
    public function toSqlDefinition(): string
    {
        $sql = $this->escapeName($this->name) . ' ' . $this->type;

        if ($this->unsigned) {
            $sql .= ' UNSIGNED';
        }

        if ($this->autoIncrement) {
            $sql .= ' AUTO_INCREMENT';
        }

        $sql .= $this->nullable ? ' NULL' : ' NOT NULL';

        if ($this->default !== null) {
            if ($this->defaultIsRaw && is_string($this->default)) {
                $sql .= ' DEFAULT ' . $this->default;
            } else {
                $default = is_numeric($this->default) ? $this->default : "'" . addslashes((string)$this->default) . "'";
                $sql .= ' DEFAULT ' . $default;
            }
        }

        if ($this->comment !== null) {
            $sql .= " COMMENT '" . addslashes($this->comment) . "'";
        }

        return $sql;
    }
}
