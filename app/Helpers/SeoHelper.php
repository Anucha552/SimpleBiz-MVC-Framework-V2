<?php
/**
 * SEO Helper
 *
 * จุดประสงค์: ช่วยจัดการ meta tags, og/twitter และ JSON-LD แบบง่าย
 * การใช้งาน:
 * SeoHelper::setTitle('My Page Title');
 * SeoHelper::setDescription('This is a description of the page.');
 * SeoHelper::setKeywords(['keyword1', 'keyword2']);
 * SeoHelper::setCanonical('https://example.com/my-page');
 * SeoHelper::setOg('image', 'https://example.com/image.jpg');
 * SeoHelper::setTwitter('card', 'summary_large_image');
 * SeoHelper::addJsonLd([
 *   '@context' => 'https://schema.org',
 *  '@type' => 'WebPage',
 *  'name' => 'My Page',
 * 'description' => 'This is a description of my page.'
 * ]);
 * ใน view ให้เรียก SeoHelper::renderMetaTags() ในส่วน <head> เพื่อแสดง meta tags และ SeoHelper::renderJsonLd() เพื่อแสดง JSON-LD
 */

namespace App\Helpers;

class SeoHelper
{
    /**
     * title: ชื่อเรื่องของหน้าเว็บ
     */
    private static ?string $title = null;

    /**
     * description: คำอธิบายสั้น ๆ ของหน้าเว็บ
     */
    private static ?string $description = null;

    /**
     * keywords: คำสำคัญของหน้าเว็บ
     */
    private static array $keywords = [];

    /**
     * canonical: URL ที่เป็น canonical ของหน้าเว็บ
     */
    private static ?string $canonical = null;

    /**
     * og: ข้อมูล Open Graph สำหรับแชร์ในโซเชียลมีเดีย
     */
    private static array $og = [];

    /**
     * twitter: ข้อมูล Twitter Card สำหรับแชร์ใน Twitter
     */
    private static array $twitter = [];

    /**
     * jsonLd: ข้อมูล JSON-LD สำหรับ SEO
     */
    private static array $jsonLd = [];

    /**
     * ฟังก์ชัน setTitle: ตั้งค่าชื่อเรื่องของหน้าเว็บ
     * จุดประสงค์: ใช้เพื่อกำหนดชื่อเรื่องที่จะแสดงในแท็บของเบราว์เซอร์และในผลการค้นหาของเครื่องมือค้นหา
     * ตัวอย่างการใช้งาน:
     * ```php
     * SeoHelper::setTitle('My Page Title');
     * ```
     * 
     * @param string $title ชื่อเรื่องที่ต้องการตั้งค่า
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะตั้งค่าชื่อเรื่องภายในของ SeoHelper ให้เป็นค่าที่กำหนด
     */
    public static function setTitle(string $title): void
    {
        self::$title = trim($title);
    }

    /**
     * ฟังก์ชัน setDescription: ตั้งค่าคำอธิบายสั้น ๆ ของหน้าเว็บ
     * จุดประสงค์: ใช้เพื่อกำหนดคำอธิบายที่จะแสดงในผลการค้นหาของเครื่องมือค้นหาและเมื่อแชร์ในโซเชียลมีเดีย
     * ตัวอย่างการใช้งาน:
     * ```php
     * SeoHelper::setDescription('This is a description of the page.');
     * ```
     * 
     * @param string $description คำอธิบายที่ต้องการตั้งค่า
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะตั้งค่าคำอธิบายภายในของ SeoHelper ให้เป็นค่าที่กำหนด
     */
    public static function setDescription(string $description): void
    {
        self::$description = trim($description);
    }

