# SimpleBiz Framework - SEO Guide

เอกสารนี้สรุปการตั้งค่า SEO แบบครบถ้วนสำหรับโปรเจกต์ที่ใช้ SimpleBiz Framework
เน้นการใช้งานด้วย Helper แบบง่าย พร้อมแนวทางปรับแต่งให้เหมาะกับงานจริง

------------------------------------------------------------------------

# 1) เป้าหมายของ SEO ในระบบนี้

- มี meta tags ที่ถูกต้อง (title, description, canonical)
- รองรับ Open Graph และ Twitter Card
- มี sitemap.xml และ robots.txt
- รองรับ JSON-LD สำหรับ Structured Data
- URLs เป็นมิตรและหลีกเลี่ยงเนื้อหาซ้ำ

------------------------------------------------------------------------

# 2) ภาพรวมเครื่องมือที่มี

- SeoHelper
  - title, description, keywords
  - canonical
  - og / twitter
  - json-ld
- SitemapHelper
  - สร้าง sitemap.xml แบบง่าย
- RobotsHelper
  - สร้าง robots.txt แบบง่าย
- UrlHelper
  - base, current, to และ asset versioned

------------------------------------------------------------------------

# 3) การใช้งาน SeoHelper พื้นฐาน

## ตั้งค่าใน Controller หรือ View ก่อน render

``` php
use App\Helpers\SeoHelper;
use App\Helpers\UrlHelper;

SeoHelper::setTitle('หน้าแรก');
SeoHelper::setDescription('คำอธิบายสั้น ๆ ของหน้า');
SeoHelper::setKeywords(['framework', 'php', 'mvc']);
SeoHelper::setCanonical(UrlHelper::current(false));

SeoHelper::setOg('image', UrlHelper::to('assets/img/og.png'));
SeoHelper::setTwitter('card', 'summary_large_image');
```

ระบบจะ render meta tags ให้ใน layout อัตโนมัติ

------------------------------------------------------------------------

# 4) Open Graph และ Twitter Card

``` php
SeoHelper::setOg('title', 'หัวข้อสำหรับแชร์');
SeoHelper::setOg('description', 'คำอธิบายสำหรับแชร์');
SeoHelper::setOg('image', UrlHelper::to('assets/img/og.png'));
SeoHelper::setOg('url', UrlHelper::current(false));

SeoHelper::setTwitter('card', 'summary_large_image');
SeoHelper::setTwitter('title', 'หัวข้อทวิตเตอร์');
SeoHelper::setTwitter('description', 'คำอธิบายทวิตเตอร์');
SeoHelper::setTwitter('image', UrlHelper::to('assets/img/og.png'));
```

------------------------------------------------------------------------

# 5) JSON-LD (Structured Data)

``` php
SeoHelper::addJsonLd([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'SimpleBiz',
    'url' => UrlHelper::base(),
]);
```

ตัวอย่าง Article

``` php
SeoHelper::addJsonLd([
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => 'บทความตัวอย่าง',
    'datePublished' => date('c'),
    'author' => [
        '@type' => 'Person',
        'name' => 'Admin'
    ]
]);
```

------------------------------------------------------------------------

# 6) Sitemap.xml

ระบบมี route:

- /sitemap.xml

และสามารถเพิ่ม URL ได้ใน controller ที่ให้บริการ sitemap

ตัวอย่างใน SeoController

``` php
SitemapHelper::addUrl('/', date('Y-m-d'), 'daily', '1.0');
SitemapHelper::addUrl('/employees', date('Y-m-d'), 'daily', '0.8');
```

ถ้าต้องการ sitemap แบบ dynamic ให้ดึงข้อมูลจากฐานข้อมูลแล้ว loop ใส่ URL

------------------------------------------------------------------------

# 7) Robots.txt

ระบบมี route:

- /robots.txt

ตัวอย่างตั้งค่า

``` php
RobotsHelper::disallow('/admin');
RobotsHelper::setSitemap(UrlHelper::base() . '/sitemap.xml');
```

------------------------------------------------------------------------

# 8) Canonical URL

- ควรตั้ง canonical ทุกหน้าเพื่อป้องกันเนื้อหาซ้ำ
- ระบบใน layout ตั้งค่าให้อัตโนมัติเป็น URL ปัจจุบัน (ไม่รวม query)

หากต้องการ override ให้เรียก

``` php
SeoHelper::setCanonical('https://example.com/custom');
```

------------------------------------------------------------------------

# 9) แนวทางสำหรับหน้า dynamic

- หน้าแบบ personalized ควรปิด cache หรือกำหนด TTL สั้น
- ถ้าเนื้อหาเปลี่ยนบ่อย ให้ตั้ง lastmod ใน sitemap ให้ตรงกับเวลาจริง
- ใช้ keyword เฉพาะหน้า ไม่ควรซ้ำกันทุกหน้า

------------------------------------------------------------------------

# 10) Checklist ก่อนเปิดใช้งานจริง

- มี title และ description ทุกหน้า
- มี canonical ทุกหน้า
- เปิดใช้งาน sitemap.xml และ robots.txt
- ตรวจสอบ Open Graph โดยใช้ตัวตรวจของ Facebook หรือ Twitter
- ตรวจสอบความเร็วและ Core Web Vitals

------------------------------------------------------------------------
