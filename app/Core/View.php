<?php
/**
 * app/Core/View.php
 *
 * จุดประสงค์: จัดการการแสดงผลของเทมเพลตในระบบ MVC โดยรองรับฟีเจอร์เช่น การใช้เลย์เอาต์ การจัดการส่วนต่างๆ ของหน้า (sections) และการใช้คอมโพเนนต์ รวมถึงการแคชผลลัพธ์เพื่อเพิ่มประสิทธิภาพ
 *
 * ตัวอย่างการใช้งาน:
 * ```php
 * $view = new View('home.index', ['name' => 'John']);
 * $view->layout('main')->cache(3600)->show();
 * ```
 */

namespace App\Core;

use App\Core\Cache;
use App\Core\Config;
use App\Core\Logger;
use App\Core\Session;
use RuntimeException;
use InvalidArgumentException;
use Throwable;

class View
{
    /**
     * Logger สำหรับบันทึกข้อมูลการแสดงผลของวิว เช่น การเริ่มต้นการเรนเดอร์ การใช้เลย์เอาต์ และข้อผิดพลาดที่เกิดขึ้นระหว่างการเรนเดอร์
     */
    private Logger $logger;

    /**
     * ชื่อของวิวที่กำลังถูกเรนเดอร์
     */
    private string $view;

    /**
     * ข้อมูลที่ส่งไปยังวิว
     */
    private array $data;

    /**
     * ชื่อของเลย์เอาต์ที่ใช้
     */
    private ?string $layout = null;

    /**
     * ส่วนต่างๆ ของหน้า (sections)
     */
    private array $sections = [];

    /**
     * ชื่อของ section ปัจจุบันที่กำลังถูกเรนเดอร์
     */
    private ?string $currentSection = null;

    /**
     * ส่วนต่างๆ ของหน้า (slots)
     */
    private array $slots = [];

    /**
     * ชื่อของ slot ปัจจุบันที่กำลังถูกเรนเดอร์
     */
    private ?string $currentSlot = null;

    /**
     * ข้อมูลที่แชร์ระหว่างวิวทั้งหมด
     */
    private static array $shared = [];

    /**
     * คอมโพเนนต์ที่ถูกกำหนดไว้สำหรับวิว
     */
    private static array $composers = [];

    /**
     * พาธฐานของวิว
     */
    private static string $basePath = '';
    
    /**
     * โหมด debug สำหรับแสดงข้อผิดพลาดที่ชัดเจนขึ้น
     */
    private static bool $debug = true;

    /**
     * รายการไฟล์ asset ที่ถูกอ้างในระหว่างการเรนเดอร์
     */
    private static array $assetDependencies = [];

    /**
     * ระยะเวลาในการแคชผลลัพธ์ของวิว (วินาที) หากตั้งค่าเป็น null จะไม่ใช้การแคช
     */
    private ?int $cacheTtl = null;

    /**
     * รายการไฟล์เลย์เอาต์ที่ถูกใช้ในการเรนเดอร์วิวปัจจุบัน เพื่อใช้ในการตรวจสอบการเปลี่ยนแปลงของไฟล์และการจัดการแคช
     */
    private const MAX_LAYOUT_DEPTH = 10;

    /**
     * ใช้เก็บไฟล์เลย์เอาต์ที่ถูกใช้ในการเรนเดอร์ เพื่อให้สามารถตรวจสอบการเปลี่ยนแปลงของไฟล์และจัดการแคชได้อย่างถูกต้อง
     */
    private array $usedLayoutFiles = [];

    /**
     * ใช้เก็บไฟล์วิวและคอมโพเนนต์ที่ถูกเรนเดอร์ เพื่อให้สามารถตรวจสอบการเปลี่ยนแปลงของไฟล์และจัดการแคชได้อย่างถูกต้อง
     */
    private array $usedViewFiles = [];

    /**
     * สร้างอินสแตนซ์ของวิวใหม่ โดยรับชื่อของวิวและข้อมูลที่ต้องการส่งไปยังวิว
     * จุดประสงค์: ใช้สำหรับสร้างอินสแตนซ์ของวิวใหม่ โดยรับชื่อของวิวที่ต้องการเรนเดอร์และข้อมูลที่ต้องการส่งไปยังวิวในรูปแบบของอาร์เรย์ ซึ่งจะถูกใช้ในการแสดงผลของเทมเพลต
     * ตัวอย่างการใช้งาน:
     * ```php
     * $view = new View('home.index', ['name' => 'John']);
     * $view->layout('main')->cache(3600)->show();
     * ```
     * 
     * @param string $view ชื่อของวิวที่ต้องการเรนเดอร์ (เช่น 'home.index' จะถูกแปลงเป็น 'home/index.php')
     * @param array $data ข้อมูลที่ต้องการส่งไปยังวิวในรูปแบบของอาร์เรย์ (เช่น ['name' => 'John'])
     * @throws \RuntimeException หากพาธฐานของวิวไม่ถูกต้อง
     */
    public function __construct(string $view, array $data = [])
    {
        self::setDebug(Config::get('app.debug') === true);

        $this->logger = new Logger();

        if (self::$basePath === '') {
            $path = realpath(__DIR__ . '/../Views');
            if ($path === false) {
                throw new \RuntimeException('Invalid view base path.');
            }
            self::$basePath = $path;
        }

        $this->view = self::normalizeTemplateName($view);
        $this->data = $data;
    }

    public static function setBasePath(string $path): void
    {
        $real = realpath($path);
        if ($real === false) {
            throw new \InvalidArgumentException('Invalid base path.');
        }

        self::$basePath = rtrim($real, '/');
    }

    /**
     * เปิดโหมด debug เพื่อแสดงข้อผิดพลาดที่ชัดเจนขึ้นในระหว่างการพัฒนา
     * จุดประสงค์: ใช้สำหรับเปิดโหมด debug ซึ่งจะช่วยให้แสดงข้อผิดพลาดที่ชัดเจนขึ้นในระหว่างการพัฒนา โดยจะทำให้สามารถระบุปัญหาได้ง่ายขึ้นและแก้ไขได้รวดเร็วขึ้น
     * ตัวอย่างการใช้งาน:
     * ```php
     * View::setDebug(true);
     * ```
     * 
     * @param bool $debug ค่าบูลีนที่ระบุว่าจะเปิดโหมด debug หรือไม่ (true เพื่อเปิด, false เพื่อปิด)
     */
    public static function setDebug(bool $debug): void
    {
        self::$debug = $debug;
    }

    /**
     * ตรวจสอบว่าโหมด debug เปิดอยู่หรือไม่
     * จุดประสงค์: ใช้สำหรับตรวจสอบว่าโหมด debug เปิดอยู่หรือไม่ ซึ่งจะช่วยให้สามารถปรับพฤติกรรมของการแสดงผลหรือการจัดการข้อผิดพลาดได้ตามสถานะของโหมด debug
     * ตัวอย่างการใช้งาน:
     * ```php
     * if (View::isDebug()) {
     *     // ทำบางอย่างเมื่อโหมด debug เปิดอยู่
     * }
     * ```
     *
     * @return bool คืนค่าบูลีนที่ระบุว่าโหมด debug เปิดอยู่หรือไม่ (true หากเปิด, false หากปิด)
     */
    public static function isDebug(): bool
    {
        return self::$debug;
    }

    /**
     * ตั้งค่าเลย์เอาต์สำหรับวิวปัจจุบัน
     * จุดประสงค์: ใช้สำหรับตั้งค่าเลย์เอาต์ที่ต้องการใช้สำหรับวิวปัจจุบัน โดยเลย์เอาต์จะเป็นเทมเพลตหลักที่ใช้ในการแสดงผลของวิว ซึ่งสามารถมีส่วนต่างๆ ที่ถูกกำหนดไว้ในวิวเพื่อให้แสดงผลในตำแหน่งที่ต้องการในเลย์เอาต์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $view->layout('main');
     * ```
     * 
     * @param string $layout ชื่อของเลย์เอาต์ที่ต้องการใช้ (เช่น 'main' จะถูกแปลงเป็น 'layouts/main.php')
     * @return self คืนค่าอินสแตนซ์ของวิวเพื่อให้สามารถเรียกใช้เมธอดอื่นๆ ต่อได้ (เช่น $view->layout('main')->cache(3600))
     */
    public function layout(string $layout): self
    {
        $layout = str_replace('\\', '/', trim($layout));
        $layout = preg_replace('#^layouts/#', '', $layout);
        $layout = preg_replace('#\.php$#', '', $layout);

        $this->layout = self::normalizeTemplateName($layout);
        return $this;
    }