    /**
     * ฟังก์ชัน setKeywords: ตั้งค่าคำสำคัญของหน้าเว็บ
     * จุดประสงค์: ใช้เพื่อกำหนดคำสำคัญที่เกี่ยวข้องกับเนื้อหาของหน้าเว็บ ซึ่งอาจช่วยให้เครื่องมือค้นหาเข้าใจเนื้อหาของหน้าได้ดีขึ้น
     * ตัวอย่างการใช้งาน:
     * ```php
     * SeoHelper::setKeywords(['keyword1', 'keyword2']);
     * // หรือ
     * SeoHelper::setKeywords('keyword1, keyword2');
     * ```
     * ในตัวอย่างนี้ เราตั้งค่าคำสำคัญเป็น 'keyword1' และ 'keyword2' โดยสามารถส่งเป็นอาร์เรย์หรือสตริงที่คั่นด้วยเครื่องหมายจุลภาคก็ได้
     * 
     * @param array|string $keywords คำสำคัญที่ต้องการตั้งค่า สามารถส่งเป็นอาร์เรย์ของคำสำคัญหรือสตริงที่คั่นด้วยเครื่องหมายจุลภาค
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะตั้งค่าคำสำคัญภายในของ SeoHelper ให้เป็นค่าที่กำหนด
     */
    public static function setKeywords(array|string $keywords): void
    {
        if (is_string($keywords)) {
            $keywords = array_map('trim', explode(',', $keywords));
        }

        $filtered = [];
        foreach ($keywords as $keyword) {
            $keyword = trim((string) $keyword);
            if ($keyword !== '') {
                $filtered[] = $keyword;
            }
        }

        self::$keywords = $filtered;
    }

    /**
     * ฟังก์ชัน setCanonical: ตั้งค่า URL ที่เป็น canonical ของหน้าเว็บ
     * จุดประสงค์: ใช้เพื่อระบุ URL ที่เป็น canonical ของหน้าเว็บ ซึ่งช่วยป้องกันปัญหาเนื้อหาซ้ำซ้อนและช่วยให้เครื่องมือค้นหาเข้าใจว่า URL ใดเป็นเวอร์ชันหลักของหน้าเว็บ
     * ตัวอย่างการใช้งาน:
     * ```php
     * SeoHelper::setCanonical('https://example.com/my-page');
     * ```
     * 
     * @param string $url URL ที่ต้องการตั้งเป็น canonical
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะตั้งค่า canonical ภายในของ SeoHelper ให้เป็นค่าที่กำหนด
     */
    public static function setCanonical(string $url): void
    {
        self::$canonical = trim($url);
    }

    /**
     * ฟังก์ชัน setOg: ตั้งค่าข้อมูล Open Graph สำหรับแชร์ในโซเชียลมีเดีย
     * จุดประสงค์: ใช้เพื่อกำหนดข้อมูล Open Graph ที่จะแสดงเมื่อแชร์หน้าเว็บในโซเชียลมีเดีย เช่น Facebook, LinkedIn เป็นต้น
     * ตัวอย่างการใช้งาน:
     * ```php
     * SeoHelper::setOg('image', 'https://example.com/image.jpg');
     * SeoHelper::setOg('title', 'My Page Title');
     * ```
     * ในตัวอย่างนี้ เราตั้งค่า Open Graph property 'og:image' เป็น 'https://example.com/image.jpg' และ 'og:title' เป็น 'My Page Title'
     * 
     * @param string $key ชื่อของ Open Graph property (เช่น 'title', 'description', 'image')
     * @param string $value ค่าของ Open Graph property
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะตั้งค่า Open Graph property ภายในของ SeoHelper ให้เป็นค่าที่กำหนด
     */
    public static function setOg(string $key, string $value): void
    {
        self::$og[$key] = $value;
    }

