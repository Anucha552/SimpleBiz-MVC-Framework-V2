<?php
/**
 * คลาสวิวสำหรับการแสดงผล HTML พร้อมรองรับเลย์เอาท์และส่วนต่างๆ  
 * 
 * จุดประสงค์: จัดการการแสดงผลวิวพร้อมรองรับเลย์เอาท์
 * View ควรใช้กับอะไร: เมื่อต้องการแสดงผล HTML จากไฟล์วิวพร้อมเลย์เอาท์
 * ฟีเจอร์: เลย์เอาท์หลัก, ส่วน, บล็อกเนื้อหา
 * 
 * คลาสนี้เป็นตัวเลือก - ตัวควบคุมสามารถแสดงผลวิวได้โดยตรง
 * ใช้สิ่งนี้เมื่อคุณต้องการฟีเจอร์ขั้นสูงเช่นเลย์เอาท์
 * 
 * ตัวอย่างการใช้งานโดยรวม:
 * ```php
 * $view = new View('home', ['name' => 'John']);
 * $view->layout('main_layout');
 * echo $view->render();
 */

namespace App\Core;

class View
{
    /**
     * ไฟล์วิวปัจจุบัน
     */
    private string $view;

    /**
     * ข้อมูลที่จะส่งไปยังวิว
     */
    private array $data;

    /**
     * ไฟล์เลย์เอาท์ (ไม่บังคับ)
     */
    private ?string $layout = null;

    /**
     * ส่วนของเนื้อหา
     */
    private array $sections = [];

    /**
     * ส่วนปัจจุบันที่กำลังจับภาพ
     */
    private ?string $currentSection = null;

    /**
     * สร้างอินสแตนซ์ View ใหม่
     * จุดประสงค์: เตรียมวิวพร้อมข้อมูลสำหรับการแสดงผล
     * View::__construct() ควรใช้กับอะไร: ชื่อไฟล์วิวและข้อมูลที่จะส่งไปยังวิว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $view = new View('home', ['name' => 'John']);
     * $view->layout('main_layout');
     * echo $view->render();
     * ```
     * 
     * @param string $view เส้นทางไฟล์วิว
     * @param array $data ข้อมูลที่จะส่งไปยังวิว
     */
    public function __construct(string $view, array $data = [])
    {
        $this->view = self::normalizeTemplateName($view);
        $this->data = $data;
    }

    /**
     * กำหนดเลย์เอาท์สำหรับวิวนี้
     * จุดประสงค์: ตั้งค่าเลย์เอาท์ที่จะใช้เมื่อแสดงผลวิว
     * layout() ควรใช้กับอะไร: ชื่อไฟล์เลย์เอาท์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $view->layout('main_layout');
     * ```
     * 
     * @param string $layout ชื่อไฟล์เลย์เอาท์
     * @return self คืนค่าอินสแตนซ์ปัจจุบันเพื่อการเชนเมธอด
     */
    public function layout(string $layout): self
    {
        // รองรับรูปแบบ: 'main', 'layouts/main', 'layouts\\main', 'main.php'
        $normalized = str_replace('\\', '/', trim($layout));
        $normalized = preg_replace('#^layouts/#', '', $normalized);
        $normalized = preg_replace('#\\.php$#', '', $normalized);

        $this->layout = self::normalizeTemplateName($normalized);
        return $this;
    }

    /**
     * Alias สำหรับ section() เพื่อให้เข้ากันได้กับเทมเพลตเดิม
     * จุดประสงค์: เริ่มการจับภาพเนื้อหาส่วน
     * start() ควรใช้กับอะไร: ชื่อส่วนที่ต้องการเริ่ม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->start('title');
     * ```
     * 
     * @param string $name ชื่อส่วน
     */
    public function start(string $name): void
    {
        $this->section($name);
    }

    /**
     * Alias สำหรับ endSection() เพื่อให้เข้ากันได้กับเทมเพลตเดิม
     * จุดประสงค์: จบการจับภาพเนื้อหาส่วน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->end();
     * ```
     * 
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function end(): void
    {
        $this->endSection();
    }

    /**
     * Alias สำหรับ yieldSection() เพื่อให้เข้ากันได้กับเทมเพลตเดิม
     * จุดประสงค์: แสดงเนื้อหาส่วนในเลย์เอาท์
     * ตัวอย่างการใช้งาน:
     * ```php
     * <?= $this->yield('title') ?>
     * ```
     * 
     * @param string $name ชื่อส่วน
     * @param string $default เนื้อหาเริ่มต้นถ้าไม่มีการตั้งค่าส่วน
     * @return string เนื้อหาส่วน
     */
    public function yield(string $name, string $default = ''): string
    {
        return $this->yieldSection($name, $default);
    }

