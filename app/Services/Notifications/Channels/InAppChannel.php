<?php
/**
 * class InAppChannel
 * 
 * จุดประสงค์: จัดการการส่งการแจ้งเตือนผ่านทางแอปพลิเคชัน (In-App Notifications) โดยบันทึกการแจ้งเตือนไว้ในฐานข้อมูลเพื่อให้ผู้ใช้สามารถดูได้ภายในแอปพลิเคชัน
 * 
 * ตัวอย่างการใช้งาน:
 * ```php
 * $notification = new Notification('หัวข้อ', 'ข้อความ', '<p>ข้อความ HTML</p>', ['key' => 'value']);
 * $recipients = [1, 2, 3]; // รายการ ID ผู้รับ
 * $inAppChannel = new InAppChannel(new Logger());
 * $inAppChannel->send($notification, $recipients);
 * ```
 * 
 */

namespace App\Services\Notifications\Channels;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Schema; // ใช้สำหรับสร้าง/แก้ไขโครงสร้างตารางในฐานข้อมูลแบบโปรแกรมมิ่ง
use App\Services\Notifications\Notification;

class InAppChannel implements NotificationChannelInterface
{
    /**
     * ตัวบันทึกเหตุการณ์ (Logger) สำหรับบันทึกข้อผิดพลาดที่เกิดขึ้นระหว่างการส่งการแจ้งเตือนผ่านทางแอปพลิเคชัน
     */
    private ?Logger $logger;

