<?php
/**
 * app/Core/Model.php
 *
 * จุดประสงค์: เป็นคลาสฐานสำหรับโมเดลที่ใช้ในการติดต่อกับฐานข้อมูล โดยมีฟังก์ชันหลักในการสร้าง QueryBuilder สำหรับการทำงานกับฐานข้อมูล เช่น การเลือกข้อมูล การเพิ่มข้อมูล และการค้นหาข้อมูลตาม primary key นอกจากนี้ยังมีฟังก์ชันช่วยในการจัดการกับ mass assignment และ timestamps เพื่อให้การทำงานกับฐานข้อมูลเป็นไปอย่างสะดวกและปลอดภัย
 *
 * ตัวอย่างการใช้งาน:
 * ```php
 * // ตั้งค่าการเชื่อมต่อฐานข้อมูลสำหรับโมเดลทั้งหมด
 * Model::setConnection($db);
 *
 * // สร้างเรคคอร์ดใหม่ในตาราง users
 * $userId = User::create(['name' => 'John Doe', 'email' => 'john.doe@example.com']);
 * ```
 *
 * Helpful IDE annotations:
 * @method static \App\Core\ModelQueryBuilder query()
 * @method static \App\Core\ModelQueryBuilder select(array|string $columns = '*')
 * @method static \App\Core\ModelQueryBuilder where(callable|string $column, ?string $operator = null, mixed $value = null)
 * @method static \App\Core\ModelQueryBuilder orWhere(string $column, string $operator, mixed $value)
 * @method static \App\Core\ModelQueryBuilder whereIn(string $column, array $values)
 * @method static \App\Core\ModelQueryBuilder whereNull(string $column)
 * @method static \App\Core\ModelQueryBuilder whereNotNull(string $column)
 * @method static \App\Core\ModelQueryBuilder orderBy(string $column, string $direction = 'ASC')
 * @method static \App\Core\ModelQueryBuilder limit(int $limit)
 * @method static \App\Core\ModelQueryBuilder offset(int $offset)
 * @method static \App\Core\ModelQueryBuilder withTrashed()
 * @method static \App\Core\ModelQueryBuilder onlyTrashed()
 * @method static \App\Core\ModelQueryBuilder forceDelete()
 * @method static \App\Core\ModelQueryBuilder restore()
 * @method static int create(array $data)
 * @method static ?array find(mixed $id)
 */

declare(strict_types=1);

namespace App\Core;

use App\Core\QueryBuilder;
use App\Core\RawExpression;
use App\Core\Database;
use App\Core\ModelQueryBuilder;
use RuntimeException;
use ReflectionClass;

/**
 * Class Model
 *
 * IDE helper annotations for fluent chaining on model subclasses.
 *
 * @method static \App\Core\ModelQueryBuilder query()
 * @method static \App\Core\ModelQueryBuilder select(array|string $columns = '*')
 * @method static \App\Core\ModelQueryBuilder where(callable|string $column, ?string $operator = null, mixed $value = null)
 * @method static \App\Core\ModelQueryBuilder orWhere(string $column, string $operator, mixed $value)
 * @method static \App\Core\ModelQueryBuilder whereIn(string $column, array $values)
 * @method static \App\Core\ModelQueryBuilder whereNull(string $column)
 * @method static \App\Core\ModelQueryBuilder whereNotNull(string $column)
 * @method static \App\Core\ModelQueryBuilder orderBy(string $column, string $direction = 'ASC')
 * @method static \App\Core\ModelQueryBuilder limit(int $limit)
 * @method static \App\Core\ModelQueryBuilder offset(int $offset)
 * @method static \App\Core\ModelQueryBuilder withTrashed()
 * @method static \App\Core\ModelQueryBuilder onlyTrashed()
 * @method static \App\Core\ModelQueryBuilder forceDelete()
 * @method static \App\Core\ModelQueryBuilder restore()
 * @method static int create(array $data)
 * @method static ?array find(mixed $id)
 */
abstract class Model
{
    /**
     * ชื่อตารางในฐานข้อมูลที่โมเดลนี้จะทำงานด้วย ต้องถูกกำหนดในแต่ละโมเดลย่อย
     */
    protected static string $table;

