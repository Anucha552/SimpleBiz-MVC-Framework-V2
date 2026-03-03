<?php
/**
 * Sitemap Helper
 *
 * จุดประสงค์: สร้าง sitemap.xml แบบง่าย สำหรับเว็บไซต์ของเรา โดยสามารถเพิ่ม URL และข้อมูลที่เกี่ยวข้องได้อย่างง่ายดาย และสามารถแสดงผลเป็น XML ที่ถูกต้องตามมาตรฐานของ sitemap.org เพื่อให้เครื่องมือค้นหา (Search Engines) สามารถเข้าใจและจัดทำดัชนีเว็บไซต์ของเราได้อย่างมีประสิทธิภาพ
 * ฟังก์ชันหลัก:
 * - reset(): รีเซ็ตข้อมูล URL ทั้งหมดใน sitemap
 * - addUrl($loc, $lastmod, $changefreq, $priority): เพิ่ม URL ใหม่พร้อมข้อมูลที่เกี่ยวข้อง
 * - addUrls($entries): เพิ่มหลาย URL พร้อมข้อมูลที่เกี่ยวข้องในครั้งเดียว
 * - renderXml($baseUrl): แสดงผล sitemap ในรูปแบบ XML โดยสามารถระบุ base URL เพื่อให้ URL ที่เพิ่มเข้ามาเป็นแบบสัมบูรณ์ได้
 * ตัวอย่างการใช้งาน:
 * ```php
 * SitemapHelper::reset();
 * SitemapHelper::addUrl('/', '2024-06-01', 'daily', '1.0');
 * SitemapHelper::addUrl('/employees', '2024-06-01', 'daily', '0.8');
 * SitemapHelper::addUrl('/employees/create', '2024-06-01', 'monthly', '0.3');
 * $xml = SitemapHelper::renderXml(UrlHelper::base());
 * echo $xml;
 * ```
 */

namespace App\Helpers;

class SitemapHelper
{
    /**
     * urls: รายการ URL ที่จะถูกแปลงเป็น sitemap.xml โดยแต่ละรายการจะเป็นอาร์เรย์ที่มีคีย์ 'loc', 'lastmod', 'changefreq', และ 'priority'
     */
    private static array $urls = [];

    /**
     * ฟังก์ชัน reset: เริ่มต้นค่าใหม่ทั้งหมด (ล้างข้อมูลเดิม)
     * จุดประสงค์: ใช้เมื่อเราต้องการสร้าง sitemap ใหม่จากศูนย์ โดยลบข้อมูลเก่าทั้งหมดออกไปก่อน
     * ตัวอย่างการใช้งาน:
     *  ```php
     * SitemapHelper::reset();
     * SitemapHelper::addUrl('/', '2024-06-01', 'daily', '1.0');
     * SitemapHelper::addUrl('/employees', '2024-06-01', 'daily', '0.8');
     * SitemapHelper::addUrl('/employees/create', '2024-06-01', 'monthly', '0.3');
     * $xml = SitemapHelper::renderXml(UrlHelper::base());
     * echo $xml;
     * ```
     */
    public static function reset(): void
    {
        self::$urls = [];
    }

