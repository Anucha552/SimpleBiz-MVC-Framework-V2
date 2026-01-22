<?php
/**
 * คลาสวิว
 * 
 * จุดประสงค์: จัดการการแสดงผลวิวพร้อมรองรับเลย์เอาท์
 * ฟีเจอร์: เลย์เอาท์หลัก, ส่วน, บล็อกเนื้อหา
 * 
 * คลาสนี้เป็นตัวเลือก - ตัวควบคุมสามารถแสดงผลวิวได้โดยตรง
 * ใช้สิ่งนี้เมื่อคุณต้องการฟีเจอร์ขั้นสูงเช่นเลย์เอาท์
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
     * 
     * @param string $layout ชื่อไฟล์เลย์เอาท์
     * @return self
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
     */
    public function start(string $name): void
    {
        $this->section($name);
    }

    /**
     * Alias สำหรับ endSection() เพื่อให้เข้ากันได้กับเทมเพลตเดิม
     */
    public function end(): void
    {
        $this->endSection();
    }

    /**
     * Alias สำหรับ yieldSection() เพื่อให้เข้ากันได้กับเทมเพลตเดิม
     */
    public function yield(string $name, string $default = ''): string
    {
        return $this->yieldSection($name, $default);
    }

    /**
     * เริ่มส่วน
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
     * Normalize/validate view/layout names to avoid path traversal.
     *
     * Allowed examples:
     * - welcome
     * - products/index
     * - admin/users/list
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
     */
    public function show(): void
    {
        echo $this->render();
    }
}