    /**
     * Database instance ที่ใช้สำหรับการเชื่อมต่อและทำงานกับฐานข้อมูล จะถูกตั้งค่าโดยการเรียก Model::setConnection() ก่อนใช้งานโมเดลใดๆ
     */
    protected static Database $db;

    /**
     * ชื่อคอลัมน์ที่เป็น primary key ของตาราง โดยค่าเริ่มต้นคือ 'id' แต่สามารถ override ได้ในแต่ละโมเดลย่อย
     */
    protected static string $primaryKey = 'id';

    /**
     * fillable: รายการคอลัมน์ที่อนุญาตให้ทำ mass assignment ได้ (เช่น ผ่านเมธอด create() หรือ update() ที่รับ array ของข้อมูล) ถ้า fillable ไม่ว่าง จะอนุญาตเฉพาะคอลัมน์ที่ระบุใน fillable เท่านั้น
     */
    protected static array $fillable = [];

    /**
     * guarded: รายการคอลัมน์ที่ไม่อนุญาตให้ทำ mass assignment ได้ ถ้า fillable ว่างและ guarded ไม่ว่าง จะอนุญาตให้ทำ mass assignment กับทุกคอลัมน์ยกเว้นคอลัมน์ที่ระบุใน guarded
     */
    protected static array $guarded = ['id'];

    /**
     * timestamps: ถ้าเป็น true โมเดลจะจัดการคอลัมน์ created_at และ updated_at อัตโนมัติเมื่อทำการแทรกหรืออัพเดตข้อมูล
     */
    protected static bool $timestamps = true;

    /**
     * softDeletes: ถ้าเป็น true โมเดลจะใช้การลบแบบ soft delete โดยจะมีคอลัมน์ deleted_at ที่เก็บเวลาที่ถูกลบแทนการลบจริง และ QueryBuilder จะกรองข้อมูลที่มี deleted_at ไม่เป็น null ออกโดยอัตโนมัติ
     */
    protected static bool $softDeletes = false;

    /**
     * ฟังก์ชัน setConnection ที่ใช้ตั้งค่าการเชื่อมต่อฐานข้อมูลสำหรับโมเดล โดยรับพารามิเตอร์เป็น instance ของ Database และตั้งค่าให้กับตัวแปร static $db ของโมเดล
     * จุดประสงค์: เพื่อให้โมเดลสามารถเข้าถึงฐานข้อมูลได้ผ่านการตั้งค่าการเชื่อมต่อที่กำหนดไว้ และเพื่อให้แน่ใจว่าโมเดลทุกตัวใช้การเชื่อมต่อเดียวกันในการทำงานกับฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * // สำหรับการใช้งานใน CLI หรือ tests ที่ไม่มีการตั้งค่าการเชื่อมต่ออัตโนมัติ
     * Model::setConnection(App\Core\Database::getInstance());
     * ```
     * 
     * @param Database $db การเชื่อมต่อฐานข้อมูลที่ต้องการใช้กับโมเดล
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะตั้งค่าการเชื่อมต่อฐานข้อมูลในตัวแปร static $db ของโมเดล
     */
    public static function setConnection(Database $db): void
    {
        static::$db = $db;
    }

    /**
     * ฟังก์ชัน query ที่ใช้สร้าง QueryBuilder สำหรับโมเดลนี้ โดยจะตรวจสอบว่ามีการตั้งค่าการเชื่อมต่อฐานข้อมูลและชื่อตารางหรือไม่ก่อนที่จะสร้าง QueryBuilder และจะคืนค่า ModelQueryBuilder ซึ่งเป็น subclass ของ QueryBuilder ที่มีการจัดการ soft delete อัตโนมัติ
     * จุดประสงค์: เพื่อให้สามารถสร้าง QueryBuilder สำหรับโมเดลนี้ได้อย่างสะดวกและปลอดภัย โดยตรวจสอบการตั้งค่าการเชื่อมต่อฐานข้อมูลและชื่อตารางก่อน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::query()->where('status', 'active')->get(); // ผลลัพธ์: สร้าง QueryBuilder สำหรับโมเดล User และดึงข้อมูลผู้ใช้ที่มีสถานะ active จากฐานข้อมูล
     * ```
     * 
     * @return ModelQueryBuilder คืนค่า ModelQueryBuilder ที่สร้างขึ้นสำหรับโมเดลนี้ ซึ่งสามารถใช้ในการสร้างคำสั่ง SQL และดึงข้อมูลจากฐานข้อมูลได้
     * @throws \RuntimeException ถ้าการเชื่อมต่อฐานข้อมูลหรือชื่อตารางไม่ถูกตั้งค่า
     */
    public static function query(): ModelQueryBuilder
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

