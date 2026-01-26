```markdown
# Environment Config Matrix

ตารางสรุปค่าที่ควรใช้ตามสภาพแวดล้อม

﻿# การตั้งค่าสภาพแวดล้อม (Environment)

ตารางสรุปค่าที่แนะนำตามสภาพแวดล้อมของระบบ

| ตัวแปร | Development | Staging | Production |
|---|---:|---:|---:|
| `APP_ENV` | `development` | `staging` | `production` |
| `APP_DEBUG` | `true` | `false` | `false` |
| `APP_URL` | `http://localhost` | `https://staging.example.com` | `https://example.com` |
| `DB_HOST` / `DB_DATABASE` | ระบุฐานข้อมูล dev | ระบุฐานข้อมูล staging | ระบุฐานข้อมูล production |
| `DB_USERNAME` / `DB_PASSWORD` | ใช้บัญชีทดสอบ | ใช้บัญชี staging | ใช้บัญชี production (ปลอดภัย) |
| `MAIL_HOST` / `MAIL_USERNAME` / `MAIL_PASSWORD` | ใช้ SMTP dev หรือ log driver | ใช้ SMTP ทดสอบ | ใช้ SMTP ของ production |
| `API_KEY` | key สำหรับ dev | key สำหรับ staging | key สำหรับ production |

หมายเหตุสำคัญ:
- ห้าม commit ค่า credentials หรือ secret ขึ้นระบบควบคุมเวอร์ชัน
- ใช้เครื่องมือจัดการ secret (Vault, AWS Secrets Manager) หรือจัดเก็บค่าลับใน environment ของ server/CI
- ใน `production` ให้ตั้ง `APP_DEBUG=false` เสมอ

ตัวอย่าง `.env` (Production - ตัวอย่าง):

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=prod_db
DB_USERNAME=prod_user
DB_PASSWORD=secure_password_here

MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=mailer@example.com
MAIL_PASSWORD=mail_password_here
MAIL_ENCRYPTION=tls

CACHE_DRIVER=file
SESSION_DRIVER=file
```

หมายเหตุ: อย่า commit ค่า credentials จริงขึ้น repo

```