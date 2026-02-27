# Database Class Usage Guide

คลาส `App\Core\Database` คือระบบเชื่อมต่อฐานข้อมูลแบบ Singleton ที่ห่อหุ้ม PDO เพื่อให้ใช้งานง่าย ปลอดภัย และมีระบบบันทึก query ในตัว

รองรับ:

- MySQL
- SQLite
- Real Prepared Statements เท่านั้น
- Transaction
- Query Logging
- Error Handling แบบ Exception

## 1. แนวคิดหลักของคลาสนี้

ทำไมต้องใช้ Singleton

- มีการเชื่อมต่อฐานข้อมูลเพียงหนึ่งเดียวตลอด lifecycle
- ลด overhead การสร้าง connection ซ้ำ
- ควบคุมจุดเชื่อมต่อได้จากศูนย์กลาง

เรียกใช้งานผ่าน:

```php
use App\Core\Database;

$db = Database::getInstance();
```

## 2. การตั้งค่า Database

ไฟล์ config จะอยู่ที่: `config/database.php`

ตัวอย่าง MySQL:

```php
return [
    'connection' => 'mysql',
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'mydb',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];
```

ตัวอย่าง SQLite:

```php
return [
    'connection' => 'sqlite',
    'database' => 'storage/database.sqlite',
];
```

## 3. หลักความปลอดภัย

ห้ามทำแบบนี้:

```php
$sql = "SELECT * FROM users WHERE id = " . $_GET['id'];
```

ต้องทำแบบนี้เท่านั้น:

```php
$user = $db->fetch(
    "SELECT * FROM users WHERE id = :id",
    ['id' => $userId]
);
```

เหตุผล:

- ป้องกัน SQL Injection
- ใช้ Real Prepared Statements
- `PDO::ATTR_EMULATE_PREPARES = false`

## 4. เมธอดหลักที่ใช้บ่อย

### 4.1 `query()`

ใช้สำหรับรันคำสั่ง SQL ทั่วไป

```php
$stmt = $db->query(
    "SELECT * FROM users WHERE status = :status",
    ['status' => 'active']
);
```

### 4.2 `fetch()` – ดึง 1 แถว

```php
$user = $db->fetch(
    "SELECT * FROM users WHERE id = :id",
    ['id' => 1]
);
```

### 4.3 `fetchAll()` – ดึงหลายแถว

```php
$users = $db->fetchAll(
    "SELECT * FROM users WHERE status = :status",
    ['status' => 'active']
);
```

### 4.4 `fetchColumn()` – ดึงค่าเดียว

```php
$count = $db->fetchColumn(
    "SELECT COUNT(*) FROM users"
);
```

### 4.5 `fetchList()` – ดึงคอลัมน์แรกทั้งหมด

```php
$usernames = $db->fetchList(
    "SELECT username FROM users"
);
```

ผลลัพธ์:

```
['golf', 'admin', 'user01']
```

### 4.6 `fetchPairs()` – ดึง key/value

```php
$userMap = $db->fetchPairs(
    "SELECT id, username FROM users"
);
```

ผลลัพธ์:

```php
[
    1 => 'golf',
    2 => 'admin'
]
```

### 4.7 `execute()` – INSERT / UPDATE / DELETE

```php
$affected = $db->execute(
    "UPDATE users SET status = :status WHERE id = :id",
    [
        'status' => 'active',
        'id' => 1
    ]
);
```

คืนค่าจำนวนแถวที่ได้รับผลกระทบ

### 4.8 `lastInsertId()`

```php
$db->execute(
    "INSERT INTO users (username) VALUES (:username)",
    ['username' => 'newuser']
);

$id = $db->lastInsertId();
```

## 5. Transaction

แบบ Manual

```php
$db->beginTransaction();

try {
    $db->execute("UPDATE accounts SET balance = balance - 100 WHERE id = 1");
    $db->execute("UPDATE accounts SET balance = balance + 100 WHERE id = 2");

    $db->commit();
} catch (\Throwable $e) {
    $db->rollBack();
    throw $e;
}
```

แบบใช้ `transaction()` Wrapper (แนะนำ)

```php
$result = $db->transaction(function ($db) {

    $db->execute("UPDATE accounts SET balance = balance - 100 WHERE id = 1");
    $db->execute("UPDATE accounts SET balance = balance + 100 WHERE id = 2");

    return true;
});
```

ข้อดี:

- rollback อัตโนมัติเมื่อเกิด exception
- โค้ดสะอาดกว่า

## 6. Query Logging

เปิดใช้งาน:

```php
$db->enableQueryLog(true);
```

ดึง log:

```php
$log = $db->getQueryLog();
```

โครงสร้างข้อมูล:

```php
[
    'sql' => 'SELECT ...',
    'params' => [...],
    'time_ms' => 12.3,
    'ts' => 1700000000.123
]
```

ระบบจะ:

- เตือน slow query (> 500ms)
- log error เมื่อเกิด exception

## 7. ดึง PDO ดิบ

กรณีต้องการใช้ฟีเจอร์ขั้นสูง:

```php
$pdo = $db->getPdo();
```

## 8. ใช้งานกับ Model (แนวทางที่แนะนำ)

ตัวอย่าง `UserModel`:

```php
class UserModel
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id)
    {
        return $this->db->fetch(
            "SELECT * FROM users WHERE id = :id",
            ['id' => $id]
        );
    }
}
```

## 9. Best Practices

- ใช้ named parameters เสมอ (`:id`, `:email`)
- อย่าใช้ `execRaw()` กับ input จาก user
- ใช้ `transaction()` เมื่อมีหลายคำสั่งที่ต้อง atomic
- เปิด query log เฉพาะตอน debug
- แยก logic database ไว้ใน Model เท่านั้น

## 10. สรุป

คลาสนี้ออกแบบมาเพื่อ:

- ปลอดภัย
- ควบคุมง่าย
- รองรับ production
- มีระบบ log
- รองรับ transaction
- รองรับทั้ง MySQL และ SQLite

ถ้าใช้อย่างถูกต้อง คุณจะได้ระบบฐานข้อมูลที่:

- ป้องกัน SQL Injection
- Debug ง่าย
- โครงสร้างชัดเจน
- พร้อมขยายในอนาคต
