<?php
/**
 * คลาส Blueprint สำหรับการสร้างและแก้ไขโครงสร้างตารางในฐานข้อมูลของแอปพลิเคชัน
 * 
 * จุดประสงค์:
 * - ให้วิธีการที่ง่ายและชัดเจนในการกำหนดโครงสร้างตาราง
 * - สนับสนุนการสร้างคอลัมน์ประเภทต่าง ๆ เช่น INTEGER, VARCHAR, TEXT, BOOLEAN, TIMESTAMP
 * - รองรับการกำหนดดัชนี (indexes) และคีย์ต่างประเทศ (foreign keys)
 * 
 * Blueprint ควรใช้กับอะไร: การสร้างและแก้ไขโครงสร้างตารางในฐานข้อมูลของแอปพลิเคชัน
 * 
 * ฟีเจอร์หลัก:
 * - เมธอดสำหรับสร้างคอลัมน์ประเภทต่าง ๆ พร้อมตัวเลือกเช่น ความยาว, ค่าเริ่มต้น, และการอนุญาตค่า NULL
 * - เมธอดสำหรับเพิ่มดัชนีและคีย์ต่างประเทศ
 * - เมธอดสำหรับแปลงโครงสร้างที่กำหนดเป็นคำสั่ง SQL สำหรับการสร้างหรือแก้ไขตาราง                                               
 * 
 * ตัวอย่างการใช้งานโดยรวม:
 * $blueprint = new Blueprint('users');
 * $blueprint->increments('id');
 * $blueprint->string('username', 150)->unique();
 * $blueprint->string('email', 255)->nullable();
 * $blueprint->timestamps();
 * $sql = $blueprint->toCreateSql();
 * // ผลลัพธ์: คำสั่ง SQL สำหรับสร้างตาราง users พร้อมคอลัมน์และดัชนีที่กำหนด
 */

namespace App\Core;

class Blueprint
{
    /**
     * ชื่อตารางที่กำลังสร้างหรือแก้ไข
     */
    protected string $table;

    /**
     * รายการคอลัมน์ที่กำหนดในตาราง
     */
    protected array $columns = [];

    /**
     * รายการคีย์หลัก (primary keys)
     */
    protected array $primary = [];

    /**
     * รายการดัชนี (indexes) และคีย์ต่างประเทศ (foreign keys)
     */
    protected array $indexes = [];

    /**
     * รายการคีย์ต่างประเทศ (foreign key definitions)
     */
    protected array $foreignKeys = [];