    /**
     * ฟังก์ชัน select ที่รับพารามิเตอร์เป็นชื่อคอลัมน์หรือ array ของชื่อคอลัมน์ และส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query() เพื่อเลือกคอลัมน์ที่ต้องการจากฐานข้อมูล
     * จุดประสงค์: เพื่อให้สามารถเลือกคอลัมน์ที่ต้องการได้อย่างสะดวกโดยการเรียกใช้ฟังก์ชัน select() โดยตรงจากโมเดล เช่น User::select('name', 'email') ซึ่งจะส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query() และทำการเลือกคอลัมน์ที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::select('name', 'email')->where('status', 'active')->get(); // ผลลัพธ์: SELECT `name`, `email` FROM `users` WHERE `status` = 'active'
     * ```
     * 
     * @param array|string $columns ชื่อคอลัมน์ที่ต้องการเลือก สามารถเป็น string เดียวหรือ array ของชื่อคอลัมน์
     * @return ModelQueryBuilder คืนค่า ModelQueryBuilder ที่มีการเลือกคอลัมน์ที่ระบุแล้ว ซึ่งสามารถใช้ในการสร้างคำสั่ง SQL และดึงข้อมูลจากฐานข้อมูลได้
     */
    public static function select(array|string $columns = '*'): ModelQueryBuilder
    {
        return static::query()->select($columns);
    }

    /**
     * ฟังก์ชัน where ที่รับพารามิเตอร์แบบเดียวกับ QueryBuilder::where() และส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query()
     * จุดประสงค์: เพื่อให้สามารถเรียกใช้ฟังก์ชัน where() ได้โดยตรงจากโมเดล เช่น User::where('status', 'active') ซึ่งจะส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query() และทำการกรองข้อมูลตามเงื่อนไขที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $activeUsers = User::where('status', '=', 'active')->get(); // ผลลัพธ์: SELECT * FROM `users` WHERE `status` = 'active'
     * ```
     * 
     * @param callable|string $column ชื่อคอลัมน์หรือ callback สำหรับเงื่อนไข where
     * @param string|null $operator ตัวดำเนินการเปรียบเทียบ (เช่น '=', '>', '<', 'LIKE') ถ้าไม่ระบุจะใช้ค่าเริ่มต้นเป็น '='
     * @param mixed|null $value ค่าที่จะเปรียบเทียบกับคอลัมน์ ถ้าไม่ระบุจะใช้ค่าเริ่มต้นเป็น null
     * @return ModelQueryBuilder คืนค่า ModelQueryBuilder ที่มีเงื่อนไข where ที่ถูกเพิ่มเข้ามาแล้ว ซึ่งสามารถใช้ในการสร้างคำสั่ง SQL และดึงข้อมูลจากฐานข้อมูลได้
     */ 
    public static function where(callable|string $column, ?string $operator = null, mixed $value = null): ModelQueryBuilder
    {
        return static::query()->where($column, $operator, $value);
    }

