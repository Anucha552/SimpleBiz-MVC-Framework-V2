<?php
/**
 * class EmailChannel
 * 
 * จุดประสงค์: จัดการการส่งการแจ้งเตือนผ่านทางอีเมล โดยใช้คลาส Mail ในการส่งอีเมล และ Logger ในการบันทึกข้อผิดพลาดที่เกิดขึ้นระหว่างการส่ง
 * 
 * ตัวอย่างการใช้งาน:
 * ```php
 * $emailChannel = new EmailChannel();
 * $notification = new Notification('หัวข้อ', 'ข้อความ', '<p>ข้อความ HTML</p>', ['key' => 'value']);
 * $recipients = ['user1@example.com', 'user2@example.com'];
 * $emailChannel->send($notification, $recipients);
 * ```
 *
 */

namespace App\Services\Notifications\Channels;

use App\Core\Logger;
use App\Core\Mail;
use App\Services\Notifications\Notification;

class EmailChannel implements NotificationChannelInterface
{
    /**
     * ตัวบันทึกเหตุการณ์ (Logger) สำหรับบันทึกข้อผิดพลาดที่เกิดขึ้นระหว่างการส่งอีเมล
     */
    private ?Logger $logger;

    /**
     * ตัวส่งอีเมล (Mail) สำหรับส่งการแจ้งเตือนผ่านทางอีเมล
     */
    private ?Mail $mail;

    /**
    * สร้างอินสแตนซ์ของ EmailChannel
    * จุดประสงค์: กำหนดค่าต่างๆ ของ EmailChannel เช่น ตัวส่งอีเมลและตัวบันทึกเหตุการณ์
    * ตัวอย่างการใช้งาน:
    * ```php
    * $emailChannel = new EmailChannel(new Mail(), new Logger());
    * ```
    *
    * @param Mail|null $mail ตัวส่งอีเมล (Mail) สำหรับส่งการแจ้งเตือนผ่านทางอีเมล (ถ้าไม่ระบุจะสร้างใหม่)
    * @param Logger|null $logger ตัวบันทึกเหตุการณ์ (Logger) สำหรับบันทึกข้อผิดพลาดที่เกิดขึ้นระหว่างการส่งอีเมล (ถ้าไม่ระบุจะไม่ใช้)
    */
    public function __construct(?Mail $mail = null, ?Logger $logger = null)
    {
        $this->mail = $mail;
        $this->logger = $logger;
    }

    /**
     * ส่งการแจ้งเตือนไปยังผู้รับผ่านทางอีเมล
     * จุดประสงค์: ใช้เพื่อส่งการแจ้งเตือนไปยังผู้รับที่ระบุผ่านทางอีเมล โดยใช้ข้อมูลจากวัตถุ Notification และรายการผู้รับที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $notification = new Notification('หัวข้อ', 'ข้อความ', '<p>ข้อความ HTML</p>', ['key' => 'value']);
     * $recipients = ['user1@example.com', 'user2@example.com'];
     * $emailChannel->send($notification, $recipients);
     * ```
     *
     * @param Notification $notification วัตถุ Notification ที่ประกอบด้วยข้อมูลการแจ้งเตือน เช่น หัวข้อ ข้อความ และข้อมูลเมตาอื่นๆ
     * @param array<int, string|array{email?: string, phone?: string, name?: string, id?: int}> $recipients รายการผู้รับที่ต้องการส่งการแจ้งเตือน (สามารถเป็นอีเมลหรือข้อมูลผู้รับในรูปแบบอาร์เรย์)
     * @return bool true ถ้าส่งการแจ้งเตือนได้สำเร็จ, false ถ้าเกิดข้อผิดพลาดในการส่ง
     */
    public function send(Notification $notification, array $recipients): bool
    {
        // ถ้าไม่มีผู้รับที่ระบุ ให้ส่งคืนค่า false ทันที
        if (empty($recipients)) {
            return false;
        }

        // ใช้ตัวส่งอีเมลที่กำหนดไว้ หรือสร้างใหม่ถ้าไม่มี
        $mail = $this->mail ?? new Mail();

        // วนลูปผ่านรายการผู้รับและเพิ่มผู้รับแต่ละคนลงในตัวส่งอีเมล
        foreach ($recipients as $recipient) {
            if (is_string($recipient)) {
                $mail->to($recipient);
                continue;
            }

            $email = $recipient['email'] ?? '';
            $name = $recipient['name'] ?? '';

            if ($email !== '') {
                $mail->to($email, $name);
            }
        }

        // ใช้ข้อความ HTML ถ้ามี ถ้าไม่มีให้ใช้ข้อความธรรมดาและแปลงบรรทัดใหม่เป็นแท็ก <br>
        $body = $notification->html ?? nl2br($notification->message, false);

        // พยายามส่งอีเมลและจับข้อผิดพลาดที่เกิดขึ้น
        try {
            $mail->subject($notification->subject)
                ->html($body)
                ->send();
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('notification.email.failed', [
                    'error' => $e->getMessage(),
                ]);
            }
            return false;
        }

        return true;
    }
}
