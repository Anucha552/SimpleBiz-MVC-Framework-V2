<?php

/**
 * Form Helper
 * 
 * จุดประสงค์: ช่วยในการจัดการฟอร์ม HTML และข้อมูลที่เกี่ยวข้อง
 * 
 * ฟีเจอร์:
 * - อ่านข้อความแฟลช (flash messages) สำหรับการแจ้งเตือนผู้ใช้
 * - ดึงข้อมูลเก่าที่ผู้ใช้ป้อนในฟอร์ม (old input) เพื่อเติมค่าในฟอร์มหลังจากการส่งที่ล้มเหลว
 * - ดึงข้อผิดพลาดการตรวจสอบข้อมูล (validation errors) ที่ถูกแฟลชไว้
 * - สร้างฟิลด์ CSRF และเมตาแท็กเพื่อป้องกันการโจมตี CSRF
 * - ฟังก์ชันช่วยเหลือสำหรับการจัดการคลาส CSS ของฟอร์ม
 * - ใช้งานร่วมกับระบบเซสชันเพื่อจัดการข้อมูลชั่วคราวระหว่างคำขอ HTTP
 * - ช่วยให้การพัฒนาแอปพลิเคชันเว็บที่มีฟอร์ม HTML มีประสิทธิภาพและปลอดภัยยิ่งขึ้นช
 */

namespace App\Helpers;

use App\Core\Session;

class FormHelper
{

    /**
     * ดึงข้อความแฟลชจากเซสชัน (ข้อความที่มีอายุสั้น)
     * จุดประสงค์: ใช้เพื่อแสดงข้อความชั่วคราว เช่น การแจ้งเตือนความสำเร็จหรือข้อผิดพลาด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $message = FormHelper::flash('success', 'Default success message');
     * ```
     * 
     * returns mixed ข้อความแฟลชหรือค่าเริ่มต้นถ้าไม่มีข้อความ
     */
    public static function flash(string $key, $default = null)
    {
        Session::start(); // เริ่มเซสชันถ้ายังไม่เริ่ม
        return Session::getFlash($key, $default); // ดึงข้อความแฟลชจากเซสชัน
    }

    /**
     * ตรวจสอบว่ามีข้อความแฟลชสำหรับคีย์ที่ระบุหรือไม่
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่ามีข้อความแฟลชสำหรับแสดงข้อความชั่วคราวหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * if (FormHelper::hasFlash('success')) {
     *     // มีข้อความแฟลชสำหรับ 'success'
     * }
     * ```
     * 
     * returns bool true ถ้ามีข้อความแฟลชสำหรับคีย์ที่ระบุ, false ถ้าไม่มี
     */
    public static function hasFlash(string $key): bool
    {
        Session::start(); // เริ่มเซสชันถ้ายังไม่เริ่ม
        return Session::hasFlash($key); // ตรวจสอบว่ามีข้อความแฟลชในเซสชัน
    }

    /**
     * ดึงข้อความแฟลชทั้งหมดจากเซสชัน
     * จุดประสงค์: ใช้เพื่อดึงข้อความแฟลชทั้งหมดสำหรับการ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $allMessages = FormHelper::allFlash();
     * ```
     * 
     * returns array ข้อความแฟลชทั้งหมดในรูปแบบของอาร์เรย์
     */
    public static function allFlash(): array
    {
        Session::start(); // เริ่มเซสชันถ้ายังไม่เริ่ม
        return Session::getAllFlash(); // ดึงข้อความแฟลชทั้งหมดจากเซสชัน
    }

    /**
     * ดึงข้อมูลเก่าที่ผู้ใช้ป้อนในฟอร์ม (จะพร้อมใช้งานในการร้องขอครั้งถัดไป)
     * จุดประสงค์: ใช้เพื่อเติมค่าฟอร์มหลังจากการส่งที่ล้มเหลว
     *
     * ใช้สำหรับแอตทริบิวต์ HTML เช่น value="..."
     * ตัวอย่างการใช้งาน:
     * ```php
     * <input type="text" name="username" value="<?= FormHelper::old('username', 'defaultUser') ?>">
     * ```
     * 
     * returns string ข้อมูลเก่าหรือค่าเริ่มต้นถ้าไม่มีข้อมูล
     */
    public static function old(string $key, $default = '', bool $escape = true): string
    {
        Session::start(); // เริ่มเซสชันถ้ายังไม่เริ่ม
        $value = Session::old($key, $default); // ดึงข้อมูลเก่าจากเซสชัน

        // ถ้าค่าที่ดึงมาเป็นอาร์เรย์หรืออ็อบเจ็กต์ ให้ใช้ค่าเริ่มต้นแทน
        if (is_array($value) || is_object($value)) {
            $value = $default;
        }

        $stringValue = (string) $value;

        return $escape ? SecurityHelper::escape($stringValue) : $stringValue; // หนีอักขระพิเศษถ้าจำเป็น
    }

