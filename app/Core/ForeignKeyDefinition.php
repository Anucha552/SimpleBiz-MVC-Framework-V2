<?php
/**
 * คลาสนี้ใช้สำหรับกำหนดคีย์ต่างประเทศ (foreign key) ในฐานข้อมูล
 * 
 * จุดประสงค์: สร้างความสัมพันธ์ระหว่างตารางในฐานข้อมูลโดยใช้คีย์ต่างประเทศ
 * ForeignKeyDefinition ควรใช้กับอะไร: เมื่อคุณต้องการกำหนดคีย์ต่างประเทศระหว่างตารางในฐานข้อมูล
 * 
 * ฟีเจอร์:
 * - กำหนดคอลัมน์ที่เป็นคีย์ต่างประเทศ
 * - ระบุคอลัมน์ที่อ้างอิงในตารางอื่น
 * - กำหนดตารางที่อ้างอิง
 * - ตั้งค่าการกระทำเมื่อมีการลบหรืออัปเดตข้อมูล
 * - กำหนดชื่อคีย์ต่างประเทศ
 * 
 * ตัวอย่างการใช้งานโดยรวม:
 * ```php
 * $foreignKey = new ForeignKeyDefinition('user_id', 'orders');
 * $foreignKey->references('id')->on('users')->onDelete('CASCADE')->onUpdate('CASCADE');
 * $sql = $foreignKey->toSql();
 * ```
 */

namespace App\Core;

class ForeignKeyDefinition
{
    /**
     * คอลัมน์ที่เป็นคีย์ต่างประเทศ
     */
    protected array $columns;

    /**
     * คอลัมน์ที่อ้างอิงในตารางอื่น
     */
    protected ?string $references = null;

    /**
     * ตารางที่อ้างอิง
     */
    protected ?string $onTable = null;

    /**
     * การกระทำเมื่อมีการลบหรืออัปเดตข้อมูล
     */
    protected ?string $onDelete = null;

    /**
     * การกระทำเมื่อมีการอัปเดตข้อมูล
     */
    protected ?string $onUpdate = null;

    /**
     * ชื่อคีย์ต่างประเทศ
     */
    protected ?string $name = null;

    /**
     * ชื่อตารางที่เป็นเจ้าของคีย์ต่างประเทศ
     */
    protected string $owningTable;

    /**
     * สร้างอินสแตนซ์ของ ForeignKeyDefinition
     * จุดประสงค์: กำหนดคอลัมน์และตารางเจ้าของสำหรับคีย์ต่างประเทศ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $foreignKey = new ForeignKeyDefinition('user_id', 'orders');
     * $foreignKey->references('id')->on('users')->onDelete('CASCADE')->onUpdate('CASCADE');
     * $sql = $foreignKey->toSql();
     * ```
     * 
     * ผลลัพธ์: คืนค่าอินสแตนซ์ของ ForeignKeyDefinition ที่กำหนดคอลัมน์และตารางเจ้าของ
     * 
     * @param string|array $columns กำหนดคอลัมน์ที่เป็นคีย์ต่างประเทศ
     * @param string $owningTable กำหนดชื่อตารางที่เป็นเจ้าของคีย์ต่างประเทศ
     */
    public function __construct(string|array $columns, string $owningTable)
    {
        $this->columns = (array) $columns;
        $this->owningTable = $owningTable;
    }

    /**
     * กำหนดคอลัมน์ที่อ้างอิงในตารางอื่น
     * จุดประสงค์: ระบุคอลัมน์ที่คีย์ต่างประเทศจะอ้างอิงไปยังตารางอื่น
     * references() ควรใช้กับอะไร: เมื่อคุณต้องการระบุคอลัมน์ที่คีย์ต่างประเทศจะอ้างอิง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $foreignKey->references('id');
     * ```
     * 
     * @param string $column กำหนดชื่อคอลัมน์ที่อ้างอิง
     * @return ForeignKeyDefinition คืนค่าอินสแตนซ์ของ ForeignKeyDefinition ที่มีการตั้งค่าคอลัมน์ที่อ้างอิง
     */
    public function references(string $column): self
    {
        $this->references = $column;
        return $this;
    }

    /**
     * กำหนดตารางที่อ้างอิง
     * จุดประสงค์: ระบุตารางที่คีย์ต่างประเทศจะอ้างอิง
     * on() ควรใช้กับอะไร: เมื่อคุณต้องการระบุตารางที่คีย์ต่างประเทศจะอ้างอิง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $foreignKey->on('users');
     * ```
     * 
     * @param string $table ชื่อตารางที่อ้างอิง
     * @return ForeignKeyDefinition คืนค่าอินสแตนซ์ของ ForeignKeyDefinition ที่มีการตั้งค่าตารางที่อ้างอิง
     */
    public function on(string $table): self
    {
        $this->onTable = $table;
        return $this;
    }