    /**
     * ตั้งค่าระยะเวลาในการแคชผลลัพธ์ของวิว
     * จุดประสงค์: ใช้สำหรับตั้งค่าระยะเวลาในการแคชผลลัพธ์ของวิวในหน่วยวินาที โดยหากตั้งค่าเป็น null จะไม่ใช้การแคช ซึ่งจะช่วยเพิ่มประสิทธิภาพในการแสดงผลของวิวโดยการเก็บผลลัพธ์ที่ได้จากการเรนเดอร์ไว้ในแคชและนำมาใช้ในการแสดงผลครั้งถัดไปแทนการเรนเดอร์ใหม่ทุกครั้ง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $view->cache(3600); // แคชผลลัพธ์เป็นเวลา 1 ชั่วโมง
     * ```
     * 
     * @param int $seconds ระยะเวลาในการแคชผลลัพธ์ของวิวในหน่วยวินาที (เช่น 3600 สำหรับ 1 ชั่วโมง) หากตั้งค่าเป็น 0 หรือค่าลบจะไม่ใช้การแคช
     * @return self คืนค่าอินสแตนซ์ของวิวเพื่อให้สามารถเรียกใช้เมธอดอื่นๆ ต่อได้ (เช่น $view->layout('main')->cache(3600))
     */
    public function cache(int $seconds): self
    {
        $this->cacheTtl = $seconds > 0 ? $seconds : null;
        return $this;
    }

    /**
     * เริ่มต้นการเรนเดอร์ส่วนต่างๆ ของหน้า (sections)
     * จุดประสงค์: ใช้สำหรับเริ่มต้นการเรนเดอร์ส่วนต่างๆ ของหน้า (sections) โดยจะรับชื่อของ section ที่ต้องการเรนเดอร์และเริ่มต้นบัฟเฟอร์เพื่อเก็บเนื้อหาที่จะถูกแสดงใน section นั้น ซึ่งจะช่วยให้สามารถจัดการกับส่วนต่างๆ ของหน้าได้อย่างยืดหยุ่นและสามารถนำไปใช้ในเลย์เอาต์หรือคอมโพเนนต์ได้ตามต้องการ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $view->section('header');
     * * // เนื้อหาของส่วน header จะถูกเก็บไว้ในบัฟเฟอร์
     * $view->endSection();
     * * // ในเลย์เอาต์หรือคอมโพเนนต์สามารถใช้ $view->yield('header') เพื่อแสดงเนื้อหาของส่วน header ได้
     * ```
     * 
     * @param string $name ชื่อของ section ที่ต้องการเรนเดอร์ (เช่น 'header', 'content', 'footer')
     * @return void ไม่มีการคืนค่า แต่จะเริ่มต้นบัฟเฟอร์เพื่อเก็บเนื้อหาของ section ที่กำหนดไว้
     */
    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * สิ้นสุดการเรนเดอร์ส่วนต่างๆ ของหน้า (sections)
     * จุดประสงค์: ใช้สำหรับสิ้นสุดการเรนเดอร์ส่วนต่างๆ ของหน้า (sections) โดยจะตรวจสอบว่ามี section ที่กำลังถูกเรนเดอร์อยู่หรือไม่ และหากมี จะทำการเก็บเนื้อหาที่ถูกบัฟเฟอร์ไว้ในอาร์เรย์ของ sections โดยใช้ชื่อของ section เป็นคีย์ ซึ่งจะช่วยให้สามารถนำเนื้อหาของ section นั้นไปใช้ในเลย์เอาต์หรือคอมโพเนนต์ได้ตามต้องการ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $view->section('header');
     * // เนื้อหาของส่วน header จะถูกเก็บไว้ในบัฟเฟอร์
     * $view->endSection();
     * // ในเลย์เอาต์หรือคอมโพเนนต์สามารถใช้ $view->yield('header') เพื่อแสดงเนื้อหาของส่วน header ได้
     * ```
     * 
     * @return void ไม่มีการคืนค่า แต่จะสิ้นสุดบัฟเฟอร์และเก็บเนื้อหาของ section ที่กำหนดไว้ในอาร์เรย์ของ sections
     * @throws \RuntimeException หากไม่มี section ที่กำลังถูกเรนเดอร์อยู่ในขณะเรียกใช้เมธอดนี้
     * @return void ไม่มีการคืนค่า แต่จะสิ้นสุดบัฟเฟอร์และเก็บเนื้อหาของ section ที่กำหนดไว้ในอาร์เรย์ของ sections
     */
    public function endSection(): void
    {
        if ($this->currentSection !== null) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = null;
        }
    }

    /**
     * แสดงเนื้อหาของส่วนต่างๆ ของหน้า (sections) ที่ถูกกำหนดไว้
     * จุดประสงค์: ใช้สำหรับแสดงเนื้อหาของส่วนต่างๆ ของหน้า (sections) ที่ถูกกำหนดไว้ โดยจะรับชื่อของ section ที่ต้องการแสดงและค่าดีฟอลต์ที่จะแสดงหากไม่มีเนื้อหาของ section นั้น ซึ่งจะช่วยให้สามารถนำเนื้อหาของ section ไปใช้ในเลย์เอาต์หรือคอมโพเนนต์ได้ตามต้องการ
     * ตัวอย่างการใช้งาน:
     * ```php
     * // ในเลย์เอาต์หรือคอมโพเนนต์สามารถใช้ $view->yield('header') เพื่อแสดงเนื้อหาของส่วน header ได้
     * echo $view->yield('header', 'Default Header');
     * ```
     * 
     * @param string $name ชื่อของ section ที่ต้องการแสดง (เช่น 'header', 'content', 'footer')
     * @param string $default ค่าดีฟอลต์ที่จะแสดงหากไม่มีเนื้อหาของ section นั้น (เช่น 'Default Header')
     * @return string คืนค่าเนื้อหาของ section ที่ถูกกำหนดไว้ หรือค่าดีฟอลต์หากไม่มีเนื้อหา
     */
    public function yield(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * เปิดโหมดการเรนเดอร์ส่วนต่างๆ ของหน้า (slots)
     * จุดประสงค์: ใช้สำหรับเปิดโหมดการเรนเดอร์ส่วนต่างๆ ของหน้า (slots) โดยจะรับชื่อของ slot ที่ต้องการเรนเดอร์และเริ่มต้นบัฟเฟอร์เพื่อเก็บเนื้อหาที่จะถูกแสดงใน slot นั้น ซึ่งจะช่วยให้สามารถจัดการกับส่วนต่างๆ ของหน้าได้อย่างยืดหยุ่นและสามารถนำไปใช้ในเลย์เอาต์หรือคอมโพเนนต์ได้ตามต้องการ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $view->slot('sidebar');
     * // เนื้อหาของส่วน sidebar จะถูกเก็บไว้ในบัฟเฟอร์
     * $view->endSlot();
     * 
     * // ในเลย์เอาต์หรือคอมโพเนนต์สามารถใช้ $view->renderSlot('sidebar') เพื่อแสดงเนื้อหาของส่วน sidebar ได้
     * echo $view->renderSlot('sidebar', 'Default Sidebar');
     * ```
     * 
     * @param string $name ชื่อของ slot ที่ต้องการเรนเดอร์ (เช่น 'sidebar', 'footer')
     * @return void ไม่มีการคืนค่า แต่จะเริ่มต้นบัฟเฟอร์เพื่อเก็บเนื้อหาของ slot ที่กำหนดไว้
     */
    public function slot(string $name): void
    {
        $this->currentSlot = $name;
        ob_start();
    }