    /**
     * ดึงข้อมูลเก่าที่ผู้ใช้ป้อนในฟอร์มแบบดิบ (สามารถเป็นอาร์เรย์ได้)
     * จุดประสงค์: ใช้เมื่อคุณต้องการรับค่าดิบโดยไม่ต้องแปลงเป็นสตริงหรือหนีอักขระพิเศษ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $rawValue = FormHelper::oldRaw('field_name');
     * ```
     * 
     * returns mixed ข้อมูลเก่าหรือค่าเริ่มต้นถ้าไม่มีข้อมูล
     */
    public static function oldRaw(?string $key = null, $default = null)
    {
        Session::start(); // เริ่มเซสชันถ้ายังไม่เริ่ม
        return Session::old($key, $default); // ดึงข้อมูลเก่าจากเซสชัน
    }

    /**
     * ตรวจสอบว่ามีข้อมูลเก่าสำหรับคีย์ที่ระบุหรือไม่
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่ามีข้อมูลเก่าสำหรับเติมฟอร์มหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * if (FormHelper::hasOld('username')) {
     *     // มีข้อมูลเก่าสำหรับ 'username'
     * }
     * ```
     * 
     * returns bool true ถ้ามีข้อมูลเก่าสำหรับคีย์ที่ระบุ, false ถ้าไม่มี
     */
    public static function hasOld(string $key): bool
    {
        Session::start(); // เริ่มเซสชันถ้ายังไม่เริ่ม
        return Session::hasOldInput($key); // ตรวจสอบว่ามีข้อมูลเก่าสำหรับคีย์ในเซสชัน
    }

    /**
     * ดึงข้อผิดพลาดการตรวจสอบข้อมูล (validation errors) ที่ถูกแฟลชไว้
     * จุดประสงค์: ใช้เพื่อแสดงข้อผิดพลาดการตรวจสอบข้อมูลในฟอร์ม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $errors = FormHelper::errors(); // ดึงข้อผิดพลาดทั้งหมด
     * $usernameErrors = FormHelper::errors('username'); // ดึงข้อผิดพลาดสำหรับฟิลด์ 'username'    
     * ```
     *
     * returns array ข้อผิดพลาดทั้งหมดหรือข้อผิดพลาดสำหรับฟิลด์ที่ระบุ
     */
    public static function errors(?string $field = null): array
    {
        Session::start(); // เริ่มเซสชันถ้ายังไม่เริ่ม
        $errors = Session::getFlash('validation_errors', []);

        // ถ้า $field เป็น null ให้คืนข้อผิดพลาดทั้งหมด, ถ้าไม่ใช่ ให้คืนเฉพาะข้อผิดพลาดสำหรับฟิลด์นั้น
        if (!is_array($errors)) {
            return [];
        }

        // ถ้า $field เป็น null ให้คืนข้อผิดพลาดทั้งหมด
        if ($field === null) {
            return $errors;
        }

        $fieldErrors = $errors[$field] ?? [];
        return is_array($fieldErrors) ? $fieldErrors : [];
    }

    /**
     * ตรวจสอบว่ามีข้อผิดพลาดสำหรับฟิลด์ที่ระบุหรือไม่
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่ามีข้อผิดพลาดการตรวจสอบข้อมูลสำหรับฟิลด์ในฟอร์มหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * if (FormHelper::hasError('username')) {
     *     // มีข้อผิดพลาดสำหรับฟิลด์ 'username'
     * }
     * ```
     * 
     * returns bool true ถ้ามีข้อผิดพลาดสำหรับฟิลด์ที่ระบุ, false ถ้าไม่มี
     */
    public static function hasError(string $field): bool
    {
        return !empty(self::errors($field));
    }

    /**
     * ดึงข้อความข้อผิดพลาดแรกสำหรับฟิลด์ที่ระบุ
     * จุดประสงค์: ใช้เพื่อแสดงข้อความข้อผิดพลาดแรกสำหรับฟิลด์ในฟอร์ม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $firstError = FormHelper::firstError('username');
     * ```
     * 
     * returns string|null ข้อความข้อผิดพลาดแรกหรือค่าเริ่มต้นถ้าไม่มีข้อผิดพลาด
     */
    public static function firstError(string $field, ?string $default = null, bool $escape = true): ?string
    {
        $messages = self::errors($field);
        $first = $messages[0] ?? $default;

        if ($first === null) {
            return null;
        }

        $stringValue = (string) $first;
        return $escape ? SecurityHelper::escape($stringValue) : $stringValue;
    }

    /**
     * คืนคลาส CSS 'is-invalid' ถ้ามีข้อผิดพลาดสำหรับฟิลด์ที่ระบุ
     * จุดประสงค์: ใช้เพื่อเพิ่มคลาส CSS สำหรับการแสดงข้อผิดพลาดในฟอร์ม
     * ตัวอย่างการใช้งาน:
     * ```php
     * echo '<input type="text" class="' . FormHelper::invalidClass('username') . '">';
     * ```
     * returns string 'is-invalid' ถ้ามีข้อผิดพลาด, '' ถ้าไม่มี
     */
    public static function invalidClass(string $field, string $class = 'is-invalid'): string
    {
        return self::hasError($field) ? $class : '';
    }

    public static function csrfField(): string
    {
        Session::start();
        return Session::csrfField();
    }

    public static function csrfMeta(): string
    {
        Session::start();
        return Session::csrfMeta();
    }
}
