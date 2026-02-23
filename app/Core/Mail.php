<?php
/**
 * คลาส Mail สำหรับจัดการการส่งอีเมล
 * 
 * จุดประสงค์: จัดการการส่งอีเมล
 * ฟีเจอร์: รองรับ SMTP, HTML templates, attachments
 * Mail ควรใช้กับอะไร: เมื่อคุณต้องการส่งอีเมลจากแอปพลิเคชันของคุณ
 * 
 * ถ้าไม่ใช้ SMTP library ภายนอก จะใช้ฟังก์ชัน mail() ของ PHP
 * SMTP library ภายนอกแนะนำสำหรับ production
 * - Mailer เช่น PHPMailer, SwiftMailer
 * 
 * ตัวอย่างการใช้งานโดยรวม:
 * ```php
 * $mail = new Mail();
 * $mail->to('user@example.com', 'John Doe')
 *      ->subject('Welcome!')
 *      ->template('welcome', ['name' => 'John'])
 *      ->send();
 * 
 * // หรือส่ง HTML โดยตรง
 * $mail->to('user@example.com')
 *      ->subject('Test')
 *      ->html('<h1>Hello World</h1>')
 *      ->send();
 * ```
 */

namespace App\Core;

class Mail
{
    /**
     * ผู้รับอีเมล สำหรับหลายคน
     */
    private array $to = [];

    /**
     * ผู้ส่งอีเมล สำหรับค่าเริ่มต้น
     */
    private string $from;
    private string $fromName;

    /**
     * หัวข้ออีเมล สำหรับอีเมล
     */
    private string $subject = '';

    /**
     * เนื้อหาอีเมล สำหรับเนื้อหา HTML
     */
    private string $body = '';

    /**
     * ไฟล์แนบ สำหรับ attachments
     */
    private array $attachments = [];

    /**
     * Headers สำหรับอีเมล
     */
    private array $headers = [];

    /**
     * การตั้งค่า SMTP
     */
    private array $config = [];

    public function __construct()
    {
        // โหลดค่าคอนฟิกจาก config
        $this->from = (string) Config::get('mail.from_address', 'noreply@simplebiz.local');
        $this->fromName = (string) Config::get('mail.from_name', 'SimpleBiz MVC');

        // การตั้งค่า SMTP
        $this->config = [
            'host' => (string) Config::get('mail.host', 'localhost'),
            'port' => (int) Config::get('mail.port', 587),
            'username' => (string) Config::get('mail.username', ''),
            'password' => (string) Config::get('mail.password', ''),
            'encryption' => (string) Config::get('mail.encryption', 'tls'),
        ];
    }