    /**
     * สิ้นสุดการเรนเดอร์ส่วนต่างๆ ของหน้า (slots)
     * จุดประสงค์: ใช้สำหรับสิ้นสุดการเรนเดอร์ส่วนต่างๆ ของหน้า (slots) โดยจะตรวจสอบว่ามี slot ที่กำลังถูกเรนเดอร์อยู่หรือไม่ และหากมี จะทำการเก็บเนื้อหาที่ถูกบัฟเฟอร์ไว้ในอาร์เรย์ของ slots โดยใช้ชื่อของ slot เป็นคีย์ ซึ่งจะช่วยให้สามารถนำเนื้อหาของ slot นั้นไปใช้ในเลย์เอาต์หรือคอมโพเนนต์ได้ตามต้องการ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $view->slot('sidebar');
     * // เนื้อหาของส่วน sidebar จะถูกเก็บไว้ในบัฟเฟอร์
     * $view->endSlot();
     * 
     * // ในเลย์เอาต์หรือคอมโพเนนต์สามารถใช้ $view->renderSlot('sidebar') เพื่อแสดงเนื้อหาของส่วน sidebar ได้
     * echo $view->renderSlot('sidebar', 'Default Sidebar');
     * ```
     * 
     * @return void ไม่มีการคืนค่า แต่จะสิ้นสุดบัฟเฟอร์และเก็บเนื้อหาของ slot ที่กำหนดไว้ในอาร์เรย์ของ slots
     * @throws \RuntimeException หากไม่มี slot ที่กำลังถูกเรนเดอร์อยู่ในขณะเรียกใช้เมธอดนี้
     */
    public function endSlot(): void
    {
        if ($this->currentSlot !== null) {
            $this->slots[$this->currentSlot] = ob_get_clean();
            $this->currentSlot = null;
        }
    }

    /**
     * แสดงเนื้อหาของส่วนต่างๆ ของหน้า (slots) ที่ถูกกำหนดไว้
     * จุดประสงค์: ใช้สำหรับแสดงเนื้อหาของส่วนต่างๆ ของหน้า (slots) ที่ถูกกำหนดไว้ โดยจะรับชื่อของ slot ที่ต้องการแสดงและค่าดีฟอลต์ที่จะแสดงหากไม่มีเนื้อหาของ slot นั้น ซึ่งจะช่วยให้สามารถนำเนื้อหาของ slot ไปใช้ในเลย์เอาต์หรือคอมโพเนนต์ได้ตามต้องการ
     * ตัวอย่างการใช้งาน:
     * ```php
     * // ในเลย์เอาต์หรือคอมโพเนนต์สามารถใช้ $view->renderSlot('sidebar') เพื่อแสดงเนื้อหาของส่วน sidebar ได้
     * echo $view->renderSlot('sidebar', 'Default Sidebar');
     * ```
     * 
     * @param string $name ชื่อของ slot ที่ต้องการแสดง (เช่น 'sidebar', 'footer')
     * @param string $default ค่าดีฟอลต์ที่จะแสดงหากไม่มีเนื้อหาของ slot นั้น (เช่น 'Default Sidebar')
     * @return string คืนค่าเนื้อหาของ slot ที่ถูกกำหนดไว้ หรือค่าดีฟอลต์หากไม่มีเนื้อหา
     */
    public function renderSlot(string $name, string $default = ''): string
    {
        return $this->slots[$name] ?? $default;
    }

    /**
     * แสดงคอมโพเนนต์ที่ถูกกำหนดไว้ โดยรับชื่อของคอมโพเนนต์และข้อมูลที่ต้องการส่งไปยังคอมโพเนนต์
     * จุดประสงค์: ใช้สำหรับแสดงคอมโพเนนต์ที่ถูกกำหนดไว้ โดยรับชื่อของคอมโพเนนต์และข้อมูลที่ต้องการส่งไปยังคอมโพเนนต์ ซึ่งจะช่วยให้สามารถนำคอมโพเนนต์ที่ถูกกำหนดไว้มาใช้ในวิวหรือเลย์เอาต์ได้อย่างง่ายดาย และสามารถส่งข้อมูลไปยังคอมโพเนนต์เพื่อให้แสดงผลตามที่ต้องการได้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $view->partial('header', ['title' => 'My Page']);
     * ```
     * 
     * @param string $view ชื่อของคอมโพเนนต์ที่ต้องการแสดง (เช่น 'header', 'footer')
     * @param array $data ข้อมูลที่ต้องการส่งไปยังคอมโพเนนต์
     * @return void ไม่มีการคืนค่า แต่จะแสดงผลลัพธ์ของคอมโพเนนต์ที่ถูกกำหนดไว้ โดยจะใช้เมธอด component() เพื่อเรนเดอร์คอมโพเนนต์และแสดงผลลัพธ์ออกมา
     */
    public function partial(string $view, array $data = []): void
    {
        echo $this->component($view, $data);
    }

    /**
     * รวมไฟล์วิวย่อยผ่านระบบ View เพื่อให้ cache track ไฟล์ได้
     * จุดประสงค์: ใช้สำหรับรวมไฟล์วิวย่อยผ่านระบบ View เพื่อให้ cache track ไฟล์ได้ โดยรับชื่อของวิวที่ต้องการรวมและข้อมูลที่ต้องการส่งไปยังวิว ซึ่งจะช่วยให้สามารถจัดการกับการแคชของไฟล์วิวได้อย่างถูกต้องและมีประสิทธิภาพมากขึ้น
     * ตัวอย่างการใช้งาน:
     * ```php
     * $view->includeView('partials.header', ['title' => 'My Page']);
     * ```
     * 
     * @param string $view ชื่อของวิวที่ต้องการรวม (เช่น 'partials.header' จะถูกแปลงเป็น 'partials/header.php')
     * @param array $data ข้อมูลที่ต้องการส่งไปยังวิว
     * @return void ไม่มีการคืนค่า แต่จะแสดงผลลัพธ์ของวิวที่ถูกกำหนดไว้ โดยจะใช้เมธอด renderViewFile() เพื่อเรนเดอร์ไฟล์วิวและแสดงผลลัพธ์ออกมา
     */
    public function includeView(string $view, array $data = []): void
    {
        $view = self::normalizeTemplateName($view);
        $data = $this->mergeData($data);
        $data = $this->applyComposers($view, $data);

        echo $this->renderViewFile($view, $data);
    }

    /**
     * เรนเดอร์คอมโพเนนต์ที่ถูกกำหนดไว้ โดยรับชื่อของคอมโพเนนต์และข้อมูลที่ต้องการส่งไปยังคอมโพเนนต์ และคืนค่าผลลัพธ์ของการเรนเดอร์คอมโพเนนต์
     * จุดประสงค์: ใช้สำหรับเรนเดอร์คอมโพเนนต์ที่ถูกกำหนดไว้ โดยรับชื่อของคอมโพเนนต์และข้อมูลที่ต้องการส่งไปยังคอมโพเนนต์ และคืนค่าผลลัพธ์ของการเรนเดอร์คอมโพเนนต์ ซึ่งจะช่วยให้สามารถนำคอมโพเนนต์ที่ถูกกำหนดไว้มาใช้ในวิวหรือเลย์เอาต์ได้อย่างง่ายดาย และสามารถส่งข้อมูลไปยังคอมโพเนนต์เพื่อให้แสดงผลตามที่ต้องการได้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $html = $view->component('header', ['title' => 'My Page']);
     * echo $html;
     * ```
     * 
     * @param string $view ชื่อของคอมโพเนนต์ที่ต้องการเรนเดอร์ (เช่น 'header', 'footer')
     * @param array $data ข้อมูลที่ต้องการส่งไปยังคอมโพเนนต์
     * @return string คืนค่าผลลัพธ์ของการเรนเดอร์คอมโพเนนต์ที่ถูกกำหนดไว้
     */
    public function component(string $view, array $data = []): string
    {
        if (self::$debug) {
            $this->logger->info('view.component.render', [
                'component' => $view
            ]);
        }

        $view = self::normalizeTemplateName($view);
        $file = self::$basePath . '/' . $view . '.php';

        if (!is_file($file)) {
            $this->handleError("Component not found: {$view}");
        }

        $data = $this->mergeData($data);
        $data = $this->applyComposers($view, $data);

        return $this->renderFile($file, $data);
    }

