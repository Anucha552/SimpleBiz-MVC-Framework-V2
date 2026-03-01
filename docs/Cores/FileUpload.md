# FileUpload Class Guide

คลาส `FileUpload` ใช้สำหรับจัดการการอัปโหลดไฟล์อย่างปลอดภัยในระบบของคุณ รองรับการตรวจสอบชนิดไฟล์, ขนาดไฟล์, สร้างชื่อไฟล์ที่ปลอดภัย และอัปโหลดหลายไฟล์

## 1. การใช้งานพื้นฐาน (อัปโหลด 1 ไฟล์)

### Step 1: สร้างฟอร์ม HTML
```html
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="document">
    <button type="submit">Upload</button>
</form>
```
> **หมายเหตุ:** ต้องมี `enctype="multipart/form-data"` เสมอ

### Step 2: เขียนโค้ดใน Controller
```php
use App\Core\FileUpload;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploader = new FileUpload();
    $uploader->setAllowedTypes(['jpg', 'png', 'pdf'])
             ->setMaxSize(5 * 1024 * 1024) // 5MB
             ->setUploadPath('uploads/documents');

    if ($uploader->upload('document')) {
        echo "อัปโหลดสำเร็จ: " . $uploader->getUploadedFileName();
        echo "<br>Path: " . $uploader->getUploadedFilePath();
    } else {
        echo "เกิดข้อผิดพลาด: " . $uploader->getError();
    }
}
```

## 2. อัปโหลดหลายไฟล์

### HTML
```html
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="files[]" multiple>
    <button type="submit">Upload</button>
</form>
```

### PHP
```php
use App\Core\FileUpload;

$uploader = new FileUpload();
$uploader->setAllowedTypes(['jpg', 'png'])
         ->setMaxSize(2 * 1024 * 1024)
         ->setUploadPath('uploads/images');

$result = $uploader->uploadMultiple('files');

if (!empty($result['success'])) {
    echo "ไฟล์ที่อัปโหลดสำเร็จ:<br>";
    foreach ($result['success'] as $file) {
        echo "- {$file}<br>";
    }
}

if (!empty($result['failed'])) {
    echo "<br>ไฟล์ที่ล้มเหลว:<br>";
    foreach ($result['failed'] as $failed) {
        echo "- {$failed['file']} : {$failed['error']}<br>";
    }
}
```

## 3. กำหนดชื่อไฟล์เอง
```php
$uploader->upload('document', 'my_report.pdf');
```
- นามสกุลต้องตรงกับไฟล์จริง
- ระบบจะ sanitize ชื่อให้ปลอดภัย
- ป้องกัน path traversal โดยอัตโนมัติ

## 4. ดึงข้อมูลหลังอัปโหลด
```php
$uploader->getUploadedFileName();   // ชื่อไฟล์ใหม่
$uploader->getUploadedFilePath();   // path เต็ม
$uploader->getFileSize();           // ขนาดไฟล์ (bytes)
$uploader->getFileExtension();      // นามสกุลไฟล์
$uploader->getFileData();           // ข้อมูลทั้งหมดจาก $_FILES
```

## 5. ลบไฟล์
```php
use App\Core\FileUpload;
FileUpload::deleteFile('uploads/documents/file.pdf');
```

## 6. ตรวจสอบว่าเป็นรูปภาพหรือไม่
```php
if (FileUpload::isImage('uploads/images/photo.jpg')) {
    echo "เป็นรูปภาพ";
}
```

## 7. แสดงขนาดไฟล์แบบอ่านง่าย
```php
echo FileUpload::formatFileSize(1048576); // Output: 1 MB
```

---

## 🔒 ระบบความปลอดภัยที่มีในคลาสนี้
- ตรวจสอบ is_uploaded_file
- ตรวจสอบขนาดไฟล์
- ตรวจสอบนามสกุล
- ตรวจสอบ MIME type ด้วย finfo
- ป้องกัน path traversal
- sanitize ชื่อไฟล์
- สร้างโฟลเดอร์อัตโนมัติ
- ป้องกันไฟล์ขนาด 0 byte

---

## 📌 แนวทางแนะนำสำหรับ Framework ของกอล์ฟ

ในแนวคิด Full Framework ของกอล์ฟ แนะนำให้ใช้ FileUpload แบบนี้ใน Controller เท่านั้น อย่าเขียนโค้ด upload ใน View หรือ Model

**ตัวอย่างโครงสร้างที่ถูกต้อง:**

- Controller
  - รับ Request
  - เรียก FileUpload
  - บันทึกชื่อไฟล์ลง Model
  - Redirect
- Model
  - จัดการฐานข้อมูลเท่านั้น

---

## 🎯 Best Practice
- แยกโฟลเดอร์ตามประเภทไฟล์ เช่น `uploads/images`, `uploads/documents`
- ห้ามเก็บไฟล์ไว้ใน public root ตรง ๆ ถ้าเป็นไฟล์สำคัญ
- จำกัดชนิดไฟล์ทุกครั้ง ห้ามปล่อยว่าง
- อย่าพึ่ง extension อย่างเดียว ต้องเช็ค MIME ด้วยเสมอ
