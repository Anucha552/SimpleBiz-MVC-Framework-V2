<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\ConsoleColor;

class HelpCommand extends BaseCommand
{
    public function name(): string
    {
        return 'help';
    }

    protected function execute(array $args): void
    {
        echo ConsoleColor::CYAN . ConsoleColor::BOLD . "SimpleBiz MVC Framework - คู่มือการใช้งาน\n" . ConsoleColor::RESET;
        echo ConsoleColor::WHITE . "เฟรมเวิร์ก SimpleBiz สำหรับพัฒนาเว็บไซต์ที่ออกแบบมาสำหรับนักพัฒนาไทย\n" . ConsoleColor::RESET;
        echo "\n";

        echo ConsoleColor::YELLOW . "━━━ ตรวจสอบระบบ ━━━\n" . ConsoleColor::RESET;
        echo ConsoleColor::GREEN . "  check" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    ตรวจสอบความพร้อมของระบบและสภาพแวดล้อม\n";
        echo "    - PHP version และ extensions\n";
        echo "    - Composer dependencies\n";
        echo "    - ไฟล์และโฟลเดอร์จำเป็น\n";
        echo "    - Permissions ของ storage/\n";
        echo "    - Configuration files (.env)\n";
        echo ConsoleColor::CYAN . "    แนะนำ: รันคำสั่งนี้หลัง git clone ครั้งแรก\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::YELLOW . "━━━ ตั้งค่าและคอนฟิก ━━━\n" . ConsoleColor::RESET;
        echo ConsoleColor::GREEN . "  setup" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    ตั้งค่าโปรเจคใหม่ทั้งหมด (interactive)\n";
        echo "    - เปลี่ยนชื่อโปรเจค\n";
        echo "    - สร้างไฟล์ .env และ APP_KEY\n";
        echo "    - ตั้งค่า database connection\n";
        echo "    - จัดการ Git repository (optional)\n\n";

        echo ConsoleColor::GREEN . "  serve " . ConsoleColor::CYAN . "[host] [port]" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    เริ่มเซิร์ฟเวอร์สำหรับพัฒนา\n";
        echo "    ค่าเริ่มต้น: localhost:8000\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console serve\n";
        echo "              php console serve 0.0.0.0 8080\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  key:generate" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    สร้าง APP_KEY ใหม่สำหรับ .env\n";
        echo "    - ใช้สำหรับการเข้ารหัสข้อมูล\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console key:generate\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::YELLOW . "━━━ จัดการฐานข้อมูล ━━━\n" . ConsoleColor::RESET;
        echo ConsoleColor::GREEN . "  db:show" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    แสดงรายการตารางทั้งหมดในฐานข้อมูล\n";
        echo "    พร้อมจำนวนแถวในแต่ละตาราง\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console db:show\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  db:table " . ConsoleColor::CYAN . "<table_name>" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    แสดงโครงสร้างของตาราง\n";
        echo "    - Columns (ชื่อ, ชนิด, null, key, default)\n";
        echo "    - Indexes และ relationships\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console db:table users\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::YELLOW . "━━━ Database Migrations ━━━\n" . ConsoleColor::RESET;
        echo ConsoleColor::GREEN . "  migrate " . ConsoleColor::CYAN . "[--path=module]" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    รัน migrations ทั้งหมดหรือเฉพาะ module\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console migrate\n";
        echo "              php console migrate --path=core\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  migrate:rollback " . ConsoleColor::CYAN . "[n]" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    ย้อนกลับ migration ตามจำนวน batch\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console migrate:rollback\n";
        echo "              php console migrate:rollback 3\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  migrate:batch" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    แสดงรายการ batch migrations ที่มีอยู่\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console migrate:batch\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  migrate:fresh" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    ลบตารางทั้งหมดและรัน migrations ใหม่\n";
        echo ConsoleColor::RED . "    [WARNING] จะลบข้อมูลทั้งหมด!\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  migrate:reset" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    Rollback migrations ทั้งหมด\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console migrate:reset\n";
        echo "              php console migrate:reset --force\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  migrate:refresh " . ConsoleColor::CYAN . "[--seed]" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    Reset และ migrate ใหม่ทั้งหมด\n";
        echo "    ใส่ --seed เพื่อ seed ข้อมูลหลัง migrate\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console migrate:refresh\n";
        echo "              php console migrate:refresh --seed\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  migrate:status" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    แสดงสถานะ migrations (รันแล้ว/ยังไม่รัน)\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console migrate:status\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  migrate:create " . ConsoleColor::CYAN . "<name>" . " " .  ConsoleColor::CYAN . "[module]" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    สร้างไฟล์ migration ใหม่\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console migrate:create create_posts_table\n" . ConsoleColor::RESET;
        echo ConsoleColor::CYAN . "              php console migrate:create create_posts_table blog\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  migrate:modules" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    แสดงรายการ migration modules ที่มี\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console migrate:modules\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::YELLOW . "━━━ Database Seeding ━━━\n" . ConsoleColor::RESET;
        echo ConsoleColor::GREEN . "  seed " . ConsoleColor::CYAN . "[SeederName]" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    รัน seeders เพื่อเพิ่มข้อมูลตัวอย่าง\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console seed\n";
        echo "              php console seed UserSeeder\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  seed:show" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    แสดงรายการ seeder ที่มีอยู่\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console seed:show\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::YELLOW . "━━━ สร้างโค้ดอัตโนมัติ (Generators) ━━━\n" . ConsoleColor::RESET;
        echo ConsoleColor::GREEN . "  make:controller " . ConsoleColor::CYAN . "<name>" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    สร้าง Controller class ใหม่\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console make:controller ProductController\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  make:model " . ConsoleColor::CYAN . "<name>" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    สร้าง Model class ใหม่\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console make:model Product\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  make:middleware " . ConsoleColor::CYAN . "<name>" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    สร้าง Middleware class ใหม่\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console make:middleware CheckAdmin\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  make:seeder " . ConsoleColor::CYAN . "<name>" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    สร้าง Seeder class ใหม่\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console make:seeder ProductSeeder\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  make:test " . ConsoleColor::CYAN . "<name> [--unit|--feature]" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    สร้าง Test class ใหม่\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console make:test StringHelperTest --unit\n";
        echo "              php console make:test UserRegistrationTest --feature\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::YELLOW . "━━━ Routes, Cache และ Optimization ━━━\n" . ConsoleColor::RESET;
        echo ConsoleColor::GREEN . "  route:list" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    แสดงรายการ routes ทั้งหมด\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console route:list\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  cache:clear" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    ลบ cache ทั้งหมด\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console cache:clear\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  cache:warm" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    เตรียม cache ล่วงหน้า (routes + config)\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console cache:warm\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  config:cache" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    Cache configuration files\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console config:cache\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  view:cache" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    Compile view files ล่วงหน้า\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console view:cache\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  optimize" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    เพิ่มประสิทธิภาพแอปพลิเคชันสำหรับ production\n";
        echo "    - ลบ cache เก่า + Cache routes + สร้าง optimization flags\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console optimize\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  optimize:clear" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    ลบ optimization caches (กลับสู่ development mode)\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console optimize:clear\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::YELLOW . "━━━ Logs และ Maintenance ━━━\n" . ConsoleColor::RESET;
        echo ConsoleColor::GREEN . "  log:clear" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    ลบไฟล์ log ทั้งหมด หรือระบุโฟลเดอร์/ชื่อ/pattern ของไฟล์ที่จะลบ\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console log:clear\n" . ConsoleColor::CYAN . "             php console log:clear --force app.log 2026-01-20.log\n" . ConsoleColor::CYAN . "             php console log:clear test '*.log'\n" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::GREEN . "  log:prune" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    ลบไฟล์ log เก่าตาม LOG_RETENTION_DAYS (แบบวันปฏิทิน)\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console log:prune\n" . ConsoleColor::CYAN . "             php console log:prune test\n" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::GREEN . "  log:show" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    แสดงรายการไฟล์ในโฟลเดอร์ logs (รองรับโฟลเดอร์ย่อย/absolute path)\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console log:show\n" . ConsoleColor::CYAN . "             php console log:show test\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  log:tail " . ConsoleColor::CYAN . "[lines] [filename]" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    แสดง log บรรทัดล่าสุด (default: 50). ระบุไฟล์หรือโฟลเดอร์เพื่อดูไฟล์อื่น\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console log:tail\n";
        echo "              php console log:tail 100\n";
        echo "              php console log:tail 200 app.log\n";
        echo "              php console log:tail 200 test\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  down" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    เปิด Maintenance Mode\n";
        echo "    - เว็บไซต์จะแสดงหน้า maintenance\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console down\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  up" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    ปิด Maintenance Mode (กลับมาใช้งานปกติ)\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console up\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::YELLOW . "━━━ Testing ━━━\n" . ConsoleColor::RESET;
        echo ConsoleColor::GREEN . "  test " . ConsoleColor::CYAN . "[options]" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    รัน PHPUnit tests ทั้งหมด\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console test\n";
        echo "              php console test --filter TestName\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  test:unit " . ConsoleColor::CYAN . "[options]" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    รัน Unit tests อย่างเดียว (เร็ว, ไม่ต้องการ DB)\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console test:unit\n";
        echo "              php console t:u\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::GREEN . "  test:feature " . ConsoleColor::CYAN . "[options]" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    รัน Feature tests อย่างเดียว (ทดสอบระบบเต็มรูปแบบ)\n";
        echo ConsoleColor::CYAN . "    ตัวอย่าง: php console test:feature\n";
        echo "              php console t:f\n" . ConsoleColor::RESET . "\n";

        echo ConsoleColor::YELLOW . "━━━ Aliases (shortcuts) ━━━\n" . ConsoleColor::RESET;
        echo ConsoleColor::GREEN . "  m, m:r, m:f, m:reset, m:refresh, m:s, m:c, m:batch," . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    ย่อคำสั่ง migration (migrate, rollback, fresh, reset, refresh, status, create, batch)\n" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::GREEN . "  s" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    ย่อคำสั่ง serve (php console s)\n" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::GREEN . "  c:c" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    ย่อคำสั่ง cache:clear\n" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::GREEN . "  t, t:u, t:f" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "    ย่อคำสั่ง test (all/unit/feature)\n" . ConsoleColor::RESET;

        echo "\n";
    }
}