    /**
     * กำหนดการกระทำเมื่อมีการลบข้อมูล
     * จุดประสงค์: ระบุการกระทำที่เกิดขึ้นเมื่อมีการลบข้อมูลในตารางที่อ้างอิง
     * onDelete() ควรใช้กับอะไร: เมื่อคุณต้องการระบุการกระทำเมื่อมีการลบข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $foreignKey->onDelete('CASCADE');
     * ```
     * 
     * ผลลัพธ์: คืนค่าอินสแตนซ์ของ ForeignKeyDefinition ที่มีการตั้งค่าการกระทำเมื่อมีการลบข้อมูล
     * 
     * @param string $action การกระทำเมื่อมีการลบข้อมูล (เช่น CASCADE, SET NULL, RESTRICT)
     * @return ForeignKeyDefinition คืนค่าอินสแตนซ์ของ ForeignKeyDefinition ที่มีการตั้งค่าการกระทำเมื่อมีการลบข้อมูล
     */
    public function onDelete(string $action): self
    {
        $this->onDelete = strtoupper($action);
        return $this;
    }

    /**
     * กำหนดการกระทำเมื่อมีการอัปเดตข้อมูล
     * จุดประสงค์: ระบุการกระทำที่เกิดขึ้นเมื่อมีการอัปเดตข้อมูลในตารางที่อ้างอิง
     * onUpdate() ควรใช้กับอะไร: เมื่อคุณต้องการระบุการกระทำเมื่อมีการอัปเดตข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $foreignKey->onUpdate('CASCADE');
     * ```
     * 
     * @param string $action การกระทำเมื่อมีการอัปเดตข้อมูล (เช่น CASCADE, SET NULL, RESTRICT)
     * @return ForeignKeyDefinition คืนค่าอินสแตนซ์ของ ForeignKeyDefinition ที่มีการตั้งค่าการกระทำเมื่อมีการอัปเดตข้อมูล
     */
    public function onUpdate(string $action): self
    {
        $this->onUpdate = strtoupper($action);
        return $this;
    }

    /**
     * กำหนดชื่อคีย์ต่างประเทศ
     * จุดประสงค์: ระบุชื่อที่ต้องการสำหรับคีย์ต่างประเทศ
     * name() ควรใช้กับอะไร: เมื่อคุณต้องการระบุชื่อสำหรับคีย์ต่างประเทศ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $foreignKey->name('fk_user_orders');
     * ```
     * @param string $name กำหนดชื่อคีย์ต่างประเทศ
     * @return ForeignKeyDefinition คืนค่าอินสแตนซ์ของ ForeignKeyDefinition ที่มีการตั้งค่าชื่อคีย์ต่างประเทศ
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * สร้าง SQL สำหรับคีย์ต่างประเทศ
     * จุดประสงค์: สร้างคำสั่ง SQL สำหรับการสร้างคีย์ต่างประเทศในฐานข้อมูล
     * toSql() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคำสั่ง SQL สำหรับคีย์ต่างประเทศ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $sql = $foreignKey->toSql();
     * ```
     * 
     * @param string|null $table ชื่อตารางเจ้าของ (ถ้าไม่ระบุจะใช้ตารางที่กำหนดในคอนสตรัคเตอร์)
     * @return string คำสั่ง SQL สำหรับคีย์ต่างประเทศ
     */
    protected function escapeName(string $name): string
    {
        return "`" . str_replace("`", "\\`", $name) . "`";
    }

    /**
     * สร้างชื่อคีย์ต่างประเทศ
     * จุดประสงค์: สร้างชื่อคีย์ต่างประเทศโดยอัตโนมัติหากไม่ได้ระบุชื่อ
     * buildConstraintName() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างชื่อคีย์ต่างประเทศ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $constraintName = $this->buildConstraintName();
     * ```
     * 
     * @return string คืนค่าชื่อคีย์ต่างประเทศ
     */
    protected function buildConstraintName(): string
    {
        if ($this->name) return $this->name;
        $cols = implode('_', $this->columns);
        return 'fk_' . $this->owningTable . '_' . $cols;
    }

    /**
     * สร้าง SQL สำหรับคีย์ต่างประเทศ
     * จุดประสงค์: สร้างคำสั่ง SQL สำหรับการสร้างคีย์ต่างประเทศในฐานข้อมูล
     * toSql() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคำสั่ง SQL สำหรับคีย์ต่างประเทศ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $sql = $foreignKey->toSql();
     * ```
     * 
     * @param string|null $table กำหนดชื่อตารางเจ้าของ (ถ้าไม่ระบุจะใช้ตารางที่กำหนดในคอนสตรัคเตอร์)
     * @return string คืนค่าคำสั่ง SQL สำหรับคีย์ต่างประเทศ
     */
    public function toSql(?string $table = null): string
    {
        $owning = $table ?? $this->owningTable;
        $constraint = $this->escapeName($this->buildConstraintName());

        $cols = implode(', ', array_map([$this, 'escapeName'], $this->columns));
        $refCols = $this->escapeName($this->references ?? $this->columns[0]);
        $onTable = $this->escapeName($this->onTable ?? '');

        $sql = "CONSTRAINT {$constraint} FOREIGN KEY ({$cols}) REFERENCES {$onTable} ({$refCols})";

        if ($this->onDelete) {
            $sql .= ' ON DELETE ' . $this->onDelete;
        }
        if ($this->onUpdate) {
            $sql .= ' ON UPDATE ' . $this->onUpdate;
        }

        return $sql;
    }
}