    /**
     * เริ่มส่วน
     * จุดประสงค์: เริ่มการจับภาพเนื้อหาส่วน
     * section() ควรใช้กับอะไร: ชื่อส่วนที่ต้องการเริ่ม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->section('title');
     * ```
     * 
     * การใช้งานในไฟล์วิว:
     * <?php $this->section('title'); ?>
     * My Page Title
     * <?php $this->endSection(); ?>
     * 
     * @param string $name ชื่อส่วน
     */
    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * จบส่วนปัจจุบัน
     * จุดประสงค์: จบการจับภาพเนื้อหาส่วน
     * endSection() ควรใช้กับอะไร: ไม่มีพารามิเตอร์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->endSection();
     * ```
     * 
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function endSection(): void
    {
        if ($this->currentSection) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = null;
        }
    }

    /**
     * แสดงเนื้อหาส่วนในเลย์เอาท์
     * จุดประสงค์: แสดงเนื้อหาส่วนในเลย์เอาท์
     * yieldSection() ควรใช้กับอะไร: ชื่อส่วนและเนื้อหาเริ่มต้น (ถ้ามี)
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->yieldSection('title', 'Default Title');
     * ```
     * 
     * การใช้งานในไฟล์เลย์เอาท์:
     * <?= $this->yieldSection('title') ?>
     * 
     * @param string $name ชื่อส่วน
     * @param string $default เนื้อหาเริ่มต้นถ้าไม่มีการตั้งค่าส่วน
     * @return string เนื้อหาส่วน
     */
    public function yieldSection(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * แสดงผลวิว
     * จุดประสงค์: แสดงผลวิวที่กำหนดพร้อมเลย์เอาท์ (ถ้ามี)
     * render() ควรใช้กับอะไร: ไม่มีพารามิเตอร์
     * ตัวอย่างการใช้งาน:
     * ```php
     * echo $view->render();
     * ```
     * 
     * @return string HTML ที่แสดงผล
     */
    public function render(): string
    {
        // แยกข้อมูลเป็นตัวแปร (ไม่ทับตัวแปรภายใน)
        extract($this->data, EXTR_SKIP);

        // แสดงผลเนื้อหาวิว
        ob_start();
        $viewFile = __DIR__ . '/../Views/' . $this->view . '.php';
        
        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: {$this->view}");
        }

        require $viewFile;
        $content = ob_get_clean();

        // ถ้าไม่มีเลย์เอาท์ ให้คืนค่าเนื้อหาโดยตรง
        if (!$this->layout) {
            return $content;
        }

        // เก็บเนื้อหาในส่วน 'content'
        $this->sections['content'] = $content;

        // แสดงผลเลย์เอาท์
        ob_start();
        $layoutFile = __DIR__ . '/../Views/layouts/' . $this->layout . '.php';
        
        if (!file_exists($layoutFile)) {
            throw new \Exception("Layout file not found: {$this->layout}");
        }

        require $layoutFile;
        return ob_get_clean();
    }

    /**
     * ปรับมาตรฐานชื่อเทมเพลต
     * จุดประสงค์: ตรวจสอบและปรับมาตรฐานชื่อเทมเพลตเพื่อความปลอดภัย
     * normalizeTemplateName() ควรใช้กับอะไร: ชื่อเทมเพล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $normalized = View::normalizeTemplateName('home');
     * ```
     * 
     * @param string $name ชื่อเทมเพลต
     * @return string ชื่อเทมเพลตที่ปรับมาตรฐานแล้ว
     */
    private static function normalizeTemplateName(string $name): string
    {
        $name = str_replace('\\', '/', trim($name));
        $name = preg_replace('#\\.php$#', '', $name);
        $name = trim($name, '/');

        if ($name === '') {
            throw new \InvalidArgumentException('Template name cannot be empty');
        }

        // Deny absolute paths (unix or windows drive)
        if (str_starts_with($name, '/') || preg_match('#^[A-Za-z]:/#', $name) === 1) {
            throw new \InvalidArgumentException('Invalid template path');
        }

        // Deny traversal and odd segments
        if (str_contains($name, '..') || str_contains($name, './') || str_contains($name, '/.')) {
            throw new \InvalidArgumentException('Invalid template path');
        }

        // Allow only safe characters
        if (preg_match('#^[A-Za-z0-9_\-/]+$#', $name) !== 1) {
            throw new \InvalidArgumentException('Invalid template name');
        }

        return $name;
    }

    /**
     * แสดงผลวิวที่แสดงผลแล้ว
     * จุดประสงค์: แสดงผลวิวที่ได้เรนเดอร์แล้ว
     * show() ควรใช้กับอะไร: ไม่มีพารามิเตอร์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $view->show();
     * ```
     * 
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function show(): void
    {
        echo $this->render();
    }
}
