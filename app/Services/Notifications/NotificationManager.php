<?php
/**
 * class NotificationManager
 * 
 * จุดประสงค์: จัดการช่องทางการแจ้งเตือนต่างๆ และส่งการแจ้งเตือนไปยังช่องทางเหล่านั้น
 * 
 * ตัวอย่างการใช้งาน:
 * ```php
 * $notificationManager = new NotificationManager();
 * $notificationManager->registerChannel('email', new EmailNotificationChannel());
 * $notificationManager->registerChannel('sms', new SmsNotificationChannel());
 * $notificationManager->registerChannel('websocket', new WebSocketNotificationChannel());
 * ```
 *
 */

namespace App\Services\Notifications;

use App\Services\Notifications\Channels\NotificationChannelInterface;

class NotificationManager
{
    /**
     * รายการช่องทางการแจ้งเตือนที่ลงทะเบียนไว้
     */
    private array $channels = [];

    /**
     * ลงทะเบียนช่องทางการแจ้งเตือนใหม่
     * จุดประสงค์: เพิ่มช่องทางการแจ้งเตือนใหม่ให้กับระบบ เช่น อีเมล SMS หรือ WebSocket
     * ตัวอย่างการใช้งาน:
     *  ```php
     * $notificationManager->registerChannel('email', new EmailNotificationChannel());
     * $notificationManager->registerChannel('sms', new SmsNotificationChannel());
     * $notificationManager->registerChannel('websocket', new WebSocketNotificationChannel());
     * ```
     *
     * @param string $name ชื่อของช่องทางการแจ้งเตือน (เช่น 'email', 'sms', 'websocket')
     * @param NotificationChannelInterface $channel อินสแตนซ์ของช่องทางการแจ้งเตือนที่ต้องการลงทะเบียน
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function registerChannel(string $name, NotificationChannelInterface $channel): void
    {
        $this->channels[$name] = $channel;
    }

    /**
     * ตรวจสอบว่ามีช่องทางการแจ้งเตือนที่ระบุหรือไม่
     * จุดประสงค์: ใช้เพื่อตรวจสอบว่าช่องทางการแจ้งเตือนที่ต้องการใช้งานมีการลงทะเบียนไว้ในระบบหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * if ($notificationManager->hasChannel('email')) {
     *     // มีช่องทาง 'email' ลงทะเบียนไว้
     * }
     * ```
     *
     * @param string $name ชื่อของช่องทางการแจ้งเตือนที่ต้องการตรวจสอบ
     * @return bool true ถ้ามีช่องทางการแจ้งเตือนที่ระบุ, false ถ้าไม่มี
     */
    public function hasChannel(string $name): bool
    {
        return array_key_exists($name, $this->channels);
    }

    /**
     * ส่งการแจ้งเตือนไปยังช่องทางที่ระบุ
     * จุดประสงค์: ใช้เพื่อส่งการแจ้งเตือนไปยังช่องทางการแจ้งเตือนที่ต้องการ เช่น ส่งอีเมลหรือข้อความ SMS
     * ตัวอย่างการใช้งาน:
     * ```php
     * $notification = new Notification('หัวข้อ', 'ข้อความ', '<p>ข้อความ HTML</p>', ['key' => 'value']);
     * $recipients = ['user1@example.com', 'user2@example.com'];
     * $notificationManager->send('email', $notification, $recipients);
     * ```
     *
     * @param string $channel ชื่อของช่องทางการแจ้งเตือนที่ต้องการส่ง
     * @param Notification $notification อินสแตนซ์ของการแจ้งเตือนที่ต้องการส่ง
     * @param array $recipients รายการผู้รับการแจ้งเตือน
     * @return bool true ถ้าส่งการแจ้งเตือนได้สำเร็จ, false ถ้าไม่สำเร็จ
     */
    public function send(string $channel, Notification $notification, array $recipients): bool
    {
        if (!isset($this->channels[$channel])) {
            throw new \RuntimeException("Notification channel not registered: {$channel}");
        }

        return $this->channels[$channel]->send($notification, $recipients);
    }

    /**
     * ส่งการแจ้งเตือนไปยังหลายช่องทางพร้อมกัน
     * จุดประสงค์: ใช้เพื่อส่งการแจ้งเตือนไปยังหลายช่องทางการแจ้งเตือนพร้อมกัน เช่น ส่งทั้งอีเมลและ SMS ในครั้งเดียว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $notification = new Notification('หัวข้อ', 'ข้อความ', '<p>ข้อความ HTML</p>', ['key' => 'value']);
     * $recipients = ['user1@example.com', 'user2@example.com'];
     * $channels = ['email', 'sms'];
     * $notificationManager->sendMany($channels, $notification, $recipients);
     * ```
     *
     * @param array $channels รายการชื่อของช่องทางการแจ้งเตือนที่ต้องการส่ง
     * @param Notification $notification อินสแตนซ์ของการแจ้งเตือนที่ต้องการส่ง
     * @param array $recipients รายการผู้รับการแจ้งเตือน
     * @return array ผลลัพธ์การส่งการแจ้งเตือนสำหรับแต่ละช่องทาง
     */
    public function sendMany(array $channels, Notification $notification, array $recipients): array
    {
        $results = [];

        foreach ($channels as $channel) {
            $results[$channel] = $this->send($channel, $notification, $recipients);
        }

        return $results;
    }
}
