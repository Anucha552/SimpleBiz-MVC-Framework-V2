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

use App\Helpers\FormHelper;
use App\Helpers\UrlHelper;

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
     * สแตกของส่วนที่กำลังจับภาพ (รองรับการซ้อน)
     */
    private array $sectionStack = [];

    /**
     * สแตกของ push สำหรับ scripts/styles
     */
    private array $stacks = [];

    /**
     * เก็บคีย์ของ push ที่ถูกเพิ่มแล้ว
     */
    private array $pushedKeys = [];

    /**
     * ชื่อ stack ปัจจุบันที่กำลังจับภาพ
     */
    private ?string $currentStack = null;

    /**
     * โหมดของ stack ปัจจุบัน (push หรือ prepend)
     */
    private string $currentStackMode = 'push';

    /**
     * สแตกของ stack ที่กำลังจับภาพ (รองรับการซ้อน)
     */
    private array $stackStack = [];

    /**
     * ข้อมูลที่ใช้ร่วมกันในทุกวิว
     */
    private static array $sharedData = [];

    /**
     * ตัวจับข้อมูลสำหรับวิว (pattern => callbacks[])
     */
    private static array $composers = [];

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
        $this->sectionStack[] = $name;
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
        if ($this->currentSection === null) {
            throw new \RuntimeException('No active section to end');
        }

        $content = ob_get_clean();
        $name = array_pop($this->sectionStack);
        if ($name === null) {
            throw new \RuntimeException('Section stack underflow');
        }

        $this->sections[$name] = $content;
        $this->currentSection = end($this->sectionStack) ?: null;
    }

    /**
     * เริ่ม push เนื้อหาเข้า stack (เช่น scripts/styles)
     * ตัวอย่างการใช้งาน:
     * ```php
     * <?php $this->push('scripts'); ?>
     * <script src="..."></script>
     * <?php $this->endPush(); ?>
     * ```
     *
     * @param string $name ชื่อ stack ที่ต้องการ push เนื้อหาเข้าไป (เช่น 'scripts', 'styles')
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function push(string $name): void
    {
        $this->currentStack = $name;
        $this->currentStackMode = 'push';
        $this->stackStack[] = $name;
        ob_start();
    }

    /**
     * เริ่ม prepend เนื้อหาเข้า stack (แทรกไว้ข้างหน้า)
     * ตัวอย่างการใช้งาน:
     * ```php
     * <?php $this->prepend('styles'); ?>
     * <link rel="stylesheet" href="...">
     * <?php $this->endPush(); ?>
     * ```
     *
     * @param string $name ชื่อ stack ที่ต้องการ prepend เนื้อหาเข้าไป (เช่น 'styles')
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function prepend(string $name): void
    {
        $this->currentStack = $name;
        $this->currentStackMode = 'prepend';
        $this->stackStack[] = $name;
        ob_start();
    }

    /**
     * เริ่ม push เนื้อหาเข้า stack แบบครั้งเดียว (ป้องกันการซ้ำ)
     * ตัวอย่างการใช้งาน:
     * ```php
     * <?php $this->pushOnce('scripts', 'app-js'); ?>
     * <script src="..."></script>
     * <?php $this->endPush(); ?>
     * ```
     *
     * @param string $name ชื่อ stack ที่ต้องการ push เนื้อหาเข้าไป (เช่น 'scripts', 'styles')
     * @param string $key คีย์ที่ใช้ตรวจสอบการซ้ำ
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function pushOnce(string $name, string $key): void
    {
        if (isset($this->pushedKeys[$name][$key])) {
            $this->currentStack = null;
            $this->currentStackMode = 'push';
            $this->stackStack[] = null;
            ob_start();
            return;
        }

        $this->pushedKeys[$name][$key] = true;
        $this->push($name);
    }

    /**
     * จบการ push เข้า stack
     * จุดประสงค์: จบการ push หรือ prepend เนื้อหาเข้า stack
     * endPush() ควรใช้กับอะไร: ไม่มีพารามิเตอร์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->endPush();
     * ```
     *
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function endPush(): void
    {
        if ($this->currentStack === null) {
            if (!empty($this->stackStack)) {
                array_pop($this->stackStack);
                ob_get_clean();
                return;
            }

            throw new \RuntimeException('No active stack to end');
        }

        $content = ob_get_clean();
        $name = array_pop($this->stackStack);
        if ($name === null) {
            throw new \RuntimeException('Stack underflow');
        }

        if (!isset($this->stacks[$name])) {
            $this->stacks[$name] = [];
        }

        if ($this->currentStackMode === 'prepend') {
            array_unshift($this->stacks[$name], $content);
        } else {
            $this->stacks[$name][] = $content;
        }

        $this->currentStackMode = 'push';
        $this->currentStack = end($this->stackStack) ?: null;
    }

    /**
     * แสดงผลเนื้อหาใน stack
     * จุดประสงค์: แสดงผลเนื้อหาใน stack ที่กำหนด
     * ตัวอย่างการใช้งาน:
     * ```php
     * <?= $this->stack('scripts') ?>
     * ```
     *
     * @param string $name ชื่อ stack ที่ต้องการแสดงผล
     * @param string $default เนื้อหาเริ่มต้นถ้าไม่มีเนื้อหาใน stack
     * @return string คืนค่าเนื้อหาใน stack หรือค่าเริ่มต้นถ้าไม่มีเนื้อหา
     */
    public function stack(string $name, string $default = ''): string
    {
        if (empty($this->stacks[$name])) {
            return $default;
        }

        return implode('', $this->stacks[$name]);
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
        if (!empty($this->sectionStack)) {
            $open = implode(', ', $this->sectionStack);
            throw new \RuntimeException("Unclosed section(s): {$open}");
        }

        if (!empty($this->stackStack)) {
            $open = implode(', ', $this->stackStack);
            throw new \RuntimeException("Unclosed stack(s): {$open}");
        }

        // แยกข้อมูลเป็นตัวแปร (ไม่ทับตัวแปรภายใน)
        $data = array_merge(self::$sharedData, $this->data);
        $data = self::applyComposers($this->view, $data, $this);
        extract($data, EXTR_SKIP);

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
     * เรนเดอร์ partial view พร้อมข้อมูลเพิ่มเติม
     * จุดประสงค์: ใช้แทรกส่วนย่อยในวิวหลัก
     * partial() ควรใช้กับอะไร: ชื่อไฟล์วิวส่วนย่อยและข้อมูลเพิ่มเติมที่จะส่งไปยังวิว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->partial('shared/header', ['title' => 'Welcome']);
     * ```
     *
     * @param string $view เส้นทางไฟล์วิวส่วนย่อย
     * @param array $data ข้อมูลเพิ่มเติมที่จะส่งไปยังวิว
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function partial(string $view, array $data = []): void
    {
        $view = self::normalizeTemplateName($view);
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new \Exception("Partial view file not found: {$view}");
        }

        $payload = array_merge(self::$sharedData, $this->data, $data);
        $payload = self::applyComposers($view, $payload, $this);
        extract($payload, EXTR_SKIP);

        require $viewFile;
    }

    /**
     * Escape ค่าเพื่อป้องกัน XSS
     * จุดประสงค์: แสดงผลข้อความที่ปลอดภัยใน HTML
     * e() ควรใช้กับอะไร: ข้อความที่ต้องการแสดงผลใน HTML
     * ตัวอย่างการใช้งาน:
     * ```php
     * <?= $this->e($name) ?>
     * ```
     *
     * @param string $value ข้อความที่ต้องการแสดงผลใน HTML
     * @return string คืนค่าข้อความที่ถูก escape แล้ว
     */
    public function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * แชร์ข้อมูลให้ทุกวิว
     * จุดประสงค์: แชร์ข้อมูลให้ทุกวิวโดยไม่ต้องส่งผ่านแต่ละวิว
     * share() ควรใช้กับอะไร: ชื่อคีย์และค่าที่ต้องการแชร์ หรืออาร์เรย์ของคีย์-ค่าที่ต้องการแชร์
     * ตัวอย่างการใช้งาน:
     * ```php
     * View::share('appName', 'SimpleBiz');
     * View::share(['appName' => 'SimpleBiz', 'year' => 2026]);
     * ```
     *
     * @param string|array $key ชื่อคีย์หรืออาร์เรย์ของคีย์-ค่าที่ต้องการแชร์ให้ทุกวิว
     * @param mixed $value ค่าที่ต้องการแชร์
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public static function share($key, $value = null): void
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                self::$sharedData[(string) $k] = $v;
            }
            return;
        }

        self::$sharedData[(string) $key] = $value;
    }

    /**
     * อ่านข้อมูลที่แชร์ไว้
     * จุดประสงค์: อ่านข้อมูลที่แชร์ไว้ให้ทุกวิว
     * shared() ควรใช้กับอะไร: ชื่อคีย์ที่ต้องการอ่านข้อมูลหรือไม่ระบุเพื่ออ่านทั้งหมด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $appName = View::shared('appName');
     * $allSharedData = View::shared();
     * ```
     *
     * @param string|null $key ชื่อคีย์ที่ต้องการอ่านข้อมูลหรือไม่ระบุเพื่ออ่านทั้งหมด
     * @param mixed $default ค่าเริ่มต้นถ้าไม่มีข้อมูลในคีย์ที่ระบุ
     * @return mixed คืนค่าข้อมูลที่แชร์หรือค่าเริ่มต้นถ้าไม่มีข้อมูลในคีย์ที่ระบุ
     */
    public static function shared(?string $key = null, $default = null)
    {
        if ($key === null) {
            return self::$sharedData;
        }

        return self::$sharedData[$key] ?? $default;
    }

    /**
     * ลงทะเบียน composer สำหรับวิว
     * จุดประสงค์: ลงทะเบียนฟังก์ชัน callback ที่จะถูกเรียกเมื่อมีการเรนเดอร์วิวที่ตรงกับ pattern เพื่อเติมข้อมูลให้กับวิว
     * composer() ควรใช้กับอะไร: รูปแบบชื่อวิวที่ต้องการลงทะเบียน composer และฟังก์ชัน callback ที่จะถูกเรียกเมื่อมีการเรนเดอร์วิวที่ตรงกับ pattern
     * ตัวอย่างการใช้งาน:
     * ```php
     * View::composer('admin/*', function (View $view, array $data) {
     *     return ['section' => 'admin'];
     * });
     * ```
     *
     * @param string $pattern รูปแบบชื่อวิวที่ต้องการลงทะเบียน composer (รองรับ * และ ?)
     * @param callable $callback ฟังก์ชัน callback ที่จะถูกเรียกเมื่อมีการเรนเดอร์วิวที่ตรงกับ pattern โดยรับพารามิเตอร์เป็นอินสแตนซ์ View และข้อมูลปัจจุบันของวิว และควรคืนค่าเป็นอาร์เรย์ของข้อมูลที่จะถูกเพิ่มเข้าไปในวิว
     * @return void ไม่มีค่าที่ส่งกลับ 
     */
    public static function composer(string $pattern, callable $callback): void
    {
        if (!isset(self::$composers[$pattern])) {
            self::$composers[$pattern] = [];
        }

        self::$composers[$pattern][] = $callback;
    }

    /**
     * ใช้ composer ที่ตรงกับวิวเพื่อเติมข้อมูล
     * จุดประสงค์: เรียกใช้ฟังก์ชัน callback ของ composer ที่ตรงกับชื่อวิวเพื่อเติมข้อมูลให้กับวิวก่อนการแสดงผล
     * applyComposers() ควรใช้กับอะไร: ชื่อวิวที่ต้องการใช้ composer, ข้อมูลปัจจุบันของวิว, และอินสแตนซ์ของวิว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $data = View::applyComposers('admin/dashboard', $data, $view);
     * ```
     *
     * @param string $viewName ชื่อวิวที่ต้องการใช้ composer
     * @param array $data ข้อมูลปัจจุบันของวิว
     * @param self $view อินสแตนซ์ของวิว
     * @return array คืนค่าข้อมูลที่ถูกเติมจาก composer
     */
    private static function applyComposers(string $viewName, array $data, self $view): array
    {
        foreach (self::$composers as $pattern => $callbacks) {
            if (!self::matchesPattern($pattern, $viewName)) {
                continue;
            }

            foreach ($callbacks as $callback) {
                $result = $callback($view, $data, $viewName);
                if (is_array($result)) {
                    $data = array_merge($data, $result);
                }
            }
        }

        return $data;
    }

    /**
     * ตรวจสอบว่า pattern ตรงกับชื่อวิวหรือไม่ (รองรับ * และ ?)
     * จุดประสงค์: ตรวจสอบว่า pattern ที่กำหนดตรงกับชื่อวิวหรือไม่ โดยรองรับการใช้ wildcard * และ ? เพื่อความยืดหยุ่นในการจับคู่ชื่อวิว
     * matchesPattern() ควรใช้กับอะไร: ชื่อวิวที่ต้องการตรวจสอบกับ pattern
     * ตัวอย่างการใช้งาน:
     * ```php
     * View::matchesPattern('admin/*', 'admin/dashboard'); // คืนค่า true
     * View::matchesPattern('admin/?', 'admin/d'); // คืนค่า true
     * View::matchesPattern('admin/?', 'admin/dashboard'); // คืนค่า false
     * ```
     *
     * @param string $pattern รูปแบบชื่อวิวที่ต้องการตรวจสอบ (รองรับ * และ ?)
     * @param string $viewName ชื่อวิวที่ต้องการตรวจสอบ
     * @return bool คืนค่า true ถ้า pattern ตรงกับชื่อวิว, false ถ้าไม่ตรง
     */
    private static function matchesPattern(string $pattern, string $viewName): bool
    {
        if ($pattern === '*' || $pattern === $viewName) {
            return true;
        }

        if (strpos($pattern, '*') === false && strpos($pattern, '?') === false) {
            return false;
        }

        if (function_exists('fnmatch')) {
            return fnmatch($pattern, $viewName);
        }

        $regex = '#^' . str_replace(['\\*', '\\?'], ['.*', '.?'], preg_quote($pattern, '#')) . '$#';
        return preg_match($regex, $viewName) === 1;
    }

    /**
     * สร้าง URL ของไฟล์ asset
     * จุดประสงค์: สร้าง URL เต็มสำหรับไฟล์ asset เช่น CSS, JavaScript, รูปภาพ โดยใช้ UrlHelper::asset() เพื่อให้แน่ใจว่า URL ถูกต้องตามการตั้งค่าของแอป
     * asset() ควรใช้กับอะไร: เส้นทางของไฟล์ asset ที่ต้องการสร้าง URL
     * ตัวอย่างการใช้งาน:
     * ```php
     * <link rel="stylesheet" href="<?= $this->asset('assets/css/app.css') ?>">
     * ```
     *
     * @param string $path เส้นทางของไฟล์ asset ที่ต้องการสร้าง URL (เช่น 'assets/css/app.css')
     * @return string คืนค่า URL เต็มของไฟล์ asset
     */
    public function asset(string $path): string
    {
        return UrlHelper::asset($path);
    }

    /**
     * สร้าง URL เต็มจาก path และ query parameters
     * จุดประสงค์: สร้าง URL เต็มสำหรับเส้นทางและพารามิเตอร์ที่กำหนด โดยใช้ UrlHelper::to() เพื่อให้แน่ใจว่า URL ถูกต้องตามการตั้งค่าของแอป
     * url() ควรใช้กับอะไร: เส้นทางและพารามิเตอร์ที่ต้องการสร้าง URL
     * ตัวอย่างการใช้งาน:
     * ```php
     * <a href="<?= $this->url('products/view', ['id' => 10]) ?>">ดูสินค้า</a>
     * ```
     *
     * @param string $path เส้นทางของไฟล์หรือ route ที่ต้องการสร้าง URL (เช่น 'products/view')
     * @param array $params พารามิเตอร์ที่ต้องการแนบกับ URL
     * @return string คืนค่า URL เต็ม
     */
    public function url(string $path = '', array $params = []): string
    {
        return UrlHelper::to($path, $params);
    }

    /**
     * Alias สำหรับสร้าง URL แบบ route
     * จุดประสงค์: สร้าง URL เต็มจากเส้นทางและพารามิเตอร์ที่กำหนด โดยใช้ UrlHelper::to() เพื่อให้แน่ใจว่า URL ถูกต้องตามการตั้งค่าของแอป
     * route() ควรใช้กับอะไร: เส้นทางและพารามิเตอร์ที่ต้องการสร้าง URL
     * ตัวอย่างการใช้งาน:
     * ```php
     * <a href="<?= $this->route('products/view', ['id' => 10]) ?>">ดูสินค้า</a>
     * ```
     *
     * @param string $path เส้นทางของไฟล์หรือ route ที่ต้องการสร้าง URL (เช่น 'products/view')
     * @param array $params พารามิเตอร์ที่ต้องการแนบกับ URL
     * @return string คืนค่า URL เต็ม
     */
    public function route(string $path, array $params = []): string
    {
        return UrlHelper::to($path, $params);
    }

    /**
     * สร้าง CSRF hidden input สำหรับฟอร์ม
     * จุดประสงค์: สร้างฟิลด์ hidden input ที่มีค่า CSRF token เพื่อป้องกันการโจมตีแบบ CSRF ในฟอร์ม โดยใช้ FormHelper::csrfField() เพื่อให้แน่ใจว่า token ถูกสร้างและจัดการอย่างถูกต้อง
     * csrfField() ควรใช้กับอะไร: ไม่มีพารามิเตอร์
     * ตัวอย่างการใช้งาน:
     * ```php
     * <form method="post"><?= $this->csrfField() ?></form>
     * ```
     *
     * @return string คืนค่า HTML ของ hidden input ที่มีค่า CSRF token
     */
    public function csrfField(): string
    {
        return FormHelper::csrfField();
    }

    /**
     * สร้าง CSRF meta tag
     * จุดประสงค์: สร้าง meta tag ที่มีค่า CSRF token เพื่อให้สามารถเข้าถึง token ผ่าน JavaScript ได้ โดยใช้ FormHelper::csrfMeta() เพื่อให้แน่ใจว่า token ถูกสร้างและจัดการอย่างถูกต้อง
     * csrfMeta() ควรใช้กับอะไร: ไม่มีพารามิเตอร์
     * ตัวอย่างการใช้งาน:
     * ```php
     * <?= $this->csrfMeta() ?>
     * ```
     *
     * @return string คืนค่า HTML ของ meta tag ที่มีค่า CSRF token
     */
    public function csrfMeta(): string
    {
        return FormHelper::csrfMeta();
    }

    /**
     * ดึงข้อความแฟลชจากเซสชัน
     * จุดประสงค์: ดึงข้อความแฟลชที่ถูกตั้งค่าในเซสชันเพื่อแสดงผลในวิว โดยใช้ FormHelper::flash() เพื่อให้แน่ใจว่าข้อความแฟลชถูกจัดการอย่างถูกต้อง
     * flash() ควรใช้กับอะไร: ชื่อคีย์ของข้อความแฟลชที่ต้องการดึงและค่าเริ่มต้นถ้าไม่มีข้อความแฟลชในคีย์นั้น
     * ตัวอย่างการใช้งาน:
     * ```php
     * <?php if ($this->hasFlash('success')): ?>
     *   <div><?= $this->flash('success') ?></div>
     * <?php endif; ?>
     * ```
     *
     * @param string $key ชื่อคีย์ของข้อความแฟลชที่ต้องการดึง
     * @param mixed $default ค่าเริ่มต้นถ้าไม่มีข้อความแฟลชในคีย์นั้น
     * @return mixed คืนค่าข้อความแฟลชหรือค่าเริ่มต้น
     */
    public function flash(string $key, $default = null)
    {
        return FormHelper::flash($key, $default);
    }

    /**
     * ตรวจสอบว่ามีข้อความแฟลชหรือไม่
     * จุดประสงค์: ตรวจสอบว่ามีข้อความแฟลชที่ถูกตั้งค่าในเซสชันสำหรับคีย์ที่ระบุหรือไม่ โดยใช้ FormHelper::hasFlash() เพื่อให้แน่ใจว่าการตรวจสอบแฟลชทำได้อย่างถูกต้อง
     * hasFlash() ควรใช้กับอะไร: ชื่อคีย์ของข้อความแฟลชที่ต้องการตรวจสอบ
     * ตัวอย่างการใช้งาน:
     * ```php
     * <?php if ($this->hasFlash('error')): ?>
     *   <div><?= $this->flash('error') ?></div>
     * <?php endif; ?>
     * ```
     *
     * @param string $key ชื่อคีย์ของข้อความแฟลชที่ต้องการตรวจสอบ
     * @return bool คืนค่า true ถ้ามีข้อความแฟลชสำหรับคีย์ที่ระบุ, false ถ้าไม่มี
     */
    public function hasFlash(string $key): bool
    {
        return FormHelper::hasFlash($key);
    }

    /**
     * ดึงข้อความแฟลชทั้งหมด
     * จุดประสงค์: ดึงข้อความแฟลชทั้งหมดที่ถูกตั้งค่าในเซสชันเพื่อแสดงผลในวิว โดยใช้ FormHelper::allFlash() เพื่อให้แน่ใจว่าข้อความแฟลชถูกจัดการอย่างถูกต้อง
     * allFlash() ควรใช้กับอะไร: ไม่มีพารามิเตอร์
     * ตัวอย่างการใช้งาน:
     * ```php
     * <?php foreach ($this->allFlash() as $key => $message): ?>
     *   <div><?= $message ?></div>
     * <?php endforeach; ?>
     * ```
     *
     * @return array คืนค่าข้อความแฟลชทั้งหมดในรูปแบบอาร์เรย์ของคีย์-ข้อความแฟลช
     */
    public function allFlash(): array
    {
        return FormHelper::allFlash();
    }

    /**
     * ดึงข้อมูลเก่าในฟอร์ม (old input)
     * จุดประสงค์: ดึงข้อมูลเก่าที่ถูกส่งในฟอร์มก่อนหน้านี้เพื่อแสดงผลในฟอร์มใหม่ โดยใช้ FormHelper::old() เพื่อให้แน่ใจว่าข้อมูลเก่าถูกจัดการอย่างถูกต้องและปลอดภัย
     * old() ควรใช้กับอะไร: ชื่อฟิลด์ในฟอร์มที่ต้องการดึงข้อมูลเก่า, ค่าเริ่มต้นถ้าไม่มีข้อมูลเก่า, และตัวเลือกการ escape ข้อมูลเพื่อความปลอดภัย
     * ตัวอย่างการใช้งาน:
     * ```php
     * <input name="email" value="<?= $this->old('email') ?>">
     * ```
     *
     * @param string $key ชื่อฟิลด์ในฟอร์มที่ต้องการดึงข้อมูลเก่า
     * @param mixed $default ค่าเริ่มต้นถ้าไม่มีข้อมูลเก่า
     * @param bool $escape ตัวเลือกการ escape ข้อมูลเพื่อความปลอดภัย
     * @return string คืนค่าข้อมูลเก่าหรือค่าเริ่มต้น
     */
    public function old(string $key, $default = '', bool $escape = true): string
    {
        return FormHelper::old($key, $default, $escape);
    }

    /**
     * ดึงข้อมูลเก่าแบบดิบ (รองรับอาร์เรย์)
     * จุดประสงค์: ดึงข้อมูลเก่าที่ถูกส่งในฟอร์มก่อนหน้านี้โดยไม่ทำการ escape ข้อมูล เพื่อรองรับกรณีที่ข้อมูลเก่าเป็นอาร์เรย์หรือมีรูปแบบที่ไม่เหมาะกับการ escape โดยใช้ FormHelper::oldRaw() เพื่อให้แน่ใจว่าข้อมูลเก่าถูกจัดการอย่างถูกต้อง
     * oldRaw() ควรใช้กับอะไร: ชื่อฟิลด์ในฟอร์มที่ต้องการดึงข้อมูลเก่า, ค่าเริ่มต้นถ้าไม่มีข้อมูลเก่า
     * ตัวอย่างการใช้งาน:
     * ```php
     * <input name="options[]" value="<?= $this->oldRaw('options.0') ?>">
     * ```
     *
     * @param string|null $key ชื่อฟิลด์ในฟอร์มที่ต้องการดึงข้อมูลเก่า
     * @param mixed $default ค่าเริ่มต้นถ้าไม่มีข้อมูลเก่า
     * @return mixed คืนค่าข้อมูลเก่าหรือค่าเริ่มต้น
     */
    public function oldRaw(?string $key = null, $default = null)
    {
        return FormHelper::oldRaw($key, $default);
    }

    /**
     * ตรวจสอบว่ามีข้อมูลเก่าหรือไม่
     * จุดประสงค์: ตรวจสอบว่ามีข้อมูลเก่าที่ถูกส่งในฟอร์มก่อนหน้านี้สำหรับฟิลด์ที่ระบุหรือไม่ โดยใช้ FormHelper::hasOld() เพื่อให้แน่ใจว่าการตรวจสอบข้อมูลเก่าทำได้อย่างถูกต้อง
     * hasOld() ควรใช้กับอะไร: ชื่อฟิลด์ในฟอร์มที่ต้องการตรวจสอบ
     * ตัวอย่างการใช้งาน:
     * ```php
     * <?php if ($this->hasOld('email')): ?>
     *   <input name="email" value="<?= $this->old('email') ?>">
     * <?php endif; ?>
     * ```
     *
     * @param string $key ชื่อฟิลด์ในฟอร์มที่ต้องการตรวจสอบ
     * @return bool คืนค่า true ถ้ามีข้อมูลเก่าสำหรับฟิลด์ที่ระบุ, false ถ้าไม่มี
     */
    public function hasOld(string $key): bool
    {
        return FormHelper::hasOld($key);
    }

    /**
     * ดึงข้อผิดพลาดการตรวจสอบข้อมูล
     * จุดประสงค์: ดึงข้อความข้อผิดพลาดที่เกิดจากการตรวจสอบข้อมูลในฟอร์มเพื่อแสดงผลในวิว โดยใช้ FormHelper::errors() เพื่อให้แน่ใจว่าข้อผิดพลาดถูกจัดการอย่างถูกต้อง
     * errors() ควรใช้กับอะไร: ชื่อฟิลด์ในฟอร์มที่ต้องการดึงข้อผิดพลาดหรือไม่ระบุเพื่อดึงข้อผิดพลาดทั้งหมด
     * ตัวอย่างการใช้งาน:
     * ```php
     * <?php foreach ($this->errors('email') as $message): ?>
     *   <div><?= $message ?></div>
     * <?php endforeach; ?>
     * ```
     *
     * @param string|null $field ชื่อฟิลด์ในฟอร์มที่ต้องการดึงข้อผิดพลาดหรือไม่ระบุเพื่อดึงข้อผิดพลาดทั้งหมด
     * @return array คืนค่าข้อผิดพลาดในรูปแบบอาร์เรย์ของข้อความข้อผิดพลาดสำหรับฟิลด์ที่ระบุหรือทั้งหมดถ้าไม่ระบุฟิลด์
     */
    public function errors(?string $field = null): array
    {
        return FormHelper::errors($field);
    }

    /**
     * ตรวจสอบว่ามีข้อผิดพลาดสำหรับฟิลด์หรือไม่
     * จุดประสงค์: ตรวจสอบว่ามีข้อผิดพลาดที่เกิดจากการตรวจสอบข้อมูลในฟอร์มสำหรับฟิลด์ที่ระบุหรือไม่ โดยใช้ FormHelper::hasError() เพื่อให้แน่ใจว่าการตรวจสอบข้อผิดพลาดทำได้อย่างถูกต้อง
     * hasError() ควรใช้กับอะไร: ชื่อฟิลด์ในฟอร์มที่ต้องการตรวจสอบ
     * ตัวอย่างการใช้งาน:
     * ```php
     * <?php if ($this->hasError('email')): ?>
     *   <div><?= $this->firstError('email') ?></div>
     * <?php endif; ?>
     * ```
     *
     * @param string $field ชื่อฟิลด์ในฟอร์มที่ต้องการตรวจสอบ
     * @return bool คืนค่า true ถ้ามีข้อผิดพลาดสำหรับฟิลด์ที่ระบุ, false ถ้าไม่มี
     */
    public function hasError(string $field): bool
    {
        return FormHelper::hasError($field);
    }

    /**
     * ดึงข้อความข้อผิดพลาดแรกสำหรับฟิลด์ที่ระบุ
     * จุดประสงค์: ดึงข้อความข้อผิดพลาดแรกที่เกิดจากการตรวจสอบข้อมูลในฟอร์มสำหรับฟิลด์ที่ระบุเพื่อแสดงผลในวิว โดยใช้ FormHelper::firstError() เพื่อให้แน่ใจว่าข้อผิดพลาดถูกจัดการอย่างถูกต้อง
     * firstError() ควรใช้กับอะไร: ชื่อฟิลด์ในฟอร์มที่ต้องการดึงข้อความข้อผิดพลาดแรก, ค่าเริ่มต้นถ้าไม่มีข้อผิดพลาด, และตัวเลือกการ escape ข้อมูลเพื่อความปลอดภัย
     * ตัวอย่างการใช้งาน:
     * ```php
     * <?php if ($this->hasError('email')): ?>
     *   <div><?= $this->firstError('email') ?></div>
     * <?php endif; ?>
     * ```
     *
     * @param string $field ชื่อฟิลด์ในฟอร์มที่ต้องการดึงข้อความข้อผิดพลาดแรก
     * @param string|null $default ค่าเริ่มต้นถ้าไม่มีข้อผิดพลาด
     * @param bool $escape ตัวเลือกการ escape ข้อมูลเพื่อความปลอดภัย
     * @return string|null คืนค่าข้อความข้อผิดพลาดแรกหรือค่าเริ่มต้นถ้าไม่มีข้อผิดพลาด
     */
    public function firstError(string $field, ?string $default = null, bool $escape = true): ?string
    {
        return FormHelper::firstError($field, $default, $escape);
    }

    /**
     * คืนคลาส CSS เมื่อมีข้อผิดพลาดสำหรับฟิลด์
     * จุดประสงค์: คืนคลาส CSS ที่ระบุเมื่อมีข้อผิดพลาดสำหรับฟิลด์ที่ระบุ เพื่อใช้ในการแสดงผลฟอร์มที่มีข้อผิดพลาด โดยใช้ FormHelper::invalidClass() เพื่อให้แน่ใจว่าการตรวจสอบข้อผิดพลาดและการคืนคลาสทำได้อย่างถูกต้อง
     * invalidClass() ควรใช้กับอะไร: ชื่อฟิลด์ในฟอร์มที่ต้องการตรวจสอบข้อผิดพลาดและคลาส CSS ที่ต้องการคืนเมื่อมีข้อผิดพลาด
     * ตัวอย่างการใช้งาน:
     * ```php
     * <input name="email" class="<?= $this->invalidClass('email') ?>">
     * ```
     * 
     * @param string $field ชื่อฟิลด์ในฟอร์มที่ต้องการตรวจสอบข้อผิดพลาด
     * @param string $class คลาส CSS ที่ต้องการคืนเมื่อมีข้อผิดพลาด
     * @return string คืนค่าคลาส CSS ถ้ามีข้อผิดพลาดสำหรับฟิลด์ที่ระบุ, ค่าว่างถ้าไม่มี
     */
    public function invalidClass(string $field, string $class = 'is-invalid'): string
    {
        return FormHelper::invalidClass($field, $class);
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
