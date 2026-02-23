<?php
/**
 * คลาส Model พื้นฐาน สำหรับโมเดลทั้งหมด
 * 
 * จุดประสงค์: คลาสแม่สำหรับโมเดลทั้งหมด ให้ฟังก์ชันการทำงานทั่วไป
 * Model ควรใช้กับอะไร: เมื่อคุณต้องการสร้างโมเดลที่เชื่อมต่อกับฐานข้อมูลและจัดการข้อมูล
 * 
 * ฟีเจอร์หลัก:
 * - CRUD operations (Create, Read, Update, Delete)
 * - การเชื่อมต่อฐานข้อมูลผ่าน Database wrapper
 * - การแมปโมเดลกับตารางฐานข้อมูล
 * - การจัดการฟิลด์ fillable และ guarded
 * - การจัดการ timestamps (created_at, updated_at)
 * - การจัดการ soft deletes (deleted_at)
 * - Query builder เบื้องต้น
 * 
 * ตัวอย่างการใช้งานโดยรวม:
 * ```php
 * // สร้าง Model
 * class Product extends Model {
 *   protected string $table = 'products';
 *   protected array $fillable = ['name', 'price'];
 * }
 * 
 * // ใช้งาน
 * $product = new Product();
 * $product->name = 'iPhone';
 * $product->price = 30000;
 * $product->save();
 * 
 * // หรือ
 * $product = Product::create(['name' => 'Samsung Galaxy', 'price' => 25000]);
 * 
 * // Query CRUD
 * $allProducts = Product::all();
 * $product = Product::find(1);
 * $products = Product::where('price', '>', 20000)->orderBy('name')->get();
 * $product->delete();
 *
 * Quick Usage (ตัวอย่างสั้น ๆ)
 * - สร้างข้อมูล: 
 * Product::create([
 *      'name'=>'X',
 *      'price'=>100]
 * );
 * 
 * - หา/แก้ไข: 
 * $p = Product::find(1); 
 * $p->price = 120; 
 * $p->save();
 * 
 * - Query builder: 
 * Product::select('id,name')
 *      ->where('price','>',100)
 *      ->orderBy('id','DESC')
 *      ->get();
 * 
 * - Nested where: 
 * User::where(function($q){ 
 *      $q->where('a',1)->orWhere('b',2); 
 *   })->where('c',3)->get();
 * 
 * - Relations ใน `User` ให้เขียน: 
 * public function posts(){ 
 *      return $this->hasMany(Post::class); 
 * } 
 * แล้วเรียก `$user->posts()`
 * 
 * - Eager load: 
 * User::with('posts')->get();
 * 
 * - Transactions: 
 * Model::beginTransaction(); 
 * try{ 
 *      ... Model::commit(); 
 * } catch(\Throwable $e){ 
 *      Model::rollBack(); 
 * }
 * 
 * - Helpers: `whereBetween`, `whereIn`, `whereNull`, `whereRaw`, `paginate`, `pluck`, `updateOrCreate`, `firstOrCreate`
 *
 * หมายเหตุ: ควรใช้ `$fillable` หรือ `$guarded` เพื่อป้องกัน mass-assignment และหลีกเลี่ยงการใช้ `raw()` เว้นแต่จำเป็น
 * / ควรใช้ prepared statements เพื่อป้องกัน SQL injection
 */

namespace App\Core;

use App\Core\Database;

abstract class Model
{
    /**
     * ชื่อตาราง สำหรับโมเดลที่เลือกใช้
     */
    protected string $table = '';

    /**
     * Primary key สำหรับตาราง
     */
    protected string $primaryKey = 'id';

    /**
     * ฟิลด์ที่สามารถ mass assign ได้
     */
    protected array $fillable = [];

    /**
     * ฟิลด์ที่ไม่สามารถ mass assign ได้
     */
    protected array $guarded = ['id'];

    /**
     * เปิดใช้ timestamps (created_at, updated_at)
     */
    protected bool $timestamps = true;

    /**
     * เปิดใช้ soft deletes (deleted_at)
     */
    protected bool $softDeletes = false;

    /**
     * ข้อมูลของ model
     */
    protected array $attributes = [];

    /**
     * ข้อมูลเดิมของ model
     */
    protected array $original = [];

    /**
     * Query builder state ของโมเดล
     */
    protected array $query = [
        'select' => ['*'],
        'where' => [],
        'joins' => [],
        'orderBy' => [],
        'limit' => null,
        'offset' => null,
        'with' => [],
    ];

    /**
     * การเชื่อมต่อฐานข้อมูล
     */
    protected Database $db;

    /**
     * สร้างอินสแตนซ์ Model ใหม่
     * จุดประสงค์: กำหนดการเชื่อมต่อฐานข้อมูลและตั้งค่าเริ่มต้น
     * __construct() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างอินสแตนซ์ของโมเดลพร้อมกับกำหนดค่า attributes เริ่มต้น
     * ตัวอย่างการใช้งาน:
     * ```php
     * $model = new User(['name' => 'John', 'email' => 'john@example.com']);                                                                        
     * ```
     * @param array $attributes กำหนดค่า attributes เริ่มต้นสำหรับโมเดล
     */
    public function __construct(array $attributes = [])
    {
        $this->db = Database::getInstance();

        if ($attributes) {
            $this->fill($attributes);
        }

        // ถ้าไม่ได้ระบุชื่อตาราง ใช้ชื่อคลาสเป็นตัวพิมพ์เล็กและเป็นพหูพจน์
        if (empty($this->table)) {
            $className = (new \ReflectionClass($this))->getShortName();
            $this->table = strtolower($className) . 's';
        }
    }

    // ========== Magic Methods  ==========

    /**
     * รับค่า attribute
     * จุดประสงค์: ดึงค่าของ attribute จากอาร์เรย์ attributes
     * __get() ควรใช้กับอะไร: เมื่อคุณต้องการเข้าถึงค่าของ attribute ในโมเดล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $name = $model->name;
     * ```
     * 
     * @param string $key กำหนดชื่อ attribute ที่ต้องการดึงค่า
     * @return mixed คืนค่าของ attribute หรือ null หากไม่มีค่า
     */
    public function __get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * ตั้งค่า attribute
     * จุดประสงค์: กำหนดค่าของ attribute ในอาร์เรย์ attributes
     * __set() ควรใช้กับอะไร: เมื่อคุณต้องการตั้งค่าของ attribute ในโมเดล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $model->name = 'John';
     * ```
     * 
     * @param string $key กำหนดชื่อ attribute ที่ต้องการตั้งค่า
     * @param mixed $value กำหนดค่าของ attribute
     * @return void ไม่คืนค่าอะไร
     */
    public function __set(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * ตรวจสอบว่ามี attribute หรือไม่
     * จุดประสงค์: ตรวจสอบว่ามีการตั้งค่า attribute ในอาร์เรย์ attributes หรือไม่
     * __isset() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่ามีการตั้งค่า attribute ในโมเดลหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * if (isset($model->name)) { ... }
     * ```
     * 
     * @param string $key กำหนดชื่อ attribute ที่ต้องการตรวจสอบ
     * @return bool คืนค่า true ถ้ามีการตั้งค่า attribute, false ถ้าไม่มี
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    // ========== Mass Assignment ==========

    /**
     * Fill attributes
     * จุดประสงค์: กำหนดค่าของ attributes จากอาร์เรย์ โดยคำนึงถึง fillable/guarded
     * fill() ควรใช้กับอะไร: เมื่อคุณต้องการตั้งค่าหลายๆ attribute พร้อมกัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $model->fill(['name' => 'John', 'email' => 'john@example.com']);
     * ```
     * 
     * @param array $attributes กำหนดค่า attributes ที่ต้องการตั้งค่า
     * @return self คืนค่าอินสแตนซ์ของโมเดลหลังจากตั้งค่า attributes
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->attributes[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * ตรวจสอบว่า attribute สามารถ fill ได้หรือไม่
     * จุดประสงค์: ตรวจสอบว่า attribute นั้นอยู่ในรายการ fillable หรือไม่
     * isFillable() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่า attribute สามารถตั้งค่าได้ผ่าน mass assignment หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * if ($this->isFillable('name')) { ... }
     * ```
     * 
     * @param string $key กำหนดชื่อ attribute ที่ต้องการตรวจสอบ
     * @return bool คืนค่า true ถ้า attribute สามารถ fill ได้, false ถ้าไม่สามารถ fill ได้
     */
    protected function isFillable(string $key): bool
    {
        // ถ้ามี fillable และ key อยู่ใน fillable
        if (!empty($this->fillable)) {
            return in_array($key, $this->fillable);
        }

        // ถ้าไม่มี fillable แต่มี guarded
        if (!empty($this->guarded)) {
            return !in_array($key, $this->guarded);
        }

        return true;
    }

