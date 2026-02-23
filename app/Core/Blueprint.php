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
     * สร้างคอลัมน์ประเภท SMALLINT
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บค่าตัวเลขจำนวนเต็มขนาดเล็ก
     * smallInteger() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บค่าตัวเลขจำนวนเต็มขนาดเล็ก
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->smallInteger('age', true, 0);
     * ```
     *
     * @param string $name กำหนดชื่อคอลัมน์
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @param mixed $default ค่าที่จะใช้เป็นค่าเริ่มต้นของคอลัมน์ (ค่าเริ่มต้น: null)
     * @return \App\Core\ColumnDefinition
     */
    public function smallInteger(string $name, bool $nullable = false, $default = null): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'SMALLINT');
        if ($nullable) $col->nullable();
        if ($default !== null) $col->default($default);
        $this->columns[] = $col;
        return $col;
    }

    /**
     * สร้างคอลัมน์ประเภท BIGINT
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บค่าตัวเลขจำนวนเต็มขนาดใหญ่
     * bigInteger() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บค่าตัวเลขจำนวนเต็มขนาดใหญ่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->bigInteger('id', true, 0);
     * ```
     *
     * @param string $name กำหนดชื่อคอลัมน์
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @param mixed $default ค่าที่จะใช้เป็นค่าเริ่มต้นของคอลัมน์ (ค่าเริ่มต้น: null)
     * @return \App\Core\ColumnDefinition
     */
    public function bigInteger(string $name, bool $nullable = false, $default = null): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'BIGINT');
        if ($nullable) $col->nullable();
        if ($default !== null) $col->default($default);
        $this->columns[] = $col;
        return $col;
    }

    /**
     * สร้างคอลัมน์ประเภท UNSIGNED INT
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บค่าตัวเลขจำนวนเต็มที่ไม่เป็นลบ
     * unsignedInteger() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บค่าตัวเลขจำนวนเต็มที่ไม่เป็นลบ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->unsignedInteger('age', true, 0);
     * ```
     *
     * @param string $name กำหนดชื่อคอลัมน์
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @param mixed $default ค่าที่จะใช้เป็นค่าเริ่มต้นของคอลัมน์ (ค่าเริ่มต้น: null)
     * @return \App\Core\ColumnDefinition
     */
    public function unsignedInteger(string $name, bool $nullable = false, $default = null): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'INT');
        $col->unsigned();
        if ($nullable) $col->nullable();
        if ($default !== null) $col->default($default);
        $this->columns[] = $col;
        return $col;
    }

    /**
     * สร้างคอลัมน์ประเภท UNSIGNED BIGINT
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บค่าตัวเลขจำนวนเต็มขนาดใหญ่ที่ไม่เป็นลบ
     * unsignedBigInteger() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บค่าตัวเลขจำนวนเต็มขนาดใหญ่ที่ไม่เป็นลบ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->unsignedBigInteger('id', true, 0);
     * ```
     * 
     * @param string $name กำหนดชื่อคอลัมน์
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @param mixed $default ค่าที่จะใช้เป็นค่าเริ่มต้นของคอลัมน์ (ค่าเริ่มต้น: null)
     * @return \App\Core\ColumnDefinition
     */
    public function unsignedBigInteger(string $name, bool $nullable = false, $default = null): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'BIGINT');
        $col->unsigned();
        if ($nullable) $col->nullable();
        if ($default !== null) $col->default($default);
        $this->columns[] = $col;
        return $col;
    }

    /**
     * สร้างคอลัมน์ประเภท TINYINT
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บค่าตัวเลขจำนวนเต็มขนาดเล็ก
     * tinyInteger() ควรใช้กับอะไร: คอลัมน์สถานะ/flag ขนาดเล็ก เช่น is_admin
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->tinyInteger('is_admin', false, 0);
     * ```
     *
     * @param string $name กำหนดชื่อคอลัมน์
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @param mixed $default ค่าที่จะใช้เป็นค่าเริ่มต้นของคอลัมน์ (ค่าเริ่มต้น: null)
     * @return \App\Core\ColumnDefinition
     */
    public function tinyInteger(string $name, bool $nullable = false, $default = null): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'TINYINT');
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
     * สร้างคอลัมน์ประเภท TINYTEXT
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บข้อความสั้นมาก ๆ
     * tinyText() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บข้อความสั้นมาก ๆ เช่น คำอธิบายสั้น ๆ หรือสถานะ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->tinyText('status', true);
     * ```
     * 
     * @param string $name กำหนดชื่อคอลัมน์
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @return \App\Core\ColumnDefinition
     */
    public function tinyText(string $name, bool $nullable = false): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'TINYTEXT');
        if ($nullable) $col->nullable();
        $this->columns[] = $col;
        return $col;
    }

    /**
     * สร้างคอลัมน์ประเภท MEDIUMTEXT
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บข้อความขนาดกลาง
     * mediumText() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บข้อความขนาดกลาง เช่น บทความหรือคำอธิบายยาว ๆ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->mediumText('content', true);
     * ```
     * 
     * @param string $name กำหนดชื่อคอลัมน์
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @return \App\Core\ColumnDefinition
     */
    public function mediumText(string $name, bool $nullable = false): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'MEDIUMTEXT');
        if ($nullable) $col->nullable();
        $this->columns[] = $col;
        return $col;
    }

    /**
     * สร้างคอลัมน์ประเภท LONGTEXT
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บข้อความยาวมาก
     * longText() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บข้อความยาวมาก เช่น บทความยาว ๆ หรือเนื้อหาขนาดใหญ่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->longText('content', true);
     * ```
     * 
     * @param string $name กำหนดชื่อคอลัมน์
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @return \App\Core\ColumnDefinition
     */
    public function longText(string $name, bool $nullable = false): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'LONGTEXT');
        if ($nullable) $col->nullable();
        $this->columns[] = $col;
        return $col;
    }

    /**
     * สร้างคอลัมน์ประเภท CHAR
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บข้อความสั้นคงที่
     * char() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บข้อความสั้นคงที่ เช่น รหัสไปรษณีย์หรือรหัสประเทศ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->char('country_code', 2, false, 'US');
     * ```
     * 
     * @param string $name กำหนดชื่อคอลัมน์
     * @param int $length กำหนดความยาวของข้อความ (ค่าเริ่มต้น: 1)
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @param mixed $default ค่าที่จะใช้เป็นค่าเริ่มต้นของคอลัมน์ (ค่าเริ่มต้น: null)
     * @return \App\Core\ColumnDefinition
     */
    public function char(string $name, int $length = 1, bool $nullable = false, $default = null): ColumnDefinition
    {
        $col = new ColumnDefinition($name, "CHAR({$length})");
        if ($nullable) $col->nullable();
        if ($default !== null) $col->default($default);
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
     * สร้างคอลัมน์ประเภท DECIMAL
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บค่าตัวเลขทศนิยมที่มีความแม่นยำสูง
     * decimal() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บค่าตัวเลขทศนิยมที่มีความแม่นยำสูง เช่น ราคาสินค้า หรือยอดเงิน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->decimal('price', 10, 2, false, 0);
     * ```
     * 
     * @param string $name กำหนดชื่อคอลัมน์
     * @param int $precision กำหนดจำนวนหลักทั้งหมด (ค่าเริ่มต้น: 10)
     * @param int $scale กำหนดจำนวนหลักทศนิยม (ค่าเริ่มต้น: 2)
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @param mixed $default ค่าที่จะใช้เป็นค่าเริ่มต้นของคอลัมน์ (ค่าเริ่มต้น: null)
     * @return \App\Core\ColumnDefinition
     */
    public function decimal(string $name, int $precision = 10, int $scale = 2, bool $nullable = false, $default = null): ColumnDefinition
    {
        $col = new ColumnDefinition($name, "DECIMAL({$precision},{$scale})");
        if ($nullable) $col->nullable();
        if ($default !== null) $col->default($default);
        $this->columns[] = $col;
        return $col;
    }

    /**
     * สร้างคอลัมน์ประเภท FLOAT
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บค่าตัวเลขทศนิยมที่มีความแม่นยำต่ำ
     * float() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บค่าตัวเลขทศนิยมที่มีความแม่นยำต่ำ เช่น คะแนนหรืออัตราส่วน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->float('rating', true, 0);
     * ```
     * 
     * @param string $name กำหนดชื่อคอลัมน์
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @param mixed $default ค่าที่จะใช้เป็นค่าเริ่มต้นของคอลัมน์ (ค่าเริ่มต้น: null)
     * @return \App\Core\ColumnDefinition
     */
    public function float(string $name, bool $nullable = false, $default = null): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'FLOAT');
        if ($nullable) $col->nullable();
        if ($default !== null) $col->default($default);
        $this->columns[] = $col;
        return $col;
    }

    /**
     * สร้างคอลัมน์ประเภท DOUBLE
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บค่าตัวเลขทศนิยมที่มีความแม่นยำสูง
     * double() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บค่าตัวเลขทศนิยมที่มีความแม่นยำสูง เช่น ค่าทางวิทยาศาสตร์หรือค่าที่ต้องการความแม่นยำสูง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->double('value', true, 0);
     * ```
     * 
     * @param string $name กำหนดชื่อคอลัมน์
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @param mixed $default ค่าที่จะใช้เป็นค่าเริ่มต้นของคอลัมน์ (ค่าเริ่มต้น: null)
     * @return \App\Core\ColumnDefinition
     */
    public function double(string $name, bool $nullable = false, $default = null): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'DOUBLE');
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
     * สร้างคอลัมน์ประเภท DATE
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บข้อมูลวันที่
     * date() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บข้อมูลวันที่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->date('birth_date', true);
     * ```
     * 
     * @param string $name กำหนดชื่อคอลัมน์
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @param mixed $default ค่าที่จะใช้เป็นค่าเริ่มต้นของคอลัมน์ (ค่าเริ่มต้น: null)
     * @return \App\Core\ColumnDefinition
     */
    public function date(string $name, bool $nullable = false, $default = null): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'DATE');
        if ($nullable) $col->nullable();
        if ($default !== null) $col->default($default);
        $this->columns[] = $col;
        return $col;
    }

    /**
     * สร้างคอลัมน์ประเภท DATETIME
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บข้อมูลวันที่และเวลาที่มีความแม่นยำสูง
     * dateTime() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บข้อมูลวันที่และเวลาที่มีความแม่นยำสูง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->dateTime('event_time', true, 'CURRENT_TIMESTAMP');
     * ```
     * 
     * @param string $name กำหนดชื่อคอลัมน์
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @param mixed $default ค่าที่จะใช้เป็นค่าเริ่มต้นของคอลัมน์ (ค่าเริ่มต้น: null)
     * @return \App\Core\ColumnDefinition
     */
    public function dateTime(string $name, bool $nullable = false, $default = null): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'DATETIME');
        if ($nullable) $col->nullable();
        if ($default !== null) $col->default($default, true);
        $this->columns[] = $col;
        return $col;
    }

    /**
     * สร้างคอลัมน์ประเภท TIME
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บข้อมูลเวลา
     * time() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บข้อมูลเวลา
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->time('start_time', true, '00:00:00');
     * ```
     * 
     * @param string $name กำหนดชื่อคอลัมน์
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @param mixed $default ค่าที่จะใช้เป็นค่าเริ่มต้นของคอลัมน์ (ค่าเริ่มต้น: null)
     * @return \App\Core\ColumnDefinition
     */
    public function time(string $name, bool $nullable = false, $default = null): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'TIME');
        if ($nullable) $col->nullable();
        if ($default !== null) $col->default($default, true);
        $this->columns[] = $col;
        return $col;
    }

    /**
     * สร้างคอลัมน์ประเภท JSON
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บข้อมูล JSON
     * json() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บข้อมูล JSON
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->json('settings', true, '{}');
     * ```
     * 
     * @param string $name กำหนดชื่อคอลัมน์
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @param mixed $default ค่าที่จะใช้เป็นค่าเริ่มต้นของคอลัมน์ (ค่าเริ่มต้น: null)
     * @return \App\Core\ColumnDefinition
     */
    public function json(string $name, bool $nullable = false, $default = null): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'JSON');
        if ($nullable) $col->nullable();
        if ($default !== null) $col->default($default);
        $this->columns[] = $col;
        return $col;
    }

    /**
     * สร้างคอลัมน์ประเภท UUID
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บค่า UUID ซึ่งเป็นตัวระบุเอกลักษณ์ที่ไม่ซ้ำกันทั่วโลก
     * uuid() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บค่า UUID ซึ่งเป็นตัวระบุเอกลักษณ์ที่ไม่ซ้ำกันทั่วโลก
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->uuid('uuid', true);
     * ```
     * 
     * @param string $name กำหนดชื่อคอลัมน์
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @param mixed $default ค่าที่จะใช้เป็นค่าเริ่มต้นของคอลัมน์ (ค่าเริ่มต้น: null)
     * @return \App\Core\ColumnDefinition
     */
    public function uuid(string $name, bool $nullable = false, $default = null): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'CHAR(36)');
        if ($nullable) $col->nullable();
        if ($default !== null) $col->default($default);
        $this->columns[] = $col;
        return $col;
    }

    /**
     * สร้างคอลัมน์ประเภท BLOB
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บข้อมูลไบนารี
     * binary() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บข้อมูลไบนารี
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->binary('file_data', true);
     * ```
     * 
     * @param string $name กำหนดชื่อคอลัมน์
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @return \App\Core\ColumnDefinition
     */
    public function binary(string $name, bool $nullable = false): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'BLOB');
        if ($nullable) $col->nullable();
        $this->columns[] = $col;
        return $col;
    }

    /**
     * สร้างคอลัมน์ประเภท ENUM
     * จุดประสงค์: เพิ่มคอลัมน์ที่เก็บค่าจากชุดค่าที่กำหนดไว้ล่วงหน้า
     * enum() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ที่เก็บค่าจากชุดค่าที่กำหนดไว้ล่วงหน้า เช่น สถานะหรือประเภท
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->enum('status', ['active', 'inactive', 'pending'], true, 'active');
     * ```
     * 
     * @param string $name กำหนดชื่อคอลัมน์
     * @param array $values กำหนดชุดค่าที่อนุญาตให้เก็บในคอลัมน์นี้
     * @param bool $nullable กำหนดว่าคอลัมน์นี้อนุญาตค่า NULL หรือไม่ (ค่าเริ่มต้น: false)
     * @param mixed $default ค่าที่จะใช้เป็นค่าเริ่มต้นของคอลัมน์ (ค่าเริ่มต้น: null)
     * @return \App\Core\ColumnDefinition
     */
    public function enum(string $name, array $values, bool $nullable = false, $default = null): ColumnDefinition
    {
        $escaped = array_map(function ($value) {
            return "'" . addslashes((string) $value) . "'";
        }, $values);
        $col = new ColumnDefinition($name, 'ENUM(' . implode(', ', $escaped) . ')');
        if ($nullable) $col->nullable();
        if ($default !== null) $col->default($default);
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
     * เพิ่มคอลัมน์ deleted_at สำหรับ soft delete
     * จุดประสงค์: เพิ่มคอลัมน์ deleted_at เพื่อใช้ในการทำ soft delete ซึ่งจะไม่ลบข้อมูลจริง แต่จะทำเครื่องหมายว่าแถวถูกลบแล้ว
     * softDeletes() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มคอลัมน์ deleted_at ในตารางเพื่อใช้ในการทำ soft delete
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->softDeletes();
     * ```
     * 
     * @return \App\Core\Blueprint
     */
    public function softDeletes(): self
    {
        $deleted = new ColumnDefinition('deleted_at', 'TIMESTAMP');
        $deleted->nullable();
        $this->columns[] = $deleted;
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
    * @param string|array $column กำหนดชื่อคอลัมน์ที่จะสร้างดัชนีแบบ UNIQUE
     * @return \App\Core\Blueprint
     */
    public function unique(string|array $column): self
    {
        $columns = is_array($column) ? $column : [$column];
        $this->indexes[] = [
            'type' => 'UNIQUE',
            'columns' => $columns,
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
    * @param string|array $column กำหนดชื่อคอลัมน์ที่จะสร้างดัชนีแบบปกติ
     * @return \App\Core\Blueprint
     */
    public function index(string|array $column): self
    {
        $columns = is_array($column) ? $column : [$column];
        $this->indexes[] = [
            'type' => 'INDEX',
            'columns' => $columns,
        ];
        return $this;
    }

    /**
     * กำหนด primary key หลายคอลัมน์
     * จุดประสงค์: สร้าง primary key ที่ประกอบด้วยหลายคอลัมน์เพื่อรับประกันความเป็นเอกลักษณ์ของแถวในตาราง
     * primary() ควรใช้กับอะไร: เมื่อคุณต้องการสร้าง primary key ที่ประกอบด้วยหลายคอลัมน์เพื่อรับประกันความเป็นเอกลักษณ์ของแถวในตาราง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->primary(['user_id', 'order_id']);
     * ```
     * 
     * @param string|array $columns กำหนดชื่อคอลัมน์ที่จะใช้เป็น primary key (สามารถระบุหลายคอลัมน์ได้)
     * @return \App\Core\Blueprint
     */
    public function primary(string|array $columns): self
    {
        $this->primary = array_values(array_unique(array_merge($this->primary, (array) $columns)));
        return $this;
    }

    /**
     * สร้างคอลัมน์ unsigned bigint สำหรับ foreign key
     * จุดประสงค์: สร้างคอลัมน์ unsigned bigint เพื่อใช้เป็น foreign key
     * foreignId() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างคอลัมน์ unsigned bigint สำหรับ foreign key
     * ตัวอย่างการใช้งาน:
     * ```php
     * $blueprint->foreignId('user_id');
     * ```
     * 
     * @param string $name กำหนดชื่อคอลัมน์ที่จะใช้เป็น foreign key
     * @return \App\Core\ForeignKeyDefinition
     */
    public function foreignId(string $name): ForeignKeyDefinition
    {
        $this->unsignedBigInteger($name);
        return $this->foreign($name);
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
     * @param string|array $columns กำหนดชื่อคอลัมน์ที่จะใช้เป็น foreign key (สามารถระบุหลายคอลัมน์ได้)
     * @return \App\Core\ForeignKeyDefinition คืนค่าอินสแตนซ์ของ ForeignKeyDefinition ที่มีการตั้งค่าคอลัมน์ที่ใช้เป็น foreign key
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
     * ดึงชื่อไดรเวอร์ฐานข้อมูลที่กำหนดในคอนฟิก
     * จุดประสงค์: ดึงชื่อไดรเวอร์ฐานข้อมูลที่กำหนดในคอนฟิกเพื่อใช้ในการสร้างคำสั่ง SQL ที่เหมาะสมกับไดรเวอร์นั้น
     * getDriver() ควรใช้กับอะไร: เมื่อคุณต้องการดึงชื่อไดรเวอร์ฐานข้อมูลที่กำหนดในคอนฟิกเพื่อใช้ในการสร้างคำสั่ง SQL
     * ตัวอย่างการใช้งาน:
     * ```php
     * $driver = $blueprint->getDriver();
     * ```
     * 
     * @return string คืนค่าชื่อไดรเวอร์ฐานข้อมูลที่กำหนดในคอนฟิก
     */
    protected function getDriver(): string
    {
        return Config::get('database.connection', 'mysql');
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
    public function toCreateSql(): array|string
    {
        $driver = $this->getDriver();
        $parts = [];
        $indexStatements = [];
        foreach ($this->columns as $col) {
            if ($col instanceof ColumnDefinition) {
                $parts[] = $col->toSqlDefinition();

                if ($col->isPrimary() && !($driver === 'sqlite' && $col->isAutoIncrement())) {
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
            if ($driver === 'sqlite') {
                if ($ix['type'] === 'UNIQUE') {
                    $parts[] = "UNIQUE ({$cols})";
                } else {
                    $indexName = $this->escapeName($this->table . '_' . implode('_', $ix['columns']) . '_idx');
                    $indexStatements[] = 'CREATE INDEX IF NOT EXISTS ' . $indexName . ' ON ' . $this->escapeName($this->table) . ' (' . $cols . ')';
                }
            } else {
                if ($ix['type'] === 'UNIQUE') {
                    $parts[] = "UNIQUE ({$cols})";
                } else {
                    $parts[] = "INDEX ({$cols})";
                }
            }
        }

        // add foreign key constraints
        foreach ($this->foreignKeys as $fk) {
            if ($fk instanceof \App\Core\ForeignKeyDefinition) {
                $parts[] = $fk->toSql($this->table);
            }
        }

        $sql = 'CREATE TABLE IF NOT EXISTS ' . $this->escapeName($this->table) . ' (' . implode(', ', $parts) . ')';
        if ($driver !== 'sqlite') {
            $sql .= ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        }

        if ($driver === 'sqlite' && !empty($indexStatements)) {
            array_unshift($indexStatements, $sql . ';');
            return $indexStatements;
        }

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
        $driver = $this->getDriver();
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
            if ($driver === 'sqlite') {
                $indexName = $this->escapeName($this->table . '_' . implode('_', $ix['columns']) . '_idx');
                if ($ix['type'] === 'UNIQUE') {
                    $stmts[] = 'CREATE UNIQUE INDEX IF NOT EXISTS ' . $indexName . ' ON ' . $this->escapeName($this->table) . ' (' . $cols . ');';
                } else {
                    $stmts[] = 'CREATE INDEX IF NOT EXISTS ' . $indexName . ' ON ' . $this->escapeName($this->table) . ' (' . $cols . ');';
                }
            } else {
                if ($ix['type'] === 'UNIQUE') {
                    $stmts[] = 'ALTER TABLE ' . $this->escapeName($this->table) . ' ADD UNIQUE (' . $cols . ');';
                } else {
                    $stmts[] = 'ALTER TABLE ' . $this->escapeName($this->table) . ' ADD INDEX (' . $cols . ');';
                }
            }
        }

        return $stmts;
    }
}