    /**
     * ฟังก์ชัน orWhere ที่รับพารามิเตอร์แบบเดียวกับ QueryBuilder::orWhere() และส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query()
     * จุดประสงค์: เพื่อให้สามารถเรียกใช้ฟังก์ชัน orWhere() ได้โดยตรงจากโมเดล เช่น User::where('status', 'active')->orWhere('role', 'admin') ซึ่งจะส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query() และทำการกรองข้อมูลตามเงื่อนไขที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::where('status', 'active')->orWhere('role', 'admin')->get(); // ผลลัพธ์: SELECT * FROM `users` WHERE `status` = 'active' OR `role` = 'admin'
     * ```
     * 
     * @param callable|string $column ชื่อคอลัมน์หรือ callback สำหรับเงื่อนไข orWhere
     * @param string|null $operator ตัวดำเนินการเปรียบเทียบ (เช่น '=', '>', '<', 'LIKE') ถ้าไม่ระบุจะใช้ค่าเริ่มต้นเป็น '='
     * @param mixed|null $value ค่าที่จะเปรียบเทียบกับคอลัมน์ ถ้าไม่ระบุจะใช้ค่าเริ่มต้นเป็น null
     * @return ModelQueryBuilder คืนค่า ModelQueryBuilder ที่มีเงื่อนไข orWhere ที่ถูกเพิ่มเข้ามาแล้ว ซึ่งสามารถใช้ในการสร้างคำสั่ง SQL และดึงข้อมูลจากฐานข้อมูลได้
     */
    public static function orWhere(string $column, string $operator, mixed $value): ModelQueryBuilder
    {
        return static::query()->orWhere($column, $operator, $value);
    }

    /**
     * ฟังก์ชัน whereIn ที่รับพารามิเตอร์เป็นชื่อคอลัมน์และ array ของค่าที่ต้องการกรอง และส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query() เพื่อกรองข้อมูลที่มีค่าของคอลัมน์อยู่ใน array ที่ระบุ
     * จุดประสงค์: เพื่อให้สามารถกรองข้อมูลที่มีค่าของคอลัมน์อยู่ในชุดของค่าที่ระบุได้อย่างสะดวกโดยการเรียกใช้ฟังก์ชัน whereIn() โดยตรงจากโมเดล เช่น User::whereIn('status', ['active', 'pending']) ซึ่งจะส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query() และทำการกรองข้อมูลที่มีค่าของคอลัมน์ status อยู่ในชุด ['active', 'pending']
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereIn('status', ['active', 'pending'])->get(); // ผลลัพธ์: SELECT * FROM `users` WHERE `status` IN ('active', 'pending')
     * ```
     * 
     * @param string $column ชื่อคอลัมน์ที่ต้องการกรอง
     * @param array $values ชุดของค่าที่ต้องการกรอง โดยจะเลือกข้อมูลที่มีค่าของคอลัมน์อยู่ในชุดนี้
     * @return ModelQueryBuilder คืนค่า ModelQueryBuilder ที่มีเงื่อนไข whereIn ที่ถูกเพิ่มเข้ามาแล้ว ซึ่งสามารถใช้ในการสร้างคำสั่ง SQL และดึงข้อมูลจากฐานข้อมูลได้
     */
    public static function whereIn(string $column, array $values): ModelQueryBuilder
    {
        return static::query()->whereIn($column, $values);
    }

    /**
     * ฟังก์ชัน whereNull ที่รับพารามิเตอร์เป็นชื่อคอลัมน์และส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query() เพื่อกรองข้อมูลที่มีค่าของคอลัมน์เป็น null
     * จุดประสงค์: เพื่อให้สามารถกรองข้อมูลที่มีค่าของคอลัมน์เป็น null ได้อย่างสะดวกโดยการเรียกใช้ฟังก์ชัน whereNull() โดยตรงจากโมเดล เช่น User::whereNull('deleted_at') ซึ่งจะส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query() และทำการกรองข้อมูลที่มีค่าของคอลัมน์ deleted_at เป็น null
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereNull('deleted_at')->get(); // ผลลัพธ์: SELECT * FROM `users` WHERE `deleted_at` IS NULL
     * ```
     * 
     * @param string $column ชื่อคอลัมน์ที่ต้องการกรอง โดยจะเลือกข้อมูลที่มีค่าของคอลัมน์นี้เป็น null
     * @return ModelQueryBuilder คืนค่า ModelQueryBuilder ที่มีเงื่อนไข whereNull ที่ถูกเพิ่มเข้ามาแล้ว ซึ่งสามารถใช้ในการสร้างคำสั่ง SQL และดึงข้อมูลจากฐานข้อมูลได้
     */
    public static function whereNull(string $column): ModelQueryBuilder
    {
        return static::query()->whereNull($column);
    }