    /**
     * ฟังก์ชัน setTwitter: ตั้งค่าข้อมูล Twitter Card สำหรับแชร์ใน Twitter
     * จุดประสงค์: ใช้เพื่อกำหนดข้อมูล Twitter Card ที่จะแสดงเมื่อแชร์หน้าเว็บใน Twitter
     * ตัวอย่างการใช้งาน:
     * ```php
     * SeoHelper::setTwitter('card', 'summary_large_image');
     * SeoHelper::setTwitter('title', 'My Page Title');
     * ```
     * ในตัวอย่างนี้ เราตั้งค่า Twitter Card property 'twitter:card' เป็น 'summary_large_image' และ 'twitter:title' เป็น 'My Page Title'
     * 
     * @param string $key ชื่อของ Twitter Card property (เช่น 'card', 'title', 'description')
     * @param string $value ค่าของ Twitter Card property
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะตั้งค่า Twitter Card property ภายในของ SeoHelper ให้เป็นค่าที่กำหนด
     */
    public static function setTwitter(string $key, string $value): void
    {
        self::$twitter[$key] = $value;
    }

    /**
     * ฟังก์ชัน addJsonLd: เพิ่มข้อมูล JSON-LD สำหรับ SEO
     * จุดประสงค์: ใช้เพื่อเพิ่มข้อมูล JSON-LD ที่จะแสดงในส่วน <head> ของหน้าเว็บ ซึ่งช่วยให้เครื่องมือค้นหาเข้าใจเนื้อหาของหน้าเว็บได้ดีขึ้น
     * ตัวอย่างการใช้งาน:
     * ```php
     * SeoHelper::addJsonLd([
     *   '@context' => 'https://schema.org',
     *  '@type' => 'WebPage',
     *  'name' => 'My Page',
     * 'description' => 'This is a description of my page.'
     * ]);
     * ```
     * ในตัวอย่างนี้ เราเพิ่มข้อมูล JSON-LD ที่ระบุว่าเป็น WebPage พร้อมกับชื่อและคำอธิบายของหน้า
     * 
     * @param array $data ข้อมูล JSON-LD ที่ต้องการเพิ่ม เป็นอาร์เรย์ที่สามารถแปลงเป็น JSON ได้
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะเพิ่มข้อมูล JSON-LD ลงในรายการภายในของ SeoHelper
     */
    public static function addJsonLd(array $data): void
    {
        self::$jsonLd[] = $data;
    }

    /** 
     * ฟังก์ชัน title: ดึงชื่อเรื่องของหน้าเว็บ
     * จุดประสงค์: ใช้เพื่อดึงชื่อเรื่องที่ตั้งค่าไว้ หรือใช้ค่า fallback หากไม่มีการตั้งค่า
     * ตัวอย่างการใช้งาน:
     * ```php
     * $title = SeoHelper::title('Default Title');
     * ```
     * ในตัวอย่างนี้ เราจะได้ชื่อเรื่องที่ตั้งค่าไว้ใน SeoHelper หากไม่มีการตั้งค่า จะได้ 'Default Title' เป็นผลลัพธ์
     * 
     * @param string|null $fallback ชื่อเรื่องที่ใช้เป็นค่าเริ่มต้นหากไม่มีการตั้งค่า
     * @return string ชื่อเรื่องที่ได้จากการตั้งค่าหรือ fallback
     */
    public static function title(?string $fallback = null): string
    {
        $title = self::$title ?? $fallback ?? '';
        return trim($title);
    }

    public static function description(): string
    {
        return self::$description ?? '';
    }

    /** 
     * ฟังก์ชัน keywords: ดึงคำสำคัญของหน้าเว็บ
     * จุดประสงค์: ใช้เพื่อดึงคำสำคัญที่ตั้งค่าไว้ในรูปแบบสตริงที่คั่นด้วยเครื่องหมายจุลภาค
     * ตัวอย่างการใช้งาน:
     * ```php
     * $keywords = SeoHelper::keywords();
     * ```
     * ในตัวอย่างนี้ เราจะได้คำสำคัญที่ตั้งค่าไว้ใน SeoHelper ในรูปแบบสตริง เช่น 'keyword1, keyword2'
     * 
     * @return string คำสำคัญที่ได้จากการตั้งค่าในรูปแบบสตริงที่คั่นด้วยเครื่องหมายจุลภาค
     */
    public static function keywords(): string
    {
        if (self::$keywords === []) {
            return '';
        }

        return implode(', ', self::$keywords);
    }

