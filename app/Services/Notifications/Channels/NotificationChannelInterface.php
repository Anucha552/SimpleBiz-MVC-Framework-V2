<?php
/**
 * interface NotificationChannelInterface
 * 
 * จุดประสงค์: กำหนดสัญญาสำหรับช่องทางการส่งการแจ้งเตือนต่างๆ เช่น อีเมล การแจ้งเตือนภายในแอปพลิเคชัน หรือช่องทางอื่นๆ ที่อาจเพิ่มในอนาคต
 * 
 * ตัวอย่างการใช้งาน:
 * ```php
 * class EmailChannel implements NotificationChannelInterface {
 *     public function send(Notification $notification, array $recipients): bool {
 *         // โค้ดสำหรับส่งการแจ้งเตือนผ่านทางอีเมล
 *     }
 * }
 * ```
 *
 */

namespace App\Services\Notifications\Channels;

use App\Services\Notifications\Notification;

interface NotificationChannelInterface
{
    /**
     * ส่งการแจ้งเตือนไปยังผู้รับ
     * จุดประสงค์: ใช้เพื่อส่งการแจ้งเตือนไปยังผู้รับที่ระบุผ่านช่องทางการแจ้งเตือนที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $notification = new Notification('หัวข้อ', 'ข้อความ', '<p>ข้อความ HTML</p>', ['key' => 'value']);
     * $recipients = [1, 2, 3]; // รายการ ID ผู้รับ
     * $channel = new EmailChannel();
     * $channel->send($notification, $recipients);
     * ```
     *
     * @param Notification $notification วัตถุ Notification ที่ประกอบด้วยข้อมูลการแจ้งเตือน เช่น หัวข้อ ข้อความ และข้อมูลเมตาอื่นๆ
     * @param array<int, string|array{email?: string, phone?: string, name?: string, id?: int}> $recipients รายการผู้รับที่ต้องการส่งการแจ้งเตือน (สามารถเป็น ID ผู้รับหรือข้อมูลผู้รับในรูปแบบอาร์เรย์)
     * @return bool คืนค่า true ถ้าส่งการแจ้งเตือนได้สำเร็จ หรือ false ถ้าเกิดข้อผิดพลาด
     */
    public function send(Notification $notification, array $recipients): bool;
}