    /**
     * ฟังก์ชัน whereNotNull ที่รับพารามิเตอร์เป็นชื่อคอลัมน์และส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query() เพื่อกรองข้อมูลที่มีค่าของคอลัมน์ไม่เป็น null
     * จุดประสงค์: เพื่อให้สามารถกรองข้อมูลที่มีค่าของคอลัมน์ไม่เป็น null ได้อย่างสะดวกโดยการเรียกใช้ฟังก์ชัน whereNotNull() โดยตรงจากโมเดล เช่น User::whereNotNull('deleted_at') ซึ่งจะส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query() และทำการกรองข้อมูลที่มีค่าของคอลัมน์ deleted_at ไม่เป็น null
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::whereNotNull('deleted_at')->get(); // ผลลัพธ์: SELECT * FROM `users` WHERE `deleted_at` IS NOT NULL
     * ```
     * 
     * @param string $column ชื่อคอลัมน์ที่ต้องการกรอง โดยจะเลือกข้อมูลที่มีค่าของคอลัมน์นี้ไม่เป็น null
     * @return ModelQueryBuilder คืนค่า ModelQueryBuilder ที่มีเงื่อนไข whereNotNull ที่ถูกเพิ่มเข้ามาแล้ว ซึ่งสามารถใช้ในการสร้างคำสั่ง SQL และดึงข้อมูลจากฐานข้อมูลได้
     */
    public static function orderBy(string $column, string $direction = 'ASC'): ModelQueryBuilder
    {
        return static::query()->orderBy($column, $direction);
    }

    /**
     * ฟังก์ชัน limit ที่รับพารามิเตอร์เป็นจำนวนจำกัดของเรคคอร์ดที่ต้องการดึง และส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query() เพื่อจำกัดจำนวนเรคคอร์ดที่ดึงจากฐานข้อมูล
     * จุดประสงค์: เพื่อให้สามารถจำกัดจำนวนเรคคอร์ดที่ดึงจากฐานข้อมูลได้อย่างสะดวกโดยการเรียกใช้ฟังก์ชัน limit() โดยตรงจากโมเดล เช่น User::limit(10) ซึ่งจะส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query() และทำการจำกัดจำนวนเรคคอร์ดที่ดึงจากฐานข้อมูลให้เหลือเพียง 10 เรคคอร์ด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::limit(10)->get(); // ผลลัพธ์: SELECT * FROM `users` LIMIT 10
     * ```
     * 
     * @param int $limit จำนวนจำกัดของเรคคอร์ดที่ต้องการดึงจากฐานข้อมูล
     * @return ModelQueryBuilder คืนค่า ModelQueryBuilder ที่มีเงื่อนไข limit ที่ถูกเพิ่มเข้ามาแล้ว ซึ่งสามารถใช้ในการสร้างคำสั่ง SQL และดึงข้อมูลจากฐานข้อมูลได้
     */
    public static function limit(int $limit): ModelQueryBuilder
    {
        return static::query()->limit($limit);
    }

    /**
     * ฟังก์ชัน offset ที่รับพารามิเตอร์เป็นจำนวนเรคคอร์ดที่ต้องการข้าม และส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query() เพื่อข้ามจำนวนเรคคอร์ดที่กำหนด
     * จุดประสงค์: เพื่อให้สามารถข้ามจำนวนเรคคอร์ดที่ดึงจากฐานข้อมูลได้อย่างสะดวกโดยการเรียกใช้ฟังก์ชัน offset() โดยตรงจากโมเดล เช่น User::offset(10) ซึ่งจะส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query() และทำการข้ามจำนวนเรคคอร์ดที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $users = User::offset(10)->get(); // ผลลัพธ์: SELECT * FROM `users` OFFSET 10
     * ```
     * 
     * @param int $offset จำนวนเรคคอร์ดที่ต้องการข้าม
     * @return ModelQueryBuilder คืนค่า ModelQueryBuilder ที่มีเงื่อนไข offset ที่ถูกเพิ่มเข้ามาแล้ว ซึ่งสามารถใช้ในการสร้างคำสั่ง SQL และดึงข้อมูลจากฐานข้อมูลได้
     */
    public static function offset(int $offset): ModelQueryBuilder
    {
        return static::query()->offset($offset);
    }

