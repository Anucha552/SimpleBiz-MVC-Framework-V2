<?php
/**
 * class Authorization
 * 
 * จุดประสงค์: จัดการการตรวจสอบสิทธิ์ของผู้ใช้ในระบบ โดยมีฟังก์ชันหลักคือ `can()` สำหรับตรวจสอบว่าผู้ใช้มีสิทธิ์ในการทำบางอย่างหรือไม่ และ `hasRole()` สำหรับตรวจสอบว่าผู้ใช้มีบทบาท (role) ที่ระบุหรือไม่
 * 
 * ตัวอย่างการใช้งาน:
 * ```php
 * if (Authorization::can('edit_posts')) {
 *     // ผู้ใช้มีสิทธิ์ในการแก้ไขโพสต์
 * } else {
 *     // ผู้ใช้ไม่มีสิทธิ์ในการแก้ไขโพสต์
 * }
 * ```
 */

namespace App\Core;

use App\Core\Auth;
use App\Core\Session;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Config;

class Authorization
{
    /**
     * บันทึก Logger instance เพื่อใช้ในการบันทึกเหตุการณ์ที่เกี่ยวข้องกับการตรวจสอบสิทธิ์ เช่น การอนุญาตหรือปฏิเสธการเข้าถึง และข้อผิดพลาดที่เกิดขึ้นในกระบวนการตรวจสอบสิทธิ์
     */
    private static ?Logger $logger = null;

    /**
     * เก็บ hash ของเหตุการณ์ที่ถูกบันทึกไปแล้วในคำขอปัจจุบัน เพื่อป้องกันการบันทึกซ้ำของเหตุการณ์เดียวกันหลายครั้งในคำขอเดียวกัน ซึ่งช่วยลดความซ้ำซ้อนในบันทึกและทำให้บันทึกมีความชัดเจนมากขึ้น
     */
    private static array $seenLogs = [];

    /**
     * รับ Logger instance แบบ lazy initialization เพื่อใช้ในการบันทึกเหตุการณ์ที่เกี่ยวข้องกับการตรวจสอบสิทธิ์
     * จุดประสงค์: ใช้เพื่อรับ Logger instance แบบ lazy initialization ซึ่งจะสร้าง instance เมื่อมีการเรียกใช้งานครั้งแรก และเก็บไว้ในตัวแปร static เพื่อใช้ในการบันทึกเหตุการณ์ที่เกี่ยวข้องกับการตรวจสอบสิทธิ์ในภายหลัง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $logger = Authorization::getLogger();
     * $logger->info('This is a log message.');
     * ```
      *
      * @return Logger คืนค่า Logger instance ที่ใช้ในการบันทึกเหตุการณ์
     */
    private static function getLogger(): Logger
    {
        if (self::$logger === null) {
            self::$logger = new Logger();
        }
        return self::$logger;
    }