    /**
    * สร้างอินสแตนซ์ของ InAppChannel
    * จุดประสงค์: กำหนดค่าต่างๆ ของ InAppChannel เช่น ตัวบันทึกเหตุการณ์
    * ตัวอย่างการใช้งาน:
    * ```php
    * $inAppChannel = new InAppChannel(new Logger());
    * ```
    * 
    * @param Logger|null $logger ตัวบันทึกเหตุการณ์ (Logger) สำหรับบันทึกข้อผิดพลาด
    */
    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * ส่งการแจ้งเตือนไปยังผู้รับผ่านทางแอปพลิเคชัน
     * จุดประสงค์: ใช้เพื่อส่งการแจ้งเตือนไปยังผู้รับที่ระบุผ่านทางแอปพลิเคชัน โดยใช้ข้อมูลจากวัตถุ Notification และรายการผู้รับที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $notification = new Notification('หัวข้อ', 'ข้อความ', '<p>ข้อความ HTML</p>', ['key' => 'value']);
     * $recipients = [1, 2, 3]; // รายการ ID ผู้รับ
     * $inAppChannel = new InAppChannel(new Logger());
     * $inAppChannel->send($notification, $recipients);
     * ```
     *
     * @param Notification $notification วัตถุ Notification ที่ประกอบด้วยข้อมูลการแจ้งเตือน เช่น หัวข้อ ข้อความ และข้อมูลเมตาอื่นๆ
     * @param array<int, int|string|array{email?: string, phone?: string, name?: string, id?: int}> $recipients รายการผู้รับที่ต้องการส่งการแจ้งเตือน (สามารถเป็น ID ผู้รับหรือข้อมูลผู้รับในรูปแบบอาร์เรย์)
     * @return bool คืนค่า true ถ้าส่งการแจ้งเตือนสำเร็จ หรือ false ถ้าเกิดข้อผิดพลาด
     */
    public function send(Notification $notification, array $recipients): bool
    {
        // แปลงรายการผู้รับให้เป็นรูปแบบ ID ผู้รับที่เป็นจำนวนเต็ม
        $recipientIds = $this->normalizeRecipients($recipients);
        if ($recipientIds === []) {
            return false;
        }

        // ตรวจสอบว่ามีตาราง notifications ในฐานข้อมูลหรือไม่ ถ้าไม่มีให้สร้างตารางขึ้นมา
        $db = Database::getInstance();
        if (!$this->ensureNotificationsTable($db)) {
            if ($this->logger) {
                $this->logger->error('notification.in_app.table_missing', [
                    'subject' => $notification->subject,
                ]);
            }
            return false;
        }

        // ตรวจสอบว่าตาราง notifications มีคอลัมน์ที่จำเป็นหรือไม่ ถ้าไม่มีให้บันทึกข้อผิดพลาดและหยุดการส่ง
        $columns = $this->getTableColumns($db, 'notifications');
        if ($columns === []) {
            if ($this->logger) {
                $this->logger->error('notification.in_app.columns_missing', [
                    'subject' => $notification->subject,
                ]);
            }
            return false;
        }

        // สร้างข้อมูลการแจ้งเตือนสำหรับแต่ละผู้รับและบันทึกลงในฐานข้อมูลภายในทรานแซคชันเพื่อความปลอดภัย
        $now = date('Y-m-d H:i:s');

        // พยายามส่งการแจ้งเตือนและจับข้อผิดพลาดที่เกิดขึ้น
        try {
            $db->transaction(function (Database $db) use ($recipientIds, $notification, $columns, $now): void {
                foreach ($recipientIds as $recipientId) {
                    $payload = $this->buildPayload($notification, $recipientId, $columns, $now);
                    if ($payload === []) {
                        continue;
                    }

                    $fields = array_keys($payload);
                    $placeholders = array_map(static fn(string $field): string => ':' . $field, $fields);
                    $sql = 'INSERT INTO `notifications` (`' . implode('`, `', $fields) . '`) VALUES (' . implode(', ', $placeholders) . ')';
                    $db->execute($sql, $payload);
                }
            });
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('notification.in_app.failed', [
                    'error' => $e->getMessage(),
                    'subject' => $notification->subject,
                ]);
            }
            return false;
        }

        // ถ้าส่งการแจ้งเตือนได้สำเร็จ ให้คืนค่า true
        return true;
    }

    /**
     * แปลงรายการผู้รับให้เป็นรูปแบบ ID ผู้รับที่เป็นจำนวนเต็ม
     * จุดประสงค์: ใช้เพื่อแปลงรายการผู้รับที่อาจเป็น ID ผู้รับในรูปแบบต่างๆ (เช่น จำนวนเต็ม, สตริงที่เป็นตัวเลข, หรืออาร์เรย์ที่มีคีย์ ID) ให้เป็นรูปแบบ ID ผู้รับที่เป็นจำนวนเต็มสำหรับการบันทึกในฐานข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $recipients = [1, '2', ['id' => 3], ['user_id' => 4], ['recipient_id' => 5], ['notifiable_id' => 6]];
     * $normalized = $inAppChannel->normalizeRecipients($recipients);
     * // ผลลัพธ์: $normalized จะเป็น [1, 2, 3, 4, 5, 6]
     * ```
     * 
     * @param array<int, mixed> $recipients รายการผู้รับที่ต้องการแปลง (สามารถเป็นจำนวนเต็ม, สตริงที่เป็นตัวเลข, หรืออาร์เรย์ที่มีคีย์ ID)
     * @return array<int, int> คืนค่ารายการ ID ผู้รับที่เป็นจำนวนเต็มที่ไม่ซ้ำกันและถูกกรองให้เป็น ID ที่มีค่ามากกว่า 0 เท่านั้น
     */
    private function normalizeRecipients(array $recipients): array
    {
        $ids = [];

        // วนลูปผ่านรายการผู้รับและแปลงเป็น ID ผู้รับที่เป็นจำนวนเต็ม
        foreach ($recipients as $recipient) {
            if (is_int($recipient)) {
                $ids[] = $recipient;
                continue;
            }
            if (is_string($recipient) && ctype_digit($recipient)) {
                $ids[] = (int) $recipient;
                continue;
            }
            if (is_array($recipient)) {
                $id = $recipient['id'] ?? $recipient['user_id'] ?? $recipient['recipient_id'] ?? $recipient['notifiable_id'] ?? null;
                if (is_int($id) || (is_string($id) && ctype_digit($id))) {
                    $ids[] = (int) $id;
                }
            }
        }

        // กรองและลบ ID ที่ไม่ถูกต้อง (เช่น ID ที่มีค่าไม่เป็นจำนวนเต็มหรือมีค่าน้อยกว่าหรือเท่ากับ 0) และลบ ID ที่ซ้ำกัน
        $ids = array_values(array_unique(array_filter($ids, static fn(int $id): bool => $id > 0)));
        return $ids;
    }