    /**
     * ฟังก์ชัน addUrl: เพิ่ม URL ใหม่พร้อมข้อมูลที่เกี่ยวข้อง
     * จุดประสงค์: ใช้เพิ่ม URL ที่เราต้องการให้ปรากฏใน sitemap พร้อมกับข้อมูลเสริม เช่น วันที่แก้ไขล่าสุด ความถี่ในการเปลี่ยนแปลง และความสำคัญของ URL นั้นๆ
     * ตัวอย่างการใช้งาน:
     *  ```php
     * SitemapHelper::addUrl('/', '2024-06-01', 'daily', '1.0');
     * SitemapHelper::addUrl('/employees', '2024-06-01', 'daily', '0.8');
     * SitemapHelper::addUrl('/employees/create', '2024-06-01', 'monthly', '0.3');
     * ```
     *
     * @param string $loc URL ที่ต้องการเพิ่ม (เช่น '/', '/employees')
     * @param string|null $lastmod วันที่แก้ไขล่าสุดในรูปแบบ 'YYYY-MM-DD' (เช่น '2024-06-01') หรือ null หากไม่ต้องการระบุ
     * @param string|null $changefreq ความถี่ในการเปลี่ยนแปลง (เช่น 'daily', 'weekly', 'monthly') หรือ null หากไม่ต้องการระบุ
     * @param string|null $priority ความสำคัญของ URL ในรูปแบบตัวเลขระหว่าง 0.0 ถึง 1.0 (เช่น '1.0' สำหรับสำคัญที่สุด) หรือ null หากไม่ต้องการระบุ
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะเพิ่ม URL และข้อมูลที่เกี่ยวข้องลงในรายการภายในของ SitemapHelper
     */
    public static function addUrl(
        string $loc,
        ?string $lastmod = null,
        ?string $changefreq = null,
        ?string $priority = null
    ): void {
        $loc = trim($loc);
        if ($loc === '') {
            return;
        }

        self::$urls[] = [
            'loc' => $loc,
            'lastmod' => $lastmod !== null ? trim($lastmod) : null,
            'changefreq' => $changefreq !== null ? trim($changefreq) : null,
            'priority' => $priority !== null ? trim($priority) : null,
        ];
    }

    /**
     * ฟังก์ชัน addUrls: เพิ่มหลาย URL พร้อมข้อมูลที่เกี่ยวข้องในครั้งเดียว
     * จุดประสงค์: ใช้เพื่อเพิ่มหลาย URL พร้อมข้อมูลที่เกี่ยวข้องในครั้งเดียว โดยรับอาร์เรย์ของรายการ URL
     * ตัวอย่างการใช้งาน:
     *  ```php
     * SitemapHelper::addUrls([
     *     ['loc' => '/', 'lastmod' => '2024-06-01', 'changefreq' => 'daily', 'priority' => '1.0'],
     *     ['loc' => '/employees', 'lastmod' => '2024-06-01', 'changefreq' => 'daily', 'priority' => '0.8'],
     *     ['loc' => '/employees/create', 'lastmod' => '2024-06-01', 'changefreq' => 'monthly', 'priority' => '0.3'],
     * ]);
     * ```
     *
     * @param array $entries อาร์เรย์ของรายการ URL ที่ต้องการเพิ่ม แต่ละรายการเป็นอาร์เรย์ที่มีคีย์ 'loc', 'lastmod', 'changefreq', และ 'priority'
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะเพิ่ม URL และข้อมูลที่เกี่ยวข้องลงในรายการภายในของ SitemapHelper
     */
    public static function addUrls(array $entries): void
    {
        foreach ($entries as $entry) {
            if (!is_array($entry) || empty($entry['loc'])) {
                continue;
            }
            self::addUrl(
                (string) $entry['loc'],
                $entry['lastmod'] ?? null,
                $entry['changefreq'] ?? null,
                $entry['priority'] ?? null
            );
        }
    }

