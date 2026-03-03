<?php
/**
 * Robots Helper
 *
 * จุดประสงค์: สร้าง robots.txt แบบง่าย เอาไว้กำหนดนโยบายการเข้าถึงของบอทต่างๆ
 * การใช้งาน:
 * RobotsHelper::reset(); // เริ่มต้นใหม่
 * RobotsHelper::allow('/path'); // อนุญาตให้บอทเข้าถึง path
 * RobotsHelper::disallow('/path'); // ไม่อนุญาตให้บอทเข้าถึง path
 * RobotsHelper::setSitemap('https://example.com/sitemap.xml'); // กำหนด sitemap
 * RobotsHelper::setHost('https://example.com'); // กำหนด host
 * RobotsHelper::renderText(); // สร้างข้อความ robots.txt
 * 
 * ตัวอย่างการใช้งาน controller:
 * public function robots(): Response
 * {
 *   RobotsHelper::reset();
 *   RobotsHelper::allow('/public');
 *   RobotsHelper::disallow('/admin');
 *   RobotsHelper::setSitemap(UrlHelper::base() . '/sitemap.xml');
 *   RobotsHelper::setHost(UrlHelper::base());
 *   $text = RobotsHelper::renderText();
 * 
 *    return new Response($text, 200, [
 *       'Content-Type' => 'text/plain; charset=UTF-8',
 *   ]);
 * }
 */

namespace App\Helpers;

class RobotsHelper
{
    /**
     * allow: รายการ path ที่อนุญาตให้บอทเข้าถึง
     */
    private static array $allow = [];

    /**
     * disallow: รายการ path ที่ไม่อนุญาตให้บอทเข้าถึง
     */
    private static array $disallow = [];

    /**
     * sitemap: URL ของไฟล์ sitemap.xml
     */
    private static ?string $sitemap = null;

    /**
     * host: URL ของเว็บไซต์ (ไม่ต้องมี path)
     */
    private static ?string $host = null;

    /**
     * ฟังก์ชัน reset: เริ่มต้นค่าใหม่ทั้งหมด (ล้างข้อมูลเดิม)
     * จุดประสงค์: ใช้เมื่อเราต้องการสร้าง robots.txt ใหม่จากศูนย์ โดยลบข้อมูลเก่าทั้งหมดออกไปก่อน
     * ตัวอย่างการใช้งาน:
     *  ```php
     * RobotsHelper::reset();
     * RobotsHelper::allow('/public');
     * RobotsHelper::disallow('/admin');
     * RobotsHelper::setSitemap('https://example.com/sitemap.xml');
     * RobotsHelper::setHost('https://example.com');
     * $text = RobotsHelper::renderText();
     * ```
     * 
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะรีเซ็ตสถานะภายในของ RobotsHelper ให้เป็นค่าเริ่มต้น (อนุญาตและไม่อนุญาตว่างเปล่า, ไม่มี sitemap และ host)
     */
    public static function reset(): void
    {
        self::$allow = [];
        self::$disallow = [];
        self::$sitemap = null;
        self::$host = null;
    }

    /**
     * ฟังก์ชัน allow: เพิ่ม path ที่อนุญาตให้บอทเข้าถึง
     * จุดประสงค์: ใช้เพื่อระบุเส้นทางหรือไฟล์ที่เราต้องการให้บอทของเครื่องมือค้นหาเข้าถึงได้
     * ตัวอย่างการใช้งาน:
     *  ```php
     * RobotsHelper::allow('/public');
     * RobotsHelper::allow('/images');
     * ```
     * ในตัวอย่างนี้ เราอนุญาตให้บอทเข้าถึงเส้นทาง /public และ /images ได้
     * 
     * @param string $path เส้นทางที่ต้องการอนุญาตให้บอทเข้าถึง (เช่น '/public', '/images')
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะเพิ่ม path ที่ระบุเข้าไปในรายการ allow ของ RobotsHelper
     */
    public static function allow(string $path): void
    {
        $path = trim($path);
        if ($path !== '') {
            self::$allow[] = $path;
        }
    }