    /**
     * บันทึกเหตุการณ์เพียงครั้งเดียวต่อคำขอ ใช้ hash ของ level/event/context
     * เพื่อลดการบันทึกซ้ำ
     * จุดประสงค์: ใช้เพื่อบันทึกเหตุการณ์ที่เกี่ยวข้องกับการตรวจสอบสิทธิ์เพียงครั้งเดียวต่อคำขอ โดยใช้ hash ของระดับเหตุการณ์ (level), ชื่อเหตุการณ์ (event) และข้อมูลบริบท (context) เพื่อป้องกันการบันทึกซ้ำของเหตุการณ์เดียวกันหลายครั้งในคำขอเดียวกัน ซึ่งช่วยลดความซ้ำซ้อนในบันทึกและทำให้บันทึกมีความชัดเจนมากขึ้น
     * ตัวอย่างการใช้งาน:
     * ```php
     * Authorization::logOnce('warning', 'authorization.denied', ['reason' => 'not_authenticated', 'permission' => $permission]);
     * ```
     *
     * @param string $level ระดับของเหตุการณ์ (เช่น 'info', 'warning', 'error', 'security')
     * @param string $event ชื่อเหตุการณ์
     * @param array $context ข้อมูลเพิ่มเติมเกี่ยวกับเหตุการณ์
     */
    private static function logOnce(string $level, string $event, array $context = []): void
    {
        $key = md5($level . '|' . $event . '|' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if (isset(self::$seenLogs[$key])) {
            return;
        }
        self::$seenLogs[$key] = true;

        $logger = self::getLogger();
        switch (strtolower($level)) {
            case 'security':
            case 'sec':
                $logger->security($event, $context);
                break;
            case 'error':
                $logger->error($event, $context);
                break;
            case 'warning':
                $logger->warning($event, $context);
                break;
            default:
                $logger->info($event, $context);
                break;
        }
    }
    /**
     * ตรวจสอบว่าผู้ใช้ที่ล็อกอินอยู่มีสิทธิ์ในการทำบางอย่างหรือไม่
      * จุดประสงค์: ใช้เพื่อตรวจสอบว่าผู้ใช้ที่ล็อกอินอยู่มีสิทธิ์ในการทำบางอย่างหรือไม่ โดยตรวจสอบจากข้อมูลผู้ใช้ใน session และฐานข้อมูล
      * ตัวอย่างการใช้งาน:
      * ```php
      * if (Authorization::can('edit_posts')) {
      *     // ผู้ใช้มีสิทธิ์ในการแก้ไขโพสต์
      * } else {
      *     // ผู้ใช้ไม่มีสิทธิ์ในการแก้ไขโพสต์
      * }
      * ```
      *
      * @param string $permission ชื่อของสิทธิ์ที่ต้องการตรวจสอบ
      * @return bool คืนค่า true หากผู้ใช้มีสิทธิ์นั้น, false หากไม่มี
     */
    public static function can(string $permission): bool
    {
        $user = Auth::user();
        if (!$user) {
            self::logOnce('warning', 'authorization.denied', ['reason' => 'not_authenticated', 'permission' => $permission]);
            return false;
        }

        if (!empty($user['is_admin']) || (!empty($user['role']) && $user['role'] === 'admin')) {
            self::logOnce('info', 'authorization.allowed', ['reason' => 'admin', 'permission' => $permission, 'user_id' => $user['id'] ?? null]);
            return true;
        }
        // ขั้นแรก ตรวจสอบว่ามีการบันทึกสิทธิ์การใช้งานไว้ในเซสชันสำหรับผู้ใช้รายนี้หรือไม่
        try {
            Session::start();
            $cached = Session::get('_auth_permissions', null);
            if (is_array($cached)) {
                // รองรับทั้งรูปแบบเก่า (array of permissions) และรูปแบบใหม่ (array with 'perms' and 'ts')
                if (isset($cached['perms']) && is_array($cached['perms'])) {
                    $permsCached = $cached['perms'];
                    $ts = (int)($cached['ts'] ?? 0);
                } else {
                    $permsCached = $cached;
                    $ts = 0;
                }

                $ttl = (int) Config::get('auth.permission_cache_ttl', 3600);
                if ($ttl > 0 && $ts > 0 && (time() - $ts) > $ttl) {
                    // หมดอายุ — ดำเนินการโหลดใหม่
                } else {
                    $allowed = in_array($permission, $permsCached, true);
                    if ($allowed) {
                        self::logOnce('info', 'authorization.allowed', ['reason' => 'session_cache', 'permission' => $permission, 'user_id' => $user['id'] ?? null]);
                    }
                    return $allowed;
                }
            }
        } catch (\Throwable $e) {
            // ข้ามปัญหาเกี่ยวกับ session และดำเนินการต่อ
        }

        $perms = self::normalizePermissions($user['permissions'] ?? null);
        if (is_array($perms) && count($perms) > 0) {
            // ถ้าเรามีสิทธิ์ที่แปลงแล้วจากข้อมูลผู้ใช้ ให้แคชมันไว้ใน session เพื่อประสิทธิภาพในการตรวจสอบครั้งต่อไป
            try {
                Session::start();
                Session::set('_auth_permissions', ['perms' => $perms, 'ts' => time()]);
            } catch (\Throwable $e) {
                // ข้ามปัญหาเกี่ยวกับการเขียน session และดำเนินการต่อ
            }
            $allowed = in_array($permission, $perms, true);
            if ($allowed) {
                self::logOnce('info', 'authorization.allowed', ['reason' => 'user_record', 'permission' => $permission, 'user_id' => $user['id'] ?? null]);
            }
            return $allowed;
        }

        try {
            $db = Database::getInstance();
            $userId = $user['id'] ?? null;

            if ($userId) {
                $sql = "SELECT 1 FROM user_permissions WHERE user_id = :uid AND permission = :perm LIMIT 1";
                $exists = $db->fetchColumn($sql, ['uid' => $userId, 'perm' => $permission]);
                if ($exists) {
                    self::logOnce('info', 'authorization.allowed', ['reason' => 'db_user_permission', 'permission' => $permission, 'user_id' => $userId]);
                    return true;
                }
            }

            $roleId = $user['role_id'] ?? null;
            $roleName = $user['role'] ?? null;

            if ($roleId) {
                $sql = "SELECT 1 FROM role_permissions WHERE role_id = :rid AND permission = :perm LIMIT 1";
                $exists = $db->fetchColumn($sql, ['rid' => $roleId, 'perm' => $permission]);
                if ($exists) {
                    self::logOnce('info', 'authorization.allowed', ['reason' => 'db_role_permission', 'permission' => $permission, 'role_id' => $roleId, 'user_id' => $userId]);
                    return true;
                }
            } elseif ($roleName) {
                $sql = "SELECT id FROM roles WHERE name = :rname LIMIT 1";
                $row = $db->fetch($sql, ['rname' => $roleName]);
                if ($row && !empty($row['id'])) {
                    $rid = $row['id'];
                    $sql = "SELECT 1 FROM role_permissions WHERE role_id = :rid AND permission = :perm LIMIT 1";
                    $exists = $db->fetchColumn($sql, ['rid' => $rid, 'perm' => $permission]);
                    if ($exists) {
                        self::logOnce('info', 'authorization.allowed', ['reason' => 'db_role_permission', 'permission' => $permission, 'role' => $roleName, 'user_id' => $userId]);
                        return true;
                    }
                }
            }
        } catch (\Throwable $e) {
            self::logOnce('error', 'authorization.error', ['message' => $e->getMessage(), 'permission' => $permission, 'user_id' => $user['id'] ?? null]);
        }

        self::logOnce('warning', 'authorization.denied', ['reason' => 'no_permission', 'permission' => $permission, 'user_id' => $user['id'] ?? null]);
        return false;
    }

    /**
     * Normalize permissions from various formats (array, JSON string, comma-separated string) into a consistent array format.
      * จุดประสงค์: ใช้เพื่อแปลงสิทธิ์ที่อาจถูกเก็บในรูปแบบต่างๆ (เช่น array, JSON string, หรือ comma-separated string) ให้เป็นรูปแบบ array ที่สอดคล้องกัน เพื่อให้ง่ายต่อการตรวจสอบสิทธิ์ในภายหลัง
      * ตัวอย่างการใช้งาน:
      * ```php
      * $perms = Authorization::normalizePermissions('["edit_posts", "delete_posts"]');
      * // ผลลัพธ์: ['edit_posts', 'delete_posts']
      * ```
      *
      * @param mixed $perms สิทธิ์ที่ต้องการแปลง อาจเป็น array, JSON string, หรือ comma-separated string
      * @return array คืนค่า array ของสิทธิ์ที่ถูกแปลงแล้ว
     */
    public static function normalizePermissions($perms): array
    {
        if (is_array($perms)) {
            return $perms;
        }

        if (is_string($perms)) {
            $decoded = json_decode($perms, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            if (strpos($perms, ',') !== false) {
                return array_filter(array_map('trim', explode(',', $perms)));
            }

            if ($perms !== '') {
                return [$perms];
            }
        }

        return [];
    }

    /**
     * โหลดสิทธิ์ทั้งหมดสำหรับผู้ใช้ปัจจุบันจากฐานข้อมูลและแคชไว้ใน session เพื่อประสิทธิภาพในการตรวจสอบสิทธิ์ในอนาคต
      * จุดประสงค์: ใช้เพื่อโหลดสิทธิ์ทั้งหมดสำหรับผู้ใช้ปัจจุบันจากฐานข้อมูล (รวมถึงสิทธิ์ที่มาจากบทบาทต่างๆ) และแคชไว้ใน session เพื่อให้การตรวจสอบสิทธิ์ในอนาคตมีประสิทธิภาพมากขึ้น โดยไม่ต้องดึงข้อมูลจากฐานข้อมูลทุกครั้งที่มีการตรวจสอบสิทธิ์
      * ตัวอย่างการใช้งาน:
      * ```php
      * $permissions = Authorization::loadAllPermissions(Auth::user());
      * ```
      *
      * @param array|null $user ข้อมูลผู้ใช้ที่ต้องการโหลดสิทธิ์ อาจเป็น null หากไม่มีผู้ใช้ที่ล็อกอินอยู่
      * @return array คืนค่า array ของสิทธิ์ทั้งหมดที่ผู้ใช้มี
     */
    public static function loadAllPermissions(?array $user): array
    {
        if (!$user) {
            return [];
        }

        // ถ้าเป็นแอดมิน ให้อนุญาตทุกสิทธิ์โดยไม่ต้องโหลดจากฐานข้อมูล
        if (!empty($user['is_admin']) || (!empty($user['role']) && $user['role'] === 'admin')) {
            return ['*'];
        }

        $result = [];

        // เริ่มต้นด้วยสิทธิ์ที่ฝังอยู่ในเรคคอร์ดของผู้ใช้
        $result = array_merge($result, self::normalizePermissions($user['permissions'] ?? null));

        try {
            $db = Database::getInstance();
            $userId = $user['id'] ?? null;

            // โหลดสิทธิ์ที่มาจาก user_permissions
            if ($userId) {
                $sql = "SELECT permission FROM user_permissions WHERE user_id = :uid";
                $rows = $db->fetchAll($sql, ['uid' => $userId]);
                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        if (!empty($r['permission'])) {
                            $result[] = $r['permission'];
                        }
                    }
                }

                // โหลดสิทธิ์ที่มาจากบทบาทของผู้ใช้ (user_roles)
                $sql = "SELECT r.id FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = :uid";
                $roles = $db->fetchAll($sql, ['uid' => $userId]);
                $roleIds = [];
                if (is_array($roles)) {
                    foreach ($roles as $r) {
                        if (!empty($r['id'])) {
                            $roleIds[] = (int) $r['id'];
                        }
                    }
                }
            } else {
                $roleIds = [];
            }

            // ถ้ามีฟิลด์ role_id เดียวในผู้ใช้ ให้รวมมันด้วย
            if (!empty($user['role_id'])) {
                $roleIds[] = (int) $user['role_id'];
            }

            // ถ้ามีชื่อบทบาทแต่ไม่พบ role ids ให้ลองแก้ไขมัน
            if (empty($roleIds) && !empty($user['role'])) {
                $sql = "SELECT id FROM roles WHERE name = :rname LIMIT 1";
                $row = $db->fetch($sql, ['rname' => $user['role']]);
                if ($row && !empty($row['id'])) {
                    $roleIds[] = (int) $row['id'];
                }
            }

            // โหลดสิทธิ์สำหรับ role ids ที่รวบรวมได้
            if (!empty($roleIds)) {
                // เตรียม placeholders
                $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
                $sql = "SELECT permission FROM role_permissions WHERE role_id IN (" . $placeholders . ")";
                $rows = $db->fetchAll($sql, $roleIds);
                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        if (!empty($r['permission'])) {
                            $result[] = $r['permission'];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // หากเกิดข้อผิดพลาดใด ๆ กับฐานข้อมูล ให้พยายามแคชสิทธิ์ที่มีจากเรคคอร์ดของผู้ใช้
            $final = array_values(array_unique($result, SORT_REGULAR));
            try {
                Session::start();
                Session::set('_auth_permissions', ['perms' => $final, 'ts' => time()]);
            } catch (\Throwable $_) {
                // ข้ามปัญหาเกี่ยวกับ session และดำเนินการต่อ
            }
            return $final;
        }

        $final = array_values(array_unique($result, SORT_REGULAR));

        // แคชสิทธิ์ลงใน session สำหรับผู้ใช้ปัจจุบันหากเป็นไปได้
        try {
            Session::start();
            Session::set('_auth_permissions', ['perms' => $final, 'ts' => time()]);
        } catch (\Throwable $e) {
            // ข้ามปัญหาเกี่ยวกับ session และดำเนินการต่อ
        }

        return $final;
    }

    /**
     * ล้างแคชสิทธิ์ใน session สำหรับผู้ใช้ปัจจุบันหรือผู้ใช้ที่ระบุ เพื่อให้การตรวจสอบสิทธิ์ในครั้งถัดไปโหลดข้อมูลใหม่จากฐานข้อมูล
      * จุดประสงค์: ใช้เพื่อล้างแคชสิทธิ์ที่เก็บไว้ใน session สำหรับผู้ใช้ปัจจุบันหรือผู้ใช้ที่ระบุ โดยจะทำให้การตรวจสอบสิทธิ์ในครั้งถัดไปต้องโหลดข้อมูลใหม่จากฐานข้อมูล ซึ่งมีประโยชน์เมื่อมีการเปลี่ยนแปลงสิทธิ์ของผู้ใช้และต้องการให้การเปลี่ยนแปลงนั้นมีผลทันที
      * ตัวอย่างการใช้งาน:
      * ```php
      * Authorization::invalidatePermissionCache($userId);
      * ```
      *
      * @param int|null $userId รหัสของผู้ใช้ที่ต้องการล้างแคชสิทธิ์ หากเป็น null จะล้างแคชสำหรับผู้ใช้ที่ล็อกอินอยู่
      * @return void ไม่มีผลลัพธ์ (void)
     */
    public static function invalidatePermissionCache(?int $userId = null): void
    {
        try {
            Session::start();
            $currentId = Auth::user()['id'] ?? null;
            if ($userId === null || $userId === $currentId) {
                Session::remove('_auth_permissions');
                // ล้างบันทึกที่เห็นในกระบวนการเพื่อให้สามารถบันทึกการตัดสินใจใหม่ได้
                self::$seenLogs = [];
            }
        } catch (\Throwable $e) {
            // ข้ามปัญหาเกี่ยวกับ session และดำเนินการต่อ
        }
    }

    /**
     * ตรวจสอบบทบาทสำหรับผู้ใช้ปัจจุบัน
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าผู้ใช้ที่ล็อกอินอยู่มีบทบาท (role) ที่ระบุหรือไม่ โดยตรวจสอบจากข้อมูลผู้ใช้ใน session และฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * if (Authorization::hasRole('editor')) {
     *     // ทำบางอย่างสำหรับผู้ใช้ที่มีบทบาท 'editor'
     * }
     * ```
     *
     * @param string $role ชื่อของบทบาทที่ต้องการตรวจสอบ
     * @return bool คืนค่า true หากผู้ใช้มีบทบาทที่ระบุ, false หากไม่ใช่
     */
    public static function hasRole(string $role): bool
    {
        $user = Auth::user();
        if (!$user) {
            self::logOnce('warning', 'authorization.role.denied', ['reason' => 'not_authenticated', 'role' => $role]);
            return false;
        }

        if (!empty($user['is_admin']) || (!empty($user['role']) && $user['role'] === 'admin')) {
            self::logOnce('info', 'authorization.role.allowed', ['reason' => 'admin', 'role' => $role, 'user_id' => $user['id'] ?? null]);
            return true;
        }

        $roles = $user['roles'] ?? ($user['role'] ?? null);
        if (is_string($roles)) {
            $decoded = json_decode($roles, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $roles = $decoded;
            } else {
                if (strpos($roles, ',') !== false) {
                    $roles = array_filter(array_map('trim', explode(',', $roles)));
                } else {
                    $roles = [$roles];
                }
            }
        }

        if (is_array($roles)) {
            $allowed = in_array($role, $roles, true);
            if ($allowed) {
                self::logOnce('info', 'authorization.role.allowed', ['reason' => 'user_record', 'role' => $role, 'user_id' => $user['id'] ?? null]);
            }
            return $allowed;
        }

        try {
            $db = Database::getInstance();
            $userId = $user['id'] ?? null;

            if ($userId) {
                $sql = "SELECT 1 FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = :uid AND (r.name = :rname OR r.slug = :rname) LIMIT 1";
                $stmt = $db->query($sql, ['uid' => $userId, 'rname' => $role]);
                if ($stmt->fetchColumn()) {
                    self::logOnce('info', 'authorization.role.allowed', ['reason' => 'db_user_role', 'role' => $role, 'user_id' => $userId]);
                    return true;
                }
            }

            $roleName = $user['role'] ?? null;
            if ($roleName && $roleName === $role) {
                self::logOnce('info', 'authorization.role.allowed', ['reason' => 'user_record_rolefield', 'role' => $role, 'user_id' => $user['id'] ?? null]);
                return true;
            }
        } catch (\Throwable $e) {
            self::logOnce('error', 'authorization.role.error', ['message' => $e->getMessage(), 'role' => $role, 'user_id' => $user['id'] ?? null]);
        }

        self::logOnce('warning', 'authorization.role.denied', ['reason' => 'no_role', 'role' => $role, 'user_id' => $user['id'] ?? null]);
        return false;
    }
}
