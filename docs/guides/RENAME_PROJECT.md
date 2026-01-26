# เปลี่ยนชื่อโปรเจค (Rename Project) — คู่มือสั้น

ขั้นตอนหลักเมื่อเปลี่ยนชื่อโปรเจค (หรือเปลี่ยน vendor/namespace):

1. ปรับ `composer.json`
	- แก้ค่า `name`, `description`, `authors` ตามต้องการ
	- ถ้าเปลี่ยน namespace ให้แก้ `autoload.psr-4` ให้ตรงกับโครงสร้างโฟลเดอร์

2. อัปเดตชื่อแอปในคอนฟิก
	- `config/app.php` หรือไฟล์คอนฟิกที่เก็บ `app_name` / `app_url`
	- `.env` : อัปเดต `APP_NAME` และค่าที่เกี่ยวข้อง

3. ปรับ namespace ของโค้ด (ถ้าจำเป็น)
	- ทำ search/replace สำหรับ namespace เดิมเป็น namespace ใหม่
	- ตรวจสอบ `app/` และ `modules/` ว่ามี `namespace` ที่ต้องเปลี่ยนหรือไม่

4. อัปเดตไฟล์ public / entry point
	- `public/index.php` หากมีการใช้ชื่อโปรเจคใน comments หรือ config ให้แก้

5. อัปเดต README, LICENSE, และเอกสารอื่น ๆ

6. รันคำสั่งหลังเปลี่ยนแปลง

```powershell
# ติดตั้ง/อัปเดต autoload ของ composer
composer dump-autoload

# ถ้าใช้ composer scripts หรือ dependencies ใหม่
composer install
```

7. ตรวจสอบการทำงาน
	- รันเซิร์ฟเวอร์พัฒนา และทดสอบ endpoint หลัก
	- รัน `composer test` หรือ `./vendor/bin/phpunit` หากมี

คำสั่ง PowerShell สำหรับค้นหาและแทนที่ namespace (ตัวอย่าง — ปรับคำสั่งให้ตรงกับกรณีของคุณ):

```powershell
# แสดงไฟล์ที่มีคำว่า Old\Namespace
Get-ChildItem -Recurse -Filter *.php | Select-String -Pattern 'Old\\Namespace' | Select-Object Path -Unique

# แทนที่แบบง่าย (สำรองไฟล์ก่อนใช้จริง)
Get-ChildItem -Recurse -Filter *.php | ForEach-Object {
  (Get-Content $_.FullName) -replace 'Old\\Namespace', 'New\\Namespace' | Set-Content $_.FullName
}
```

ข้อควรระวัง:
- สำรอง repo (commit ทุกอย่างก่อน) ก่อนทำการเปลี่ยน namespace แบบ mass-replace
- ตรวจสอบว่า composer autoload ถูกตั้งค่าอย่างถูกต้องหลังแก้

หากต้องการ ผมช่วยค้นหา `Old\` namespace ที่อยู่ในโครงการ และสร้างรายการไฟล์ที่ต้องเปลี่ยนให้ก่อนจะทำการแทนที่จริง
````markdown
# เปลี่ยนชื่อโปรเจค Framework

(เอกสารย้ายไปยัง `docs/guides/RENAME_PROJECT.md`)

````