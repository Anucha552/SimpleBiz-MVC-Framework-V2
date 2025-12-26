<?php
/**
 * คลาส Model พื้นฐาน
 * 
 * จุดประสงค์: คลาสแม่สำหรับโมเดลทั้งหมด ให้ฟังก์ชัน CRUD พื้นฐาน
 * ฟีเจอร์: Query Builder, Active Record Pattern, CRUD operations
 * 
 * ฟีเจอร์หลัก:
 * - CRUD operations พื้นฐาน (Create, Read, Update, Delete)
 * - Query Builder แบบง่าย
 * - Soft Deletes (ไม่บังคับ)
 * - Timestamps (created_at, updated_at)
 * - Mass assignment protection
 * 
 * ตัวอย่างการใช้งาน:
 * ```php
 * // สร้าง Model
 * class Product extends Model {
 *     protected $table = 'products';
 *     protected $fillable = ['name', 'price', 'description'];
 * }
 * 
 * // ใช้งาน
 * $product = new Product();
 * $product->name = 'iPhone';
 * $product->price = 30000;
 * $product->save();
 * 
 * // หรือ
 * $product = Product::create([
 *     'name' => 'iPhone',
 *     'price' => 30000
 * ]);
 * 
 * // Query
 * $products = Product::all();
 * $product = Product::find(1);
 * $products = Product::where('price', '>', 10000)->get();
 * ```
 */

namespace App\Core;

use PDO;

abstract class Model
{
    /**
     * ชื่อตาราง
     */
    protected string $table = '';

    /**
     * Primary key
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
     * Query builder state
     */
    protected array $query = [
        'select' => ['*'],
        'where' => [],
        'orderBy' => [],
        'limit' => null,
        'offset' => null,
    ];

    /**
     * Database connection
     */
    protected PDO $db;

