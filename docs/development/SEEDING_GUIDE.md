# คู่มือการใช้ Seeder (ภาษาไทย)

เอกสารนี้อธิบายการสร้างและรัน Seeder เพื่อเติมข้อมูลตัวอย่างในฐานข้อมูลของโปรเจค

---

## โครงสร้างและตำแหน่งไฟล์
- Seeder ปกติอยู่ในโฟลเดอร์ `database/seeders/` หรือ `seeders/` ตามโครงงาน
- แต่ละ Seeder เป็นคลาสที่มีเมธอด `run()` สำหรับเขียนคำสั่ง insert

---

## ตัวอย่าง Seeder (แบบง่าย)

```php
<?php

class UserSeeder extends Seeder
{
    public function run()
    {
        $users = [
            ['name' => 'Admin', 'email' => 'admin@example.com', 'password' => password_hash('password', PASSWORD_DEFAULT)],
            ['name' => 'Demo', 'email' => 'demo@example.com', 'password' => password_hash('demo', PASSWORD_DEFAULT)],
        ];

        foreach ($users as $u) {
            // ตัวอย่าง: ใช้ DB facade หรือ Model ในโปรเจค
            \App\Models\User::create($u);
        }
    }
}
```

---

## รัน Seeder
- ผ่าน CLI ของโปรเจค:

```bash
php console seed
# หรือระบุ seeder เฉพาะ
php console seed --class=UserSeeder
```

---

## คำแนะนำ
- อย่าใช้ข้อมูลจริง (เช่น รหัสผ่านจริง) ใน seeders ที่เก็บใน repo
- สำหรับ environment production พิจารณาไม่รัน seeders อัตโนมัติ หรือแยก seeders สำหรับ test/dev
