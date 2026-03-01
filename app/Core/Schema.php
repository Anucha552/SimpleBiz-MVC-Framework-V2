<?php
/**
 * คลาสจัดการโครงสร้างฐานข้อมูล
 * 
 * จุดประสงค์: ให้ฟังก์ชันสำหรับสร้าง แก้ไข หรือลบตารางและคอลัมน์ในฐานข้อมูล
 * Schema() ควรใช้กับอะไร: การเชื่อมต่อฐานข้อมูลเพื่อจัดการโครงสร้างตาราง
 * 
 * ฟีเจอร์หลัก:
 * - สร้างตารางใหม่
 * - แก้ไขตารางที่มีอยู่
 * - ลบตาราง
 * - ลบคอลัมน์จากตาราง
 * - เปลี่ยนชื่อตาราง
 * 
 * ตัวอย่างการใช้งานโดยรวม:
 * ```php
 * // สร้างตารางใหม่
 * Schema::create($db, 'users', function($table) {
 *     $table->increments('id');
 *    $table->string('name');
 * });
 */

namespace App\Core;

use Throwable;
use RuntimeException;

class Schema
{
    /**
     * สร้างตารางโดยไม่ต้องเขียน SQL ด้วยตนเอง
     * จุดประสงค์: ให้วิธีง่ายๆ ในการสร้างตารางในฐานข้อมูลโดยใช้โครงสร้างที่กำหนดผ่าน callback
     * create() ควรใช้กับอะไร: การเชื่อมต่อฐานข้อมูล, ชื่อตาราง, และ callback สำหรับกำหนดโครงสร้างตาราง
     * ตัวอย่างการใช้งาน:
     * ```php
     * Schema::create($db, 'users', function($table) {
     *     $table->increments('id');
     *     $table->string('name');
     *     $table->string('email')->nullable();
     *     $table->timestamps();
     * });
     * ```
     * 
     * @param Database $db การเชื่อมต่อฐานข้อมูล
     * @param string $table ชื่อตารางที่จะสร้าง
     * @param callable $callback ฟังก์ชันสำหรับกำหนดโครงสร้างตาราง
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public static function create(Database $db, string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $db->beginTransaction();
        try {
            $sql = $blueprint->toCreateSql();
            if (is_array($sql)) {
                foreach ($sql as $stmt) {
                    $db->execRaw($stmt);
                }
            } else {
                $db->execRaw($sql);
            }
            if ($db->inTransaction()) $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    /**
     * แก้ไขตาราง (เพิ่มคอลัมน์) โดยไม่ต้องเขียน SQL ด้วยตนเอง
     * จุดประสงค์: ให้วิธีง่ายๆ ในการแก้ไขโครงสร้างตารางในฐานข้อมูลโดยใช้โครงสร้างที่กำหนดผ่าน callback
     * table() ควรใช้กับอะไร: การเชื่อมต่อฐานข้อมูล, ชื่อตาราง, และ callback สำหรับกำหนดการแก้ไขตาราง
     * ตัวอย่างการใช้งาน:
     * ```php
     * Schema::table($db, 'users', function($table) {
     *     $table->string('phone')->nullable();
     * });
     * ```
     * 
     * @param Database $db การเชื่อมต่อฐานข้อมูล
     * @param string $table ชื่อตารางที่จะทำการแก้ไข
     * @param callable $callback ฟังก์ชันสำหรับกำหนดการแก้ไขตาราง
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public static function table(Database $db, string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        $stmts = $blueprint->toAlterAddSql();

        $db->beginTransaction();
        try {
            foreach ($stmts as $sql) {
                $db->execRaw($sql);
            }
            if ($db->inTransaction()) $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    /**
     * ลบตาราง
     * จุดประสงค์: ให้ฟังก์ชันง่ายๆ ในการลบตารางจากฐานข้อมูล
     * drop() ควรใช้กับอะไร: การเชื่อมต่อฐานข้อมูลและชื่อตารางที่ต้องการลบ
     * ตัวอย่างการใช้งาน:
     * ```php
     * Schema::drop($db, 'users');
     * ```
     * 
     * @param Database $db การเชื่อมต่อฐานข้อมูล
     * @param string $table ชื่อตารางที่จะลบ
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public static function drop(Database $db, string $table): void
    {
        $db->execRaw('DROP TABLE IF EXISTS `' . str_replace('`', '``', $table) . '`');
    }

    /**
     * ลบคอลัมน์จากตาราง
     * จุดประสงค์: ให้ฟังก์ชันง่ายๆ ในการลบคอลัมน์จากตารางในฐานข้อมูล
     * dropColumn() ควรใช้กับอะไร: การเชื่อมต่อฐานข้อมูล, ชื่อตาราง, และชื่อคอลัมน์ที่จะลบ
     * ตัวอย่างการใช้งาน:
     * ```php
     * Schema::dropColumn($db, 'users', 'phone');
     * ```
     * 
     * @param Database $db การเชื่อมต่อฐานข้อมูล
     * @param string $table ชื่อตารางที่มีคอลัมน์ที่จะลบ
     * @param string $column ชื่อคอลัมน์ที่จะลบ
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public static function dropColumn(Database $db, string $table, string $column): void
    {
        $driver = Config::get('database.connection', 'mysql');
        if ($driver === 'sqlite') {
            throw new RuntimeException('SQLite does not support DROP COLUMN directly.');
        }

        $sql = 'ALTER TABLE `' . str_replace('`', '``', $table) . '` DROP COLUMN `' . str_replace('`', '``', $column) . '`';
        $db->execRaw($sql);
    }

    /**
     * เปลี่ยนชื่อตาราง
     * จุดประสงค์: ให้ฟังก์ชันง่ายๆ ในการเปลี่ยนชื่อตารางในฐานข้อมูล
     * renameTable() ควรใช้กับอะไร: การเชื่อมต่อฐานข้อมูล, ชื่อตารางเดิม, และชื่อตารางใหม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * Schema::renameTable($db, 'old_table', 'new_table');
     * ```
     * 
     * @param Database $db การเชื่อมต่อฐานข้อมูล
     * @param string $from ชื่อตารางเดิม
     * @param string $to ชื่อตารางใหม่
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public static function renameTable(Database $db, string $from, string $to): void
    {
        $driver = Config::get('database.connection', 'mysql');
        if ($driver === 'sqlite') {
            $sql = 'ALTER TABLE `' . str_replace('`', '``', $from) . '` RENAME TO `' . str_replace('`', '``', $to) . '`';
        } else {
            $sql = 'RENAME TABLE `' . str_replace('`', '``', $from) . '` TO `' . str_replace('`', '``', $to) . '`';
        }
        $db->execRaw($sql);
    }
}
