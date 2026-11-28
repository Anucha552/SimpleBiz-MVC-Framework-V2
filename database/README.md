# โครงสร้าง Migration Modules

โครงสร้าง migration ปัจจุบันของโปรเจกต์นี้:

```text
database/migrations/
├── system/      - users, roles, user_roles, role_permissions, user_permissions (5 migrations)
└── employee/    - departments, employees (2 migrations)
```

## คำสั่งที่ใช้บ่อย

### ดูรายการโมดูล

```bash
php console migrate:modules
```

### รัน migration ทั้งหมด

```bash
php console migrate
```

### รันเฉพาะโมดูล

```bash
php console migrate --path=system
php console migrate --path=employee
```

### ตรวจสอบสถานะ

```bash
php console migrate:status
```

### Rollback

```bash
php console migrate:rollback
php console migrate:rollback 3
```

### รีเซ็ตฐานข้อมูลและรันใหม่

```bash
php console migrate:fresh --force
```

## รายละเอียดโมดูล

### System Module
**ไฟล์:** `database/migrations/system/`

| Migration | ตาราง |
|-----------|-------|
| 2024_01_01_000001_create_users_table | users |
| 2024_01_01_000002_create_roles_table | roles |
| 2024_01_01_000003_create_user_roles_table | user_roles |
| 2024_01_01_000004_create_role_permissions_table | role_permissions |
| 2024_01_01_000005_create_user_permissions_table | user_permissions |

### Employee Module
**ไฟล์:** `database/migrations/employee/`

| Migration | ตาราง |
|-----------|-------|
| 2026_02_21_124720_departments | departments |
| 2026_02_21_124736_employees | employees |

## Dependency ที่สำคัญ

- `employees.department_id` อ้างอิง `departments.id`
- `user_roles.user_id` อ้างอิง `users.id`
- `user_roles.role_id` อ้างอิง `roles.id`
- `role_permissions.role_id` อ้างอิง `roles.id`
- `user_permissions.user_id` อ้างอิง `users.id`
- `users.role_id` อ้างอิง `roles.id`
