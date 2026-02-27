<?php
/**
 * ModelQueryBuilder
 *
 * จุดประสงค์: เป็นคลาสที่ขยายจาก QueryBuilder เพื่อเพิ่มฟีเจอร์ที่เกี่ยวข้องกับโมเดล 
 * เช่น การจัดการ soft deletes และการเตรียมข้อมูลสำหรับการแทรกหรืออัพเดต 
 * โดยจะใช้ร่วมกับโมเดลที่กำหนดใน property $modelClass เพื่อให้สามารถใช้งานฟีเจอร์เหล่านี้ได้อย่างสะดวก
 * 
 * ตัวอย่างการใช้งาน:
 * ```php
 * // สร้าง QueryBuilder สำหรับโมเดล User
 * $query = new ModelQueryBuilder($db, 'users', User::class);
 * 
 * // แทรกข้อมูลใหม่
 * $query->insert(['name' => 'John', 'email' => 'john@example.com']);
 * ```
 */
declare(strict_types=1);

namespace App\Core;

use App\Core\Database;
use App\Core\RawExpression;
use App\Core\QueryBuilder;

class ModelQueryBuilder extends QueryBuilder
{
    /**
     * ชื่อคลาสของโมเดลที่ใช้ร่วมกับ QueryBuilder นี้ เพื่อให้สามารถใช้ฟีเจอร์ที่เกี่ยวข้องกับโมเดลได้ เช่น การเตรียมข้อมูลสำหรับการแทรกหรืออัพเดต และการจัดการ soft deletes
     */
    protected string $modelClass = '';

    /**
     * สร้าง instance ของ ModelQueryBuilder โดยรับพารามิเตอร์เป็น Database, ชื่อตาราง, ชื่อคลาสของโมเดล และตัวเลือกสำหรับการใช้ soft deletes
     * จุดประสงค์: เพื่อสร้าง instance ของ ModelQueryBuilder ที่พร้อมใช้งาน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query = new ModelQueryBuilder($db, 'users', User::class);
     * ```
     * 
     * @param Database $db instance ของ Database ที่ใช้ในการเชื่อมต่อฐานข้อมูล
     * @param string $table ชื่อตารางในฐานข้อมูลที่ต้องการทำงานด้วย
     * @param string $modelClass ชื่อคลาสของโมเดลที่ใช้ร่วมกับ QueryBuilder นี้ เพื่อให้สามารถใช้ฟีเจอร์ที่เกี่ยวข้องกับโมเดลได้ เช่น การเตรียมข้อมูลสำหรับการแทรกหรืออัพเดต และการจัดการ soft deletes
     * @param bool $applySoftDelete ตัวเลือกสำหรับการใช้ soft deletes ถ้าเป็น true จะเพิ่มเงื่อนไขในการกรองข้อมูลที่ถูกลบแบบ soft delete ออกโดยอัตโนมัติเมื่อสร้าง QueryBuilder นี้
     */
    public function __construct(
        Database $db,
        string $table,
        string $modelClass = '',
        bool $applySoftDelete = true
    ) {
        parent::__construct($db, $table);
        $this->modelClass = $modelClass;

        if (
            $applySoftDelete &&
            $this->modelClass !== '' &&
            $this->modelClass::usesSoftDeletes()
        ) {
            $sql = $this->formatIdentifierOrRaw('deleted_at') . ' IS NULL';
            $this->wheres[] = [
                'boolean' => 'AND',
                'sql' => $sql,
                'soft_delete' => true
            ];
        }
    }

    /**
     * ฟังก์ชัน insert ที่ใช้เตรียมข้อมูลสำหรับการแทรกข้อมูลโดยการกรองข้อมูลตาม fillable/guarded และเพิ่ม timestamps อัตโนมัติถ้าเปิดใช้งาน ก่อนที่จะทำการแทรกข้อมูลจริง โดยจะเรียกใช้ฟังก์ชัน prepareInsertData() จากโมเดลเพื่อเตรียมข้อมูลก่อนที่จะส่งต่อไปยังเมธอด insert() ของ QueryBuilder
     * จุดประสงค์: เพื่อให้สามารถเตรียมข้อมูลสำหรับการแทรกข้อมูลโดยการกรองข้อมูลตาม fillable/guarded และเพิ่ม timestamps อัตโนมัติถ้าเปิดใช้งานได้อย่างสะดวก โดยการเรียกใช้เมธอด insert() จาก ModelQueryBuilder ซึ่งจะส่งต่อไปยังเมธอด insert() ของ QueryBuilder หลังจากที่ได้เตรียมข้อมูลแล้ว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->insert(['name' => 'John', 'email' => 'john@example.com']); // ผลลัพธ์: INSERT INTO `table` (`name`, `email`) VALUES ('John', 'john@example.com')
     * ```
     * 
     * @param array $data ข้อมูลที่ต้องการแทรก โดยจะถูกกรองตาม fillable/guarded และเพิ่ม timestamps อัตโนมัติถ้าเปิดใช้งาน
     * @return bool คืนค่า true ถ้าแทรกข้อมูลสำเร็จ, false ถ้าแทรกข้อมูลไม่สำเร็จ
     */
    public function insert(array $data): bool
    {
        if ($this->modelClass !== '') {
            $data = $this->modelClass::prepareInsertData($data);
        }

        return parent::insert($data);
    }

