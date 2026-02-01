<?php
/**
 * คลาส Pagination สำหรับจัดการการแบ่งหน้าข้อมูล
 * 
 * จุดประสงค์: จัดการการแบ่งหน้าข้อมูล
 * Pagination() ควรใช้กับอะไร: การแสดงผลรายการข้อมูลที่มีจำนวนมาก
 * 
 * ฟีเจอร์หลัก:
 * - คำนวณจำนวนหน้า
 * - สร้าง pagination links
 * - รองรับ Bootstrap styling
 * - Customizable
 * 
 * ตัวอย่างการใช้งาน:
 * ```php
 * // คำนวณ offset จากหน้าปัจจุบัน
 * $page = $_GET['page'] ?? 1;
 * $perPage = 10;
 * $totalItems = 100;
 * 
 * $pagination = new Pagination($totalItems, $perPage, $page);
 * 
 * // ใช้กับ query
 * $offset = $pagination->getOffset();
 * $sql = "SELECT * FROM products LIMIT {$perPage} OFFSET {$offset}";
 * 
 * // แสดง pagination links
 * echo $pagination->render();
 * ```
 */

namespace App\Core;

class Pagination
{
    /**
     * จำนวนรายการทั้งหมด
     */
    private int $totalItems;

    /**
     * จำนวนรายการต่อหน้า
     */
    private int $perPage;

    /**
     * หน้าปัจจุบัน
     */
    private int $currentPage;

    /**
     * จำนวนหน้าทั้งหมด
     */
    private int $totalPages;

    /**
     * URL base สำหรับ pagination links
     */
    private string $baseUrl;

    /**
     * Query string parameters
     */
    private array $queryParams = [];

    /**
     * จำนวน page links ที่จะแสดง
     */
    private int $linksCount = 5;

    /**
     * CSS class สำหรับ pagination container
     */
    private string $containerClass = 'pagination';

    /**
     * CSS class สำหรับ active page
     */
    private string $activeClass = 'active';

    /**
     * CSS class สำหรับ disabled link
     */
    private string $disabledClass = 'disabled';

    /**
     * ข้อความสำหรับปุ่ม Previous
     */
    private string $previousText = '&laquo; ก่อนหน้า';

    /**
     * ข้อความสำหรับปุ่ม Next
     */
    private string $nextText = 'ถัดไป &raquo;';

    /**
     * สร้างอินสแตนซ์ Pagination ใหม่
     * จุดประสงค์: กำหนดค่าพื้นฐานสำหรับการแบ่งหน้า
     * ตัวอย่างการใช้งาน:
     * ```php
     * $pagination = new Pagination(100, 10, 1, '/products');
     * ```
     * 
     * @param int $totalItems กำหนดจำนวนรายการทั้งหมด
     * @param int $perPage กำหนดจำนวนรายการต่อหน้า
     * @param int $currentPage กำหนดหน้าปัจจุบัน
     * @param string|null $baseUrl กำหนด URL base (ถ้าไม่ระบุจะใช้ URL ปัจจุบัน)
     */
    public function __construct(
        int $totalItems, 
        int $perPage = 10, 
        int $currentPage = 1, 
        ?string $baseUrl = null
    ) {
        $this->totalItems = max(0, $totalItems);
        $this->perPage = max(1, $perPage);
        $this->totalPages = (int)ceil($this->totalItems / $this->perPage);
        $this->currentPage = max(1, min($currentPage, max(1, $this->totalPages)));
        
        // ใช้ URL ปัจจุบันถ้าไม่ระบุ
        if ($baseUrl === null) {
            $this->baseUrl = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        } else {
            $this->baseUrl = $baseUrl;
        }

        // รับ query parameters ปัจจุบัน
        $this->queryParams = $_GET ?? [];
        unset($this->queryParams['page']); // ลบ page parameter
    }

    /**
     * รับ offset สำหรับ SQL query
     * จุดประสงค์: คำนวณ offset สำหรับการดึงข้อมูลจากฐานข้อมูล
     * getOffset() ควรใช้กับอะไร: เมื่อคุณต้องการดึงข้อมูลสำหรับหน้าปัจจุบัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $offset = $pagination->getOffset();
     * ```
     * 
     * @return int คืนค่า offset ของหน้าปัจจุบัน
     */
    public function getOffset(): int
    {
        return ($this->currentPage - 1) * $this->perPage;
    }

