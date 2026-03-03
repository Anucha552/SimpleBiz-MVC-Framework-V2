<?php

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\Response;
use App\Helpers\RobotsHelper;
use App\Helpers\SitemapHelper;
use App\Helpers\UrlHelper;

class SeoController extends Controller
{
    /**
     * ฟังก์ชัน sitemap: สร้างไฟล์ sitemap.xml โดยใช้ข้อมูล URL ที่กำหนดไว้ใน SitemapHelper และส่งกลับเป็น Response ที่มี Content-Type เป็น application/xml
     * จุดประสงค์: ให้ Search Engine สามารถเข้าถึงข้อมูล URL ของเว็บไซต์ได้อย่างมีประสิทธิภาพ เพื่อช่วยในการจัดทำดัชนีและเพิ่มโอกาสในการแสดงผลในผลการค้นหา
     */
    public function sitemap(): Response
    {
        SitemapHelper::reset(); // เริ่มต้นใหม่ก่อนเพิ่ม URL ใหม่

        $today = date('Y-m-d');
        SitemapHelper::addUrl('/', $today, 'daily', '1.0'); // เพิ่ม URL หน้าแรกของเว็บไซต์
        SitemapHelper::addUrl('/employees', $today, 'daily', '0.8'); // เพิ่ม URL หน้าแสดงรายการพนักงาน
        SitemapHelper::addUrl('/employees/create', $today, 'monthly', '0.3'); // เพิ่ม URL หน้าเพิ่มพนักงานใหม่

        $xml = SitemapHelper::renderXml(UrlHelper::base()); // สร้าง XML โดยใช้ URL พื้นฐานของเว็บไซต์

        return new Response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * ฟังก์ชัน robots: สร้างไฟล์ robots.txt โดยใช้ข้อมูลที่กำหนดไว้ใน RobotsHelper และส่งกลับเป็น Response ที่มี Content-Type เป็น text/plain
     * จุดประสงค์: กำหนดนโยบายการเข้าถึงของบอทต่างๆ เพื่อควบคุมการเข้าถึงของ Search Engine และบอทอื่นๆ ที่เข้ามาเยี่ยมชมเว็บไซต์ โดยสามารถระบุได้ว่าอนุญาตหรือไม่อนุญาตให้เข้าถึงส่วนใดของเว็บไซต์ รวมถึงการกำหนด sitemap และ host ของเว็บไซต์
     */
    public function robots(): Response
    {
        RobotsHelper::reset(); // เริ่มต้นใหม่ก่อนเพิ่มนโยบายใหม่

        $baseUrl = UrlHelper::base();
        $host = parse_url($baseUrl, PHP_URL_HOST) ?? ''; // ดึง host จาก base URL
        
        // ตรวจสอบว่า host ไม่ว่างเปล่าก่อนที่จะตั้งค่า host ใน RobotsHelper
        if ($host !== '') {
            RobotsHelper::setHost($host); // กำหนด host ของเว็บไซต์
        }

        RobotsHelper::setSitemap($baseUrl . '/sitemap.xml'); // กำหนด URL ของไฟล์ sitemap.xml
        RobotsHelper::allow('/'); // อนุญาตให้บอทเข้าถึงทุกหน้า

        $text = RobotsHelper::renderText(); // สร้างข้อความสำหรับไฟล์ robots.txt

        return new Response($text, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