    /**
     * ฟังก์ชัน insertGetId ที่ใช้เตรียมข้อมูลสำหรับการแทรกข้อมูลโดยการกรองข้อมูลตาม fillable/guarded และเพิ่ม timestamps อัตโนมัติถ้าเปิดใช้งาน ก่อนที่จะทำการแทรกข้อมูลจริง และคืนค่า id ของเรคคอร์ดที่ถูกแทรก โดยจะเรียกใช้ฟังก์ชัน prepareInsertData() จากโมเดลเพื่อเตรียมข้อมูลก่อนที่จะส่งต่อไปยังเมธอด insertGetId() ของ QueryBuilder
     * จุดประสงค์: เพื่อให้สามารถเตรียมข้อมูลสำหรับการแทรกข้อมูลโดยการกรองข้อมูลตาม fillable/guarded และเพิ่ม timestamps อัตโนมัติถ้าเปิดใช้งานได้อย่างสะดวก โดยการเรียกใช้เมธอด insertGetId() จาก ModelQueryBuilder ซึ่งจะส่งต่อไปยังเมธอด insertGetId() ของ QueryBuilder หลังจากที่ได้เตรียมข้อมูลแล้ว และคืนค่า id ของเรคคอร์ดที่ถูกแทรก
     * ตัวอย่างการใช้งาน:
     * ```php
     * $id = $query->insertGetId(['name' => 'John', 'email' => 'john@example.com']); // ผลลัพธ์: INSERT INTO `table` (`name`, `email`) VALUES ('John', 'john@example.com')
     * ```
     * 
     * @param array $data ข้อมูลที่ต้องการแทรก โดยจะถูกกรองตาม fillable/guarded และเพิ่ม timestamps อัตโนมัติถ้าเปิดใช้งาน
     * @return int คืนค่า id ของเรคคอร์ดที่ถูกแทรก
     */
    public function insertGetId(array $data): int
    {
        // ถ้า $modelClass ไม่เป็นค่าว่าง ให้เรียกใช้ฟังก์ชัน prepareInsertData() จากโมเดลเพื่อเตรียมข้อมูลก่อนที่จะส่งต่อไปยังเมธอด insertGetId() ของ QueryBuilder
        if ($this->modelClass !== '') {
            $data = $this->modelClass::prepareInsertData($data);
        }

        return parent::insertGetId($data);
    }

    /**
     * ฟังก์ชัน update ที่ใช้เตรียมข้อมูลสำหรับการอัพเดตข้อมูลโดยการกรองข้อมูลตาม fillable/guarded และเพิ่ม timestamps อัตโนมัติถ้าเปิดใช้งาน ก่อนที่จะทำการอัพเดตข้อมูลจริง โดยจะเรียกใช้ฟังก์ชัน prepareUpdateData() จากโมเดลเพื่อเตรียมข้อมูลก่อนที่จะส่งต่อไปยังเมธอด update() ของ QueryBuilder
     * จุดประสงค์: เพื่อให้สามารถเตรียมข้อมูลสำหรับการอัพเดตข้อมูลโดยการกรองข้อมูลตาม fillable/guarded และเพิ่ม timestamps อัตโนมัติถ้าเปิดใช้งานได้อย่างสะดวก โดยการเรียกใช้เมธอด update() จาก ModelQueryBuilder ซึ่งจะส่งต่อไปยังเมธอด update() ของ QueryBuilder หลังจากที่ได้เตรียมข้อมูลแล้ว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $affectedRows = $query->update(['name' => 'John', 'email' => 'john@example.com']); // ผลลัพธ์: UPDATE `table` SET `name` = 'John', `email` = 'john@example.com' WHERE `id` = 1
     * ```
     * 
     * @param array $data ข้อมูลที่ต้องการอัพเดต โดยจะถูกกรองตาม fillable/guarded และเพิ่ม timestamps อัตโนมัติถ้าเปิดใช้งาน
     * @return int คืนค่า จำนวนแถวที่ถูกอัพเดต
     */
    public function update(array $data): int
    {
        if ($this->modelClass !== '') {
            $data = $this->modelClass::prepareUpdateData($data);
        }

        return parent::update($data);
    }