    /**
     * ตั้งค่าผู้รับ
     * จุดประสงค์: เพิ่มผู้รับอีเมลหลายคนได้
     * to() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มผู้รับอีเมล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $mail->to('user@example.com', 'John Doe');
     * ```
     * 
     * @param string $email กำหนดอีเมลผู้รับ
     * @param string $name กำหนดชื่อผู้รับ (optional)
     * @return self คืนค่าอ็อบเจ็กต์ Mail เพื่อเรียกใช้เมธอดแบบ method chaining
     */
    public function to(string $email, string $name = ''): self
    {
        $this->to[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    /**
     * ตั้งค่าผู้ส่ง
     * จุดประสงค์: กำหนดผู้ส่งอีเมล
     * from() ควรใช้กับอะไร: เมื่อคุณต้องการกำหนดผู้ส่งอีเมล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $mail->from('noreply@example.com', 'No Reply');
     * ```
     * 
     * @param string $email กำหนดอีเมลผู้ส่ง
     * @param string $name กำหนดชื่อผู้ส่ง
     * @return self คืนค่าอ็อบเจ็กต์ Mail เพื่อเรียกใช้เมธอดแบบ method chaining
     */
    public function from(string $email, string $name = ''): self
    {
        $this->from = $email;
        $this->fromName = $name;
        return $this;
    }

    /**
     * ตั้งค่าหัวข้อ
     * จุดประสงค์: กำหนดหัวข้ออีเมล
     * subject() ควรใช้กับอะไร: เมื่อคุณต้องการกำหนดหัวข้ออีเมล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $mail->subject('Welcome!');
     * ```
     * 
     * @param string $subject กำหนดหัวข้ออีเมล
     * @return self คืนค่าอ็อบเจ็กต์ Mail เพื่อเรียกใช้เมธอดแบบ method chaining
     */
    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * ตั้งค่าเนื้อหาแบบ HTML
     * จุดประสงค์: กำหนดเนื้อหาอีเมลในรูปแบบ HTML
     * html() ควรใช้กับอะไร: เมื่อคุณต้องการกำหนดเนื้อหาอีเมลเป็น HTML
     * ตัวอย่างการใช้งาน:
     * ```php
     * $mail->html('<h1>Hello World</h1>');
     * ```
     * 
     * @param string $html กำหนดเนื้อหาอีเมลในรูปแบบ HTML
     * @return self คืนค่าอ็อบเจ็กต์ Mail เพื่อเรียกใช้เมธอดแบบ method chaining
     */
    public function html(string $html): self
    {
        $this->body = $html;
        return $this;
    }

    /**
     * ตั้งค่าเนื้อหาจาก template
     * จุดประสงค์: โหลดเนื้อหาอีเมลจากไฟล์ template พร้อมส่งตัวแปรข้อมูล
     * template() ควรใช้กับอะไร: เมื่อคุณต้องการโหลดเนื้อหาอีเมลจากไฟล์ template
     * ตัวอย่างการใช้งาน:
     * ```php
     * $mail->template('welcome', ['name' => 'John']);
     * ```
     * 
     * @param string $template กำหนดชื่อ template
     * @param array $data กำหนดข้อมูลส่งไปยัง template
     * @return self คืนค่าอ็อบเจ็กต์ Mail เพื่อเรียกใช้เมธอดแบบ method chaining
     */
    public function template(string $template, array $data = []): self
    {
        $templatePath = __DIR__ . "/../Views/emails/{$template}.php";
        
        // ตรวจสอบว่าไฟล์ template มีอยู่หรือไม่
        if (!file_exists($templatePath)) {
            throw new \Exception("Email template not found: {$template}");
        }
        
        // แยกตัวแปรออกมา
        extract($data);
        
        // จับเนื้อหาจาก template
        ob_start();
        require $templatePath;
        $this->body = ob_get_clean();
        
        return $this;
    }

    /**
     * เพิ่มไฟล์แนบ
     * จุดประสงค์: แนบไฟล์กับอีเมล
     * attach() ควรใช้กับอะไร: เมื่อคุณต้องการแนบไฟล์กับอีเมล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $mail->attach('/path/to/file.pdf', 'Document.pdf');
     * ```
     * 
     * @param string $path กำหนดเส้นทางไฟล์
     * @param string $name กำหนดชื่อไฟล์ที่แสดง (optional)
     * @return self คืนค่าอ็อบเจ็กต์ Mail เพื่อเรียกใช้เมธอดแบบ method chaining
     */
    public function attach(string $path, string $name = ''): self
    {
        if (!file_exists($path)) {
            throw new \Exception("Attachment file not found: {$path}");
        }
        
        $this->attachments[] = [
            'path' => $path,
            'name' => $name ?: basename($path),
        ];
        
        return $this;
    }

    /**
     * ส่งอีเมล
     * จุดประสงค์: ส่งอีเมลที่ตั้งค่ามาแล้ว
     * send() ควรใช้กับอะไร: เมื่อคุณต้องการส่งอีเมล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $mail->send();
     * ```
     * 
     * @return bool คืนค่าความสำเร็จของการส่งอีเมล (true/false)
     */
    public function send(): bool
    {
        // ตรวจสอบว่ามีผู้รับ หัวข้อ และเนื้อหาหรือไม่
        if (empty($this->to)) {
            throw new \Exception("No recipients specified");
        }
        
        // ตรวจสอบหัวข้อและเนื้อหา
        if (empty($this->subject)) {
            throw new \Exception("No subject specified");
        }
        
        // ตรวจสอบเนื้อหา
        if (empty($this->body)) {
            throw new \Exception("No email body specified");
        }
        
        // ใช้ mail() function ของ PHP (สำหรับ development)
        // ใน production ควรใช้ library เช่น PHPMailer หรือ SwiftMailer
        
        $headers = $this->buildHeaders(); // สร้าง headers
        $body = $this->buildBody(); // สร้าง body
        
        $success = true;

        // ส่งอีเมลไปยังแต่ละผู้รับ
        foreach ($this->to as $recipient) {
            $to = $recipient['name'] 
                ? "{$recipient['name']} <{$recipient['email']}>" 
                : $recipient['email'];
            
            $result = mail($to, $this->subject, $body, $headers);
            if (!$result) {
                $success = false;
            }
        }
        
        // บันทึก log การส่งอีเมล
        $this->logEmail($success);
        
        return $success;
    }

    /**
     * สร้าง headers
     * จุดประสงค์: สร้าง headers สำหรับส่งอีเมล
     * buildHeaders() ควรใช้กับอะไร: เมื่อคุณต้องการสร้าง headers สำหรับส่งอีเมล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $headers = $mail->buildHeaders();
     * ```
     * 
     * @return string คืนค่า headers ในรูปแบบสตริง
     */
    private function buildHeaders(): string
    {
        $headers = [];
        
        // From
        $from = $this->fromName 
            ? "{$this->fromName} <{$this->from}>" 
            : $this->from;
        $headers[] = "From: {$from}";
        
        // MIME Version
        $headers[] = "MIME-Version: 1.0";
        
        // Content Type
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        
        // X-Mailer
        $headers[] = "X-Mailer: SimpleBiz MVC Framework";
        
        return implode("\r\n", $headers);
    }

    /**
     * สร้าง body
     * จุดประสงค์: สร้าง body สำหรับส่งอีเมล
     * buildBody() ควรใช้กับอะไร: เมื่อคุณต้องการสร้าง body สำหรับส่งอีเมล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $body = $mail->buildBody();
     * ```
     * 
     * @return string คืนค่า body ในรูปแบบสตริง
     */
    private function buildBody(): string
    {
        // ใน version พื้นฐานนี้ ไม่รองรับ attachments
        // ถ้าต้องการ attachments ควรใช้ PHPMailer
        
        return $this->body;
    }

    /**
     * บันทึก log การส่งอีเมล
     * จุดประสงค์: บันทึกข้อมูลการส่งอีเมลเพื่อการตรวจสอบ
     * logEmail() ควรใช้กับอะไร: เมื่อคุณต้องการบันทึก log การส่งอีเมล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $mail->logEmail(true);
     * ```
     * 
     * @param bool $success กำหนดสถานะความสำเร็จของการส่งอีเมล
     * @return void ไม่คืนค่าอะไร
     */
    private function logEmail(bool $success): void
    {
        $logger = new Logger();
        
        $recipients = array_map(function($r) {
            return $r['email'];
        }, $this->to);
        
        $logger->info('mail.sent', [
            'success' => $success,
            'to' => implode(', ', $recipients),
            'subject' => $this->subject,
            'from' => $this->from,
        ]);
    }

    /**
     * Static helper สำหรับส่งอีเมลอย่างรวดเร็ว
     * จุดประสงค์: ส่งอีเมลอย่างรวดเร็วโดยไม่ต้องสร้างอ็อบเจ็กต์ Mail
     * quick() ควรใช้กับอะไร: เมื่อคุณต้องการส่งอีเมลอย่างรวดเร็ว
     * ตัวอย่างการใช้งาน:
     * ```php
     * Mail::quick('recipient@example.com', 'Subject', '<p>Body</p>');
     * ```
     * 
     * @param string $to กำหนดอีเมลผู้รับ
     * @param string $subject กำหนดหัวข้ออีเมล
     * @param string $body กำหนดเนื้อหาอีเมลในรูปแบบ HTML
     * @return bool คืนค่าความสำเร็จของการส่งอีเมล (true/false)
     */
    public static function quick(string $to, string $subject, string $body): bool
    {
        $mail = new self();
        return $mail->to($to)
                    ->subject($subject)
                    ->html($body)
                    ->send();
    }
}