    /**
     * แชร์ข้อมูลระหว่างวิวทั้งหมด
     * จุดประสงค์: ใช้สำหรับแชร์ข้อมูลระหว่างวิวทั้งหมด โดยรับชื่อของคีย์และค่าที่ต้องการแชร์ ซึ่งจะช่วยให้สามารถเข้าถึงข้อมูลที่แชร์ได้จากทุกวิว
     * ตัวอย่างการใช้งาน:
     * ```php
     * View::share('siteName', 'My Website');
     * ```
     * 
     * @param string $key ชื่อของคีย์ที่ต้องการแชร์ (เช่น 'siteName', 'user')
     * @param mixed $value ค่าที่ต้องการแชร์
     * @return void ไม่มีการคืนค่า แต่จะเก็บข้อมูลที่แชร์ไว้ในตัวแปร static
     */
    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    /**
     * กำหนดคอมโพเนนต์สำหรับวิวที่ตรงกับแพทเทิร์นที่กำหนด
     * จุดประสงค์: ใช้สำหรับกำหนดคอมโพเนนต์สำหรับวิวที่ตรงกับแพทเทิร์นที่กำหนด โดยรับชื่อของวิวหรือแพทเทิร์นและ callback ที่จะถูกเรียกเมื่อมีการเรนเดอร์วิวที่ตรงกับแพทเทิร์นนั้น ซึ่งจะช่วยให้สามารถเพิ่มข้อมูลหรือปรับแต่งการแสดงผลของวิวได้ตามต้องการ
     * ตัวอย่างการใช้งาน:
     * ```php
     * View::composer('home.*', function($view, $data) {
     *     return ['sharedData' => 'This is shared data for home views'];
     * });
     * ```
     * 
     * @param string $view ชื่อของวิวหรือแพทเทิร์นที่ต้องการกำหนดคอมโพเนนต์ (เช่น 'home.index' หรือ 'home.*')
     * @param callable $callback ฟังก์ชัน callback ที่จะถูกเรียกเมื่อมีการเรนเดอร์วิวที่ตรงกับแพทเทิร์นนั้น โดยจะรับพารามิเตอร์เป็นอินสแตนซ์ของวิวและข้อมูลที่ส่งไปยังวิว และควรคืนค่าเป็นอาร์เรย์ของข้อมูลเพิ่มเติมที่จะถูกผสานเข้ากับข้อมูลของวิว
     * @return void ไม่มีการคืนค่า แต่จะเก็บ callback ไว้ในตัวแปร static เพื่อใช้ในการเรียกเมื่อมีการเรนเดอร์วิวที่ตรงกับแพทเทิร์นนั้น
     */
    public static function composer(string $view, callable $callback): void
    {
        self::$composers[$view][] = $callback;
    }

    /**
     * นำคอมโพเนนต์ที่ถูกกำหนดไว้มาใช้กับวิวที่ตรงกับแพทเทิร์นที่กำหนด โดยรับชื่อของวิวและข้อมูลที่ส่งไปยังวิว และคืนค่าเป็นอาร์เรย์ของข้อมูลที่ถูกปรับแต่งโดยคอมโพเนนต์ที่ตรงกับแพทเทิร์นนั้น
     * จุดประสงค์: ใช้สำหรับนำคอมโพเนนต์ที่ถูกกำหนดไว้มาใช้กับวิวที่ตรงกับแพทเทิร์นที่กำหนด โดยรับชื่อของวิวและข้อมูลที่ส่งไปยังวิว และคืนค่าเป็นอาร์เรย์ของข้อมูลที่ถูกปรับแต่งโดยคอมโพเนนต์ที่ตรงกับแพทเทิร์นนั้น ซึ่งจะช่วยให้สามารถเพิ่มข้อมูลหรือปรับแต่งการแสดงผลของวิวได้ตามต้องการโดยไม่ต้องแก้ไขโค้ดของวิวโดยตรง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $data = $view->applyComposers('home.index', ['name' => 'John']);
     * // $data จะมีข้อมูลที่ถูกปรับแต่งโดยคอมโพเนนต์ที่ตรงกับแพทเทิร์น 'home.*' รวมอยู่ด้วย
     * ```
     * 
     * @param string $view ชื่อของวิวที่ต้องการนำคอมโพเนนต์มาใช้ (เช่น 'home.index')
     * @param array $data ข้อมูลที่ส่งไปยังวิว
     * @return array คืนค่าเป็นอาร์เรย์ของข้อมูลที่ถูกปรับแต่งโดยคอมโพเนนต์ที่ตรงกับแพทเทิร์น
     */
    private function applyComposers(string $view, array $data): array
    {
        foreach (self::$composers as $pattern => $callbacks) {
            if ($this->matchPattern($pattern, $view)) {
                if (self::$debug) {
                    $this->logger->info('view.composer.apply', [
                        'view'    => $view,
                        'pattern' => $pattern
                    ]);
                }

                foreach ($callbacks as $callback) {
                    $extra = $callback($this, $data);
                    if (is_array($extra)) {
                        $data = array_merge($data, $extra);
                    }
                }
            }
        }
        return $data;
    }

    /**
     * ตรวจสอบว่าชื่อของวิวตรงกับแพทเทิร์นที่กำหนดหรือไม่ โดยรองรับการใช้ wildcard (*) ในแพทเทิร์น
     * จุดประสงค์: ใช้สำหรับตรวจสอบว่าชื่อของวิวตรงกับ
     * แพทเทิร์นที่กำหนดหรือไม่ โดยรองรับการใช้ wildcard (*) ในแพทเทิร์น ซึ่งจะช่วยให้สามารถกำหนดคอมโพเนนต์สำหรับกลุ่มของวิวที่มีรูปแบบชื่อที่คล้ายกันได้อย่างง่ายดาย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $matches = $view->matchPattern('home.*', 'home.index'); // คืนค่า true เพราะ 'home.index' ตรงกับแพทเทิร์น 'home.*'
     * $matches = $view->matchPattern('home.*', 'about.index'); // คืนค่า false เพราะ 'about.index' ไม่ตรงกับแพทเทิร์น 'home.*'
     * ```
     * 
     * @param string $pattern แพทเทิร์นที่ต้องการตรวจสอบ (เช่น 'home.*')
     * @param string $view ชื่อของวิวที่ต้องการตรวจสอบ (เช่น 'home.index')
     * @return bool คืนค่าบูลีนที่ระบุว่าชื่อของวิวตรงกับแพทเทิร์นที่กำหนดหรือไม่ (true หากตรง, false หากไม่ตรง)
     */
    private function matchPattern(string $pattern, string $view): bool
    {
        if ($pattern === $view) {
            return true;
        }

        if (str_contains($pattern, '*')) {
            $escaped = preg_quote($pattern, '#');
            $regex = '#^' . str_replace('\*', '.*', $escaped) . '$#';
            return (bool) preg_match($regex, $view);
        }

        return false;
    }