    /** 
     * ฟังก์ชัน canonical: ดึง URL ที่เป็น canonical ของหน้าเว็บ
     * จุดประสงค์: ใช้เพื่อดึง URL ที่ตั้งค่าไว้เป็น canonical ซึ่งช่วยให้เครื่องมือค้นหาเข้าใจว่า URL ใดเป็นเวอร์ชันหลักของหน้าเว็บ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $canonical = SeoHelper::canonical();
     * ```
     * ในตัวอย่างนี้ เราจะได้ URL ที่ตั้งค่าไว้ใน SeoHelper เป็น canonical หากไม่มีการตั้งค่า จะได้ null เป็นผลลัพธ์
     * 
     * @return string|null URL ที่เป็น canonical ของหน้าเว็บ หรือ null หากไม่มีการตั้งค่า
     */
    public static function canonical(): ?string
    {
        return self::$canonical;
    }

    /** 
     * ฟังก์ชัน renderMetaTags: สร้าง meta tags สำหรับส่วน <head> ของหน้าเว็บ
     * จุดประสงค์: ใช้เพื่อสร้าง meta tags ที่จำเป็นสำหรับ SEO รวมถึง title, description, keywords, canonical, Open Graph และ Twitter Card
     * ตัวอย่างการใช้งาน:
     * ```php
     * echo SeoHelper::renderMetaTags('Default Title');
     * ```
     * ในตัวอย่างนี้ เราจะได้ meta tags ที่สร้างขึ้นจากการตั้งค่าใน SeoHelper หากไม่มีการตั้งค่า title จะใช้ 'Default Title' เป็นค่าเริ่มต้น
     * 
     * @param string|null $fallbackTitle ชื่อเรื่องที่ใช้เป็นค่าเริ่มต้นหากไม่มีการตั้งค่า title
     * @return string สตริงที่ประกอบด้วย meta tags สำหรับส่วน <head> ของหน้าเว็บ
     */
    public static function renderMetaTags(?string $fallbackTitle = null): string
    {
        $parts = [];
        $title = self::title($fallbackTitle);
        $description = self::description();
        $keywords = self::keywords();
        $canonical = self::canonical();

        if ($title !== '') {
            $parts[] = '<title>' . self::escape($title) . '</title>';
        }

        if ($description !== '') {
            $parts[] = '<meta name="description" content="' . self::escape($description) . '">';
        }

        if ($keywords !== '') {
            $parts[] = '<meta name="keywords" content="' . self::escape($keywords) . '">';
        }

        if ($canonical !== null && $canonical !== '') {
            $parts[] = '<link rel="canonical" href="' . self::escape($canonical) . '">';
        }

        foreach (self::buildOg($title, $description, $canonical) as $key => $value) {
            if ($value === '') {
                continue;
            }
            $property = str_contains($key, ':') ? $key : 'og:' . $key;
            $parts[] = '<meta property="' . self::escape($property) . '" content="' . self::escape($value) . '">';
        }

        foreach (self::buildTwitter($title, $description, $canonical) as $key => $value) {
            if ($value === '') {
                continue;
            }
            $name = str_contains($key, ':') ? $key : 'twitter:' . $key;
            $parts[] = '<meta name="' . self::escape($name) . '" content="' . self::escape($value) . '">';
        }

        return implode("\n    ", $parts);
    }