    /**
     * ฟังก์ชัน delete ที่ใช้จัดการการลบข้อมูล โดยถ้าโมเดลนี้ใช้ soft-delete จะทำการอัพเดตคอลัมน์ deleted_at แทนการลบข้อมูลจริง และถ้าโมเดลนี้ไม่ใช้ soft-delete จะทำการลบข้อมูลจริง โดยจะตรวจสอบว่าโมเดลนี้ใช้ soft-delete หรือไม่โดยการเรียกใช้ฟังก์ชัน usesSoftDeletes() จากโมเดล
     * จุดประสงค์: เพื่อให้สามารถจัดการการลบข้อมูลได้อย่างสะดวก โดยการเรียกใช้เมธอด delete() จาก ModelQueryBuilder ซึ่งจะตรวจสอบว่าโมเดลนี้ใช้ soft-delete หรือไม่ และทำการอัพเดตคอลัมน์ deleted_at แทนการลบข้อมูลจริงถ้าใช้ soft-delete หรือทำการลบข้อมูลจริงถ้าไม่ใช้ soft-delete
     * ตัวอย่างการใช้งาน:
     * ```php
     * $affectedRows = $query->delete(); // ผลลัพธ์: DELETE FROM `table` WHERE `id` = 1 หรือ UPDATE `table` SET `deleted_at` = CURRENT_TIMESTAMP WHERE `id` = 1 ขึ้นอยู่กับว่าโมเดลนี้ใช้ soft-delete หรือไม่
     * ```
     * 
     * @return int คืนค่า จำนวนแถวที่ถูกลบหรืออัพเดต (ในกรณีที่ใช้ soft-delete)
     */
    public function delete(): int
    {
        if (
            $this->modelClass !== '' &&
            $this->modelClass::usesSoftDeletes()
        ) {
            return parent::update([
                'deleted_at' => new RawExpression('CURRENT_TIMESTAMP'),
                'updated_at' => new RawExpression('CURRENT_TIMESTAMP')
            ]);
        }

        return parent::delete();
    }

    /**
     * ฟังก์ชัน restore ที่ใช้จัดการการกู้คืนข้อมูลที่ถูกลบแบบ soft delete โดยจะอัพเดตคอลัมน์ deleted_at ให้เป็น NULL เพื่อให้ข้อมูลกลับมาแสดงในผลลัพธ์ของ QueryBuilder ที่มีเงื่อนไข deleted_at IS NULL
     * จุดประสงค์: เพื่อให้สามารถจัดการการกู้คืนข้อมูลที่ถูกลบแบบ soft delete ได้อย่างสะดวก โดยการเรียกใช้เมธอด restore() จาก ModelQueryBuilder ซึ่งจะตรวจสอบว่าโมเดลนี้ใช้ soft-delete หรือไม่ และทำการอัพเดตคอลัมน์ deleted_at ให้เป็น NULL ถ้าใช้ soft-delete
     * ตัวอย่างการใช้งาน:
     * ```php
     * $affectedRows = $query->restore(); // ผลลัพธ์: UPDATE `table` SET `deleted_at` = NULL WHERE `id` = 1
     * ```
     * 
     * @return int คืนค่า จำนวนแถวที่ถูกอัพเดต (จำนวนแถวที่ถูกกู้คืน)
     */
    public function restore(): int
    {
        if (
            $this->modelClass === '' ||
            !$this->modelClass::usesSoftDeletes()
        ) {
            return 0;
        }

        return parent::update([
            'deleted_at' => new RawExpression('NULL'),
            'updated_at' => new RawExpression('CURRENT_TIMESTAMP')
        ]);
    }

    /**
     * ฟังก์ชัน withTrashed ที่คืนค่า QueryBuilder โดยไม่สนใจเงื่อนไขการลบแบบ soft-delete
     * จุดประสงค์: เพื่อให้สามารถสร้าง QueryBuilder ที่ไม่กรองข้อมูลที่ถูกลบแบบ soft delete ออกได้ โดยการเรียกใช้ฟังก์ชัน withTrashed() จากโมเดล เช่น User::withTrashed()->get() ซึ่งจะส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query() และไม่กรองข้อมูลที่มี deleted_at ไม่เป็น null ออก ทำให้สามารถดึงข้อมูลทั้งหมดรวมถึงข้อมูลที่ถูกลบแบบ soft delete ได้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $allUsers = User::withTrashed()->get(); // ผลลัพธ์: ดึงข้อมูลทั้งหมดรวมถึงข้อมูลที่ถูกลบแบบ soft delete
     * ```
     * 
     * @return QueryBuilder คืนค่า QueryBuilder ที่ไม่กรองข้อมูลที่ถูกลบแบบ soft delete ออก
     */
    public function withTrashed(): static
    {
        $this->removeDeletedAtConstraint();
        return $this;
    }