    /**
     * สร้างอินสแตนซ์ Model ใหม่
     * 
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->db = Database::getInstance()->getConnection();

        if ($attributes) {
            $this->fill($attributes);
        }

        // ถ้าไม่ได้ระบุชื่อตาราง ใช้ชื่อคลาสเป็นตัวพิมพ์เล็กและเป็นพหูพจน์
        if (empty($this->table)) {
            $className = (new \ReflectionClass($this))->getShortName();
            $this->table = strtolower($className) . 's';
        }
    }

    // ========== Magic Methods ==========

    /**
     * รับค่า attribute
     */
    public function __get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * ตั้งค่า attribute
     */
    public function __set(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * ตรวจสอบว่ามี attribute หรือไม่
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    // ========== Mass Assignment ==========

    /**
     * Fill attributes
     * 
     * @param array $attributes
     * @return self
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
     * 
     * @return bool
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
     */
    protected function insert(): bool
    {
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $this->attributes['created_at'] = $now;
            $this->attributes['updated_at'] = $now;
        }

        $fields = array_keys($this->attributes);
        $placeholders = array_map(fn($field) => ":{$field}", $fields);

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->db->prepare($sql);

        foreach ($this->attributes as $key => $value) {
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
     */
    protected function update(): bool
    {
        if ($this->timestamps) {
            $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        }

        $fields = [];
        foreach ($this->attributes as $key => $value) {
            if ($key !== $this->primaryKey) {
                $fields[] = "{$key} = :{$key}";
            }
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " 
                WHERE {$this->primaryKey} = :{$this->primaryKey}";

        $stmt = $this->db->prepare($sql);

        foreach ($this->attributes as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }

        $result = $stmt->execute();

        if ($result) {
            $this->original = $this->attributes;
        }

        return $result;
    }

    /**
     * ลบข้อมูล
     * 
     * @return bool
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
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $this->attributes[$this->primaryKey]);

        return $stmt->execute();
    }

    // ========== Static Query Methods ==========

    /**
     * สร้างอินสแตนซ์ใหม่สำหรับ query
     * 
     * @return static
     */
    protected static function query()
    {
        return new static();
    }

    /**
     * รับข้อมูลทั้งหมด
     * 
     * @return array
     */
    public static function all(): array
    {
        $instance = static::query();
        $sql = "SELECT * FROM {$instance->table}";

        if ($instance->softDeletes) {
            $sql .= " WHERE deleted_at IS NULL";
        }

        $stmt = $instance->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_CLASS, static::class);
    }

    /**
     * ค้นหาด้วย primary key
     * 
     * @param mixed $id
     * @return static|null
     */
    public static function find($id): ?self
    {
        $instance = static::query();
        $sql = "SELECT * FROM {$instance->table} 
                WHERE {$instance->primaryKey} = :id";

        if ($instance->softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }

        $sql .= " LIMIT 1";

        $stmt = $instance->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        $stmt->setFetchMode(PDO::FETCH_CLASS, static::class);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * สร้างและบันทึกข้อมูลใหม่
     * 
     * @param array $attributes
     * @return static
     */
    public static function create(array $attributes): self
    {
        $instance = new static($attributes);
        $instance->save();

        return $instance;
    }

    /**
     * Where clause
     * 
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return static
     */
    public static function where(string $column, string $operator, $value = null): self
    {
        $instance = static::query();

        // ถ้าไม่มี operator (where('id', 1))
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $instance->query['where'][] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'type' => 'AND'
        ];

        return $instance;
    }

    /**
     * Order By
     * 
     * @param string $column
     * @param string $direction
     * @return static
     */
    public static function orderBy(string $column, string $direction = 'ASC'): self
    {
        $instance = static::query();
        $instance->query['orderBy'][] = "{$column} {$direction}";

        return $instance;
    }

    /**
     * Limit
     * 
     * @param int $limit
     * @return static
     */
    public static function limit(int $limit): self
    {
        $instance = static::query();
        $instance->query['limit'] = $limit;

        return $instance;
    }

    /**
     * Offset
     * 
     * @param int $offset
     * @return static
     */
    public static function offset(int $offset): self
    {
        $instance = static::query();
        $instance->query['offset'] = $offset;

        return $instance;
    }

    /**
     * Execute query และรับผลลัพธ์
     * 
     * @return array
     */
    public function get(): array
    {
        $sql = "SELECT " . implode(', ', $this->query['select']) . " FROM {$this->table}";

        // WHERE clauses
        $bindings = [];
        if (!empty($this->query['where'])) {
            $whereClauses = [];
            foreach ($this->query['where'] as $index => $where) {
                $placeholder = "where_{$index}";
                $whereClauses[] = "{$where['column']} {$where['operator']} :{$placeholder}";
                $bindings[$placeholder] = $where['value'];
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);

            // Soft deletes
            if ($this->softDeletes) {
                $sql .= " AND deleted_at IS NULL";
            }
        } elseif ($this->softDeletes) {
            $sql .= " WHERE deleted_at IS NULL";
        }

        // ORDER BY
        if (!empty($this->query['orderBy'])) {
            $sql .= " ORDER BY " . implode(', ', $this->query['orderBy']);
        }

        // LIMIT
        if ($this->query['limit']) {
            $sql .= " LIMIT {$this->query['limit']}";
        }

        // OFFSET
        if ($this->query['offset']) {
            $sql .= " OFFSET {$this->query['offset']}";
        }

        $stmt = $this->db->prepare($sql);

        foreach ($bindings as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_CLASS, static::class);
    }

    /**
     * รับผลลัพธ์ชุดแรก
     * 
     * @return static|null
     */
    public function first(): ?self
    {
        $this->query['limit'] = 1;
        $results = $this->get();

        return $results[0] ?? null;
    }

    /**
     * นับจำนวนแถว
     * 
     * @return int
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
     * 
     * @return array
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * รับข้อมูลเป็น JSON
     * 
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->attributes);
    }

    /**
     * Refresh ข้อมูลจากฐานข้อมูล
     * 
     * @return self
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
     * 
     * @return bool
     */
    public function isDirty(): bool
    {
        return $this->attributes !== $this->original;
    }
}