    /**
     * ฟังก์ชัน create ที่รับพารามิเตอร์เป็น array ของข้อมูลที่ต้องการแทรกลงในฐานข้อมูล และทำการแทรกข้อมูลโดยใช้ QueryBuilder ที่สร้างจาก static::query() หลังจากกรองข้อมูลตาม fillable/guarded และเพิ่ม timestamps อัตโนมัติถ้าเปิดใช้งาน และคืนค่า id ของเรคคอร์ดที่ถูกแทรก
     * จุดประสงค์: เพื่อให้สามารถสร้างเรคคอร์ดใหม่ในฐานข้อมูลได้อย่างสะดวกโดยการเรียกใช้ฟังก์ชัน create() โดยตรงจากโมเดล เช่น User::create(['name' => 'John Doe', 'email' => 'john@example.com']) ซึ่งจะทำการกรองข้อมูลตาม fillable/guarded และเพิ่ม timestamps อัตโนมัติถ้าเปิดใช้งาน และคืนค่า id ของเรคคอร์ดที่ถูกแทรก
     * ตัวอย่างการใช้งาน:
     * ```php
     * $userId = User::create(['name' => 'John Doe', 'email' => 'john@example.com']); // ผลลัพธ์: INSERT INTO `users` (`name`, `email`) VALUES ('John Doe', 'john@example.com')
     * ```
     * 
     * @param array $data ข้อมูลที่ต้องการแทรกลงในฐานข้อมูล โดยจะถูกกรองตาม fillable/guarded และเพิ่ม timestamps อัตโนมัติถ้าเปิดใช้งาน
     * @return int คืนค่า id ของเรคคอร์ดที่ถูกแทรกลงในฐานข้อมูล
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
        return static::query()->insertGetId($data); // ใช้เมธอด insertGetId() ของ QueryBuilder เพื่อแทรกข้อมูลและคืนค่า id ที่แทรกลงในฐานข้อมูล
    }

    /**
     * ฟังก์ชัน find ที่รับพารามิเตอร์เป็นค่า id ของเรคคอร์ดที่ต้องการค้นหา และส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query() เพื่อค้นหาเรคคอร์ดที่มีค่า primary key ตรงกับ id ที่ระบุ และคืนค่าเป็น array ของข้อมูลเรคคอร์ดนั้น หรือ null ถ้าไม่พบ
     * จุดประสงค์: เพื่อให้สามารถค้นหาเรคคอร์ดตาม primary key ได้อย่างสะดวกโดยการเรียกใช้ฟังก์ชัน find() โดยตรงจากโมเดล เช่น User::find(1) ซึ่งจะส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query() และทำการค้นหาเรคคอร์ดที่มีค่า primary key ตรงกับ 1 และคืนค่าเป็น array ของข้อมูลเรคคอร์ดนั้น หรือ null ถ้าไม่พบ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $user = User::find(1); // ผลลัพธ์: SELECT * FROM `users` WHERE `id` = 1
     * ```
     * 
     * @param mixed $id ค่า id ของเรคคอร์ดที่ต้องการค้นหา
     * @return array|null คืนค่า array ของข้อมูลเรคคอร์ดที่พบ หรือ null ถ้าไม่พบ
     */
    public static function find(mixed $id): ?array
    {
        return static::query()->where(static::$primaryKey, '=', $id)->first();
    }