    /**
     * ตรวจสอบว่ามีตาราง notifications ในฐานข้อมูลหรือไม่ ถ้าไม่มีให้สร้างตารางขึ้นมา
     * จุดประสงค์: ใช้เพื่อให้แน่ใจว่ามีตาราง notifications ในฐานข้อมูลสำหรับบันทึกการแจ้งเตือนผ่านทางแอปพลิเคชัน ถ้าไม่มีตารางนี้จะพยายามสร้างขึ้นมาใหม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $db = Database::getInstance();
     * $inAppChannel->ensureNotificationsTable($db);
     * ```
     * 
     * @param Database $db อินสแตนซ์ของฐานข้อมูลที่ใช้ในการตรวจสอบและสร้างตาราง
     * @return bool คืนค่า true ถ้ามีตาราง notifications หรือสร้างได้สำเร็จ, false ถ้าเกิดข้อผิดพลาดในการสร้างตาราง
     */
    /**
     * ตรวจสอบและสร้างตาราง notifications หากยังไม่มีในฐานข้อมูล
     * จุดที่เกี่ยวข้องกับ Schema: ใช้ Schema::create เพื่อสร้างตาราง notifications ด้วยโครงสร้างที่กำหนดผ่าน callback
     * Schema จะสร้าง instance ของ Blueprint แล้วส่งเข้า callback เป็น $table
     *
     * @param Database $db อินสแตนซ์ของฐานข้อมูล
     * @return bool คืนค่า true ถ้าตารางมีอยู่หรือสร้างได้สำเร็จ, false ถ้าเกิดข้อผิดพลาด
     */
    private function ensureNotificationsTable(Database $db): bool
    {
        // ถ้าไม่มีผู้รับที่ระบุ ให้ส่งคืนค่า false ทันที
        if ($this->tableExists($db, 'notifications')) {
            return true;
        }

        try {
            // ใช้ Schema::create เพื่อสร้างตาราง notifications
            // $table ที่อยู่ใน callback คือ instance ของ Blueprint
            Schema::create($db, 'notifications', function ($table): void {
                $table->increments('id')->comment('รหัสแจ้งเตือน (Primary Key)');
                $table->integer('user_id')->comment('รหัสผู้รับการแจ้งเตือน');
                $table->string('title', 255)->comment('หัวข้อการแจ้งเตือน');
                $table->text('message')->comment('ข้อความแจ้งเตือน (Text)');
                $table->text('html', true)->comment('ข้อความ HTML สำหรับแสดงผล');
                $table->text('meta', true)->comment('ข้อมูลเพิ่มเติม (Meta/JSON)');
                $table->boolean('is_read', false, 0)->comment('สถานะการอ่าน (1=อ่านแล้ว, 0=ยังไม่อ่าน)');
                $table->timestamp('read_at', true)->comment('วันที่และเวลาที่อ่าน');
                $table->timestamps(); // created_at, updated_at (มีคอมเมนต์ใน Blueprint อยู่แล้ว)
            });
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('notification.in_app.create_table_failed', [
                    'error' => $e->getMessage(),
                ]);
            }
            return false;
        }