    /**
     * ฟังก์ชัน renderXml: แสดงผล sitemap ในรูปแบบ XML โดยสามารถระบุ base URL เพื่อให้ URL ที่เพิ่มเข้ามาเป็นแบบสัมบูรณ์ได้
     * จุดประสงค์: ใช้เพื่อสร้างสตริง XML ที่เป็น sitemap ตามมาตรฐานของ sitemap.org โดยสามารถระบุ base URL เพื่อให้ URL ที่เพิ่มเข้ามาเป็นแบบสัมบูรณ์ได้
     * ตัวอย่างการใช้งาน:
     *  ```php
     * SitemapHelper::reset();
     * SitemapHelper::addUrl('/', '2024-06-01', 'daily', '1.0');
     * SitemapHelper::addUrl('/employees', '2024-06-01', 'daily', '0.8');
     * SitemapHelper::addUrl('/employees/create', '2024-06-01', 'monthly', '0.3');
     * $xml = SitemapHelper::renderXml(UrlHelper::base());
     * echo $xml;
     * ```
     *
     * @param string|null $baseUrl URL พื้นฐานที่ต้องการใช้สำหรับแปลง URL ที่เพิ่มเข้ามาเป็นแบบสัมบูรณ์ (เช่น 'https://www.example.com') หรือ null หากไม่ต้องการใช้ base URL
     * @return string สตริง XML ที่เป็น sitemap ตามมาตรฐานของ sitemap.org ซึ่งประกอบด้วยรายการ URL ที่ถูกเพิ่มเข้ามาใน SitemapHelper
     */
    public static function renderXml(?string $baseUrl = null): string
    {
        $baseUrl = $baseUrl !== null ? rtrim($baseUrl, '/') : null;

        $urls = self::$urls;
        if ($urls === []) {
            $urls[] = [
                'loc' => $baseUrl !== null ? $baseUrl . '/' : '/',
                'lastmod' => null,
                'changefreq' => null,
                'priority' => null,
            ];
        }

        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $entry) {
            $loc = (string) ($entry['loc'] ?? '');
            if ($loc === '') {
                continue;
            }

            if ($baseUrl !== null && !self::isAbsoluteUrl($loc)) {
                $loc = $baseUrl . '/' . ltrim($loc, '/');
            }

            $lines[] = '  <url>';
            $lines[] = '    <loc>' . self::escape($loc) . '</loc>';

            if (!empty($entry['lastmod'])) {
                $lines[] = '    <lastmod>' . self::escape((string) $entry['lastmod']) . '</lastmod>';
            }
            if (!empty($entry['changefreq'])) {
                $lines[] = '    <changefreq>' . self::escape((string) $entry['changefreq']) . '</changefreq>';
            }
            if (!empty($entry['priority'])) {
                $lines[] = '    <priority>' . self::escape((string) $entry['priority']) . '</priority>';
            }

            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines);
    }

    /**
     * ฟังก์ชัน isAbsoluteUrl: ตรวจสอบว่า URL ที่กำหนดเป็น URL แบบสัมบูรณ์หรือไม่
     * จุดประสงค์: ใช้เพื่อแยกแยะว่า URL ที่เพิ่มเข้ามาเป็นแบบสัมบูรณ์ (เช่น 'https://www.example.com/page') หรือแบบสัมพัทธ์ (เช่น '/page') เพื่อให้สามารถจัดการกับการสร้าง sitemap ได้อย่างถูกต้อง
     * ตัวอย่างการใช้งาน:
     *  ```php
     * $isAbsolute = SitemapHelper::isAbsoluteUrl('https://www.example.com/page'); // คืนค่า true
     * $isAbsolute = SitemapHelper::isAbsoluteUrl('/page'); // คืนค่า false
     * ```
     *
     * @param string $url URL ที่ต้องการตรวจสอบ
     * @return bool คืนค่า true หาก URL เป็นแบบสัมบูรณ์ (เริ่มต้นด้วย http:// หรือ https://) และ false หากเป็นแบบสัมพัทธ์
     */
    private static function isAbsoluteUrl(string $url): bool
    {
        return (bool) preg_match('#^https?://#i', $url);
    }

    /**
     * ฟังก์ชัน escape: แปลงข้อความให้ปลอดภัยสำหรับการแสดงใน XML
     * จุดประสงค์: ใช้เพื่อป้องกันปัญหาที่อาจเกิดขึ้นเมื่อมีตัวอักษรพิเศษใน URL หรือข้อมูลที่เกี่ยวข้อง โดยการแปลงตัวอักษรเหล่านั้นให้เป็นรูปแบบที่ปลอดภัยสำหรับ XML
     * ตัวอย่างการใช้งาน:
     *  ```php
     * $safeString = SitemapHelper::escape('https://www.example.com/page?query=1&sort=asc');
     * // ผลลัพธ์จะเป็น 'https://www.example.com/page?query=1&amp;sort=asc'
     * ```
     *
     * @param string $value ข้อความที่ต้องการแปลงให้ปลอดภัยสำหรับ XML
     * @return string ข้อความที่ถูกแปลงแล้ว ซึ่งปลอดภัยสำหรับการแสดงใน XML
     */
    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