    /**
     * ฟังก์ชัน withTrashed ที่คืนค่า QueryBuilder โดยไม่สนใจเงื่อนไขการลบแบบ soft-delete
     * จุดประสงค์: เพื่อให้สามารถสร้าง QueryBuilder ที่ไม่กรองข้อมูลที่ถูกลบแบบ soft delete ออกได้ โดยการเรียกใช้ฟังก์ชัน withTrashed() จากโมเดล เช่น User::withTrashed()->get() ซึ่งจะส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query() และไม่กรองข้อมูลที่มี deleted_at ไม่เป็น null ออก ทำให้สามารถดึงข้อมูลทั้งหมดรวมถึงข้อมูลที่ถูกลบแบบ soft delete ได้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $allUsers = User::withTrashed()->get(); // ผลลัพธ์: SELECT * FROM `users` (รวมถึงเรคคอร์ดที่มี deleted_at ไม่เป็น null ด้วย)
     * ```
     * 
     * @return ModelQueryBuilder คืนค่า ModelQueryBuilder ที่ไม่กรองข้อมูลที่ถูกลบแบบ soft delete ออก ซึ่งสามารถใช้ในการสร้างคำสั่ง SQL และดึงข้อมูลจากฐานข้อมูลได้
     */
    public static function withTrashed(): ModelQueryBuilder
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
     * ฟังก์ชัน onlyTrashed ที่คืนค่า QueryBuilder ที่กรองเฉพาะเรคคอร์ดที่ถูกลบแบบ soft-delete
     * จุดประสงค์: เพื่อให้สามารถสร้าง QueryBuilder ที่กรองเฉพาะข้อมูลที่ถูกลบแบบ soft delete ออกได้ โดยการเรียกใช้ฟังก์ชัน onlyTrashed() จากโมเดล เช่น User::onlyTrashed()->get() ซึ่งจะส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query() และกรองข้อมูลที่มี deleted_at ไม่เป็น null ออก ทำให้สามารถดึงข้อมูลเฉพาะเรคคอร์ดที่ถูกลบแบบ soft delete ได้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $trashedUsers = User::onlyTrashed()->get(); // ผลลัพธ์: SELECT * FROM `users` WHERE `deleted_at` IS NOT NULL
     * ```
     * 
     * @return ModelQueryBuilder คืนค่า ModelQueryBuilder ที่กรองเฉพาะข้อมูลที่ถูกลบแบบ soft delete ออก ซึ่งสามารถใช้ในการสร้างคำสั่ง SQL และดึงข้อมูลจากฐานข้อมูลได้
     */
    public static function onlyTrashed(): ModelQueryBuilder
    {
        $qb = static::withTrashed();
        return $qb->whereNotNull('deleted_at');
    }

    /* ---------- Mass assignment & timestamps helpers ---------- */
    /**
     * ฟังก์ชัน filterFillable ที่กรองข้อมูลตาม fillable/guarded ก่อนที่จะทำการแทรกหรืออัพเดต
     * จุดประสงค์: เพื่อป้องกันการแทรกหรืออัพเดตข้อมูลที่ไม่ได้ระบุใน fillable หรือถูกระบุใน guarded โดยการเรียกใช้ฟังก์ชัน filterFillable() จากโมเดล เช่น User::filterFillable($data) ซึ่งจะกรองข้อมูลตาม fillable/guarded และคืนค่า array ของข้อมูลที่ผ่านการกรองแล้ว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $filteredData = User::filterFillable($data); // ผลลัพธ์: คืนค่า array ของข้อมูลที่ผ่านการกรองตาม fillable/guarded แล้ว โดยจะอนุญาตเฉพาะคอลัมน์ที่ระบุใน fillable หรือไม่อยู่ใน guarded เท่านั้น
     * ```
     * 
     * @param array $data ข้อมูลที่ต้องการกรอง
     * @return array คืนค่า array ของข้อมูลที่ผ่านการกรองแล้ว
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
     * ฟังก์ชัน prepareInsertData ที่ใช้เตรียมข้อมูลสำหรับการแทรกข้อมูล
     * จุดประสงค์: เพื่อเตรียมข้อมูลสำหรับการแทรกข้อมูลโดยการกรองข้อมูลตาม fillable/guarded และเพิ่ม timestamps อัตโนมัติถ้าเปิดใช้งาน โดยการเรียกใช้ฟังก์ชัน prepareInsertData() จากโมเดล เช่น User::prepareInsertData($data) ซึ่งจะกรองข้อมูลตาม fillable/guarded และเพิ่ม timestamps อัตโนมัติถ้าเปิดใช้งาน และคืนค่า array ของข้อมูลที่พร้อมสำหรับการแทรก
     * ตัวอย่างการใช้งาน:
     * ```php
     * $insertData = User::prepareInsertData($data); // ผลลัพธ์: คืนค่า array ของข้อมูลที่พร้อมสำหรับการแทรก โดยกรองข้อมูลตาม fillable/guarded และเพิ่ม timestamps อัตโนมัติถ้าเปิดใช้งาน
     * ```
     * 
     * @param array $data ข้อมูลที่ต้องการเตรียมสำหรับการแทรก โดยจะถูกกรองตาม fillable/guarded และเพิ่ม timestamps อัตโนมัติถ้าเปิดใช้งาน
     * @return array คืนค่า array ของข้อมูลที่พร้อมสำหรับการแทรก
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
     * ฟังก์ชัน prepareUpdateData ที่ใช้เตรียมข้อมูลสำหรับการอัพเดตข้อมูล
     * จุดประสงค์: เพื่อเตรียมข้อมูลสำหรับการอัพเดตข้อมูลโดยการกรองข้อมูลตาม fillable/guarded และเพิ่ม timestamps อัตโนมัติถ้าเปิดใช้งาน โดยการเรียกใช้ฟังก์ชัน prepareUpdateData() จากโมเดล เช่น User::prepareUpdateData($data) ซึ่งจะกรองข้อมูลตาม fillable/guarded และเพิ่ม timestamps อัตโนมัติถ้าเปิดใช้งาน และคืนค่า array ของข้อมูลที่พร้อมสำหรับการอัพเดต
     * ตัวอย่างการใช้งาน:
     * ```php
     * $updateData = User::prepareUpdateData($data); // ผลลัพธ์: คืนค่า array ของข้อมูลที่พร้อมสำหรับการอัพเดต โดยกรองข้อมูลตาม fillable/guarded และเพิ่ม timestamps อัตโนมัติถ้าเปิดใช้งาน
     * ```
     * 
     * @param array $data ข้อมูลที่ต้องการเตรียมสำหรับการอัพเดต โดยจะถูกกรองตาม fillable/guarded และเพิ่ม timestamps อัตโนมัติถ้าเปิดใช้งาน
     * @return array คืนค่า array ของข้อมูลที่พร้อมสำหรับการอัพเดต
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
     * ฟังก์ชัน usesSoftDeletes ที่ใช้ตรวจสอบว่าโมเดลนี้ใช้ soft-delete หรือไม่
     * จุดประสงค์: เพื่อให้ ModelQueryBuilder สามารถตรวจสอบได้ว่าโมเดลนี้ใช้ soft-delete หรือไม่ โดยการเรียกใช้ฟังก์ชัน usesSoftDeletes() จากโมเดล เช่น User::usesSoftDeletes()
     * ตัวอย่างการใช้งาน:
     * ```php
     * $usesSoftDeletes = User::usesSoftDeletes(); // ผลลัพธ์: คืนค่า true ถ้าโมเดลนี้ใช้ soft-delete, false ถ้าไม่ใช้
     * ```
     * 
     * @return bool คืนค่า true ถ้าโมเดลนี้ใช้ soft-delete, false ถ้าไม่ใช้
     */
    public static function usesSoftDeletes(): bool
    {
        // ตรวจสอบว่ามีการกำหนด property softDeletes ในคลาสนี้หรือไม่ ถ้ามีให้ตรวจสอบค่าเริ่มต้นของ property นั้นเพื่อดูว่าเป็น true หรือ false ถ้าไม่มี property softDeletes ให้ถือว่าโมเดลนี้ไม่ใช้ soft-delete
        $class = static::class;
        if (property_exists($class, 'softDeletes')) {
            $defaults = (new \ReflectionClass($class))->getDefaultProperties();
            return isset($defaults['softDeletes']) ? (bool)$defaults['softDeletes'] : false;
        }

        return false;
    }
}
