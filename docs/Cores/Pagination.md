# คู่มือการใช้งานคลาส Pagination

คลาส Pagination ใช้สำหรับจัดการการแบ่งหน้าข้อมูล (Pagination) เหมาะกับกรณีที่มีข้อมูลจำนวนมาก เช่น รายการสินค้า รายชื่อพนักงาน หรือข้อมูลในระบบหลังบ้าน

## รองรับ
- คำนวณจำนวนหน้าอัตโนมัติ
- สร้าง Offset สำหรับ SQL
- แสดง Pagination แบบ Bootstrap
- แสดงแบบ Simple (Previous / Next)
- ส่งออกเป็น Array หรือ JSON (เหมาะกับ API)

---

## 1️⃣ การใช้งานพื้นฐาน
**ขั้นตอนที่ 1:** รับค่าหน้าปัจจุบันจาก URL
```php
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
```
**ขั้นตอนที่ 2:** กำหนดค่าพื้นฐาน
```php
$totalItems = 150;   // จำนวนข้อมูลทั้งหมดในฐานข้อมูล
$perPage = 10;       // จำนวนรายการต่อหน้า
```
**ขั้นตอนที่ 3:** สร้าง Pagination
```php
use App\Core\Pagination;

$pagination = new Pagination(
    totalItems: $totalItems,
    perPage: $perPage,
    currentPage: $page,
    baseUrl: '/products'
);
```
**ขั้นตอนที่ 4:** ใช้ Offset กับ SQL
```php
$offset = $pagination->getOffset();
$sql = "SELECT * FROM products LIMIT {$perPage} OFFSET {$offset}";
```
**ขั้นตอนที่ 5:** แสดงผล Pagination
```php
echo $pagination->render();
```

---

## 2️⃣ ตัวอย่างการใช้งานจริงใน Controller
```php
use App\Core\Pagination;

$page = $_GET['page'] ?? 1;
$perPage = 10;
// สมมติว่าดึงจำนวนทั้งหมดจากฐานข้อมูลแล้ว
$totalItems = 120;

$pagination = new Pagination($totalItems, $perPage, $page);
// ดึงข้อมูลตามหน้า
$offset = $pagination->getOffset();
$products = $productModel->getAll($perPage, $offset);
// ส่งไป view
return view('products.index', [
    'products' => $products,
    'pagination' => $pagination
]);
```
**ใน View:**
```php
<?= $pagination->summary(); ?>
<?= $pagination->render(); ?>
```

---

## 3️⃣ การแสดงผลแบบ Simple (เหมาะกับ Mobile)
```php
echo $pagination->renderSimple();
```
**ผลลัพธ์:**

« ก่อนหน้า   2 / 10   ถัดไป »

---

## 4️⃣ การปรับแต่งจำนวนลิงก์หน้า
ค่า default คือ 5 หน้า
```php
$pagination->setLinksCount(7);
```

## 5️⃣ การกำหนด CSS Class เอง
```php
$pagination->setClasses(
    container: 'pagination justify-content-center',
    active: 'active',
    disabled: 'disabled'
);
```

## 6️⃣ เปลี่ยนข้อความปุ่ม
```php
$pagination->setButtonTexts(
    previous: 'Previous',
    next: 'Next'
);
```

## 7️⃣ ใช้กับ API (JSON Response)
```php
return response()->json([
    'data' => $products,
    'pagination' => $pagination->toArray()
]);
// หรือ
echo $pagination->toJson();
```

## 8️⃣ ดึงข้อมูลเป็น Array
```php
$info = $pagination->toArray();
print_r($info);
```
**ผลลัพธ์ตัวอย่าง:**
```php
[
    "current_page" => 2,
    "per_page" => 10,
    "total_pages" => 12,
    "total_items" => 120,
    "has_previous" => true,
    "has_next" => true,
    "previous_page" => 1,
    "next_page" => 3,
    "offset" => 10
]
```

## 9️⃣ แสดงข้อความสรุปจำนวนข้อมูล
```php
echo $pagination->summary();
```
**ตัวอย่างผลลัพธ์:**

แสดง 11 ถึง 20 จากทั้งหมด 120 รายการ

---

## 🔟 Flow การทำงานแบบเข้าใจง่าย
1. รับ page จาก URL
2. สร้าง Pagination
3. ใช้ getOffset() กับ SQL
4. แสดงข้อมูล
5. แสดง $pagination->render()
6. จบกระบวนการ

---

## 🔥 Best Practice แนะนำ
- อย่าใช้ $_GET ตรง ๆ ใน Model
- ให้ Controller เป็นคนสร้าง Pagination
- View มีหน้าที่แค่แสดงผล
- ถ้าเป็น API ใช้ toArray() แทน render()

---

## 🎯 สรุป
ใช้ Pagination เมื่อ:
- มีข้อมูลจำนวนมาก
- ต้องการแบ่งหน้า
- ต้องการรองรับทั้ง Web และ API
- ต้องการควบคุม URL และ Query String
