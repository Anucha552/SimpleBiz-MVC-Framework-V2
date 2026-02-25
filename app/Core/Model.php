<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\QueryBuilder;
use App\Core\RawExpression;
use App\Core\Database;
use App\Core\ModelQueryBuilder;

/**
 * Minimal Model wrapper that proxies to QueryBuilder.
 * Keeps no SQL state and only holds a shared Database instance.
 */
abstract class Model
{
    // Table name for the concrete model (set in subclasses)
    protected static string $table;

    // Shared Database instance for all models
    protected static Database $db;

    // Primary key column name
    protected static string $primaryKey = 'id';

    // Mass-assignment control
    protected static array $fillable = [];
    protected static array $guarded = ['id'];

    // Timestamp & soft delete flags
    protected static bool $timestamps = true;
    protected static bool $softDeletes = false;

    /**
     * Set Database instance used by all models.
     */
    public static function setConnection(Database $db): void
    {
        static::$db = $db;
    }

    /**
     * Start a new QueryBuilder for this model's table.
     */
    public static function query(): QueryBuilder
    {
        // ตรวจสอบว่ามีการตั้งค่าการเชื่อมต่อฐานข้อมูลและชื่อตารางหรือไม่ก่อนที่จะสร้าง QueryBuilder
        if (!isset(static::$db)) {
            throw new \RuntimeException('Database connection not configured. Call Model::setConnection($db) first.');
        }

        // ตรวจสอบว่าชื่อตารางถูกตั้งค่าในโมเดลหรือไม่ เพราะจำเป็นสำหรับการสร้าง QueryBuilder
        if (!isset(static::$table) || static::$table === '') {
            throw new \RuntimeException('Model::$table not set on ' . static::class);
        }

        // สร้างและคืนค่า ModelQueryBuilder ซึ่งเป็น subclass ของ QueryBuilder ที่มีการจัดการ soft delete อัตโนมัติ
        return new ModelQueryBuilder(static::$db, static::$table, static::class, true);
    }

    /* ---------- Convenience proxies to QueryBuilder ---------- */
    public static function select(array|string $columns = '*'): QueryBuilder
    {
        return static::query()->select($columns);
    }

    public static function where(callable|string $column, ?string $operator = null, mixed $value = null): QueryBuilder
    {
        return static::query()->where($column, $operator, $value);
    }

    public static function orWhere(string $column, string $operator, mixed $value): QueryBuilder
    {
        return static::query()->orWhere($column, $operator, $value);
    }

    public static function whereIn(string $column, array $values): QueryBuilder
    {
        return static::query()->whereIn($column, $values);
    }

    public static function whereNull(string $column): QueryBuilder
    {
        return static::query()->whereNull($column);
    }

    public static function orderBy(string $column, string $direction = 'ASC'): QueryBuilder
    {
        return static::query()->orderBy($column, $direction);
    }

    public static function limit(int $limit): QueryBuilder
    {
        return static::query()->limit($limit);
    }

    public static function offset(int $offset): QueryBuilder
    {
        return static::query()->offset($offset);
    }

    /**
     * Create a new record and return inserted id.
     */
    public static function create(array $data): int
    {
        // ตรวจสอบการตั้งค่าการเชื่อมต่อฐานข้อมูลก่อนทำการแทรก
        if (!isset(static::$db)) {
            throw new \RuntimeException(
                'Database connection not configured. Call Model::setConnection($db) before creating records. For CLI/tests you can call: Model::setConnection(App\\Core\\Database::getInstance())'
            );
        }

        $data = static::prepareInsertData($data); // เตรียมข้อมูลสำหรับการแทรก (กรอง fillable, เพิ่ม timestamps)
        return static::query()->insertGetId($data); // ใช้เมธอด insertGetId() ของ QueryBuilder เพื่อแทรกข้อมูลและคืนค่า id ที่แทรก
    }

    /**
     * Find by primary key using model's primaryKey.
     */
    public static function find(mixed $id): ?array
    {
        return static::query()->where(static::$primaryKey, '=', $id)->first();
    }

    /**
     * Return a query builder that ignores soft-delete constraint.
     */
    public static function withTrashed(): QueryBuilder
    {
        if (!isset(static::$db)) {
            throw new \RuntimeException('Database connection not configured. Call Model::setConnection($db) first.');
        }
        if (!isset(static::$table) || static::$table === '') {
            throw new \RuntimeException('Model::$table not set on ' . static::class);
        }

        return new ModelQueryBuilder(static::$db, static::$table, static::class, false);
    }

    /**
     * Return a query builder scoped to only soft-deleted rows.
     */
    public static function onlyTrashed(): QueryBuilder
    {
        $qb = static::withTrashed();
        return $qb->whereNotNull('deleted_at');
    }

    /* ---------- Mass assignment & timestamps helpers ---------- */
    /**
     * กรองข้อมูลตาม fillable/guarded ก่อนที่จะทำการแทรกหรืออัพเดต
     */
    protected static function filterFillable(array $data): array
    {
        if (!empty(static::$fillable)) {
            return array_intersect_key($data, array_flip(static::$fillable));
        }

        // allow all except guarded
        if (!empty(static::$guarded)) {
            return array_diff_key($data, array_flip(static::$guarded));
        }

        return $data;
    }

    /**
     * Public wrapper used by the QueryBuilder wrapper to prepare insert data.
     */
    public static function prepareInsertData(array $data): array
    {
        $data = static::filterFillable($data); // กรองข้อมูลตาม fillable/guarded
        if (static::$timestamps) {
            $now = date('Y-m-d H:i:s');
            if (!array_key_exists('created_at', $data)) {
                $data['created_at'] = $now;
            }
            if (!array_key_exists('updated_at', $data)) {
                $data['updated_at'] = $now;
            }
        }
        return $data;
    }

    /**
     * Public wrapper used by the QueryBuilder wrapper to prepare update data.
     */
    public static function prepareUpdateData(array $data): array
    {
        $data = static::filterFillable($data);
        if (static::$timestamps) {
            $now = date('Y-m-d H:i:s');
            if (!array_key_exists('updated_at', $data)) {
                $data['updated_at'] = $now;
            }
        }
        return $data;
    }

    /**
     * Public accessor for soft-delete flag for use by ModelQueryBuilder.
     */
    public static function usesSoftDeletes(): bool
    {
        // Support subclasses that may declare the flag as either a static
        // property or a non-static default property. Use reflection to read
        // the default value safely without requiring an instance.
        $class = static::class;
        if (property_exists($class, 'softDeletes')) {
            $defaults = (new \ReflectionClass($class))->getDefaultProperties();
            return isset($defaults['softDeletes']) ? (bool)$defaults['softDeletes'] : false;
        }

        return false;
    }
}