    /**
     * ฟังก์ชัน disallow: เพิ่ม path ที่ไม่อนุญาตให้บอทเข้าถึง
     * จุดประสงค์: ใช้เพื่อระบุเส้นทางหรือไฟล์ที่เราต้องการปิดกั้นไม่ให้บอทของเครื่องมือค้นหาเข้าถึงได้
     * ตัวอย่างการใช้งาน:
     *  ```php
     * RobotsHelper::disallow('/admin');
     * RobotsHelper::disallow('/private');
     * ```
     * ในตัวอย่างนี้ เราไม่อนุญาตให้บอทเข้าถึงเส้นทาง /admin และ /private ได้
     * 
     * @param string $path เส้นทางที่ต้องการไม่อนุญาตให้บอทเข้าถึง (เช่น '/admin', '/private')
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะเพิ่ม path ที่ระบุเข้าไปในรายการ disallow ของ RobotsHelper
     */
    public static function disallow(string $path): void
    {
        $path = trim($path);
        if ($path !== '') {
            self::$disallow[] = $path;
        }
    }

    /**
     * ฟังก์ชัน setSitemap: กำหนด URL ของไฟล์ sitemap.xml
     * จุดประสงค์: ใช้เพื่อระบุที่อยู่ของไฟล์ sitemap.xml ที่บอทควรเข้าถึง
     * ตัวอย่างการใช้งาน:
     *  ```php
     * RobotsHelper::setSitemap('https://example.com/sitemap.xml');
     * ```
     * 
     * @param string $url URL ของไฟล์ sitemap.xml
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะตั้งค่า sitemap ของ RobotsHelper
     */
    public static function setSitemap(string $url): void
    {
        self::$sitemap = trim($url);
    }

    /**
     * ฟังก์ชัน setHost: กำหนด URL ของเว็บไซต์ (ไม่ต้องมี path)
     * จุดประสงค์: ใช้เพื่อระบุที่อยู่ของเว็บไซต์ที่บอทควรเข้าถึง
     * ตัวอย่างการใช้งาน:
     *  ```php
     * RobotsHelper::setHost('https://example.com');
     * ```
     * 
     * @param string $host URL ของเว็บไซต์
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะตั้งค่า host ของ RobotsHelper
     */
    public static function setHost(string $host): void
    {
        self::$host = trim($host);
    }

    /**
    * ฟังก์ชัน renderText: สร้างข้อความสำหรับไฟล์ robots.txt
    * จุดประสงค์: ใช้เพื่อสร้างเนื้อหาของไฟล์ robots.txt ตามนโยบายที่กำหนดไว้ใน allow, disallow, sitemap และ host
    * ตัวอย่างการใช้งาน:
    *  ```php
    * RobotsHelper::reset();
    * RobotsHelper::allow('/public');
    * RobotsHelper::disallow('/admin');
    * RobotsHelper::setSitemap('https://example.com/sitemap.xml');
    * RobotsHelper::setHost('https://example.com');
    * $text = RobotsHelper::renderText();
    * echo $text;
    * ```
    * ในตัวอย่างนี้ เราจะได้ข้อความที่สามารถใช้เป็นเนื้อหาในไฟล์ robots.txt ซึ่งจะมีการอนุญาตให้บอทเข้าถึง /public, ไม่อนุญาตให้เข้าถึง /admin, และระบุ sitemap และ host ตามที่กำหนด
    * 
    * @return string ข้อความที่สามารถใช้เป็นเนื้อหาในไฟล์ robots.txt
    */
    public static function renderText(): string
    {
        $lines = [];
        $lines[] = 'User-agent: *';

        if (self::$allow !== []) {
            foreach (self::$allow as $path) {
                $lines[] = 'Allow: ' . $path;
            }
        }

        if (self::$disallow !== []) {
            foreach (self::$disallow as $path) {
                $lines[] = 'Disallow: ' . $path;
            }
        } else {
            $lines[] = 'Disallow:';
        }

        if (self::$host !== null && self::$host !== '') {
            $lines[] = 'Host: ' . self::$host;
        }

        if (self::$sitemap !== null && self::$sitemap !== '') {
            $lines[] = 'Sitemap: ' . self::$sitemap;
        }

        return implode("\n", $lines) . "\n";
    }
}