    /**
     * ผสานข้อมูลที่แชร์ระหว่างวิวทั้งหมดกับข้อมูลของวิวปัจจุบันและข้อมูลเพิ่มเติมที่ส่งมา โดยรับข้อมูลเพิ่มเติมในรูปแบบของอาร์เรย์และคืนค่าเป็นอาร์เรย์ของข้อมูลที่ถูกผสานกันแล้ว
     * จุดประสงค์: ใช้สำหรับผสานข้อมูลที่แชร์ระหว่างวิวทั้งหมดกับข้อมูลของวิวปัจจุบันและข้อมูลเพิ่มเติมที่ส่งมา โดยรับข้อมูลเพิ่มเติมในรูปแบบของอาร์เรย์และคืนค่าเป็นอาร์เรย์ของข้อมูลที่ถูกผสานกันแล้ว ซึ่งจะช่วยให้สามารถรวมข้อมูลจากแหล่งต่างๆ เข้าด้วยกันเพื่อใช้ในการแสดงผลของวิวได้อย่างสะดวกและยืดหยุ่น
     * ตัวอย่างการใช้งาน:
     * ```php
     * $data = $view->mergeData(['extra' => 'This is extra data']);
     * // $data จะมีข้อมูลที่ถูกแชร์ระหว่างวิวทั้งหมด ข้อมูลของวิวปัจจุบัน และข้อมูลเพิ่มเติมที่ส่งมา รวมอยู่ด้วย
     * ```
     * 
     * @param array $extra ข้อมูลเพิ่มเติมที่ต้องการผสานเข้ากับข้อมูลของวิว (เช่น ['extra' => 'This is extra data'])
     * @return array คืนค่าเป็นอาร์เรย์ของข้อมูลที่ถูกผสานกันแล้ว ซึ่งรวมข้อมูลที่แชร์ระหว่างวิวทั้งหมด ข้อมูลของวิวปัจจุบัน และข้อมูลเพิ่มเติมที่ส่งมา
     */
    private function mergeData(array $extra = []): array
    {
        return array_merge(self::$shared, $this->data, $extra);
    }

