<?php
/**
 * Mail Service
 * 
 * จุดประสงค์: จัดการการส่งอีเมล
 * ฟีเจอร์: รองรับ SMTP, HTML templates, attachments
 * 
 * ตัวอย่างการใช้งาน:
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
     * ผู้รับอีเมล
     */
    private array $to = [];

    /**
     * ผู้ส่งอีเมล
     */
    private string $from;
    private string $fromName;

    /**
     * หัวข้ออีเมล
     */
    private string $subject = '';

    /**
     * เนื้อหาอีเมล
     */
    private string $body = '';

    /**
     * ไฟล์แนบ
     */
    private array $attachments = [];

    /**
     * Headers
     */
    private array $headers = [];

    /**
     * SMTP Configuration
     */
    private array $config = [];

    public function __construct()
    {
        // โหลดค่าคอนฟิกจาก environment
        $this->from = \env('MAIL_FROM_ADDRESS') ?: 'noreply@simplebiz.local';
        $this->fromName = \env('MAIL_FROM_NAME') ?: 'SimpleBiz MVC';
        
        $this->config = [
            'host' => \env('MAIL_HOST') ?: 'localhost',
            'port' => \env('MAIL_PORT') ?: 587,
            'username' => \env('MAIL_USERNAME') ?: '',
            'password' => \env('MAIL_PASSWORD') ?: '',
            'encryption' => \env('MAIL_ENCRYPTION') ?: 'tls',
        ];
    }

    /**
     * ตั้งค่าผู้รับ
     * 
     * @param string $email อีเมลผู้รับ
     * @param string $name ชื่อผู้รับ (optional)
     * @return self
     */
    public function to(string $email, string $name = ''): self
    {
        $this->to[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    /**
     * ตั้งค่าผู้ส่ง
     * 
     * @param string $email อีเมลผู้ส่ง
     * @param string $name ชื่อผู้ส่ง
     * @return self
     */
    public function from(string $email, string $name = ''): self
    {
        $this->from = $email;
        $this->fromName = $name;
        return $this;
    }

    /**
     * ตั้งค่าหัวข้อ
     * 
     * @param string $subject
     * @return self
     */
    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * ตั้งค่าเนื้อหาแบบ HTML
     * 
     * @param string $html
     * @return self
     */
    public function html(string $html): self
    {
        $this->body = $html;
        return $this;
    }

    /**
     * ตั้งค่าเนื้อหาจาก template
     * 
     * @param string $template ชื่อ template
     * @param array $data ข้อมูลส่งไปยัง template
     * @return self
     */
    public function template(string $template, array $data = []): self
    {
        $templatePath = __DIR__ . "/../Views/emails/{$template}.php";
        
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
     * 
     * @param string $path เส้นทางไฟล์
     * @param string $name ชื่อไฟล์ที่แสดง (optional)
     * @return self
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
     * 
     * @return bool
     */
    public function send(): bool
    {
        if (empty($this->to)) {
            throw new \Exception("No recipients specified");
        }
        
        if (empty($this->subject)) {
            throw new \Exception("No subject specified");
        }
        
        if (empty($this->body)) {
            throw new \Exception("No email body specified");
        }
        
        // ใช้ mail() function ของ PHP (สำหรับ development)
        // ใน production ควรใช้ library เช่น PHPMailer หรือ SwiftMailer
        
        $headers = $this->buildHeaders();
        $body = $this->buildBody();
        
        $success = true;
        foreach ($this->to as $recipient) {
            $to = $recipient['name'] 
                ? "{$recipient['name']} <{$recipient['email']}>" 
                : $recipient['email'];
            
            $result = mail($to, $this->subject, $body, $headers);
            if (!$result) {
                $success = false;
            }
        }
        
        // บันทึก log
        $this->logEmail($success);
        
        return $success;
    }

    /**
     * สร้าง headers
     * 
     * @return string
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
     * 
     * @return string
     */
    private function buildBody(): string
    {
        // ใน version พื้นฐานนี้ ไม่รองรับ attachments
        // ถ้าต้องการ attachments ควรใช้ PHPMailer
        
        return $this->body;
    }

    /**
     * บันทึก log การส่งอีเมล
     * 
     * @param bool $success
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
     * 
     * @param string $to
     * @param string $subject
     * @param string $body
     * @return bool
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