    /** 
     * ฟังก์ชัน renderJsonLd: สร้างสคริปต์ JSON-LD สำหรับส่วน <head> ของหน้าเว็บ
     * จุดประสงค์: ใช้เพื่อสร้างสคริปต์ JSON-LD ที่ประกอบด้วยข้อมูลที่ตั้งค่าไว้ใน SeoHelper ซึ่งช่วยให้เครื่องมือค้นหาเข้าใจเนื้อหาของหน้าเว็บได้ดีขึ้น
     * ตัวอย่างการใช้งาน:
     * ```php
     * echo SeoHelper::renderJsonLd();
     * ```
     * ในตัวอย่างนี้ เราจะได้สคริปต์ JSON-LD ที่สร้างขึ้นจากข้อมูลที่เพิ่มไว้ใน SeoHelper หากไม่มีข้อมูล JSON-LD จะได้สตริงว่างเป็นผลลัพธ์
     * 
     * @return string สตริงที่ประกอบด้วยสคริปต์ JSON-LD สำหรับส่วน <head> ของหน้าเว็บ หรือสตริงว่างหากไม่มีข้อมูล JSON-LD
     */
    public static function renderJsonLd(): string
    {
        if (self::$jsonLd === []) {
            return '';
        }

        $parts = [];
        foreach (self::$jsonLd as $item) {
            $json = json_encode($item, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                continue;
            }
            $parts[] = '<script type="application/ld+json">' . $json . '</script>';
        }

        return implode("\n    ", $parts);
    }

    /** 
     * ฟังก์ชัน buildOg: สร้างข้อมูล Open Graph สำหรับแชร์ในโซเชียลมีเดีย
     * จุดประสงค์: ใช้เพื่อสร้างข้อมูล Open Graph ที่จะใช้เมื่อแชร์หน้าเว็บในโซเชียลมีเดีย โดยจะใช้ข้อมูลที่ตั้งค่าไว้ใน SeoHelper และเติมค่า title, description, url จาก title, description และ canonical หากยังไม่มีการตั้งค่า
     * ตัวอย่างการใช้งาน:
     * ```php
     * $ogData = SeoHelper::buildOg('My Page Title', 'This is a description of the page.', 'https://example.com/my-page');
     * ```
     * ในตัวอย่างนี้ เราจะได้ข้อมูล Open Graph ที่ประกอบด้วย title, description และ url ตามที่กำหนดไว้ใน SeoHelper หรือจากพารามิเตอร์ที่ส่งเข้ามา
     * 
     * @param string $title ชื่อเรื่องของหน้าเว็บที่ใช้เป็นค่า fallback สำหรับ og:title
     * @param string $description คำอธิบายของหน้าเว็บที่ใช้เป็นค่า fallback สำหรับ og:description
     * @param string|null $canonical URL ที่เป็น canonical ของหน้าเว็บที่ใช้เป็นค่า fallback สำหรับ og:url
     * @return array ข้อมูล Open Graph ที่ประกอบด้วยค่าที่ตั้งไว้ใน SeoHelper และค่าที่เติมจากพารามิเตอร์
     */
    private static function buildOg(string $title, string $description, ?string $canonical): array
    {
        $og = self::$og;

        if ($title !== '' && !self::hasAnyKey($og, ['title', 'og:title'])) {
            $og['title'] = $title;
        }

        if ($description !== '' && !self::hasAnyKey($og, ['description', 'og:description'])) {
            $og['description'] = $description;
        }

        if ($canonical !== null && $canonical !== '' && !self::hasAnyKey($og, ['url', 'og:url'])) {
            $og['url'] = $canonical;
        }

        return $og;
    }