    // ========== CRUD Operations ==========

    /**
     * บันทึกข้อมูล (Create หรือ Update)
     * จุดประสงค์: บันทึกข้อมูลโมเดลลงในฐานข้อมูล
     * save() ควรใช้กับอะไร: เมื่อคุณต้องการบันทึกข้อมูลโมเดล ไม่ว่าจะเป็นการสร้างใหม่หรืออัปเดตข้อมูลเดิม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $model = new User();
     * $model->name = 'John';
     * $model->save();
     * ```
     * 
     * @return bool คืนค่า true หากบันทึกสำเร็จ, false หากล้มเหลว
     */
    public function save(): bool
    {
        if (isset($this->attributes[$this->primaryKey])) {
            return $this->update();
        }

        return $this->insert();
    }

    /**
     * Insert ข้อมูลใหม่
     * จุดประสงค์: แทรกข้อมูลโมเดลใหม่ลงในฐานข้อมูล
     * insert() ควรใช้กับอะไร: เมื่อคุณต้องการแทรกข้อมูลโมเดลใหม่ลงในฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->insert();
     * ```
     * 
     * ผลลัพธ์: คืนค่า true หากแทรกสำเร็จ, false หากล้มเหลว
     * 
     * @return bool คืนค่า true หากแทรกสำเร็จ, false หากล้มเหลว
     */
    protected function insert(): bool
    {
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $this->attributes['created_at'] = $now;
            $this->attributes['updated_at'] = $now;
        }

        $data = $this->getInsertableAttributes();

        if (empty($data)) {
            return false;
        }

        $fields = array_keys($data);
        $placeholders = array_map(fn($field) => ":{$field}", $fields);