        return true;
    }

    /**
     * ตรวจสอบว่ามีตารางในฐานข้อมูลหรือไม่
     * จุดประสงค์: ใช้เพื่อเช็คว่าตารางที่ระบุมีอยู่ในฐานข้อมูลหรือไม่ โดยรองรับทั้ง MySQL และ SQLite
     * ตัวอย่างการใช้งาน:
     * ```php
     * $db = Database::getInstance();
     * $exists = $inAppChannel->tableExists($db, 'notifications');
     * ```
     *
     * @param Database $db อินสแตนซ์ของฐานข้อมูล
     * @param string $table ชื่อตารางที่ต้องการตรวจสอบ
     * @return bool คืนค่า true ถ้าตารางมีอยู่, false ถ้าไม่มีหรือเกิดข้อผิดพลาด
     */
    private function tableExists(Database $db, string $table): bool
    {
        try {
            $driver = $db->getDriverName();
            if ($driver === 'sqlite') {
                return (bool) $db->fetchColumn(
                    "SELECT name FROM sqlite_master WHERE type='table' AND name = :name",
                    ['name' => $table]
                );
            }
            return (bool) $db->fetchColumn('SHOW TABLES LIKE :name', ['name' => $table]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * ดึงรายชื่อคอลัมน์ของตารางจากฐานข้อมูล
     * จุดประสงค์: ใช้เพื่อดึงรายชื่อคอลัมน์ของตารางที่ระบุจากฐานข้อมูล โดยรองรับทั้ง MySQL และ SQLite
     * ตัวอย่างการใช้งาน:
     * ```php
     * $db = Database::getInstance();
     * $columns = $inAppChannel->getTableColumns($db, 'notifications');
     * ```
     *
     * @param Database $db อินสแตนซ์ของฐานข้อมูล
     * @param string $table ชื่อตารางที่ต้องการดึงรายชื่อคอลัมน์
     * @return array<int, string> คืนค่ารายชื่อคอลัมน์ในรูปแบบอาร์เรย์ของสตริง หรืออาร์เรย์ว่างถ้าเกิดข้อผิดพลาด
     */
    private function getTableColumns(Database $db, string $table): array
    {
        try {
            $driver = $db->getDriverName();
            if ($driver === 'sqlite') {
                $rows = $db->fetchAll('PRAGMA table_info(' . $table . ')');
                return array_values(array_filter(array_map(static fn(array $row): string => $row['name'] ?? '', $rows)));
            }
            $rows = $db->fetchAll('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
            return array_values(array_filter(array_map(static fn(array $row): string => $row['Field'] ?? '', $rows)));
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * สร้างข้อมูลการแจ้งเตือนสำหรับการบันทึกลงในฐานข้อมูล
     * จุดประสงค์: ใช้เพื่อสร้างข้อมูลการแจ้งเตือนในรูปแบบอาร์เรย์ที่สามารถบันทึกลงในฐานข้อมูลได้ โดยตรวจสอบว่าคอลัมน์ที่ต้องการมีอยู่ในตารางหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $db = Database::getInstance();
     * $columns = $inAppChannel->getTableColumns($db, 'notifications');
     * $payload = $inAppChannel->buildPayload($notification, $recipientId, $columns, date('Y-m-d H:i:s'));
     * ```
     *
     * @param Notification $notification วัตถุ Notification ที่ประกอบด้วยข้อมูลการแจ้งเตือน
     * @param int $recipientId ID ของผู้รับการแจ้งเตือน
     * @param array<int, string> $columns รายชื่อคอลัมน์ของตาราง notifications
     * @param string $now เวลาปัจจุบันในรูปแบบ 'Y-m-d H:i:s'
     * @return array<string, mixed> คืนค่าอาร์เรย์ของข้อมูลการแจ้งเตือนที่สามารถบันทึกลงในฐานข้อมูล
     */
    private function buildPayload(Notification $notification, int $recipientId, array $columns, string $now): array
    {
        $available = array_flip($columns);
        $metaJson = $notification->meta !== []
            ? json_encode($notification->meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : null;

        $payload = [];

        $this->setIfExists($payload, $available, ['user_id', 'recipient_id', 'notifiable_id'], $recipientId);
        $this->setIfExists($payload, $available, ['title', 'subject'], $notification->subject);
        $this->setIfExists($payload, $available, ['message', 'body', 'content'], $notification->message);
        $this->setIfExists($payload, $available, ['html', 'content_html'], $notification->html);
        $this->setIfExists($payload, $available, ['meta', 'data', 'payload', 'extra'], $metaJson);
        $this->setIfExists($payload, $available, ['is_read', 'read'], 0);
        $this->setIfExists($payload, $available, ['read_at'], null);
        $this->setIfExists($payload, $available, ['type', 'channel'], 'in_app');
        $this->setIfExists($payload, $available, ['created_at'], $now);
        $this->setIfExists($payload, $available, ['updated_at'], $now);

        return $payload;
    }

    /**
     * ตั้งค่าข้อมูลใน payload ถ้าคอลัมน์ที่ต้องการมีอยู่ในตาราง
     * จุดประสงค์: ใช้เพื่อช่วยในการสร้างข้อมูลการแจ้งเตือนโดยตรวจสอบว่าคอลัมน์ที่ต้องการมีอยู่ในตารางหรือไม่ และถ้ามีให้ตั้งค่าข้อมูลลงใน payload
     * ตัวอย่างการใช้งาน:
     * ```php
     * $payload = [];
     * $available = ['user_id' => 0, 'title' => 1, 'message' => 2];
     * $inAppChannel->setIfExists($payload, $available, ['user_id', 'recipient_id'], $recipientId);
     * $inAppChannel->setIfExists($payload, $available, ['title', 'subject'], $notification->subject);
     * $inAppChannel->setIfExists($payload, $available, ['message', 'body'], $notification->message);
     * ```
     *
     * @param array<string, mixed> $payload อาร์เรย์ของข้อมูลการแจ้งเตือนที่กำลังสร้างขึ้น
     * @param array<string, int> $available รายชื่อคอลัมน์ที่มีอยู่ในตาราง (เป็นอาร์เรย์ที่มีคีย์เป็นชื่อคอลัมน์และค่าที่ไม่สำคัญ)
     * @param array<int, string> $candidates รายชื่อคอลัมน์ที่ต้องการตรวจสอบและตั้งค่า (จะวนลูปผ่านรายชื่อนี้และตรวจสอบกับรายชื่อคอลัมน์ที่มีอยู่)
     * @param mixed $value ค่าที่ต้องการตั้งถ้าพบว่าคอลัมน์ที่ต้องการมีอยู่ในตาราง
     * @return void ไม่มีการคืนค่า แต่จะปรับเปลี่ยนอาร์เรย์ $payload ที่ส่งเข้ามาโดยตรงถ้าพบว่าคอลัมน์ที่ต้องการมีอยู่ในตาราง
     */
    private function setIfExists(array &$payload, array $available, array $candidates, $value): void
    {
        foreach ($candidates as $column) {
            if (isset($available[$column])) {
                $payload[$column] = $value;
                return;
            }
        }
    }
}
