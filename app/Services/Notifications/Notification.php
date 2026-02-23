<?php
/**
 * class Notification
 * 
 * จุดประสงค์: จัดการข้อมูลการแจ้งเตือน เช่น หัวข้อ ข้อความ HTML และข้อมูลเมตาอื่นๆ
 * 
 * ตัวอย่างการใช้งาน:
 * ```php
 * $notification = new Notification('หัวข้อ', 'ข้อความ', '<p>ข้อความ HTML</p>', ['key' => 'value']);
 * ```
 *
 */

namespace App\Services\Notifications;

class Notification
{
    /**
     * หัวข้อของการแจ้งเตือน
     */
    public string $subject;

    /**
     * ข้อความของการแจ้งเตือน
     */
    public string $message;

    /**
     * ข้อความ HTML ของการแจ้งเตือน (ถ้ามี)
     */
    public ?string $html;

    /**
     * ข้อมูลเมตาของการแจ้งเตือน
     */
    public array $meta;

    /**
     * สร้างการแจ้งเตือนใหม่
     * จุดประสงค์: กำหนดค่าต่างๆ ของการแจ้งเตือน เช่น หัวข้อ ข้อความ HTML และข้อมูลเมตาอื่นๆ
     *
     * @param string $subject หัวข้อของการแจ้งเตือน
     * @param string $message ข้อความของการแจ้งเตือน
     * @param string|null $html ข้อความ HTML ของการแจ้งเตือน (ถ้ามี)
     * @param array $meta ข้อมูลเมตาของการแจ้งเตือน
     */
    public function __construct(string $subject, string $message, ?string $html = null, array $meta = [])
    {
        $this->subject = $subject;
        $this->message = $message;
        $this->html = $html;
        $this->meta = $meta;
    }
}