        $sql = "INSERT INTO " . $this->quoteIdentifier($this->table) . " (" . implode(', ', array_map(fn($f) => $this->quoteIdentifier($f), $fields)) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->db->prepare($sql);

        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }

        $result = $stmt->execute();

        if ($result) {
            $this->attributes[$this->primaryKey] = $this->db->lastInsertId();
            $this->original = $this->attributes;
        }

        return $result;
    }

    /**
     * Update ข้อมูล
     * จุดประสงค์: อัปเดตข้อมูลโมเดลในฐานข้อมูล
     * update() ควรใช้กับอะไร: เมื่อคุณต้องการอัปเดตข้อมูลโมเดลที่มีอยู่ในฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->update();
     * ```
     * 
     * @return bool คืนค่า true หากอัปเดตสำเร็จ, false หากล้มเหลว
     * 
     */
    protected function update(): bool
    {
        if ($this->timestamps) {
            $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        }

        $data = $this->getUpdatableAttributes();

        if (empty($data) || !isset($this->attributes[$this->primaryKey])) {
            return false;
        }

        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = $this->quoteIdentifier($key) . " = :{$key}";
        }

        $sql = "UPDATE " . $this->quoteIdentifier($this->table) . " SET " . implode(', ', $fields) . " 
                WHERE " . $this->quoteIdentifier($this->primaryKey) . " = :{$this->primaryKey}";

        $stmt = $this->db->prepare($sql);

        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }

        // bind primary key
        $stmt->bindValue(":{$this->primaryKey}", $this->attributes[$this->primaryKey]);

        $result = $stmt->execute();

        if ($result) {
            $this->original = $this->attributes;
        }

        return $result;
    }

    /**
     * ลบข้อมูล
     * จุดประสงค์: ลบข้อมูลโมเดลจากฐานข้อมูล
     * delete() ควรใช้กับอะไร: เมื่อคุณต้องการลบข้อมูลโมเดลจากฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->delete();
     * ```
     * 
     * @return bool คืนค่า true หากลบสำเร็จ, false หากล้มเหลว
     */
    public function delete(): bool
    {
        if (!isset($this->attributes[$this->primaryKey])) {
            return false;
        }

        // Soft delete
        if ($this->softDeletes) {
            $this->attributes['deleted_at'] = date('Y-m-d H:i:s');
            return $this->update();
        }

        // Hard delete
        $sql = "DELETE FROM " . $this->quoteIdentifier($this->table) . " WHERE " . $this->quoteIdentifier($this->primaryKey) . " = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $this->attributes[$this->primaryKey]);

        return $stmt->execute();
    }

    // ========== Static Query Methods ==========

    /**
     * สร้างอินสแตนซ์ใหม่สำหรับ query
     * จุดประสงค์: สร้างอินสแตนซ์โมเดลใหม่เพื่อเริ่มต้น query builder
     * query() ควรใช้กับอะไร: เมื่อคุณต้องการเริ่มต้นการสร้าง query สำหรับโมเดล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::query()->where('status', 'active')->get();
     * ```
     * 
     * @return static
     */
    protected static function query()
    {
        return new static();
    }

    /**
     * รับข้อมูลทั้งหมด
     * จุดประสงค์: ดึงข้อมูลทั้งหมดจากตารางที่โมเดลแมปไว้
     * all() ควรใช้กับอะไร: เมื่อคุณต้องการดึงข้อมูลทั้งหมดจากตารางที่โมเดลแมปไว้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::all();
     * ```
     * 
     * all() ควรใช้กับอะไร: เมื่อคุณต้องการดึงข้อมูลทั้งหมดจากตารางที่โมเดลแมปไว้
     * 
     * @return array
     */
    public static function all(): array
    {
        $instance = static::query();
        $sql = "SELECT * FROM " . $instance->quoteIdentifier($instance->table);

        if ($instance->softDeletes) {
            $sql .= " WHERE deleted_at IS NULL";
        }

        return $instance->db->fetchAllAsClass($sql, [], static::class);
    }

    /**
     * ค้นหาด้วย primary key
     * จุดประสงค์: ดึงข้อมูลโมเดลตาม primary key
     * find() ควรใช้กับอะไร: เมื่อคุณต้องการค้นหาข้อมูลโมเดลตาม primary key
     * ตัวอย่างการใช้งาน:
     * ```php
     * $user = User::find(1);
     * ```
     *  
     * @param mixed $id กำหนดค่า primary key ที่ต้องการค้นหา
     * @return static|null คืนค่าอินสแตนซ์ของโมเดลหากพบ, null หากไม่พบ
     */
    public static function find($id): ?self
    {
        $instance = static::query();
        $sql = "SELECT * FROM " . $instance->quoteIdentifier($instance->table) . " 
            WHERE " . $instance->quoteIdentifier($instance->primaryKey) . " = :id";

        if ($instance->softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }

        $sql .= " LIMIT 1";

        $result = $instance->db->fetchAsClass($sql, ['id' => $id], static::class);

        return $result ?: null;
    }

    /**
     * สร้างและบันทึกข้อมูลใหม่
     * จุดประสงค์: สร้างอินสแตนซ์โมเดลใหม่และบันทึกลงฐานข้อมูล
     * create() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างและบันทึกข้อมูลโมเดลใหม่ในขั้นตอนเดียว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
     * ```
     * 
     * @param array $attributes กำหนดค่า attributes สำหรับโมเดลใหม่
     * @return static คืนค่าอินสแตนซ์ของโมเดลที่ถูกสร้างและบันทึกแล้ว
     */
    public static function create(array $attributes): self
    {
        $instance = new static($attributes);
        $instance->save();

        return $instance;
    }

    /**
     * Where clause
     * จุดประสงค์: เพิ่มเงื่อนไข where ใน query builder
     * where() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไขในการค้นหาข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::where('status', 'active')->get();
     * ```
     * 
     * @param string $column กำหนดชื่อคอลัมน์ที่ต้องการเงื่อนไข
     * @param string $operator กำหนดตัวดำเนินการเปรียบเทียบ เช่น '=', '>', '<'
     * @param mixed $value กำหนดค่าที่ต้องการเปรียบเทียบ
     * @param string $boolean กำหนดประเภทการเชื่อมต่อเงื่อนไข ('AND' หรือ 'OR')
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function where($column, $operator = null, $value = null, string $boolean = 'AND'): self
    {
        $instance = static::query();

        // ถ้าไม่มี operator (where('id', 1))
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $instance->query['where'][] = [
            'column' => $column,
            'operator' => strtoupper($operator),
            'value' => $value,
            'type' => 'AND'
        ];

        return $instance;
    }

    /**
     * orWhere convenience
     * จุดประสงค์: เพิ่มเงื่อนไข where แบบ OR ใน query builder
     * orWhere() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไขในการค้นหาข้อมูลโดยใช้ OR
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::where('status', 'active')->orWhere('role', 'admin')->get();
     * ```
     * 
     * @param string $column กำหนดชื่อคอลัมน์ที่ต้องการเงื่อนไข
     * @param string $operator กำหนดตัวดำเนินการเปรียบเทียบ เช่น '=', '>', '<'
     * @param mixed $value กำหนดค่าที่ต้องการเปรียบเทียบ
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function orWhere($column, $operator = null, $value = null): self
    {
        return static::where($column, $operator, $value, 'OR');
    }

    /**
     * whereRaw (supports ? placeholders)
     * จุดประสงค์: เพิ่มเงื่อนไข where แบบ raw SQL ใน query builder
     * whereRaw() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไข where โดยใช้ SQL ดิบ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereRaw("age > ? AND status = ?", [18, 'active'])->get();
     * ```
     * 
     * @param string $sql กำหนด SQL ดิบที่ต้องการใช้ในเงื่อนไข where
     * @param array $bindings กำหนดค่าที่จะผูกกับ placeholders ใน SQL
     * @param string $boolean กำหนดประเภทการเชื่อมต่อเงื่อนไข ('AND' หรือ 'OR')
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function whereRaw(string $sql, array $bindings = [], string $boolean = 'AND'): self
    {
        $instance = static::query();
        $instance->query['where'][] = [
            'type' => 'raw',
            'sql' => $sql,
            'bindings' => array_values($bindings),
            'boolean' => strtoupper($boolean),
        ];

        return $instance;
    }

    /**
     * orWhereRaw
     * จุดประสงค์: เพิ่มเงื่อนไข where แบบ raw SQL ใน query builder โดยใช้ OR
     * orWhereRaw() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไข where โดยใช้ SQL ดิบ และเชื่อมต่อด้วย OR
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereRaw("age > ? AND status = ?", [18, 'active'])->orWhereRaw("role = ?", ['admin'])->get();
     * ```
     * 
     * @param string $sql กำหนด SQL ดิบที่ต้องการใช้ในเงื่อนไข where
     * @param array $bindings กำหนดค่าที่จะผูกกับ placeholders ใน SQL
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function orWhereRaw(string $sql, array $bindings = []): self
    {
        return static::whereRaw($sql, $bindings, 'OR');
    }

    /**
     * whereBetween
     * จุดประสงค์: เพิ่มเงื่อนไข where แบบ between ใน query builder
     * whereBetween() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไข where แบบระหว่างค่าที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereBetween('age', [18, 30])->get();
     * ```
     * 
     * @param string $column กำหนดชื่อคอลัมน์ที่ต้องการเงื่อนไข
     * @param array $values กำหนดค่าที่ต้องการใช้ในเงื่อนไข between
     * @param string $boolean กำหนดประเภทการเชื่อมต่อเงื่อนไข ('AND' หรือ 'OR')
     * @param bool $not กำหนดว่าเป็นเงื่อนไข NOT BETWEEN หรือไม่
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function whereBetween(string $column, array $values, string $boolean = 'AND', bool $not = false): self
    {
        $instance = static::query();
        $instance->query['where'][] = [
            'type' => 'between',
            'column' => $column,
            'values' => $values,
            'boolean' => strtoupper($boolean),
            'not' => $not,
        ];

        return $instance;
    }

    /**
     * orWhereBetween
     * จุดประสงค์: เพิ่มเงื่อนไข where แบบ between ใน query builder โดยใช้ OR
     * orWhereBetween() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไข where แบบระหว่างค่าที่กำหนด โดยใช้ OR
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereBetween('age', [18, 30])->orWhereBetween('age', [40, 50])->get();
     * ```
     * 
     * @param string $column กำหนดชื่อคอลัมน์ที่ต้องการเงื่อนไข
     * @param array $values กำหนดค่าที่ต้องการใช้ในเงื่อนไข between
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function orWhereBetween(string $column, array $values): self
    {
        return static::whereBetween($column, $values, 'OR', false);
    }

    /**
     * whereNull
     * จุดประสงค์: เพิ่มเงื่อนไข where แบบ is null ใน query builder
     * whereNull() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไข where แบบ is null
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereNull('deleted_at')->get();
     * ```
     * 
     * @param string $column กำหนดชื่อคอลัมน์ที่ต้องการเงื่อนไข
     * @param string $boolean กำหนดประเภทการเชื่อมต่อเงื่อนไข ('AND' หรือ 'OR')
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function whereNull(string $column, string $boolean = 'AND'): self
    {
        $instance = static::query();
        $instance->query['where'][] = [
            'type' => 'null',
            'column' => $column,
            'not' => false,
            'boolean' => strtoupper($boolean),
        ];

        return $instance;
    }

    /**
     * orWhereNull
     * จุดประสงค์: เพิ่มเงื่อนไข where แบบ is null ใน query builder โดยใช้ OR
     * orWhereNull() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไข where แบบ is null โดยใช้ OR
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereNull('deleted_at')->orWhereNull('last_login')->get();
     * ```
     * 
     * @param string $column กำหนดชื่อคอลัมน์ที่ต้องการเงื่อนไข
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function orWhereNull(string $column): self
    {
        return static::whereNull($column, 'OR');
    }

    /**
     * whereNotNull
     * จุดประสงค์: เพิ่มเงื่อนไข where แบบ is not null ใน query builder
     * whereNotNull() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไข where แบบ is not null
     * ```php
     * $users = User::whereNotNull('email_verified_at')->get();
     * ```
     * 
     * @param string $column กำหนดชื่อคอลัมน์ที่ต้องการเงื่อนไข
     * @param string $boolean กำหนดประเภทการเชื่อมต่อเงื่อนไข ('AND' หรือ 'OR')
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function whereNotNull(string $column, string $boolean = 'AND'): self
    {
        $instance = static::query();
        $instance->query['where'][] = [
            'type' => 'null',
            'column' => $column,
            'not' => true,
            'boolean' => strtoupper($boolean),
        ];

        return $instance;
    }

    /**
     * orWhereNotNull
     * จุดประสงค์: เพิ่มเงื่อนไข where แบบ is not null ใน query builder โดยใช้ OR
     * orWhereNotNull() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไข where แบบ is not null โดยใช้ OR
     * ```php
     * $users = User::whereNotNull('email_verified_at')->orWhereNotNull('last_login')->get();
     * ```
     * 
     * @param string $column กำหนดชื่อคอลัมน์ที่ต้องการเงื่อนไข
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function orWhereNotNull(string $column): self
    {
        return static::whereNotNull($column, 'OR');
    }

    /**
     * whereIn
     * จุดประสงค์: เพิ่มเงื่อนไข where แบบ IN ใน query builder
     * whereIn() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไข where แบบ IN
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereIn('status', ['active', 'pending'])->get();
     * ```
     * 
     * @param string $column กำหนดชื่อคอลัมน์ที่ต้องการเงื่อนไข
     * @param array $values กำหนดค่าที่ต้องการใช้ในเงื่อนไข IN
     * @param string $boolean กำหนดประเภทการเชื่อมต่อเงื่อนไข ('AND' หรือ 'OR')
     * @param bool $not กำหนดว่าเป็นเงื่อนไข NOT IN หรือไม่
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function whereIn(string $column, array $values, string $boolean = 'AND', bool $not = false): self
    {
        $op = $not ? 'NOT IN' : 'IN';
        return static::where($column, $op, $values, $boolean);
    }

    /**
     * orWhereIn
     * จุดประสงค์: เพิ่มเงื่อนไข where แบบ IN ใน query builder โดยใช้ OR
     * orWhereIn() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไข where แบบ IN โดยใช้ OR
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereIn('status', ['active', 'pending'])->orWhereIn('role', ['admin', 'editor'])->get();
     * ```
     * 
     * @param string $column กำหนดชื่อคอลัมน์ที่ต้องการเงื่อนไข
     * @param array $values กำหนดค่าที่ต้องการใช้ในเงื่อนไข IN
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function orWhereIn(string $column, array $values): self
    {
        return static::whereIn($column, $values, 'OR', false);
    }

    /**
     * whereNotIn
     * จุดประสงค์: เพิ่มเงื่อนไข where แบบ NOT IN ใน query builder
     * whereNotIn() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไข where แบบ NOT IN
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereNotIn('status', ['inactive', 'banned'])->get();
     * ```
     * 
     * @param string $column กำหนดชื่อคอลัมน์ที่ต้องการเงื่อนไข
     * @param array $values กำหนดค่าที่ต้องการใช้ในเงื่อนไข NOT IN
     * @param string $boolean กำหนดประเภทการเชื่อมต่อเงื่อนไข ('AND' หรือ 'OR')
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */ 
    public static function whereNotIn(string $column, array $values, string $boolean = 'AND'): self
    {
        return static::whereIn($column, $values, $boolean, true);
    }

    /**
     * orWhereNotIn
     * จุดประสงค์: เพิ่มเงื่อนไข where แบบ NOT IN ใน query builder โดยใช้ OR
     * orWhereNotIn() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไข where แบบ NOT IN โดยใช้ OR
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereNotIn('status', ['inactive', 'banned'])->orWhereNotIn('role', ['guest', 'subscriber'])->get();
     * ```
     * 
     * @param string $column กำหนดชื่อคอลัมน์ที่ต้องการเงื่อนไข
     * @param array $values กำหนดค่าที่ต้องการใช้ในเงื่อนไข NOT IN
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function orWhereNotIn(string $column, array $values): self
    {
        return static::whereNotIn($column, $values, 'OR');
    }

    /**
     * whereColumn (compare two columns)
     * จุดประสงค์: เพิ่มเงื่อนไข where เพื่อเปรียบเทียบค่าของสองคอลัมน์ใน query builder
     * whereColumn() ควรใช้กับอะไร: เมื่อคุณต้องการเปรียบเทียบค่าของสองคอลัมน์ในเงื่อนไข where
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereColumn('created_by', '=', 'updated_by')->get();
     * ```
     * 
     * @param string $first กำหนดชื่อคอลัมน์แรกที่ต้องการเปรียบเทียบ
     * @param string $operator กำหนดตัวดำเนินการเปรียบเทียบ (เช่น '=', '<>', '>', '<', '>=', '<=')
     * @param string $second กำหนดชื่อคอลัมน์ที่สองที่ต้องการเปรียบเทียบ
     * @param string $boolean กำหนดประเภทการเชื่อมต่อเงื่อนไข ('AND' หรือ 'OR')
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function whereColumn(string $first, string $operator, string $second, string $boolean = 'AND'): self
    {
        $instance = static::query();
        $instance->query['where'][] = [
            'type' => 'column',
            'first' => $first,
            'operator' => strtoupper($operator),
            'second' => $second,
            'boolean' => strtoupper($boolean),
        ];

        return $instance;
    }

    /**
     * whereExists (subquery SQL)
     * จุดประสงค์: เพิ่มเงื่อนไข where แบบ EXISTS ใน query builder
     * whereExists() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไข where แบบ EXISTS
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereExists('SELECT 1 FROM orders WHERE orders.user_id = users.id')->get();
     * ```
     * 
     * @param string $subquerySql กำหนดคำสั่ง SQL ของ subquery
     * @param array $bindings กำหนดค่าที่ต้องการใช้ใน subquery
     * @param string $boolean กำหนดประเภทการเชื่อมต่อเงื่อนไข ('AND' หรือ 'OR')
     * @param bool $not กำหนดว่าเป็นเงื่อนไข NOT EXISTS หรือไม่
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function whereExists(string $subquerySql, array $bindings = [], string $boolean = 'AND', bool $not = false): self
    {
        $instance = static::query();
        $instance->query['where'][] = [
            'type' => 'exists',
            'sql' => $subquerySql,
            'bindings' => array_values($bindings),
            'boolean' => strtoupper($boolean),
            'not' => $not,
        ];

        return $instance;
    }

    /**
     * orWhereExists
     * จุดประสงค์: เพิ่มเงื่อนไข where แบบ EXISTS ใน query builder โดยใช้ OR
     * orWhereExists() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไข where แบบ EXISTS โดยใช้ OR
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereExists('SELECT 1 FROM orders WHERE orders.user_id = users.id')
     *          ->orWhereExists('SELECT 1 FROM profiles WHERE profiles.user_id = users.id')
     *          ->get();
     * ```
     * 
     * @param string $subquerySql กำหนดคำสั่ง SQL ของ subquery
     * @param array $bindings กำหนดค่าที่ต้องการใช้ใน subquery
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function orWhereExists(string $subquerySql, array $bindings = []): self
    {
        return static::whereExists($subquerySql, $bindings, 'OR', false);
    }

    /**
     * whereLike
     * จุดประสงค์: เพิ่มเงื่อนไข where แบบ LIKE ใน query builder
     * whereLike() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไข where แบบ LIKE
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereLike('name', '%John%')->get();
     * ```
     * 
     * @param string $column กำหนดชื่อคอลัมน์ที่ต้องการเงื่อนไข
     * @param string $value กำหนดค่าที่ต้องการใช้ในเงื่อนไข LIKE
     * @param string $boolean กำหนดประเภทการเชื่อมต่อเงื่อนไข ('AND' หรือ 'OR')
     * @param bool $not กำหนดว่าเป็นเงื่อนไข NOT LIKE หรือไม่
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง
     */
    public static function whereLike(string $column, string $value, string $boolean = 'AND', bool $not = false): self
    {
        $op = $not ? 'NOT LIKE' : 'LIKE';
        return static::where($column, $op, $value, $boolean);
    }

    public static function orWhereLike(string $column, string $value): self
    {
        return static::whereLike($column, $value, 'OR', false);
    }

    /**
     * whereDate
     * จุดประสงค์: เพิ่มเงื่อนไข where สำหรับวันที่ใน query builder
     * whereDate() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไข where สำหรับวันที่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereDate('created_at', '2024-01-01')->get();
     * ```
     * 
     * @param string $column กำหนดชื่อคอลัมน์ที่ต้องการเงื่อนไข
     * @param string $value กำหนดค่าที่ต้องการใช้ในเงื่อนไข
     * @param string $boolean กำหนดประเภทการเชื่อมต่อเงื่อนไข ('AND' หรือ 'OR')
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function whereDate(string $column, string $value, string $boolean = 'AND'): self
    {
        return static::whereRaw("DATE(" . $column . ") = ?", [$value], $boolean);
    }

    /**
     * whereMonth
     * จุดประสงค์: เพิ่มเงื่อนไข where สำหรับเดือนใน query builder
     * whereMonth() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไข where สำหรับเดือน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereMonth('created_at', 1)->get();
     * ```
     * 
     * @param string $column กำหนดชื่อคอลัมน์ที่ต้องการเงื่อนไข
     * @param int $month กำหนดค่าของเดือนที่ต้องการใช้ในเงื่อนไข
     * @param string $boolean กำหนดประเภทการเชื่อมต่อเงื่อนไข ('AND' หรือ 'OR')
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function whereMonth(string $column, int $month, string $boolean = 'AND'): self
    {
        return static::whereRaw("MONTH(" . $column . ") = ?", [$month], $boolean);
    }

    /**
     * whereYear
     * จุดประสงค์: เพิ่มเงื่อนไข where สำหรับปีใน query builder
     * whereYear() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไข where สำหรับปี
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereYear('created_at', 2024)->get();
     * ```
     * 
     * @param string $column กำหนดชื่อคอลัมน์ที่ต้องการเงื่อนไข
     * @param int $year กำหนดค่าของปีที่ต้องการใช้ในเงื่อนไข
     * @param string $boolean กำหนดประเภทการเชื่อมต่อเงื่อนไข ('AND' หรือ 'OR')
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function whereYear(string $column, int $year, string $boolean = 'AND'): self
    {
        return static::whereRaw("YEAR(" . $column . ") = ?", [$year], $boolean);
    }

    /**
     * whereDay
     * จุดประสงค์: เพิ่มเงื่อนไข where สำหรับวันใน query builder
     * whereDay() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเงื่อนไข where สำหรับวัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereDay('created_at', 15)->get();
     * ```
     * 
     * @param string $column กำหนดชื่อคอลัมน์ที่ต้องการเงื่อนไข
     * @param int $day กำหนดค่าของวันที่ต้องการใช้ในเงื่อนไข
     * @param string $boolean กำหนดประเภทการเชื่อมต่อเงื่อนไข ('AND' หรือ 'OR')
     * @return self คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function whereDay(string $column, int $day, string $boolean = 'AND'): self
    {
        return static::whereRaw("DAY(" . $column . ") = ?", [$day], $boolean);
    }

    /**
     * Order By
     * จุดประสงค์: เพิ่มคำสั่ง ORDER BY ใน query builder
     * orderBy() ควรใช้กับอะไร: เมื่อคุณต้องการจัดเรียงผลลัพธ์ของ query
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::orderBy('created_at', 'DESC')->get();
     * ```
     * 
     * @param string $column กำหนดชื่อคอลัมน์ที่ต้องการจัดเรียง
     * @param string $direction กำหนดทิศทางการจัดเรียง ('ASC' หรือ 'DESC')
     * @return static คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function orderBy(string $column, string $direction = 'ASC'): self
    {
        $instance = static::query();
        $direction = $instance->sanitizeDirection($direction);
        $instance->query['orderBy'][] = $instance->quoteColumnOrExpression($column) . " {$direction}";

        return $instance;
    }

    /**
     * Select columns
     * จุดประสงค์: กำหนดคอลัมน์ที่จะเลือกใน query builder
     * select() ควรใช้กับอะไร: เมื่อคุณต้องการระบุคอลัมน์ที่ต้องการดึงข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::select('id, name, email')->get();
     * ```
     *
     * @param array|string $columns กำหนดคอลัมน์ที่ต้องการเลือก
     * @return static คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function select($columns): self
    {
        $instance = static::query();

        if (is_string($columns)) {
            $columns = array_map('trim', explode(',', $columns));
        }

        $processed = [];
        foreach ($columns as $col) {
            $processed[] = $instance->quoteColumnOrExpression($col);
        }

        $instance->query['select'] = $processed;

        return $instance;
    }

    /**
     * Join clause
     * จุดประสงค์: เพิ่มคำสั่ง JOIN ใน query builder
     * join() ควรใช้กับอะไร: เมื่อคุณต้องการเชื่อมตารางในฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::join('profiles', 'users.id', '=', 'profiles.user_id')->get();
     * ```
     *
     * @param string $table กำหนดชื่อตารางที่ต้องการเชื่อม
     * @param string $first กำหนดคอลัมน์แรกในเงื่อนไขการเชื่อม
     * @param string $operator กำหนดตัวดำเนินการในการเชื่อม (เช่น '=', '<', '>')
     * @param string $second กำหนดคอลัมน์ที่สองในเงื่อนไขการเชื่อม
     * @param string $type กำหนดประเภทของการเชื่อม (เช่น 'INNER', 'LEFT', 'RIGHT')
     * @return static คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $instance = static::query();
        $instance->query['joins'][] = [
            'type' => $type,
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $instance;
    }

    /**
     * Limit
     * จุดประสงค์: กำหนดจำนวนแถวสูงสุดที่จะดึงใน query builder
     * limit() ควรใช้กับอะไร: เมื่อคุณต้องการจำกัดจำนวนผลลัพธ์ที่ดึงมา
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::limit(10)->get();
     * ```
     * 
     * @param int $limit กำหนดจำนวนแถวสูงสุดที่จะดึง
     * @return static คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function limit(int $limit): self
    {
        $instance = static::query();
        $instance->query['limit'] = $limit;

        return $instance;
    }

    /**
     * Offset
     * จุดประสงค์: กำหนดจำนวนแถวที่จะข้ามใน query builder
     * offset() ควรใช้กับอะไร: เมื่อคุณต้องการข้ามจำนวนผลลัพธ์ที่ดึงมา
     * ```php
     * $users = User::offset(5)->get();
     * ```
     * 
     * @param int $offset กำหนดจำนวนแถวที่จะข้าม
     * @return static คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function offset(int $offset): self
    {
        $instance = static::query();
        $instance->query['offset'] = $offset;

        return $instance;
    }

    /**
     * Execute query และรับผลลัพธ์
     * จุดประสงค์: ดำเนินการ query ที่สร้างขึ้นและดึงผลลัพธ์
     * get() ควรใช้กับอะไร: เมื่อคุณต้องการดึงข้อมูลจากฐานข้อมูลตามเงื่อนไขที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::get();
     * ```
     * 
     * @return array คืนค่าอาร์เรย์ของผลลัพธ์ที่ได้จากการ query
     */
    public function get(): array
    {
        $sql = "SELECT " . implode(', ', $this->query['select']) . " FROM " . $this->quoteIdentifier($this->table);

        // JOINS
        if (!empty($this->query['joins'])) {
            foreach ($this->query['joins'] as $join) {
                $sql .= " {$join['type']} JOIN " . $this->quoteIdentifier($join['table']) . " ON " . $this->quoteColumnOrExpression($join['first']) . " " . strtoupper($join['operator']) . " " . $this->quoteColumnOrExpression($join['second']);
            }
        }

        // WHERE clauses
        $bindings = [];
        if (!empty($this->query['where'])) {
            $whereClauses = [];
            $index = 0;
            $clause = $this->compileWhere($this->query['where'], $bindings, $index);
            $sql .= " WHERE " . $clause;

            // Soft deletes
            if ($this->softDeletes) {
                $sql .= " AND " . $this->quoteIdentifier('deleted_at') . " IS NULL";
            }
        } elseif ($this->softDeletes) {
            $sql .= " WHERE " . $this->quoteIdentifier('deleted_at') . " IS NULL";
        }

        // ORDER BY
        if (!empty($this->query['orderBy'])) {
            $sql .= " ORDER BY " . implode(', ', $this->query['orderBy']);
        }

        // LIMIT
        if ($this->query['limit']) {
            $sql .= " LIMIT " . (int)$this->query['limit'];
        }

        // OFFSET
        if ($this->query['offset']) {
            $sql .= " OFFSET " . (int)$this->query['offset'];
        }

        $results = $this->db->fetchAllAsClass($sql, $bindings, static::class);

        // Eager load relations if requested
        if (!empty($this->query['with']) && $results) {
            foreach ($this->query['with'] as $relation) {
                foreach ($results as $model) {
                    if (method_exists($model, $relation)) {
                        $model->{$relation} = $model->{$relation}();
                    }
                }
            }
        }

        return $results;
    }

    /**
     * With (eager loading)
     * จุดประสงค์: กำหนดความสัมพันธ์ที่ต้องการโหลดล่วงหน้าใน query builder
     * with() ควรใช้กับอะไร: เมื่อคุณต้องการโหลดความสัมพันธ์ของโมเดลพร้อมกับข้อมูลหลัก
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::with('posts, profile')->get();
     * ```
     *
     * @param array|string $relations กำหนดความสัมพันธ์ที่ต้องการโหลดล่วงหน้า
     * @return static คืนค่าอินสแตนซ์ของโมเดลสำหรับการสร้าง query
     */
    public static function with($relations): self
    {
        $instance = static::query();

        if (is_string($relations)) {
            $relations = array_map('trim', explode(',', $relations));
        }

        $instance->query['with'] = $relations;

        return $instance;
    }

    /**
     * Execute raw SQL
     * จุดประสงค์: ดำเนินการคำสั่ง SQL ดิบพร้อมการผูกค่า
     * raw() ควรใช้กับอะไร: เมื่อคุณต้องการรันคำสั่ง SQL ดิบที่ซับซ้อนหรือเฉพาะเจาะจง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $results = User::raw("SELECT * FROM users WHERE status = ?", ['active']);
     * ```
     * 
     * @param string $sql กำหนดคำสั่ง SQL ดิบที่ต้องการรัน
     * @param array $bindings กำหนดค่าที่จะผูกกับคำสั่ง SQL
     * @return array|bool คืนค่าอาร์เรย์ของผลลัพธ์หรือ false หากล้มเหลว
     */
    public static function raw(string $sql, array $bindings = [])
    {
        $instance = static::query();
        try {
            return $instance->db->fetchAll($sql, $bindings);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Begin transaction
     * จุดประสงค์: เริ่มต้นการทำธุรกรรมฐานข้อมูล
     * beginTransaction() ควรใช้กับอะไร: เมื่อคุณต้องการเริ่มต้นการทำธุรกรรมฐานข้อมูลเพื่อให้สามารถ commit หรือ rollback ได้
     * ตัวอย่างการใช้งาน:
     * ```php
     * Model::beginTransaction();
     * ```
     * 
     * @return bool คืนค่า true หากเริ่มต้นสำเร็จ
     */
    public static function beginTransaction(): bool
    {
        $instance = static::query();
        return $instance->db->beginTransaction();
    }

    /**
     * Commit transaction
     * จุดประสงค์: ยืนยันการทำธุรกรรมฐานข้อมูล
     * commit() ควรใช้กับอะไร: เมื่อคุณต้องการยืนยันการทำธุรกรรมฐานข้อมูลที่เริ่มต้นไว้
     * ตัวอย่างการใช้งาน:
     * ```php
     * Model::commit();
     * ```
     * 
     * @return bool คืนค่า true หากยืนยันสำเร็จ
     */
    public static function commit(): bool
    {
        $instance = static::query();
        return $instance->db->commit();
    }

    /**
     * Rollback transaction
     * จุดประสงค์: ยกเลิกการทำธุรกรรมฐานข้อมูล
     * rollBack() ควรใช้กับอะไร: เมื่อคุณต้องการยกเลิกการทำธุรกรรมฐานข้อมูลที่เริ่มต้นไว้
     * ตัวอย่างการใช้งาน:
     * ```php
     * Model::rollBack();
     * ```
     * 
     * @return bool คืนค่า true หากยกเลิกสำเร็จ
     */
    public static function rollBack(): bool
    {
        $instance = static::query();
        return $instance->db->rollBack();
    }

    /**
     * Simple hasMany relation
     * จุดประสงค์: ดึงข้อมูลความสัมพันธ์แบบ hasMany
     * hasMany() ควรใช้กับอะไร: เมื่อคุณต้องการดึงข้อมูลความสัมพันธ์แบบ hasMany
     * ตัวอย่างการใช้งาน:
     * ```php
     * $posts = $user->hasMany(Post::class, 'user_id', 'id');
     * ```
     * 
     * @return array คืนค่าอาร์เรย์ของอินสแตนซ์โมเดลที่เกี่ยวข้อง
     *
     * @param string $relatedClass กำหนดชื่อคลาสของโมเดลที่เกี่ยวข้อง
     * @param string|null $foreignKey กำหนดชื่อคีย์ต่างประเทศ
     * @param string|null $localKey กำหนดชื่อคีย์ท้องถิ่น
     * @return array คืนค่าอาร์เรย์ของอินสแตนซ์โมเดลที่เกี่ยวข้อง
     */
    public function hasMany(string $relatedClass, ?string $foreignKey = null, ?string $localKey = null): array
    {
        $related = new $relatedClass();

        $localKey = $localKey ?? $this->primaryKey;
        $foreignKey = $foreignKey ?? strtolower((new \ReflectionClass($this))->getShortName()) . '_id';

        return $related::where($foreignKey, $this->attributes[$localKey] ?? null)->get();
    }

    /**
     * Simple hasOne relation
     * จุดประสงค์: ดึงข้อมูลความสัมพันธ์แบบ hasOne
     * hasOne() ควรใช้กับอะไร: เมื่อคุณต้องการดึงข้อมูลความสัมพันธ์แบบ hasOne
     * ตัวอย่างการใช้งาน:
     * ```php
     * $profile = $user->hasOne(Profile::class, 'user_id', 'id');
     * ```
     * 
     * ผลลัพธ์: คืนค่าอินสแตนซ์ของโมเดลที่เกี่ยวข้อง หรือ null หากไม่มี
     * 
     * @return self|null คืนค่าอินสแตนซ์ของโมเดลที่เกี่ยวข้อง หรือ null หากไม่มี
     */
    public function hasOne(string $relatedClass, ?string $foreignKey = null, ?string $localKey = null): ?self
    {
        $related = new $relatedClass();

        $localKey = $localKey ?? $this->primaryKey;
        $foreignKey = $foreignKey ?? strtolower((new \ReflectionClass($this))->getShortName()) . '_id';

        return $related::where($foreignKey, $this->attributes[$localKey] ?? null)->first();
    }

    /**
     * Simple belongsTo relation
     * จุดประสงค์: ดึงข้อมูลความสัมพันธ์แบบ belongsTo
     * belongsTo() ควรใช้กับอะไร: เมื่อคุณต้องการดึงข้อมูลความสัมพันธ์แบบ belongsTo
     * ตัวอย่างการใช้งาน:
     * ```php
     * $user = $post->belongsTo(User::class, 'user_id', 'id');
     * ```
     * 
     * @param string $relatedClass กำหนดชื่อคลาสของโมเดลที่เกี่ยวข้อง
     * @param string|null $foreignKey กำหนดชื่อคีย์ต่างประเทศ                                         
     * @param string|null $ownerKey กำหนดชื่อคีย์เจ้าของ
     * @return self|null คืนค่าอินสแตนซ์ของโมเดลที่เกี่ยวข้อง หรือ null หากไม่มี
     */
    public function belongsTo(string $relatedClass, ?string $foreignKey = null, ?string $ownerKey = null): ?self
    {
        $related = new $relatedClass();

        $foreignKey = $foreignKey ?? strtolower((new \ReflectionClass($related))->getShortName()) . '_id';
        $ownerKey = $ownerKey ?? $related->getPrimaryKey();

        return $related::where($ownerKey, $this->attributes[$foreignKey] ?? null)->first();
    }

    /**
     * Restore soft deleted item
     * จุดประสงค์: คืนค่ารายการที่ถูกลบแบบ soft delete
     * restore() ควรใช้กับอะไร: เมื่อคุณต้องการคืนค่ารายการที่ถูกลบแบบ soft delete
     * ตัวอย่างการใช้งาน:
     * ```php
     * $user->restore();
     * ```
     * 
     * @return bool คืนค่า true หากคืนค่าสำเร็จ
     */
    public function restore(): bool
    {
        if (!$this->softDeletes || !isset($this->attributes[$this->primaryKey])) {
            return false;
        }

        $this->attributes['deleted_at'] = null;
        return $this->update();
    }

    /**
     * Force delete
     * จุดประสงค์: ลบรายการอย่างถาวรโดยไม่คำนึงถึง soft delete
     * forceDelete() ควรใช้กับอะไร: เมื่อคุณต้องการลบรายการอย่างถาวรโดยไม่คำนึงถึง soft delete
     * ตัวอย่างการใช้งาน:
     * ```php
     * $user->forceDelete();
     * ```
     * 
     * @return bool คืนค่า true หากลบสำเร็จ
     */
    public function forceDelete(): bool
    {
        if (!isset($this->attributes[$this->primaryKey])) {
            return false;
        }

        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $this->attributes[$this->primaryKey]);

        return $stmt->execute();
    }

    /**
     * Update or create
     * จุดประสงค์: อัปเดตรายการหากมีอยู่ หรือสร้างรายการใหม่หากไม่มี
     * updateOrCreate() ควรใช้กับอะไร: เมื่อคุณต้องการอัปเดตรายการหากมีอยู่ หรือสร้างรายการใหม่หากไม่มี
     * ตัวอย่างการใช้งาน:
     * ```php
     * $user = User::updateOrCreate(['email' => 'example@example.com'], ['name' => 'John Doe']);
     * ```
     * 
     * @return self คืนค่าอินสแตนซ์ของโมเดลที่อัปเดตหรือสร้างขึ้น
     */
    public static function updateOrCreate(array $attributes, array $values = []): self
    {
        $instance = static::where(array_keys($attributes)[0], array_values($attributes)[0])->first();

        if ($instance) {
            $instance->fill(array_merge($instance->attributes, $values));
            $instance->save();
            return $instance;
        }

        return static::create(array_merge($attributes, $values));
    }

    /**
     * First or create
     * จุดประสงค์: ดึงรายการแรกที่ตรงกับเงื่อนไข หรือสร้างรายการใหม่หากไม่มี
     * firstOrCreate() ควรใช้กับอะไร: เมื่อคุณต้องการดึงรายการแรกที่ตรงกับเงื่อนไข หรือสร้างรายการใหม่หากไม่มี
     * ตัวอย่างการใช้งาน:
     * ```php
     * $user = User::firstOrCreate(['email' => 'example@example.com'], ['name' => 'John Doe']);
     * ```
     * 
     * @param array $attributes กำหนดเงื่อนไขการค้นหา
     * @param array $values กำหนดค่าที่จะใช้ในการสร้างรายการใหม่
     * @return self คืนค่าอินสแตนซ์ของโมเดลที่อัปเดตหรือสร้างขึ้น
     * 
     */
    public static function firstOrCreate(array $attributes, array $values = []): self
    {
        $instance = static::where(array_keys($attributes)[0], array_values($attributes)[0])->first();

        if ($instance) {
            return $instance;
        }

        return static::create(array_merge($attributes, $values));
    }

    /**
     * Paginate results
     * จุดประสงค์: ดึงผลลัพธ์แบบแบ่งหน้า
     * paginate() ควรใช้กับอะไร: เมื่อคุณต้องการดึงผลลัพธ์แบบแบ่งหน้า
     * ตัวอย่างการใช้งาน:
     * ```php
     * $paginated = User::paginate(10, 2);
     * ```
     * 
     * @return array คืนค่าอาร์เรย์ที่มีข้อมูลการแบ่งหน้า
     * 
     */
    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $total = $this->count();

        $this->query['limit'] = $perPage;
        $this->query['offset'] = $offset;

        $data = $this->get();

        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int)ceil($total / $perPage),
        ];
    }

    /**
     * Pluck a single column
     * จุดประสงค์: ดึงค่าของคอลัมน์เดียวจากผลลัพธ์
     * pluck() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่าของคอลัมน์เดียวจากผลลัพธ์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $emails = User::where('status', 'active')->pluck('email');
     * ```
     * 
     * @return array คืนค่าอาร์เรย์ของค่าคอลัมน์ที่ระบุ
     */
    public function pluck(string $column): array
    {
        $this->query['select'] = [$column];
        $rows = $this->get();
        $values = [];
        foreach ($rows as $row) {
            $values[] = $row->{$column} ?? null;
        }

        return $values;
    }

    /**
     * Check existence
     * จุดประสงค์: ตรวจสอบว่ามีรายการที่ตรงกับเงื่อนไขหรือไม่
     * exists() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่ามีรายการที่ตรงกับเงื่อนไขหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * 
     * $exists = User::where('email', 'example@example.com')->exists();
     * ```
     * 
     * @return bool คืนค่า true หากมีรายการที่ตรงกับเงื่อนไข, false หากไม่มี
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Compile where clauses
     * จุดประสงค์: แปลงอาร์เรย์เงื่อนไข where เป็น SQL และการผูกค่าแบบเรียกซ้ำ
     * compileWhere() ควรใช้กับอะไร: เมื่อคุณต้องการแปลงอาร์เรย์เงื่อนไข where เป็น SQL และการผูกค่าแบบเรียกซ้ำ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $whereArray = [
     *   ['type' => 'basic', 'column' => 'status', 'operator' => '=', 'value' => 'active', 'boolean' => 'AND'],
     *   ['type' => 'group', 'queries' => [
     *       ['type' => 'basic', 'column' => 'age', 'operator' => '>', 'value' => 18, 'boolean' => 'AND'],
     *       ['type' => 'basic', 'column' => 'age', 'operator' => '<', 'value' => 65, 'boolean' => 'AND'],
     *   ], 'boolean' => 'OR'],
     * ];
     * $bindings = [];
     * $index = 0;
     * $sqlWhere = $this->compileWhere($whereArray, $bindings, $index);
     * ```
     * 
     * @return string คืนค่า SQL เงื่อนไข where
     * 
     * @param array $wheres
     * @param array $bindings (by ref)
     * @param int $index (by ref) placeholder counter
     * @return string
     */
    protected function compileWhere(array $wheres, array &$bindings, int &$index): string
    {
        $parts = [];

        foreach ($wheres as $where) {
            $boolean = isset($where['boolean']) ? strtoupper($where['boolean']) : 'AND';

            if (isset($where['type']) && $where['type'] === 'group') {
                $inner = $this->compileWhere($where['queries'], $bindings, $index);
                if (empty($parts)) {
                    $parts[] = "(" . $inner . ")";
                } else {
                    $parts[] = $boolean . " (" . $inner . ")";
                }
                continue;
            }

            // handle different where types
            $type = $where['type'] ?? 'basic';

            if ($type === 'raw') {
                // raw SQL with ? placeholders
                $sql = $where['sql'];
                $rawBindings = $where['bindings'] ?? [];

                // replace each ? with a named placeholder
                $pos = 0;
                foreach ($rawBindings as $j => $val) {
                    $ph = "w_{$index}_{$j}";
                    $qpos = strpos($sql, '?', $pos);
                    if ($qpos === false) {
                        // no more ?; append binding separately
                        $bindings[$ph] = $val;
                        continue;
                    }
                    $sql = substr_replace($sql, ":{$ph}", $qpos, 1);
                    $bindings[$ph] = $val;
                    $pos = $qpos + strlen($ph) + 1;
                }

                if (empty($parts)) {
                    $parts[] = $sql;
                } else {
                    $parts[] = $boolean . ' ' . $sql;
                }

                $index++;
                continue;
            }

            if ($type === 'between') {
                $vals = $where['values'] ?? [];
                if (!is_array($vals) || count($vals) < 2) {
                    throw new \InvalidArgumentException('whereBetween requires array with two values');
                }

                $ph1 = "w_{$index}_0";
                $ph2 = "w_{$index}_1";
                $not = !empty($where['not']);
                $sqlPart = $this->quoteIdentifier($where['column']) . ($not ? ' NOT BETWEEN ' : ' BETWEEN ') . ":{$ph1} AND :{$ph2}";

                if (empty($parts)) {
                    $parts[] = $sqlPart;
                } else {
                    $parts[] = $boolean . ' ' . $sqlPart;
                }

                $bindings[$ph1] = $vals[0];
                $bindings[$ph2] = $vals[1];
                $index++;
                continue;
            }

            if ($type === 'null') {
                $not = !empty($where['not']);
                $sqlPart = $this->quoteIdentifier($where['column']) . ($not ? ' IS NOT NULL' : ' IS NULL');
                if (empty($parts)) {
                    $parts[] = $sqlPart;
                } else {
                    $parts[] = $boolean . ' ' . $sqlPart;
                }
                continue;
            }

            if ($type === 'exists') {
                $sql = $where['sql'];
                $rawBindings = $where['bindings'] ?? [];
                $not = !empty($where['not']);

                // replace ? with named placeholders
                $pos = 0;
                foreach ($rawBindings as $j => $val) {
                    $ph = "w_{$index}_{$j}";
                    $qpos = strpos($sql, '?', $pos);
                    if ($qpos === false) {
                        $bindings[$ph] = $val;
                        continue;
                    }
                    $sql = substr_replace($sql, ":{$ph}", $qpos, 1);
                    $bindings[$ph] = $val;
                    $pos = $qpos + strlen($ph) + 1;
                }

                $sqlPart = ($not ? 'NOT EXISTS (' : 'EXISTS (') . $sql . ')';
                if (empty($parts)) {
                    $parts[] = $sqlPart;
                } else {
                    $parts[] = $boolean . ' ' . $sqlPart;
                }

                $index++;
                continue;
            }

            if ($type === 'column') {
                $first = $this->quoteColumnOrExpression($where['first']);
                $second = $this->quoteColumnOrExpression($where['second']);
                $op = strtoupper($where['operator'] ?? '=');
                $allowed = ['=', '!=', '<>', '<', '>', '<=', '>='];
                if (!in_array($op, $allowed, true)) {
                    throw new \InvalidArgumentException("Invalid operator for column comparison: {$where['operator']}");
                }

                $sqlPart = $first . " " . $op . " " . $second;
                if (empty($parts)) {
                    $parts[] = $sqlPart;
                } else {
                    $parts[] = $boolean . ' ' . $sqlPart;
                }

                continue;
            }

            // basic comparisons
            $op = strtoupper($where['operator'] ?? '=');
            $allowed = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'IS', 'IS NOT'];
            if (!in_array($op, $allowed, true)) {
                throw new \InvalidArgumentException("Invalid operator: {$where['operator']}");
            }

            if (in_array($op, ['IN', 'NOT IN']) && is_array($where['value'])) {
                $placeholders = [];
                foreach ($where['value'] as $j => $val) {
                    $ph = "w_{$index}_{$j}";
                    $placeholders[] = ":{$ph}";
                    $bindings[$ph] = $val;
                }

                if (empty($parts)) {
                    $parts[] = $this->quoteIdentifier($where['column']) . " {$op} (" . implode(', ', $placeholders) . ")";
                } else {
                    $parts[] = $boolean . " " . $this->quoteIdentifier($where['column']) . " {$op} (" . implode(', ', $placeholders) . ")";
                }

                $index++;
            } else {
                $ph = "w_{$index}";
                if (empty($parts)) {
                    $parts[] = $this->quoteIdentifier($where['column']) . " {$op} :{$ph}";
                } else {
                    $parts[] = $boolean . " " . $this->quoteIdentifier($where['column']) . " {$op} :{$ph}";
                }

                $bindings[$ph] = $where['value'];
                $index++;
            }
        }

        return implode(' ', $parts);
    }

    /**
     * รับผลลัพธ์ชุดแรก
     * จุดประสงค์: ดึงแถวแรกที่ตรงกับเงื่อนไข
     * first() ควรใช้กับอะไร: เมื่อคุณต้องการดึงแถวแรกที่ตรงกับเงื่อนไข
     * ตัวอย่างการใช้งาน:
     * ```php
     * $user = User::where('email', 'example@example.com')->first();
     * ```
     * 
     * @return static|null คืนค่าอินสแตนซ์ของโมเดลหรือ null หากไม่มีผลลัพธ์
     */
    public function first(): ?self
    {
        $this->query['limit'] = 1;
        $results = $this->get();

        return $results[0] ?? null;
    }

    /**
     * นับจำนวนแถว
     * จุดประสงค์: นับจำนวนแถวที่ตรงกับเงื่อนไข
     * count() ควรใช้กับอะไร: เมื่อคุณต้องการนับจำนวนแถวที่ตรงกับเงื่อนไข
     * ตัวอย่างการใช้งาน:
     * ```php
     * $count = User::where('status', 'active')->count();
     * ```
     * 
     * @return int คืนค่าจำนวนแถวที่ตรงกับเงื่อนไข
     */
    public function count(): int
    {
        $this->query['select'] = ['COUNT(*) as count'];
        $results = $this->get();

        return (int)($results[0]->count ?? 0);
    }

    // ========== Helper Methods ==========

    /**
     * รับข้อมูลทั้งหมดเป็น array
     * จุดประสงค์: แปลงข้อมูลโมเดลเป็นอาร์เรย์
     * toArray() ควรใช้กับอะไร: เมื่อคุณต้องการแปลงข้อมูลโมเดลเป็นอาร์เรย์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $userArray = $user->toArray();
     * ```
     * 
     * @return array คืนค่าอาร์เรย์ของข้อมูลโมเดล
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * ป้องกันตัวระบุ SQL (ตารางหรือคอลัมน์) โดยรองรับการระบุแบบจุด
     * จุดประสงค์: ป้องกันตัวระบุ SQL (ตารางหรือคอลัมน์) โดยรองรับการระบุแบบจุด
     * quoteIdentifier() ควรใช้กับอะไร: เมื่อคุณต้องการป้องกันตัวระบุ SQL เช่น ชื่อตารางหรือคอลัมน์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $quoted = $this->quoteIdentifier('users.name');
     * ```
     * 
     * @param string $identifier ชื่อตัวระบุที่ต้องการป้องกัน
     * @return string คืนค่าตัวระบุที่ถูกป้องกัน
     */
    protected function quoteIdentifier(string $identifier): string
    {
        // support dot notation for table.column
        $parts = explode('.', $identifier);
        $safe = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '*' ) {
                $safe[] = $part;
                continue;
            }

            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $part)) {
                throw new \InvalidArgumentException("Invalid identifier: {$identifier}");
            }

            $safe[] = "`" . $part . "`";
        }

        return implode('.', $safe);
    }

    /**
     * ป้องกันคอลัมน์หรือคืนค่า expression ตามที่เป็น
     * จุดประสงค์: ป้องกันคอลัมน์หรือคืนค่า expression ตามที่เป็นสำหรับฟังก์ชัน/นามแฝง
     * quoteColumnOrExpression() ควรใช้กับอะไร: เมื่อคุณต้องการป้องกันคอลัมน์หรือคืนค่า expression ตามที่เป็น
     * ตัวอย่างการใช้งาน:
     * ```php
     * $quoted = $this->quoteColumnOrExpression('COUNT(users.id) AS user_count');
     * ```
     * 
     * @param string $col ชื่อคอลัมน์หรือ expression
     * @return string คืนค่าคอลัมน์ที่ถูกป้องกันหรือ expression ตามที่เป็น
     */
    protected function quoteColumnOrExpression(string $col): string
    {
        $lower = strtolower($col);

        if (strpos($col, '(') !== false || strpos($lower, ' as ') !== false || trim($col) === '*' ) {
            // likely an expression or alias, return as-is but keep safety for dotted identifiers before AS
            if (stripos($col, ' as ') !== false) {
                [$left, $right] = preg_split('/\s+as\s+/i', $col, 2);
                return $this->quoteColumnOrExpression($left) . ' AS ' . $this->quoteIdentifier($right);
            }

            return $col;
        }

        // simple column, may be dotted
        return $this->quoteIdentifier($col);
    }

    /**
     * Sanitize order direction
     * จุดประสงค์: ทำความสะอาดทิศทางการจัดเรียง (ASC/DESC)
     * sanitizeDirection() ควรใช้กับอะไร: เมื่อคุณต้องการทำความสะอาดทิศทางการจัดเรียง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $dir = $this->sanitizeDirection('desc');
     * ```
     * 
     * @param string $dir ทิศทางการจัดเรียงที่ต้องการทำความสะอาด
     * @return string คืนค่าทิศทางการจัดเรียงที่ถูกต้อง (ASC หรือ DESC)
     */
    protected function sanitizeDirection(string $dir): string
    {
        $dir = strtoupper(trim($dir));
        return $dir === 'DESC' ? 'DESC' : 'ASC';
    }

    /**
     * Get attributes allowed for insert
     * จุดประสงค์: รับข้อมูลที่อนุญาตให้แทรกในฐานข้อมูล
     * getInsertableAttributes() ควรใช้กับอะไร: เมื่อคุณต้องการรับข้อมูลที่อนุญาตให้แทรกในฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $data = $this->getInsertableAttributes();
     * ```
     * ผลลัพธ์: คืนค่าอาร์เรย์ของแอตทริบิวต์ที่อนุญาตให้แทรก
     */
    protected function getInsertableAttributes(): array
    {
        $data = [];
        foreach ($this->attributes as $key => $value) {
            if ($key === $this->primaryKey) {
                continue;
            }

            if ($this->isFillable($key)) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Get attributes allowed for update
     * จุดประสงค์: รับข้อมูลที่อนุญาตให้อัปเดตในฐานข้อมูล
     * getUpdatableAttributes() ควรใช้กับอะไร: เมื่อคุณต้องการรับข้อมูลที่อนุญาตให้อัปเดตในฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $data = $this->getUpdatableAttributes();
     * ```
     * 
     * @return array คืนค่าอาร์เรย์ของแอตทริบิวต์ที่อนุญาตให้อัปเดต
     */
    protected function getUpdatableAttributes(): array
    {
        $data = [];
        foreach ($this->attributes as $key => $value) {
            if ($key === $this->primaryKey) {
                continue;
            }

            if ($this->isFillable($key)) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * รับข้อมูลเป็น JSON
     * จุดประสงค์: แปลงข้อมูลโมเดลเป็น JSON
     * toJson() ควรใช้กับอะไร: เมื่อคุณต้องการแปลงข้อมูลโมเดลเป็น JSON
     * ตัวอย่างการใช้งาน:
     * ```php
     * $json = $user->toJson();
     * ```
     * 
     * @return string คืนค่า JSON ของข้อมูลโมเดล
     */
    public function toJson(): string
    {
        return json_encode($this->attributes);
    }

    /**
     * Refresh ข้อมูลจากฐานข้อมูล
     * จุดประสงค์: ดึงข้อมูลล่าสุดจากฐานข้อมูลและอัปเดตแอตทริบิวต์ของโมเดล
     * refresh() ควรใช้กับอะไร: เมื่อคุณต้องการดึงข้อมูลล่าสุดจากฐานข้อมูลและอัปเดตแอตทริบิวต์ของโมเดล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $user->refresh();
     * ```
     * 
     * @return self คืนค่าอินสแตนซ์ของโมเดลหลังจากรีเฟรชข้อมูล
     */
    public function refresh(): self
    {
        if (!isset($this->attributes[$this->primaryKey])) {
            return $this;
        }

        $fresh = static::find($this->attributes[$this->primaryKey]);

        if ($fresh) {
            $this->attributes = $fresh->attributes;
            $this->original = $fresh->original;
        }

        return $this;
    }

    /**
     * ตรวจสอบว่ามีการเปลี่ยนแปลงหรือไม่
     * จุดประสงค์: ตรวจสอบว่าแอตทริบิวต์ของโมเดลมีการเปลี่ยนแปลงตั้งแต่โหลดครั้งล่าสุดหรือไม่
     * isDirty() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าแอตทริบิวต์ของโมเดลมีการเปลี่ยนแปลงตั้งแต่โหลดครั้งล่าสุดหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * if ($user->isDirty()) {
     *     // มีการเปลี่ยนแปลง
     * }
     * ```
     * 
     * @return bool คืนค่า true หากมีการเปลี่ยนแปลง, false หากไม่มี
     */
    public function isDirty(): bool
    {
        return $this->attributes !== $this->original;
    }

    // ========== Getter Methods for Testing ==========

    /**
     * รับชื่อตาราง
     * จุดประสงค์: รับชื่อของตารางที่โมเดลเชื่อมโยง
     * getTable() ควรใช้กับอะไร: เมื่อคุณต้องการรับชื่อของตารางที่โมเดลเชื่อมโยง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $table = $user->getTable();
     * ```
     * 
     * @return string คืนค่าชื่อตาราง
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * รับชื่อ primary key
     * จุดประสงค์: รับชื่อของ primary key ที่โมเดลใช้
     * getPrimaryKey() ควรใช้กับอะไร: เมื่อคุณต้องการรับชื่อของ primary key ที่โมเดลใช้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $pk = $user->getPrimaryKey();
     * ```
     * 
     * @return string คืนค่าชื่อ primary key
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * รับ fillable fields
     * จุดประสงค์: รับรายการของฟิลด์ที่อนุญาตให้เติมข้อมูล
     * getFillable() ควรใช้กับอะไร: เมื่อคุณต้องการรับรายการของฟิลด์ที่อนุญาตให้เติมข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $fillable = $user->getFillable();
     * ```
     * 
     * @return array คืนค่าอาร์เรย์ของฟิลด์ที่อนุญาตให้เติมข้อมูล
     */
    public function getFillable(): array
    {
        return $this->fillable;
    }

    /**
     * รับ guarded fields
     * จุดประสงค์: รับรายการของฟิลด์ที่ถูกป้องกันไม่ให้เติมข้อมูล
     * getGuarded() ควรใช้กับอะไร: เมื่อคุณต้องการรับรายการของฟิลด์ที่ถูกป้องกันไม่ให้เติมข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $guarded = $user->getGuarded();
     * ```
     * 
     * @return array คืนค่าอาร์เรย์ของฟิลด์ที่ถูกป้องกันไม่ให้เติมข้อมูล
     */
    public function getGuarded(): array
    {
        return $this->guarded;
    }
}