    /**
     * รับจำนวนรายการต่อหน้า
     * จุดประสงค์: ดึงค่าจำนวนรายการที่แสดงต่อหน้า
     * getPerPage() ควรใช้กับอะไร: เมื่อคุณต้องการทราบจำนวนรายการต่อหน้า
     * ตัวอย่างการใช้งาน:
     * ```php
     * $perPage = $pagination->getPerPage();
     * ```
     * 
     * @return int คืนค่าจำนวนรายการต่อหน้า
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * รับหน้าปัจจุบัน
     * จุดประสงค์: ดึงหมายเลขหน้าปัจจุบัน
     * getCurrentPage() ควรใช้กับอะไร: เมื่อคุณต้องการทราบหมายเลขหน้าปัจจุบัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $currentPage = $pagination->getCurrentPage();
     * ```
     * 
     * @return int คืนค่าหมายเลขหน้าปัจจุบัน
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * รับจำนวนหน้าทั้งหมด
     * จุดประสงค์: ดึงค่าจำนวนหน้าทั้งหมด
     * getTotalPages() ควรใช้กับอะไร: เมื่อคุณต้องการทราบจำนวนหน้าทั้งหมด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $totalPages = $pagination->getTotalPages();
     * ```
     * 
     * @return int คืนค่าจำนวนหน้าทั้งหมด
     */
    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    /**
     * รับจำนวนรายการทั้งหมด
     * จุดประสงค์: ดึงค่าจำนวนรายการทั้งหมด
     * getTotalItems() ควรใช้กับอะไร: เมื่อคุณต้องการทราบจำนวนรายการทั้งหมด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $totalItems = $pagination->getTotalItems();
     * ```
     * 
     * @return int คืนค่าจำนวนรายการทั้งหมด
     */
    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    /**
     * ตรวจสอบว่ามีหน้าก่อนหน้าหรือไม่
     * จุดประสงค์: ตรวจสอบว่ามีหน้าก่อนหน้าหรือไม่
     * hasPrevious() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่ามีหน้าก่อนหน้าหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $hasPrevious = $pagination->hasPrevious();
     * ```
     * 
     * @return bool
     */
    public function hasPrevious(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * ตรวจสอบว่ามีหน้าถัดไปหรือไม่
     * จุดประสงค์: ตรวจสอบว่ามีหน้าถัดไปหรือไม่
     * hasNext() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่ามีหน้าถัดไปหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $hasNext = $pagination->hasNext();
     * ```
     * 
     * @return bool คืนค่าความเป็นไปได้ว่ามีหน้าถัดไปหรือไม่
     */
    public function hasNext(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    /**
     * รับหมายเลขหน้าก่อนหน้า
     * จุดประสงค์: ดึงหมายเลขหน้าก่อนหน้า
     * getPreviousPage() ควรใช้กับอะไร: เมื่อคุณต้องการดึงหมายเลขหน้าก่อนหน้า
     * ตัวอย่างการใช้งาน:
     * ```php
     * $previousPage = $pagination->getPreviousPage();
     * ```
     * 
     * @return int คืนค่าหมายเลขหน้าก่อนหน้า
     */
    public function getPreviousPage(): int
    {
        return max(1, $this->currentPage - 1);
    }

    /**
     * รับหมายเลขหน้าถัดไป
     * จุดประสงค์: ดึงหมายเลขหน้าถัดไป
     * getNextPage() ควรใช้กับอะไร: เมื่อคุณต้องการดึงหมายเลขหน้าถัดไป
     * ตัวอย่างการใช้งาน:
     * ```php
     * $nextPage = $pagination->getNextPage();
     * ```
     * 
     * @return int
     */
    public function getNextPage(): int
    {
        return min($this->totalPages, $this->currentPage + 1);
    }

    /**
     * ตั้งค่าจำนวน page links ที่จะแสดง
     * จุดประสงค์: กำหนดจำนวนลิงก์หน้าที่จะแสดงใน pagination
     * setLinksCount() ควรใช้กับอะไร: เมื่อคุณต้องการกำหนดจำนวนลิงก์หน้าที่จะแสดง
     * ตัวอย่างการใช้งาน:
     * ```php
     * $pagination->setLinksCount(5);
     * ```
     * 
     * @param int $count กำหนดจำนวนลิงก์หน้าที่จะแสดง
     * @return self คืนค่าอินสแตนซ์ของคลาสปัจจุบัน
     */
    public function setLinksCount(int $count): self
    {
        $this->linksCount = max(1, $count);
        return $this;
    }

    /**
     * ตั้งค่า CSS classes
     * จุดประสงค์: กำหนด CSS classes สำหรับ container, active, และ disabled states
     * setClasses() ควรใช้กับอะไร: เมื่อคุณต้องการกำหนด CSS classes สำหรับการแสดงผลข้อมูลแบบแบ่งหน้า
     * ตัวอย่างการใช้งาน:
     * ```php
     * $pagination->setClasses('pagination', 'active', 'disabled');
     * ```
     * 
     * @param string $container กำหนด CSS class สำหรับ container
     * @param string $active กำหนด CSS class สำหรับ active state
     * @param string $disabled กำหนด CSS class สำหรับ disabled state
     * @return self คืนค่าอินสแตนซ์ของคลาสปัจจุบัน
     */
    public function setClasses(string $container, string $active, string $disabled): self
    {
        $this->containerClass = $container;
        $this->activeClass = $active;
        $this->disabledClass = $disabled;
        return $this;
    }

    /**
     * ตั้งค่าข้อความปุ่ม
     * จุดประสงค์: กำหนดข้อความสำหรับปุ่ม Previous และ Next
     * setButtonTexts() ควรใช้กับอะไร: เมื่อคุณต้องการกำหนดข้อความสำหรับปุ่ม Previous และ Next
     * ตัวอย่างการใช้งาน:
     * ```php
     * $pagination->setButtonTexts('Previous', 'Next');
     * ```
     * 
     * @param string $previous กำหนดข้อความสำหรับปุ่ม Previous
     * @param string $next กำหนดข้อความสำหรับปุ่ม Next
     * @return self คืนค่าอินสแตนซ์ของคลาสปัจจุบัน
     */
    public function setButtonTexts(string $previous, string $next): self
    {
        $this->previousText = $previous;
        $this->nextText = $next;
        return $this;
    }

    /**
     * สร้าง URL สำหรับหน้าที่ระบุ
     * จุดประสงค์: สร้าง URL สำหรับหน้าที่ระบุ
     * getUrl() ควรใช้กับอะไร: เมื่อคุณต้องการสร้าง URL สำหรับหน้าที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $url = $pagination->getUrl(2);
     * ```
     * 
     * @param int $page กำหนดหมายเลขหน้าที่ต้องการสร้าง URL
     * @return string คืนค่า URL สำหรับหน้าที่ระบุ
     */
    public function getUrl(int $page): string
    {
        $params = array_merge($this->queryParams, ['page' => $page]);
        $queryString = http_build_query($params);

        return $this->baseUrl . ($queryString ? '?' . $queryString : '');
    }

    /**
     * สร้าง pagination HTML (Bootstrap style)
     * จุดประสงค์: สร้าง HTML สำหรับการแบ่งหน้าแบบ Bootstrap
     * render() ควรใช้กับอะไร: เมื่อคุณต้องการแสดงผลข้อมูลแบบแบ่งหน้า
     * ตัวอย่างการใช้งาน:
     * ```php
     * echo $pagination->render();
     * ```
     * 
     * @return string คืนค่า HTML สำหรับ pagination
     */
    public function render(): string
    {
        if ($this->totalPages <= 1) {
            return '';
        }

        $html = '<nav aria-label="Page navigation">';
        $html .= '<ul class="' . htmlspecialchars($this->containerClass) . '">';

        // Previous button
        $html .= $this->renderPreviousButton();

        // Page numbers
        $html .= $this->renderPageNumbers();

        // Next button
        $html .= $this->renderNextButton();

        $html .= '</ul>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * สร้างปุ่ม Previous
     * จุดประสงค์: สร้าง HTML สำหรับปุ่ม Previous
     * renderPreviousButton() ควรใช้กับอะไร: เมื่อคุณต้องการแสดงผลปุ่ม Previous ในการแบ่งหน้า
     * ตัวอย่างการใช้งาน:
     * ```php
     * echo $pagination->renderPreviousButton();
     * ```
     * 
     * @return string คืนค่า HTML สำหรับปุ่ม Previous
     */
    private function renderPreviousButton(): string
    {
        $class = 'page-item';
        if (!$this->hasPrevious()) {
            $class .= ' ' . $this->disabledClass;
        }

        $html = '<li class="' . htmlspecialchars($class) . '">';

        if ($this->hasPrevious()) {
            $url = $this->getUrl($this->getPreviousPage());
            $html .= '<a class="page-link" href="' . htmlspecialchars($url) . '">' . $this->previousText . '</a>';
        } else {
            $html .= '<span class="page-link">' . $this->previousText . '</span>';
        }

        $html .= '</li>';

        return $html;
    }

    /**
     * สร้างปุ่ม Next
     * จุดประสงค์: สร้าง HTML สำหรับปุ่ม Next
     * renderNextButton() ควรใช้กับอะไร: เมื่อคุณต้องการแสดงผลปุ่ม Next ในการแบ่งหน้า
     * ตัวอย่างการใช้งาน:
     * ```php
     * echo $pagination->renderNextButton();
     * ```
     * 
     * @return string คืนค่า HTML สำหรับปุ่ม Next
     */
    private function renderNextButton(): string
    {
        $class = 'page-item';
        if (!$this->hasNext()) {
            $class .= ' ' . $this->disabledClass;
        }

        $html = '<li class="' . htmlspecialchars($class) . '">';

        if ($this->hasNext()) {
            $url = $this->getUrl($this->getNextPage());
            $html .= '<a class="page-link" href="' . htmlspecialchars($url) . '">' . $this->nextText . '</a>';
        } else {
            $html .= '<span class="page-link">' . $this->nextText . '</span>';
        }

        $html .= '</li>';

        return $html;
    }

    /**
     * สร้างหมายเลขหน้า
     * จุดประสงค์: สร้าง HTML สำหรับหมายเลขหน้า
     * renderPageNumbers() ควรใช้กับอะไร: เมื่อคุณต้องการแสดงผลหมายเลขหน้าในการแบ่งหน้า
     * ตัวอย่างการใช้งาน:
     * ```php
     * echo $pagination->renderPageNumbers();
     * ```
     * 
     * @return string คืนค่า HTML สำหรับหมายเลขหน้า
     */
    private function renderPageNumbers(): string
    {
        $html = '';
        $pages = $this->calculatePageRange();

        // แสดง ... ถ้าไม่ได้เริ่มจากหน้า 1
        if ($pages[0] > 1) {
            $html .= $this->renderPageLink(1);
            if ($pages[0] > 2) {
                $html .= '<li class="page-item ' . $this->disabledClass . '"><span class="page-link">...</span></li>';
            }
        }

        // แสดงหมายเลขหน้า
        foreach ($pages as $page) {
            $html .= $this->renderPageLink($page);
        }

        // แสดง ... ถ้าไม่ได้จบที่หน้าสุดท้าย
        if (end($pages) < $this->totalPages) {
            if (end($pages) < $this->totalPages - 1) {
                $html .= '<li class="page-item ' . $this->disabledClass . '"><span class="page-link">...</span></li>';
            }
            $html .= $this->renderPageLink($this->totalPages);
        }

        return $html;
    }

    /**
     * สร้าง link สำหรับหน้าเดียว
     * จุดประสงค์: สร้าง HTML สำหรับ link หน้าเดียว
     * renderPageLink() ควรใช้กับอะไร: เมื่อคุณต้องการแสดงผลลิงก์สำหรับหน้าเดียวในการแบ่งหน้า
     * ตัวอย่างการใช้งาน:
     * ```php
     * echo $pagination->renderPageLink(1);
     * ```
     * 
     * @param int $page หมายเลขหน้าที่ต้องการสร้าง link
     * @return string คืนค่า HTML สำหรับ link หน้าเดียว
     */
    private function renderPageLink(int $page): string
    {
        $class = 'page-item';
        if ($page === $this->currentPage) {
            $class .= ' ' . $this->activeClass;
        }

        $html = '<li class="' . htmlspecialchars($class) . '">';

        if ($page === $this->currentPage) {
            $html .= '<span class="page-link">' . $page . '</span>';
        } else {
            $url = $this->getUrl($page);
            $html .= '<a class="page-link" href="' . htmlspecialchars($url) . '">' . $page . '</a>';
        }

        $html .= '</li>';

        return $html;
    }

    /**
     * คำนวณช่วงหน้าที่จะแสดง
     * จุดประสงค์: คำนวณช่วงหมายเลขหน้าที่จะแสดงใน pagination
     * calculatePageRange() ควรใช้กับอะไร: เมื่อคุณต้องการแสดงผลหมายเลขหน้าในการแบ่งหน้า
     * ตัวอย่างการใช้งาน:
     * ```php
     * $pages = $pagination->calculatePageRange();
     * ```
     * 
     * @return array คืนค่าช่วงหมายเลขหน้าที่จะแสดง
     */
    private function calculatePageRange(): array
    {
        $half = (int)floor($this->linksCount / 2);
        $start = max(1, $this->currentPage - $half);
        $end = min($this->totalPages, $start + $this->linksCount - 1);

        // ปรับ start ถ้า end ชนขอบ
        if ($end - $start < $this->linksCount - 1) {
            $start = max(1, $end - $this->linksCount + 1);
        }

        return range($start, $end);
    }

    /**
     * สร้างข้อมูลสรุปการแบ่งหน้า
     * จุดประสงค์: สร้างข้อความสรุปการแบ่งหน้า
     * summary() ควรใช้กับอะไร: เมื่อคุณต้องการแสดงผลข้อมูลแบบแบ่งหน้า
     * ตัวอย่างการใช้งาน:
     * ```php
     * echo $pagination->summary();
     * ```
     * 
     * @return string คืนค่าข้อความสรุปการแบ่งหน้า
     */
    public function summary(): string
    {
        if ($this->totalItems === 0) {
            return 'ไม่มีรายการ';
        }

        $start = $this->getOffset() + 1;
        $end = min($this->getOffset() + $this->perPage, $this->totalItems);

        return "แสดง {$start} ถึง {$end} จากทั้งหมด {$this->totalItems} รายการ";
    }

    /**
     * สร้าง pagination แบบง่าย (เฉพาะ Previous/Next)
     * จุดประสงค์: สร้าง HTML สำหรับการแบ่งหน้าแบบง่าย
     * renderSimple() ควรใช้กับอะไร: เมื่อคุณต้องการแสดงผลข้อมูลแบบแบ่งหน้าแบบง่าย
     * ตัวอย่างการใช้งาน:
     * ```php
     * echo $pagination->renderSimple();
     * 
     * 
     * @return string คืนค่า HTML สำหรับ pagination แบบง่าย
     */
    public function renderSimple(): string
    {
        if ($this->totalPages <= 1) {
            return '';
        }

        $html = '<nav aria-label="Page navigation">';
        $html .= '<ul class="' . htmlspecialchars($this->containerClass) . '">';
        $html .= $this->renderPreviousButton();
        
        // แสดงหน้าปัจจุบัน
        $html .= '<li class="page-item active">';
        $html .= '<span class="page-link">' . $this->currentPage . ' / ' . $this->totalPages . '</span>';
        $html .= '</li>';
        
        $html .= $this->renderNextButton();
        $html .= '</ul>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * แปลงเป็น array
     * จุดประสงค์: แปลงข้อมูล pagination เป็น array
     * toArray() ควรใช้กับอะไร: การแปลงข้อมูลเพื่อใช้งานในรูปแบบอื่น เช่น JSON
     * ตัวอย่างการใช้งาน:
     * ```php
     * $array = $pagination->toArray();
     * ```
     * 
     * @return array คืนค่าข้อมูล pagination ในรูปแบบ array
     */
    public function toArray(): array
    {
        return [
            'current_page' => $this->currentPage,
            'per_page' => $this->perPage,
            'total_pages' => $this->totalPages,
            'total_items' => $this->totalItems,
            'has_previous' => $this->hasPrevious(),
            'has_next' => $this->hasNext(),
            'previous_page' => $this->hasPrevious() ? $this->getPreviousPage() : null,
            'next_page' => $this->hasNext() ? $this->getNextPage() : null,
            'offset' => $this->getOffset(),
        ];
    }

    /**
     * แปลงเป็น JSON
     * จุดประสงค์: แปลงข้อมูล pagination เป็น JSON
     * toJson() ควรใช้กับอะไร: การแปลงข้อมูลเพื่อใช้งานในรูปแบบอื่น เช่น API response
     * ตัวอย่างการใช้งาน:
     * ```php
     * $json = $pagination->toJson();
     * ```
     * 
     * @return string คืนค่าข้อมูล pagination ในรูปแบบ JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