    /**
     * สร้างอินสแตนซ์ของ Blueprint สำหรับตารางที่ระบุ
     * จุดประสงค์: กำหนดชื่อตารางที่ต้องการสร้างหรือแก้ไขพร้อมสร้างอินสแตนซ์ของ Blueprint
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint = new Blueprint('users');
     * ```
     *
     * @param string $table ชื่อตาราง
     */
    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * สร้างคอลัมน์ประเภท AUTO INCREMENT INTEGER
     * จุดประสงค์: เพิ่มคอลัมน์ที่เป็นตัวระบุเอกลักษณ์ของแถวในตาราง
     * increments() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เป็นตัวระบุเอกลักษณ์ของแถวในตาราง เช่น primary key
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->increments('id');
     * ```
     *
     * @param string $name ชื่อคอลัมน์ (ค่าเริ่มต้น: 'id')
     * @return \App\Core\ColumnDefinition
     */ 
    public function increments(string $name = 'id'): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'INT');
        $col->autoIncrement()->primary();
        $this->columns[] = $col;
        return $col;
    }

    /**
     * สร้างคอลัมน์ประเภท INTEGER
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บค่าตัวเลขจำนวนเต็ม
     * integer() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บค่าตัวเลขจำนวนเต็ม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->integer('age', true, 0);
     * ```
     *
     * @param string $name กำหนดชื่อคอลัมน์
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @param mixed $default ค่าที่จะใช้เป็นค่าเริ่มต้นของคอลัมน์ (ค่าเริ่มต้น: null)
     * @return \App\Core\ColumnDefinition
     */
    public function integer(string $name, bool $nullable = false, $default = null): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'INT');
        if ($nullable) $col->nullable();
        if ($default !== null) $col->default($default);
        $this->columns[] = $col;
        return $col;
    }

    /**
     * สร้างคอลัมน์ประเภท VARCHAR
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บข้อความสั้น ๆ
     * string() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บข้อความสั้น ๆ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->string('username', 150, false, 'guest');
     * ```
     * 
     * @param string $name กำหนดชื่อคอลัมน์
     * @param int $length กำหนดความยาวสูงสุดของข้อความ (ค่าเริ่มต้น: 255)
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @param mixed $default ค่าที่จะใช้เป็นค่าเริ่มต้นของคอลัมน์ (ค่าเริ่มต้น: null)
     * @return \App\Core\ColumnDefinition
     */
    public function string(string $name, int $length = 255, bool $nullable = false, $default = null): ColumnDefinition
    {
        $col = new ColumnDefinition($name, "VARCHAR({$length})");
        if ($nullable) $col->nullable();
        if ($default !== null) $col->default($default);
        $this->columns[] = $col;
        return $col;
    }

    /**
     * สร้างคอลัมน์ประเภท TEXT
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บข้อความยาว
     * text() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บข้อความยาว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->text('description', true);
     * ```
     * 
     * @param string $name กำหนดชื่อคอลัมน์
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @return \App\Core\ColumnDefinition
     */
    public function text(string $name, bool $nullable = false): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'TEXT');
        if ($nullable) $col->nullable();
        $this->columns[] = $col;
        return $col;
    }

    /**
     * สร้างคอลัมน์ประเภท BOOLEAN
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บค่าจริง/เท็จ
     * boolean() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บค่าจริง/เท็จ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->boolean('is_active', false, 1);
     * ```
     * 
     * @param string $name ชื่อคอลัมน์
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @param mixed $default ค่าที่จะใช้เป็นค่าเริ่มต้นของคอลัมน์ (ค่าเริ่มต้น: null)
     * @return \App\Core\ColumnDefinition
     */
    public function boolean(string $name, bool $nullable = false, $default = null): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'TINYINT(1)');
        if ($nullable) $col->nullable();
        if ($default !== null) $col->default($default);
        $this->columns[] = $col;
        return $col;
    }


    /**
     * สร้างคอลัมน์ประเภท TIMESTAMP
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บข้อมูลเวลาที่มีความแม่นยำสูง
     * timestamp() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บข้อมูลเวลาที่มีความแม่นยำสูง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->timestamp('created_at', true, 'CURRENT_TIMESTAMP');
     * ```
     * 
     * @param string $name กำหนดชื่อคอลัมน์
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @param mixed $default ค่าที่จะใช้เป็นค่าเริ่มต้นของคอลัมน์ (ค่าเริ่มต้น: null)
     * @return \App\Core\ColumnDefinition
     */
    public function timestamp(string $name, bool $nullable = false, $default = null): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'TIMESTAMP');
        if ($nullable) $col->nullable();
        if ($default !== null) $col->default($default, true);
        $this->columns[] = $col;
        return $col;
    }

    /**
     * สร้างคอลัมน์ created_at และ updated_at สำหรับการติดตามเวลาที่สร้างและแก้ไขแถว
     * จุดประสงค์: เพิ่มคอลัมน์มาตรฐานสำหรับการติดตามเวลาที่สร้างและแก้ไขข้อมูล
     * timestamps() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มคอลัมน์ created_at และ updated_at ในตารางเพื่อการติดตามเวลาที่สร้างและแก้ไขข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->timestamps();
     * ```
     * 
     * @return \App\Core\Blueprint
     */
    public function timestamps(): self
    {
        $created = new ColumnDefinition('created_at', 'TIMESTAMP');
        $created->nullable()->default('CURRENT_TIMESTAMP', true);
        $updated = new ColumnDefinition('updated_at', 'TIMESTAMP');
        $updated->nullable()->default('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', true);
        $this->columns[] = $created;
        $this->columns[] = $updated;
        return $this;
    }

    /**
     * เพิ่มดัชนีแบบ UNIQUE สำหรับคอลัมน์ที่ระบุ
     * จุดประสงค์: สร้างดัชนีที่รับประกันว่าค่าของคอลัมน์จะไม่ซ้ำกันในตาราง
     * unique() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างดัชนีที่รับประกันว่าค่าของคอลัมน์จะไม่ซ้ำกันในตาราง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->unique('email');
     * ```
     *
     * @param string $column กำหนดชื่อคอลัมน์ที่จะสร้างดัชนีแบบ UNIQUE
     * @return \App\Core\Blueprint
     */
    public function unique(string $column): self
    {
        $this->indexes[] = [
            'type' => 'UNIQUE',
            'columns' => [$column],
        ];
        return $this;
    }

    /**
     * เพิ่มดัชนีแบบปกติสำหรับคอลัมน์ที่ระบุ
     * จุดประสงค์: สร้างดัชนีเพื่อเพิ่มประสิทธิภาพในการค้นหาข้อมูลในคอลัมน์
     * index() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างดัชนีเพื่อเพิ่มประสิทธิภาพในการค้นหาข้อมูลในคอลัมน์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->index('username');
     * ```
     *
     * @param string $column กำหนดชื่อคอลัมน์ที่จะสร้างดัชนีแบบปกติ
     * @return \App\Core\Blueprint
     */
    public function index(string $column): self
    {
        $this->indexes[] = [
            'type' => 'INDEX',
            'columns' => [$column],
        ];
        return $this;
    }

    /**
     * เพิ่มคีย์ต่างประเทศ (foreign key) สำหรับคอลัมน์ที่ระบุ
     * จุดประสงค์: สร้างความสัมพันธ์ระหว่างตารางโดยใช้คีย์ต่างประเทศ
     * foreign() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างความสัมพันธ์ระหว่างตารางโดยใช้คีย์ต่างประเทศ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
     * ```
     *
     * @param string|array $columns
     * @return \App\Core\ForeignKeyDefinition
     */
    public function foreign(string|array $columns): ForeignKeyDefinition
    {
        $fk = new \App\Core\ForeignKeyDefinition($columns, $this->table);
        $this->foreignKeys[] = $fk;
        return $fk;
    }

    /**
     * หน้ากากชื่อคอลัมน์เพื่อป้องกัน SQL Injection
     * จุดประสงค์: ป้องกันการโจมตี SQL Injection โดยการหน้ากากชื่อคอลัมน์
     * escapeName() ควรใช้กับอะไร: เมื่อคุณต้องการป้องกัน SQL Injection ในชื่อคอลัมน์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $escapedName = $blueprint->escapeName('column_name');
     * ```
     * 
     * @param string $name กำหนดชื่อคอลัมน์ที่ต้องการหน้ากาก
     * @return string คืนค่าชื่อคอลัมน์ที่ถูกหน้ากาก
     */
    protected function escapeName(string $name): string
    {
        return "`" . str_replace("`", "\\`", $name) . "`";
    }

    /**
     * แปลงโครงสร้างที่กำหนดเป็นคำสั่ง SQL สำหรับการสร้างตาราง
     * จุดประสงค์: สร้างคำสั่ง SQL ที่สามารถใช้ในการสร้างตารางในฐานข้อมูลตามโครงสร้างที่กำหนด
     * toCreateSql() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคำสั่ง SQL สำหรับการสร้างตารางในฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $sql = $blueprint->toCreateSql();
     * ```
     * 
     * @return string คืนค่าคำสั่ง SQL สำหรับการสร้างตาราง
     */
    public function toCreateSql(): string
    {
        $parts = [];
        foreach ($this->columns as $col) {
            if ($col instanceof ColumnDefinition) {
                $parts[] = $col->toSqlDefinition();

                if ($col->isPrimary()) {
                    $this->primary[] = $col->getName();
                }

                if ($col->isUnique()) {
                    $this->indexes[] = ['type' => 'UNIQUE', 'columns' => [$col->getName()]];
                }

                if ($col->isIndex()) {
                    $this->indexes[] = ['type' => 'INDEX', 'columns' => [$col->getName()]];
                }

                continue;
            }

            // legacy array support
            $sql = $this->escapeName($col['name']) . ' ' . $col['type'];

            if (!empty($col['extra'])) {
                $sql .= ' ' . $col['extra'];
            }

            $sql .= isset($col['nullable']) && $col['nullable'] ? ' NULL' : ' NOT NULL';

            if (array_key_exists('default', $col) && $col['default'] !== null) {
                $default = $col['default'];
                // allow raw expressions like CURRENT_TIMESTAMP
                if (is_string($default) && strtoupper($default) === $default && preg_match('/^[A-Z0-9_ ]+$/', $default)) {
                    $sql .= ' DEFAULT ' . $default;
                } else {
                    $sql .= ' DEFAULT ' . (is_numeric($default) ? $default : "'" . addslashes((string)$default) . "'");
                }
            }

            $parts[] = $sql;
        }

        if (!empty($this->primary)) {
            $parts[] = 'PRIMARY KEY (' . implode(', ', array_map([$this, 'escapeName'], $this->primary)) . ')';
        }

        foreach ($this->indexes as $ix) {
            $cols = implode(', ', array_map([$this, 'escapeName'], $ix['columns']));
            if ($ix['type'] === 'UNIQUE') {
                $parts[] = "UNIQUE ({$cols})";
            } else {
                $parts[] = "INDEX ({$cols})";
            }
        }

        // add foreign key constraints
        foreach ($this->foreignKeys as $fk) {
            if ($fk instanceof \App\Core\ForeignKeyDefinition) {
                $parts[] = $fk->toSql($this->table);
            }
        }

        $sql = 'CREATE TABLE IF NOT EXISTS ' . $this->escapeName($this->table) . ' (' . implode(', ', $parts) . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

        return $sql . ';';
    }

    /**
     * แปลงโครงสร้างที่กำหนดเป็นคำสั่ง SQL สำหรับการแก้ไขตาราง (เพิ่มคอลัมน์และดัชนี)
     * จุดประสงค์: สร้างคำสั่ง SQL ที่สามารถใช้ในการแก้ไขตารางในฐานข้อมูลตามโครงสร้างที่กำหนด
     * toAlterAddSql() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคำสั่ง SQL สำหรับการแก้ไขตารางในฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $stmts = $blueprint->toAlterAddSql();
     * ```
     * 
     * @return array คืนค่ารายการคำสั่ง SQL สำหรับการแก้ไขตาราง
     */
    public function toAlterAddSql(): array
    {
        $stmts = [];
        foreach ($this->columns as $col) {
            if ($col instanceof ColumnDefinition) {
                $sql = 'ALTER TABLE ' . $this->escapeName($this->table) . ' ADD COLUMN ' . $col->toSqlDefinition() . ';';
                $stmts[] = $sql;
                continue;
            }

            $sql = 'ALTER TABLE ' . $this->escapeName($this->table) . ' ADD COLUMN ' . $this->escapeName($col['name']) . ' ' . $col['type'];
            $sql .= isset($col['nullable']) && $col['nullable'] ? ' NULL' : ' NOT NULL';

            if (array_key_exists('default', $col) && $col['default'] !== null) {
                $default = $col['default'];
                if (is_string($default) && strtoupper($default) === $default && preg_match('/^[A-Z0-9_ ]+$/', $default)) {
                    $sql .= ' DEFAULT ' . $default;
                } else {
                    $sql .= ' DEFAULT ' . (is_numeric($default) ? $default : "'" . addslashes((string)$default) . "'");
                }
            }

            if (!empty($col['extra'])) {
                $sql .= ' ' . $col['extra'];
            }

            $sql .= ';';
            $stmts[] = $sql;
        }

        foreach ($this->indexes as $ix) {
            $cols = implode(', ', array_map([$this, 'escapeName'], $ix['columns']));
            if ($ix['type'] === 'UNIQUE') {
                $stmts[] = 'ALTER TABLE ' . $this->escapeName($this->table) . ' ADD UNIQUE (' . $cols . ');';
            } else {
                $stmts[] = 'ALTER TABLE ' . $this->escapeName($this->table) . ' ADD INDEX (' . $cols . ');';
            }
        }

        return $stmts;
    }
}