    /**
     * ฟังก์ชัน onlyTrashed ที่คืนค่า QueryBuilder ที่กรองเฉพาะเรคคอร์ดที่ถูกลบแบบ soft-delete
     * จุดประสงค์: เพื่อให้สามารถสร้าง QueryBuilder ที่กรองเฉพาะข้อมูลที่ถูกลบแบบ soft delete ออกได้ โดยการเรียกใช้ฟังก์ชัน onlyTrashed() จากโมเดล เช่น User::onlyTrashed()->get() ซึ่งจะส่งต่อไปยัง QueryBuilder ที่สร้างจาก static::query() และกรองข้อมูลที่มี deleted_at ไม่เป็น null ออก ทำให้สามารถดึงข้อมูลเฉพาะเรคคอร์ดที่ถูกลบแบบ soft delete ได้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $trashedUsers = User::onlyTrashed()->get(); // ผลลัพธ์: ดึงข้อมูลเฉพาะเรคคอร์ดที่ถูกลบแบบ soft delete
     * ```
     * 
     * @return QueryBuilder คืนค่า QueryBuilder ที่กรองเฉพาะเรคคอร์ดที่ถูกลบแบบ soft delete ออก
     */
    public function onlyTrashed(): static
    {
        $this->removeDeletedAtConstraint();
        return $this->whereNotNull('deleted_at');
    }

    /**
     * ฟังก์ชัน forceDelete ที่ใช้ลบข้อมูลจริงโดยไม่สนใจว่าโมเดลนี้ใช้ soft-delete หรือไม่ โดยจะเรียกใช้เมธอด delete() ของ QueryBuilder โดยตรงเพื่อทำการลบข้อมูลจริง
     * จุดประสงค์: เพื่อให้สามารถลบข้อมูลจริงได้อย่างสะดวก โดยการเรียกใช้เมธอด forceDelete() จาก ModelQueryBuilder ซึ่งจะทำการลบข้อมูลจริงโดยไม่สนใจว่าโมเดลนี้ใช้ soft-delete หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $affectedRows = $query->forceDelete(); // ผลลัพธ์: DELETE FROM `table` WHERE `id` = 1 โดยไม่สนใจว่าโมเดลนี้ใช้ soft-delete หรือไม่
     * ```
     * 
     * @return int คืนค่า จำนวนแถวที่ถูกลบ
     */
    public function forceDelete(): int
    {
        return parent::delete();
    }

    /**
     * ฟังก์ชัน removeDeletedAtConstraint ที่ใช้ลบเงื่อนไขการกรองข้อมูลที่ถูกลบแบบ soft delete ออกจาก QueryBuilder โดยจะวนลูปผ่านเงื่อนไขใน $this->wheres และลบเงื่อนไขที่มีคีย์ 'soft_delete' ออก
     * จุดประสงค์: เพื่อให้สามารถลบเงื่อนไขการกรองข้อมูลที่ถูกลบแบบ soft delete ออกจาก QueryBuilder ได้อย่างสะดวก โดยการเรียกใช้ฟังก์ชัน removeDeletedAtConstraint() จาก ModelQueryBuilder ซึ่งจะทำการลบเงื่อนไขที่มีคีย์ 'soft_delete' ออกจาก $this->wheres
     * ตัวอย่างการใช้งาน:
     * ```php
     * $query->withTrashed()->get(); // ผลลัพธ์: ดึงข้อมูลทั้งหมดรวมถึงข้อมูลที่ถูกลบแบบ soft delete โดยฟังก์ชัน withTrashed() จะเรียกใช้ฟังก์ชัน removeDeletedAtConstraint() เพื่อให้ไม่กรองข้อมูลที่ถูกลบแบบ soft delete ออก
     * ```
     * @return void ไม่มีการคืนค่า
     */
    protected function removeDeletedAtConstraint(): void
    {
        $newWheres = [];

        foreach ($this->wheres as $w) {
            if (!empty($w['soft_delete'])) {
                continue;
            }

            $newWheres[] = $w;
        }

        $this->wheres = $newWheres;
    }
}