    /**
     * เรนเดอร์วิวและเลย์เอาต์ที่กำหนดไว้ โดยจะจัดการกับการแคชผลลัพธ์และการตรวจสอบการเปลี่ยนแปลงของไฟล์เพื่อให้ได้ผลลัพธ์ที่ถูกต้องและมีประสิทธิภาพ
     * จุดประสงค์: ใช้สำหรับเรนเดอร์วิวและเลย์เอาต์ที่กำหนดไว้ โดยจะจัดการกับการแคชผลลัพธ์และการตรวจสอบการเปลี่ยนแปลงของไฟล์เพื่อให้ได้ผลลัพธ์ที่ถูกต้องและมีประสิทธิภาพ ซึ่งจะช่วยให้สามารถแสดงผลของวิวได้อย่างรวดเร็วและลดภาระในการเรนเดอร์ซ้ำๆ เมื่อไม่มีการเปลี่ยนแปลงของไฟล์หรือข้อมูล
     * ตัวอย่างการใช้งาน:
     * ```php
     * $html = $view->render();
     * echo $html;
     * ```
     * 
     * @return string คืนค่าผลลัพธ์ของการเรนเดอร์วิวและเลย์เอาต์ที่กำหนดไว้ในรูปแบบของสตริง HTML
     */
    public function render(): string
    {
        // เริ่มต้นจับเวลาการเรนเดอร์เพื่อใช้ในการบันทึก log และตรวจสอบประสิทธิภาพของการเรนเดอร์
        $start = microtime(true); 

        $this->logger->info('view.render.start', ['view' => $this->view]);
        self::$assetDependencies = [];

        try {
            $data = $this->mergeData();
            $data = $this->applyComposers($this->view, $data);

            $hasFlash = $this->hasFlashMessages();

            $viewFile = self::$basePath . '/' . $this->view . '.php';
            $dataHash = md5(json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR));
            $cacheKey = 'view_' . md5($this->view . $dataHash);

            // ลบแคชเมื่อมีการเปลี่ยนแปลงของไฟล์หรือข้อมูล หรือเมื่อมีข้อความ flash เพื่อให้ได้ผลลัพธ์ที่ถูกต้องและไม่แสดงข้อมูลเก่าที่อาจจะไม่ถูกต้อง
            if ($hasFlash || $this->cacheTtl === null || self::$debug === true) {
                // หากมีข้อความ flash หรือการแคชถูกปิดหรือโหมด debug เปิดอยู่ ให้ลบแคชที่เกี่ยวข้องกับวิวนี้เพื่อให้ได้ผลลัพธ์ที่ถูกต้องและไม่แสดงข้อมูลเก่าที่อาจจะไม่ถูกต้อง
                if (Cache::has($cacheKey)) {
                    Cache::forget($cacheKey);
                }
            }

            // ตรวจสอบแคชก่อนการเรนเดอร์เพื่อให้ได้ผลลัพธ์ที่รวดเร็วขึ้นเมื่อไม่มีการเปลี่ยนแปลงของไฟล์หรือข้อมูล และไม่มีข้อความ flash ที่ต้องแสดง โดยจะตรวจสอบว่าแคชยังคงถูกต้องตามการเปลี่ยนแป
            if ($this->cacheTtl !== null && self::$debug === false && !$hasFlash) {
                $cached = Cache::get($cacheKey);

                // หากแคชยังคงถูกต้องตามการเปลี่ยนแปลงของไฟล์หรือข้อมูล และไม่มีข้อความ flash ที่ต้องแสดง ให้ใช้ผลลัพธ์จากแคชเพื่อแสดงผลลัพธ์ได้อย่างรวดเร็ว โดยไม่ต้องเรนเดอร์ใหม่
                if (is_array($cached) && isset($cached['html'], $cached['deps']) && $this->isCacheValid($cached['deps'])) {

                    // หากแคชยังคงถูกต้องตามการเปลี่ยนแปลงของไฟล์หรือข้อมูล และไม่มีข้อความ flash ที่ต้องแสดง ให้ใช้ผลลัพธ์จากแคชเพื่อแสดงผลลัพธ์ได้อย่างรวดเร็ว โดยไม่ต้องเรนเดอร์ใหม่
                    $this->resetState();
                    return $cached['html'];
                }

                // หากแคชไม่ถูกต้องตามการเปลี่ยนแปลงของไฟล์หรือข้อมูล หรือมีข้อความ flash ที่ต้องแสดง ให้ลบแคชที่เกี่ยวข้องกับวิวนี้เพื่อให้ได้ผลลัพธ์ที่ถูกต้องและไม่แสดงข้อมูลเก่าที่อาจจะไม่ถูกต้อง
                if ($cached !== null) {
                    Cache::forget($cacheKey);
                }
            }

            // เรนเดอร์วิวและเลย์เอาต์ที่กำหนดไว้ โดยจะจัดการกับการแคชผลลัพธ์และการตรวจสอบการเปลี่ยนแปลงของไฟล์เพื่อให้ได้ผลลัพธ์ที่ถูกต้องและมีประสิทธิภาพ
            $html = $this->renderViewFile($this->view, $data);
            $html = $this->renderLayoutChain($html, $data);

            // จัดการกับการแคชผลลัพธ์ของวิวและเลย์เอาต์ที่กำหนดไว้ โดยจะตรวจสอบการเปลี่ยนแปลงของไฟล์และข้อมูลเพื่อให้ได้ผลลัพธ์ที่ถูกต้องและมีประสิทธิภาพ
            if ($this->cacheTtl !== null && self::$debug === false && !$hasFlash) {
                $deps = $this->buildCacheDependencies($viewFile);
                Cache::set($cacheKey, ['html' => $html, 'deps' => $deps], $this->cacheTtl);
            }

            $duration = microtime(true) - $start;
            $this->logger->info('view.render.complete', ['view' => $this->view, 'duration' => $duration, 'layouts' => count($this->usedLayoutFiles)]);
            $this->resetState();

            $threshold = (float) Config::get('logging.view_slow_threshold', 0.5);
            
            // หากระยะเวลาในการเรนเดอร์เกินค่า threshold ที่กำหนดไว้ในคอนฟิก ให้บันทึก log ในระดับ warning เพื่อแจ้งเตือนว่าการเรนเดอร์ช้า
            if ($duration > $threshold) {
                $this->logger->warning('view.render.slow', ['view' => $this->view, 'duration' => $duration]);
            }

            return $html;
        } catch (\Throwable $e) {
            $duration = microtime(true) - $start;
            $this->logger->error('view.render.exception', ['view' => $this->view, 'error' => $e->getMessage(), 'duration' => $duration]);
            $this->resetState();
            throw $e;
        }
    }

    /**
     * เรนเดอร์เลย์เอาต์ที่กำหนดไว้ในวิว โดยจะจัดการกับการเรนเดอร์เลย์เอาต์ที่เป็นแบบซ้อนกันได้อย่างมีประสิทธิภาพและตรวจสอบความลึกของเลย์เอาต์เพื่อป้องกันการเกิดปัญหาการเรียกใช้งานแบบไม่สิ้นสุด
     * จุดประสงค์: ใช้สำหรับเรนเดอร์เลย์เอาต์ที่กำหนดไว้ในวิว โดยจะจัดการกับการเรนเดอร์เลย์เอาต์ที่เป็นแบบซ้อนกันได้อย่างมีประสิทธิภาพและตรวจสอบความลึกของเลย์เอาต์เพื่อป้องกันการเกิดปัญหาการเรียกใช้งานแบบไม่สิ้นสุด ซึ่งจะช่วยให้สามารถใช้เลย์เอาต์ที่ซ้อนกันได้อย่างปลอดภัยและมีประสิทธิภาพในการแสดงผลของวิว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $html = $view->renderLayoutChain($html, $data);
     * echo $html;
     * ```
     * 
     * @param string $content เนื้อหาที่ได้จากการเรนเดอร์วิวที่ต้องการนำไปแสดงในเลย์เอาต์
     * @param array $data ข้อมูลที่ต้องการส่งไปยังเลย์เอาต์ในระหว่างการเรนเดอร์
     * @return string คืนค่าผลลัพธ์ของการเรนเดอร์เลย์เอาต์ที่กำหนดไว้ในวิวในรูปแบบของสตริง HTML หลังจากที่ได้จัดการกับการเรนเดอร์เลย์เอาต์ที่เป็นแบบซ้อนกันและตรวจสอบความลึกของเลย์เอาต์แล้ว
     */
    private function renderLayoutChain(string $content, array $data): string
    {
        $depth = 0;

        while ($this->layout !== null) {

            if (++$depth > self::MAX_LAYOUT_DEPTH) {
                $this->logger->error('view.layout.too_deep', ['view' => $this->view, 'layout' => $this->layout, 'depth' => $depth]);
                throw new \RuntimeException('Layout nesting too deep.');
            }

            $currentLayout = $this->layout;
            $this->layout = null;

            $layoutFile = self::$basePath . '/layouts/' . $currentLayout . '.php';
            $this->usedLayoutFiles[] = $layoutFile;

            // Don't overwrite an existing `content` section defined in the view
            if (!array_key_exists('content', $this->sections) || $this->sections['content'] === '') {
                $this->sections['content'] = $content;
            }

            $data = $this->applyComposers('layouts/' . $currentLayout, $data);

            if (static::$debug) {
                $this->logger->info('view.layout.apply', [
                    'view'   => $this->view,
                    'layout' => $currentLayout,
                    'depth'  => $depth
                ]);
            }

            $content = $this->renderViewFile('layouts/' . $currentLayout, $data);
        }

        return $content;
    }

    /**
     * เรนเดอร์ไฟล์ของวิวที่กำหนดไว้ โดยจะตรวจสอบว่ามีไฟล์ของวิวที่ต้องการเรนเดอร์อยู่หรือไม่ และหากมี จะทำการเรนเดอร์ไฟล์นั้นด้วยข้อมูลที่ส่งไปยังวิว
     * จุดประสงค์: ใช้สำหรับเรนเดอร์ไฟล์ของวิวที่กำหนดไว้ โดยจะตรวจสอบว่ามีไฟล์ของวิวที่ต้องการเรนเดอร์อยู่หรือไม่ และหากมี จะทำการเรนเดอร์ไฟล์นั้นด้วยข้อมูลที่ส่งไปยังวิว ซึ่งจะช่วยให้สามารถแสดงผลของวิวได้อย่างถูกต้องและมีประสิทธิภาพ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $html = $view->renderViewFile('home.index', ['name' => 'John']);
     * echo $html;
     * ```
     * 
     * @param string $view ชื่อของวิวที่ต้องการเรนเดอร์ (เช่น 'home.index')
     * @param array $data ข้อมูลที่ต้องการส่งไปยังวิวในระหว่างการเรนเดอร์
     * @return string คืนค่าผลลัพธ์ของการเรนเดอร์ไฟล์ของวิวที่กำหนดไว้ในรูปแบบของสตริง HTML
     */
    private function renderViewFile(string $view, array $data): string
    {
        $file = self::$basePath . '/' . $view . '.php';

        if (!is_file($file)) {
            $this->handleError("View not found: {$view}");
        }

        return $this->renderFile($file, $data);
    }

    /**
     * เรนเดอร์ไฟล์ที่กำหนดไว้ด้วยข้อมูลที่ส่งไปยังวิว โดยจะจัดการกับการแยกตัวแปรและการบัฟเฟอร์เพื่อให้ได้ผลลัพธ์ที่ถูกต้องและมีประสิทธิภาพ
     * จุดประสงค์: ใช้สำหรับเรนเดอร์ไฟล์ที่กำหนดไว้ด้วยข้อมูลที่ส่งไปยังวิว โดยจะจัดการกับการแยกตัวแปรและการบัฟเฟอร์เพื่อให้ได้ผลลัพธ์ที่ถูกต้องและมีประสิทธิภาพ ซึ่งจะช่วยให้สามารถแสดงผลของวิวได้อย่างถูกต้องและมีประสิทธิภาพโดยไม่ต้องกังวลเกี่ยวกับการจัดการตัวแปรหรือบัฟเฟอร์เอง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $html = $view->renderFile('/path/to/view.php', ['name' => 'John']);
     * echo $html;
     * ```
     * 
     * @param string $file เส้นทางของไฟล์ที่ต้องการเรนเดอร์ (เช่น '/path/to/view.php')
     * @param array $data ข้อมูลที่ต้องการส่งไปยังวิวในระหว่างการเรนเดอร์
     * @return string คืนค่าผลลัพธ์ของการเรนเดอร์ไฟล์ในรูปแบบของสตริง HTML
     */
    private function renderFile(string $file, array $data): string
    {
        $this->usedViewFiles[] = $file;

        return (function () use ($file, $data) {
            try {
                extract($data, EXTR_SKIP);
                ob_start();
                require $file;
                return ob_get_clean();
            } catch (\Throwable $e) {
                $this->logger->error('view.renderfile.exception', ['file' => $file, 'error' => $e->getMessage()]);
                throw $e;
            }
        })->call($this);
    }

    /**
     * ฟังก์ชันช่วยสำหรับการแปลงค่าต่างๆ ให้เป็นรูปแบบที่ปลอดภัยสำหรับการแสดงผลใน HTML โดยจะใช้ฟังก์ชัน htmlspecialchars เพื่อป้องกันการโจมตีแบบ XSS และทำให้ค่าที่แสดงออกมาไม่ถูกตีความเป็นโค้ด HTML หรือ JavaScript
     * จุดประสงค์: ใช้สำหรับการแปลงค่าต่างๆ ให้เป็นรูปแบบที่ปลอดภัยสำหรับการแสดงผลใน HTML โดยจะใช้ฟังก์ชัน htmlspecialchars เพื่อป้องกันการโจมตีแบบ XSS และทำให้ค่าที่แสดงออกมาไม่ถูกตีความเป็นโค้ด HTML หรือ JavaScript ซึ่งจะช่วยเพิ่มความปลอดภัยในการแสดงผลของวิวและป้องกันการโจมตีจากผู้ไม่หวังดี
     * ตัวอย่างการใช้งาน:
     * ```php
     * $safeValue = $view->e('<script>alert("XSS")</script>');
     * echo $safeValue; // จะแสดงผลเป็น &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt; แทนที่จะรันโค้ด JavaScript
     * ```
     * 
     * @param mixed $value ค่าที่ต้องการแปลงให้เป็นรูปแบบที่ปลอดภัยสำหรับการแสดงผลใน HTML (สามารถเป็นสตริง, ตัวเลข, หรือค่าอื่นๆ ที่สามารถแปลงเป็นสตริงได้)
     * @return string คืนค่าที่ถูกแปลงให้เป็นรูปแบบที่ปลอดภัยสำหรับการแสดงผลใน HTML
     */
    public function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * ฟังก์ชันช่วยสำหรับการแปลงชื่อของเทมเพลตให้เป็นรูปแบบที่เหมาะสมสำหรับการค้นหาไฟล์ โดยจะทำการแทนที่เครื่องหมาย backslash (\) ด้วยเครื่องหมาย slash (/) และลบส่วนขยาย .php ออก รวมถึงตรวจสอบความถูกต้องของชื่อเทมเพลตเพื่อป้องกันการโจมตีแบบ Directory Traversal
     * จุดประสงค์: ใช้สำหรับการแปลงชื่อของเทมเพลตให้เป็นรูปแบบที่เหมาะสมสำหรับการค้นหาไฟล์ โดยจะทำการแทนที่เครื่องหมาย backslash (\) ด้วยเครื่องหมาย slash (/) และลบส่วนขยาย .php ออก รวมถึงตรวจสอบความถูกต้องของชื่อเทมเพลตเพื่อป้องกันการโจมตีแบบ Directory Traversal ซึ่งจะช่วยให้สามารถค้นหาไฟล์ของเทมเพลตได้อย่างถูกต้องและปลอดภัย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $normalized = View::normalizeTemplateName('home\\index.php');
     * echo $normalized; // จะแสดงผลเป็น 'home/index'
     * ```
     * 
     * @param string $name ชื่อของเทมเพลตที่ต้องการแปลง (เช่น 'home\\index.php')
     * @return string คืนค่าชื่อของเทมเพลตที่ถูกแปลงให้เป็นรูปแบบที่เหมาะสมสำหรับการค้นหาไฟล์ (เช่น 'home/index')
     * @throws \InvalidArgumentException หากชื่อของเทมเพลตไม่ถูกต้องหรือมีความพยายามในการโจมตีแบบ Directory Traversal
     */
    private static function normalizeTemplateName(string $name): string
    {
        $name = str_replace('\\', '/', trim($name));
        $name = preg_replace('#\.php$#', '', $name);
        $name = trim($name, '/');

        if (
            $name === '' ||
            str_contains($name, '..') ||
            str_starts_with($name, '/')
        ) {
            throw new \InvalidArgumentException('Invalid template path');
        }

        return $name;
    }

    /**
     * ฟังก์ชันช่วยสำหรับการจัดการกับข้อผิดพลาดที่เกิดขึ้นในระหว่างการเรนเดอร์วิว โดยจะทำการบันทึกข้อผิดพลาดลงในระบบล็อกและแสดงข้อความที่เหมาะสมให้กับผู้ใช้
     * จุดประสงค์: ใช้สำหรับการจัดการกับข้อผิดพลาดที่เกิดขึ้นในระหว่างการเรนเดอร์วิว โดยจะทำการบันทึกข้อผิดพลาดลงในระบบล็อกและแสดงข้อความที่เหมาะสมให้กับผู้ใช้ ซึ่งจะช่วยให้สามารถติดตามและแก้ไขปัญหาที่เกิดขึ้นในการเรนเดอร์วิวได้อย่างมีประสิทธิภาพ
     * ตัวอย่างการใช้งาน:
     * ```php
     * // หากมีข้อผิดพลาดในการเรนเดอร์วิว เช่น ไฟล์ไม่พบ จะเรียกใช้ฟังก์ชัน handleError เพื่อจัดการกับข้อผิดพลาดนั้น
     * $this->handleError("View not found: {$view}");
     * ```
     * 
     * @param string $message ข้อความของข้อผิดพลาดที่ต้องการจัดการ (เช่น "View not found: home.index")
     * @return void ไม่มีการคืนค่า แต่จะบันทึกข้อผิดพลาดลงในระบบล็อกและแสดงข้อความที่เหมาะสมให้กับผู้ใช้
     */
    private function handleError(string $message): void
    {
        $this->logger->error('view.error', ['message' => $message, 'view' => $this->view ?? null]);

        if (self::$debug) {
            throw new \RuntimeException($message);
        }

        throw new \RuntimeException('View rendering error.');
    }

    /**
     * ฟังก์ชันช่วยสำหรับการรีเซ็ตสถานะของวิวหลังจากการเรนเดอร์เสร็จสิ้นหรือเกิดข้อผิดพลาด โดยจะทำการเคลียร์ข้อมูลของ sections, slots, layout, และตัวแปรที่เกี่ยวข้องกับสถานะของวิว เพื่อให้พร้อมสำหรับการเรนเดอร์ครั้งถัดไป
     * จุดประสงค์: ใช้สำหรับการรีเซ็ตสถานะของวิวหลังจากการเรนเดอร์เสร็จสิ้นหรือเกิดข้อผิดพลาด โดยจะทำการเคลียร์ข้อมูลของ sections, slots, layout, และตัวแปรที่เกี่ยวข้องกับสถานะของวิว เพื่อให้พร้อมสำหรับการเรนเดอร์ครั้งถัดไป ซึ่งจะช่วยให้สามารถใช้ instance ของวิวเดียวกันในการเรนเดอร์หลายๆ ครั้งได้อย่างปลอดภัยและไม่เกิดปัญหาจากสถานะที่ค้างอยู่
     * ตัวอย่างการใช้งาน:
     * ```php
     * // หลังจากการเรนเดอร์เสร็จสิ้นหรือเกิดข้อผิดพลาด จะเรียกใช้ฟังก์ชัน resetState เพื่อรีเซ็ตสถานะของวิว
     * $this->resetState();
     * ```
     * 
     * @return void ไม่มีการคืนค่า แต่จะรีเซ็ตสถานะของวิวโดยการเคลียร์ข้อมูลของ sections, slots, layout, และตัวแปรที่เกี่ยวข้องกับสถานะของวิว
     */
    private function resetState(): void
    {
        $this->sections = [];
        $this->slots = [];
        $this->layout = null;
        $this->usedLayoutFiles = [];
        $this->usedViewFiles = [];
        $this->currentSection = null;
        $this->currentSlot = null;
        self::$assetDependencies = [];
    }

    /**
     * ตรวจสอบว่า cache ยังใช้ได้อยู่หรือไม่โดยดูจากการเปลี่ยนแปลงของไฟล์ที่เกี่ยวข้อง
     * จุดประสงค์: ใช้สำหรับตรวจสอบว่า cache ยังใช้ได้อยู่หรือไม่โดยดูจากการเปลี่ยนแปลงของไฟล์ที่เกี่ยวข้อง ซึ่งจะช่วยให้สามารถตัดสินใจได้ว่าควรใช้ cache ที่มีอยู่หรือควรทำการเรนเดอร์ใหม่เพื่อให้ได้ผลลัพธ์ที่ถูกต้องและเป็นปัจจุบัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $valid = $this->isCacheValid($deps);
     * if ($valid) {
     *     // ใช้ cache ที่มีอยู่
     * } else {
     *     // ทำการเรนเดอร์ใหม่
     * }
     * ```
     * 
     * @param array $deps รายการของไฟล์ที่เกี่ยวข้องกับการเรนเดอร์ที่ต้องตรวจสอบ (เช่น [{'file' => '/path/to/view.php', 'mtime' => 1234567890}, ...])
     * @return bool คืนค่าบูลีนที่ระบุว่า cache ยังใช้ได้อยู่หรือไม่ (true หากยังใช้ได้, false หากไม่ใช้ได้) โดยจะตรวจสอบว่าไฟล์ที่เกี่ยวข้องยังคงมีอยู่และไม่ได้ถูกแก้ไขตั้งแต่ครั้งที่ cache ถูกสร้างขึ้น
     */
    private function isCacheValid(array $deps): bool
    {
        foreach ($deps as $dep) {
            if (!is_array($dep) || !isset($dep['file'], $dep['mtime'])) {
                return false;
            }

            $file = $dep['file'];
            $mtime = $dep['mtime'];
            if (!is_string($file) || !is_int($mtime)) {
                return false;
            }

            if (!is_file($file) || filemtime($file) !== $mtime) {
                return false;
            }
        }

        return true;
    }

    /**
     * สร้างรายการไฟล์ที่ใช้สำหรับตรวจสอบ cache
     * จุดประสงค์: ใช้สำหรับสร้างรายการไฟล์ที่ใช้สำหรับตรวจสอบ cache โดยจะรวมไฟล์ของวิวหลัก, เลย์เอาต์ที่ถูกใช้, ไฟล์ของวิวที่ถูกใช้, และไฟล์ของ asset ที่ถูกลงทะเบียนไว้ เพื่อให้สามารถตรวจสอบการเปลี่ยนแปลงของไฟล์เหล่านี้ได้อย่างครบถ้วนและแม่นยำ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $deps = $this->buildCacheDependencies($viewFile);
     * // $deps จะเป็นรายการของไฟล์ที่ใช้สำหรับตรวจสอบ cache เช่น [{'file' => '/path/to/view.php', 'mtime' => 1234567890}, ...]
     * ```
     * 
     * @param string $viewFile เส้นทางของไฟล์ของวิวหลักที่ต้องการสร้างรายการไฟล์สำหรับตรวจสอบ cache (เช่น '/path/to/view.php')
     * @return array คืนค่ารายการของไฟล์ที่ใช้สำหรับตรวจสอบ cache ในรูปแบบของอาร์เรย์ที่มีโครงสร้างเป็น [{'file' => '/path/to/file.php', 'mtime' => 1234567890}, ...] โดยจะรวมไฟล์ของวิวหลัก, เลย์เอาต์ที่ถูกใช้, ไฟล์ของวิวที่ถูกใช้, และไฟล์ของ asset ที่ถูกลงทะเบียนไว้
     */
    private function buildCacheDependencies(string $viewFile): array
    {
        $deps = [];

        $allFiles = array_merge([$viewFile], $this->usedLayoutFiles, $this->usedViewFiles, self::$assetDependencies);
        $allFiles = array_unique($allFiles);

        foreach ($allFiles as $file) {
            $deps[] = [
                'file' => $file,
                'mtime' => is_file($file) ? filemtime($file) : 0,
            ];
        }

        return $deps;
    }

    /**
     * ลงทะเบียนไฟล์ asset เพื่อใช้ตรวจสอบ cache
     * จุดประสงค์: ใช้สำหรับลงทะเบียนไฟล์ asset เพื่อใช้ตรวจสอบ cache โดยรับเส้นทางของไฟล์ asset ที่ต้องการลงทะเบียนและเก็บไว้ในรายการของไฟล์ที่เกี่ยวข้องกับการเรนเดอร์ ซึ่งจะช่วยให้สามารถตรวจสอบการเปลี่ยนแปลงของไฟล์ asset ได้อย่างแม่นยำและรวมไว้ในการตัดสินใจว่าจะใช้ cache หรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * View::registerAssetDependency('/path/to/asset.js');
     * ```
     * 
     * @param string $filePath เส้นทางของไฟล์ asset ที่ต้องการลงทะเบียน (เช่น '/path/to/asset.js')
     * @return void ไม่มีการคืนค่า แต่จะเก็บเส้นทางของไฟล์ asset ที่ลงทะเบียนไว้ในรายการของไฟล์ที่เกี่ยวข้องกับการเรนเดอร์เพื่อใช้ในการตรวจสอบ cache
     */
    public static function registerAssetDependency(string $filePath): void
    {
        if ($filePath === '') {
            return;
        }

        self::$assetDependencies[] = $filePath;
    }

    /**
     * ตรวจสอบว่ามี flash message ใน session หรือไม่
     * จุดประสงค์: ใช้สำหรับตรวจสอบว่ามี flash message ใน session หรือไม่ ซึ่งจะช่วยให้สามารถตัดสินใจได้ว่าควรใช้ cache ที่มีอยู่หรือควรทำการเรนเดอร์ใหม่เพื่อให้ได้ผลลัพธ์ที่ถูกต้องและเป็นปัจจุบัน โดยเฉพาะเมื่อมีการใช้ flash message ที่มักจะเปลี่ยนแปลงบ่อยๆ และไม่ควรถูกแคชไว้
     * ตัวอย่างการใช้งาน:
     * ```php
     * $hasFlash = $this->hasFlashMessages();
     * if ($hasFlash) {
     *     // ทำบางอย่างเมื่อมี flash message
     * }
     * ```
     * 
     * @return bool คืนค่าบูลีนที่ระบุว่ามี flash message ใน session หรือไม่ (true หากมี flash message, false หากไม่มี) โดยจะตรวจสอบข้อมูล flash message ที่เก็บไว้ใน session และคืนค่าตามนั้น
     */
    private function hasFlashMessages(): bool
    {
        $flash = Session::getAllFlash();
        return !empty($flash);
    }

    /**
     * ฟังก์ชันช่วยสำหรับการเข้าถึงข้อมูลที่แชร์ระหว่างวิวทั้งหมด โดยรับชื่อของคีย์และค่าดีฟอลต์ที่ต้องการคืนค่า หากไม่มีข้อมูลที่แชร์สำหรับคีย์นั้น
     * จุดประสงค์: ใช้สำหรับการเข้าถึงข้อมูลที่แชร์ระหว่างวิวทั้งหมด โดยรับชื่อของคีย์และค่าดีฟอลต์ที่ต้องการคืนค่า หากไม่มีข้อมูลที่แชร์สำหรับคีย์นั้น ซึ่งจะช่วยให้สามารถเข้าถึงข้อมูลที่แชร์ได้อย่างสะดวกและมีความยืดหยุ่นในการจัดการกับข้อมูลที่ใช้ร่วมกันในหลายๆ วิว
     * ตัวอย่างการใช้งาน:
     * ```php
     * $siteName = View::shared('siteName', 'Default Site Name');
     * echo $siteName; // จะแสดงผลเป็นค่าที่ถูกแชร์สำหรับ 'siteName' หรือ 'Default Site Name' หากไม่มีการแชร์ค่าใดๆ สำหรับ 'siteName'
     * ```
     * 
     * @param string|null $key ชื่อของคีย์ที่ต้องการเข้าถึงข้อมูลที่แชร์ (เช่น 'siteName', 'user') หรือ null เพื่อคืนค่าทั้งหมดของข้อมูลที่แชร์
     * @param mixed $default ค่าดีฟอลต์ที่ต้องการคืนค่า หากไม่มีข้อมูลที่แชร์สำหรับคีย์นั้น
     * @return mixed คืนค่าข้อมูลที่แชร์สำหรับคีย์นั้น หรือค่าดีฟอลต์หากไม่มีข้อมูลที่แชร์สำหรับคีย์นั้น หรืออาร์เรย์ของข้อมูลที่แชร์ทั้งหมดหาก $key เป็น null
     */
    public static function shared(?string $key = null, mixed $default = null)
    {
        if ($key === null) {
            return self::$shared;
        }

        return self::$shared[$key] ?? $default;
    }

    /**
     * ฟังก์ชันช่วยสำหรับการแสดงผลของวิว โดยจะเรียกใช้ฟังก์ชัน render เพื่อเรนเดอร์วิวและแสดงผลลัพธ์ออกมา
     * จุดประสงค์: ใช้สำหรับการแสดงผลของวิว โดยจะเรียกใช้ฟังก์ชัน render เพื่อเรนเดอร์วิวและแสดงผลลัพธ์ออกมา ซึ่งจะช่วยให้สามารถแสดงผลของวิวได้อย่างง่ายดายโดยไม่ต้องเรียกใช้ฟังก์ชัน render และ echo ด้วยตัวเอง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $view->show();
     * ```
     * 
     * @return void ไม่มีการคืนค่า แต่จะแสดงผลลัพธ์ของการเรนเดอร์วิวออกมาโดยการเรียกใช้ฟังก์ชัน render และ echo ผลลัพธ์นั้น
     */
    public function show(): void
    {
        echo $this->render();
    }
}