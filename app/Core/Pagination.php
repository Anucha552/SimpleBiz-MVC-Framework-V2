<?php
/**
 * คลาส Pagination
 * 
 * จุดประสงค์: จัดการการแบ่งหน้าข้อมูล
 * ฟีเจอร์: คำนวณหน้า, สร้าง pagination links, รองรับ Bootstrap
 * 
 * ฟีเจอร์หลัก:
 * - คำนวณจำนวนหน้า
 * - สร้าง pagination links
 * - รองรับ Bootstrap styling
 * - Customizable
 * 
 * ตัวอย่างการใช้งาน:
 * ```php
 * // คำนวณ offset
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
     * 
     * @param int $totalItems จำนวนรายการทั้งหมด
     * @param int $perPage จำนวนรายการต่อหน้า
     * @param int $currentPage หน้าปัจจุบัน
     * @param string|null $baseUrl URL base (ถ้าไม่ระบุจะใช้ URL ปัจจุบัน)
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
     * 
     * @return int
     */
    public function getOffset(): int
    {
        return ($this->currentPage - 1) * $this->perPage;
    }

    /**
     * รับจำนวนรายการต่อหน้า
     * 
     * @return int
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * รับหน้าปัจจุบัน
     * 
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * รับจำนวนหน้าทั้งหมด
     * 
     * @return int
     */
    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    /**
     * รับจำนวนรายการทั้งหมด
     * 
     * @return int
     */
    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    /**
     * ตรวจสอบว่ามีหน้าก่อนหน้าหรือไม่
     * 
     * @return bool
     */
    public function hasPrevious(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * ตรวจสอบว่ามีหน้าถัดไปหรือไม่
     * 
     * @return bool
     */
    public function hasNext(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    /**
     * รับหมายเลขหน้าก่อนหน้า
     * 
     * @return int
     */
    public function getPreviousPage(): int
    {
        return max(1, $this->currentPage - 1);
    }

    /**
     * รับหมายเลขหน้าถัดไป
     * 
     * @return int
     */
    public function getNextPage(): int
    {
        return min($this->totalPages, $this->currentPage + 1);
    }

    /**
     * ตั้งค่าจำนวน page links ที่จะแสดง
     * 
     * @param int $count
     * @return self
     */
    public function setLinksCount(int $count): self
    {
        $this->linksCount = max(1, $count);
        return $this;
    }

    /**
     * ตั้งค่า CSS classes
     * 
     * @param string $container
     * @param string $active
     * @param string $disabled
     * @return self
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
     * 
     * @param string $previous
     * @param string $next
     * @return self
     */
    public function setButtonTexts(string $previous, string $next): self
    {
        $this->previousText = $previous;
        $this->nextText = $next;
        return $this;
    }

    /**
     * สร้าง URL สำหรับหน้าที่ระบุ
     * 
     * @param int $page
     * @return string
     */
    public function getUrl(int $page): string
    {
        $params = array_merge($this->queryParams, ['page' => $page]);
        $queryString = http_build_query($params);

        return $this->baseUrl . ($queryString ? '?' . $queryString : '');
    }

    /**
     * สร้าง pagination HTML (Bootstrap style)
     * 
     * @return string
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
     * 
     * @return string
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
     * 
     * @return string
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
     * 
     * @return string
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
     * 
     * @param int $page
     * @return string
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
     * 
     * @return array
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
     * 
     * @return string
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
     * 
     * @return string
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
     * 
     * @return array
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
     * 
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