    /** 
     * ฟังก์ชัน buildTwitter: สร้างข้อมูล Twitter Card สำหรับแชร์ใน Twitter
     * จุดประสงค์: ใช้เพื่อสร้างข้อมูล Twitter Card ที่จะใช้เมื่อแชร์หน้าเว็บใน Twitter โดยจะใช้ข้อมูลที่ตั้งค่าไว้ใน SeoHelper และเติมค่า title, description, url จาก title, description และ canonical หากยังไม่มีการตั้งค่า
     * ตัวอย่างการใช้งาน:
     * ```php
     * $twitterData = SeoHelper::buildTwitter('My Page Title', 'This is a description of the page.', 'https://example.com/my-page');
     * ```
     * ในตัวอย่างนี้ เราจะได้ข้อมูล Twitter Card ที่ประกอบด้วย title, description และ url ตามที่กำหนดไว้ใน SeoHelper หรือจากพารามิเตอร์ที่ส่งเข้ามา
     * 
     * @param string $title ชื่อเรื่องของหน้าเว็บที่ใช้เป็นค่า fallback สำหรับ twitter:title
     * @param string $description คำอธิบายของหน้าเว็บที่ใช้เป็นค่า fallback สำหรับ twitter:description
     * @param string|null $canonical URL ที่เป็น canonical ของหน้าเว็บที่ใช้เป็นค่า fallback สำหรับ twitter:url
     * @return array ข้อมูล Twitter Card ที่ประกอบด้วยค่าที่ตั้งไว้ใน SeoHelper และค่าที่เติมจากพารามิเตอร์
     */
    private static function buildTwitter(string $title, string $description, ?string $canonical): array
    {
        $twitter = self::$twitter;

        if ($title !== '' && !self::hasAnyKey($twitter, ['title', 'twitter:title'])) {
            $twitter['title'] = $title;
        }

        if ($description !== '' && !self::hasAnyKey($twitter, ['description', 'twitter:description'])) {
            $twitter['description'] = $description;
        }

        if ($canonical !== null && $canonical !== '' && !self::hasAnyKey($twitter, ['url', 'twitter:url'])) {
            $twitter['url'] = $canonical;
        }

        return $twitter;
    }

    /** 
     * ฟังก์ชัน hasAnyKey: ตรวจสอบว่าอาร์เรย์มีคีย์ใดคีย์หนึ่งหรือไม่
     * จุดประสงค์: ใช้เพื่อช่วยในการตรวจสอบว่าอาร์เรย์ที่เก็บข้อมูล Open Graph หรือ Twitter Card มีคีย์ที่ระบุไว้หรือไม่ เพื่อป้องกันการเติมค่า fallback ซ้ำซ้อน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $hasTitle = SeoHelper::hasAnyKey($ogData, ['title', 'og:title']);
     * ```
     * ในตัวอย่างนี้ เราจะได้ผลลัพธ์เป็น true หาก $ogData มีคีย์ 'title' หรือ 'og:title' อยู่ และ false หากไม่มีคีย์เหล่านั้น
     * 
     * @param array $data อาร์เรย์ที่ต้องการตรวจสอบ
     * @param array $keys รายการของคีย์ที่ต้องการตรวจสอบในอาร์เรย์
     * @return bool คืนค่า true หากอาร์เรย์มีคีย์ใดคีย์หนึ่งในรายการ และ false หากไม่มีเลย
     */
    private static function hasAnyKey(array $data, array $keys): bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return true;
            }
        }

        return false;
    }

    /** 
     * ฟังก์ชัน escape: ป้องกันการโจมตีแบบ XSS โดยการแปลงอักขระพิเศษในสตริงให้เป็น HTML entities
     * จุดประสงค์: ใช้เพื่อป้องกันการโจมตีแบบ Cross-Site Scripting (XSS) โดยการแปลงอักขระพิเศษในสตริงที่จะแสดงใน HTML ให้เป็น HTML entities ซึ่งช่วยให้มั่นใจได้ว่าข้อมูลที่แสดงจะไม่ถูกตีความเป็นโค้ด HTML หรือ JavaScript ที่เป็นอันตราย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $safeTitle = SeoHelper::escape('<script>alert("XSS")</script>');
     * ```
     * ในตัวอย่างนี้ เราจะได้ผลลัพธ์เป็น '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;' ซึ่งจะไม่ถูกตีความเป็นโค้ด JavaScript เมื่อแสดงใน HTML
     * 
     * @param string $value สตริงที่ต้องการป้องกัน XSS
     * @return string สตริงที่ถูกแปลงอักขระพิเศษเป็น HTML entities เพื่อความปลอดภัยในการแสดงผลใน HTML
     */
    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
