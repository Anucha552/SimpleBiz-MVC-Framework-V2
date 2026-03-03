# SimpleBiz Framework --- View System Guide

เอกสารนี้อธิบายการใช้งานระบบ View ของ SimpleBiz Framework
ตั้งแต่พื้นฐานจนถึงขั้นสูง\
View มีหน้าที่แสดงผลเท่านั้น ไม่ควรมี Business Logic
หรือการเชื่อมต่อฐานข้อมูลอยู่ภายใน

------------------------------------------------------------------------

# 1) การสร้าง View และแสดงผล

## ตัวอย่างใน Controller

``` php
use App\Core\View;

public function index()
{
    $view = new View('home/index', [
        'title' => 'Dashboard',
        'user'  => 'Golf'
    ]);

    $view->show();
}
```

## โครงสร้างไฟล์

    app/
     └── Views/
          └── home/
               └── index.php

## ภายในไฟล์ home/index.php

``` php
<h1><?= $this->e($title) ?></h1>
<p>Welcome <?= $this->e($user) ?></p>
```

ควรใช้ `$this->e()` ทุกครั้งเมื่อแสดงข้อมูลจากผู้ใช้ เพื่อป้องกัน XSS

------------------------------------------------------------------------

# 2) Layout System

## กำหนด Layout ใน Controller

``` php
(new View('home/index', [
    'title' => 'Dashboard'
]))
->layout('main')
->show();
```

## โครงสร้าง

    Views/
     ├── home/index.php
     └── layouts/main.php

## layouts/main.php

``` php
<html>
<head>
    <title><?= $this->yield('title', 'Default Title') ?></title>
</head>
<body>
    <?= $this->yield('content') ?>
</body>
</html>
```

## home/index.php

``` php
<?php $this->section('title'); ?>
Dashboard
<?php $this->endSection(); ?>

<h1>Welcome to Dashboard</h1>
```

ระบบจะ inject section ชื่อ `content` ให้อัตโนมัติ

------------------------------------------------------------------------

# 3) Sections

ใช้กำหนดพื้นที่เฉพาะใน layout

## ใน View

``` php
<?php $this->section('sidebar'); ?>
<ul>
    <li>Menu 1</li>
    <li>Menu 2</li>
</ul>
<?php $this->endSection(); ?>
```

## ใน Layout

``` php
<?= $this->yield('sidebar', '') ?>
```

------------------------------------------------------------------------

# 4) Slots (สำหรับ Component)

ใช้ส่งเนื้อหาเข้า Component

## ใน View หลัก

``` php
<?php $this->slot('header'); ?>
<h2>Card Title</h2>
<?php $this->endSlot(); ?>

<?= $this->component('components/card') ?>
```

## components/card.php

``` php
<div class="card">
    <?= $this->renderSlot('header') ?>
</div>
```

------------------------------------------------------------------------

# 5) Partial และ Component

## Partial (แสดงผลทันที)

``` php
$this->partial('partials/navbar');
```

## Component (คืนค่าเป็น string)

``` php
<?= $this->component('components/button', [
    'text' => 'Save'
]) ?>

## includeView (รวมไฟล์ผ่าน View system)

ใช้เมื่ออยาก include view ย่อย และให้ระบบ cache/compose จัดการให้

``` php
$this->includeView('partials/navbar', ['title' => 'My Page']);
```
```

------------------------------------------------------------------------

# 6) Shared Data (Global Data)

กำหนดข้อมูลที่ทุก View ใช้งานได้

``` php
View::share('appName', 'SimpleBiz');
```

ใน View

``` php
<h1><?= $this->e($appName) ?></h1>
```

------------------------------------------------------------------------

# 7) View Composer

เพิ่มข้อมูลเข้า View อัตโนมัติ

``` php
View::composer('home/*', function ($view, $data) {
    return [
        'year' => date('Y')
    ];
});
```

ใน View

``` php
<footer>© <?= $year ?></footer>
```

รองรับ wildcard เช่น `home/*`

หมายเหตุ: `includeView()` จะเรียก composer ด้วยเหมือน view ปกติ

------------------------------------------------------------------------

# 8) Cache View

ใช้เฉพาะ production (debug = false)

``` php
(new View('home/index'))
    ->cache(60)
    ->show();
```

ระบบสร้าง cache key จาก

-   ชื่อ view\
-   ข้อมูลที่ส่งเข้า view\
-   เวลาแก้ไขไฟล์ view\
-   เวลาแก้ไข layout

------------------------------------------------------------------------

# 9) Debug Mode

กำหนดผ่าน Config เช่น `app.debug`

-   debug = true → แสดง error แบบละเอียด\
-   debug = false → แสดงข้อความ error แบบปลอดภัย

ตรวจสอบสถานะได้ด้วย

``` php
View::isDebug();
```

------------------------------------------------------------------------

# 10) Nested Layout

สามารถซ้อน layout ได้

``` php
$this->layout('base');
```

ระบบป้องกันการซ้อนเกิน 10 ชั้นโดยอัตโนมัติ

------------------------------------------------------------------------

# 11) Escape Output

``` php
<?= $this->e($value) ?>
```

ภายในใช้ `htmlspecialchars()` พร้อม `ENT_QUOTES` และ `UTF-8`

------------------------------------------------------------------------

# 12) โครงสร้างโฟลเดอร์แนะนำ

    Views/
     ├── layouts/
     │    ├── main.php
     │    └── base.php
     ├── components/
     ├── partials/
     └── home/

------------------------------------------------------------------------

# Best Practices

1.  ห้ามเขียน Business Logic ใน View\
2.  ห้าม Query Database ใน View\
3.  ใช้ `$this->e()` เสมอกับข้อมูลที่ไม่แน่ใจ\
4.  เปิด Cache เฉพาะ Production\
5.  ใช้ Composer สำหรับข้อมูลที่ใช้ซ้ำหลายหน้า

------------------------------------------------------------------------

SimpleBiz View System --- Production Ready
