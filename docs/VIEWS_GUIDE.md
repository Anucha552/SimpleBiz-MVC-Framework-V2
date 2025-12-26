# คู่มือการใช้งาน Views

## โครงสร้าง Views

```
app/Views/
├── layouts/           # Layout templates
│   └── main.php      # Layout หลัก
├── home/             # Views สำหรับหน้าแรก
├── products/         # Views สำหรับสินค้า
├── cart/             # Views สำหรับตะกร้า
├── orders/           # Views สำหรับคำสั่งซื้อ
└── auth/             # Views สำหรับ Authentication
```

## การใช้งานใน Controller

### 1. แสดงผล View แบบธรรมดา
```php
use App\Core\View;

public function index(): void
{
    $view = new View('home/index');
    $view->show();
}
```

### 2. ส่งข้อมูลไปยัง View
```php
$view = new View('products/index', [
    'products' => $products,
    'total' => count($products)
]);
$view->show();
```

### 3. ใช้ Layout
```php
$view = new View('products/index', ['products' => $products]);
$view->layout('main')->show();
```

## การเขียน View Template

### ไฟล์ View พื้นฐาน
```php
<!-- app/Views/products/index.php -->
<h1>สินค้าทั้งหมด</h1>

<?php foreach ($products as $product): ?>
    <div>
        <h3><?= htmlspecialchars($product['name']) ?></h3>
        <p><?= number_format($product['price'], 2) ?> บาท</p>
    </div>
<?php endforeach; ?>
```

### ใช้ร่วมกับ Layout

**สร้าง Layout** (app/Views/layouts/main.php):
```php
<!DOCTYPE html>
<html>
<head>
    <title><?= $this->yieldSection('title', 'SimpleBiz') ?></title>
    <?= $this->yieldSection('head', '') ?>
</head>
<body>
    <header>
        <!-- Navigation -->
    </header>
    
    <main>
        <?= $this->yieldSection('content') ?>
    </main>
    
    <footer>
        <!-- Footer -->
    </footer>
    
    <?= $this->yieldSection('scripts', '') ?>
</body>
</html>
```

**ใช้ Layout ในไฟล์ View**:
```php
<!-- app/Views/products/show.php -->

<?php $this->section('title'); ?>
<?= htmlspecialchars($product['name']) ?> - SimpleBiz
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<h1><?= htmlspecialchars($product['name']) ?></h1>
<p><?= htmlspecialchars($product['description']) ?></p>
<p>ราคา: ฿<?= number_format($product['price'], 2) ?></p>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
console.log('Product page loaded');
</script>
<?php $this->endSection(); ?>
```

## Sections

### Section ที่รองรับใน Layout หลัก

| Section | จุดประสงค์ | Required |
|---------|-----------|----------|
| `title` | Title ของหน้า | ❌ |
| `content` | เนื้อหาหลักของหน้า | ✅ |
| `styles` | CSS เพิ่มเติม | ❌ |
| `head` | HTML เพิ่มเติมใน `<head>` | ❌ |
| `scripts` | JavaScript เพิ่มเติม | ❌ |

### วิธีใช้ Section

```php
<?php $this->section('title'); ?>
หน้าแรก - SimpleBiz
<?php $this->endSection(); ?>

<?php $this->section('styles'); ?>
.custom-class {
    color: blue;
}
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<h1>เนื้อหาหลัก</h1>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
alert('Hello!');
</script>
<?php $this->endSection(); ?>
```

## การเข้าถึงข้อมูล

### ข้อมูลที่ส่งจาก Controller
```php
// Controller
$view = new View('products/index', [
    'products' => $products,
    'category' => 'Electronics'
]);

// View - เข้าถึงได้โดยตรง
<?php foreach ($products as $product): ?>
    <p>หมวดหมู่: <?= $category ?></p>
<?php endforeach; ?>
```

### Session Data
```php
<?php if (isset($_SESSION['user_id'])): ?>
    <p>สวัสดี, <?= htmlspecialchars($_SESSION['username']) ?></p>
<?php endif; ?>
```

### Flash Messages
```php
<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert">
        <?= htmlspecialchars($_SESSION['flash_message']) ?>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>
```

## Best Practices

### 1. ใช้ htmlspecialchars() เสมอ
```php
<!-- ❌ ไม่ดี -->
<h1><?= $product['name'] ?></h1>

<!-- ✅ ดี -->
<h1><?= htmlspecialchars($product['name']) ?></h1>
```

### 2. แยก Logic กับ Presentation
```php
<!-- ❌ ไม่ดี - Logic มากเกินไป -->
<?php
$filteredProducts = array_filter($products, function($p) {
    return $p['price'] > 100 && $p['stock'] > 0;
});
?>

<!-- ✅ ดี - Logic อยู่ใน Controller -->
<?php foreach ($products as $product): ?>
    <!-- Display only -->
<?php endforeach; ?>
```

### 3. ใช้ Alternative Syntax
```php
<!-- ✅ ดี - อ่านง่าย -->
<?php if ($condition): ?>
    <p>Content</p>
<?php endif; ?>

<?php foreach ($items as $item): ?>
    <li><?= $item ?></li>
<?php endforeach; ?>
```

### 4. แยกไฟล์ตามความรับผิดชอบ
```
products/
├── index.php      # รายการสินค้า
├── show.php       # รายละเอียดสินค้า
├── create.php     # ฟอร์มเพิ่มสินค้า
└── edit.php       # ฟอร์มแก้ไขสินค้า
```

## ตัวอย่างการใช้งานจริง

### หน้า Product List
```php
// Controller
public function index(): void
{
    $products = $this->productModel->getAll();
    
    $view = new View('products/index', [
        'products' => $products
    ]);
    $view->layout('main')->show();
}
```

```php
<!-- View: app/Views/products/index.php -->
<?php $this->section('title'); ?>
สินค้าทั้งหมด - SimpleBiz
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<h1>สินค้าทั้งหมด</h1>

<?php if (empty($products)): ?>
    <p>ยังไม่มีสินค้า</p>
<?php else: ?>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <h3><?= htmlspecialchars($product['name']) ?></h3>
                <p>฿<?= number_format($product['price'], 2) ?></p>
                <a href="/products/<?= $product['id'] ?>">ดูรายละเอียด</a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php $this->endSection(); ?>
```

### หน้า Login
```php
// Controller
public function showLogin(): void
{
    $view = new View('auth/login');
    $view->layout('main')->show();
}
```

```php
<!-- View: app/Views/auth/login.php -->
<?php $this->section('title'); ?>
เข้าสู่ระบบ
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<h1>เข้าสู่ระบบ</h1>

<?php if (isset($error)): ?>
    <div class="alert alert-error">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<form method="POST" action="/login">
    <input type="email" name="email" required>
    <input type="password" name="password" required>
    <button type="submit">เข้าสู่ระบบ</button>
</form>
<?php $this->endSection(); ?>
```

## สรุป

1. **สร้าง View** → `new View('path/to/view', $data)`
2. **เลือก Layout** → `$view->layout('main')`
3. **แสดงผล** → `$view->show()`
4. **ใน View** → ใช้ Sections เพื่อส่งเนื้อหาไป Layout
5. **ความปลอดภัย** → ใช้ `htmlspecialchars()` เสมอ
