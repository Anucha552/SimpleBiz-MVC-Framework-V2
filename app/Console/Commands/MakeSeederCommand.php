<?php
/**
 * MakeSeederCommand
 *
 * จุดประสงค์: คำสั่งสำหรับสร้าง Seeder ใหม่ในโครงสร้างของแอปพลิเคชัน
 */

declare(strict_types=1);

namespace App\Console\Commands;

class MakeSeederCommand extends BaseCommand
{
    public function name(): string
    {
        return 'make:seeder';
    }

    protected function execute(array $args): void
    {
        if (empty($args)) {
            $this->error("กรุณาระบุชื่อ seeder");
            $this->info("วิธีใช้: php console make:seeder SeederName");
            return;
        }

        $name = $args[0];
        if (!str_ends_with($name, 'Seeder')) {
            $name .= 'Seeder';
        }

        $path = $this->path("database/seeders/{$name}.php");

        if (file_exists($path)) {
            $this->error("Seeder นี้มีอยู่แล้ว!");
            return;
        }

        $template = $this->getSeederTemplate($name);
        file_put_contents($path, $template);

        $this->success("สร้าง Seeder สำเร็จ: database/seeders/{$name}.php");

        $this->ensureDatabaseSeederExists($name);

        echo "\n";
    }

    /**
     * สร้าง DatabaseSeeder.php ให้อัตโนมัติถ้ายังไม่มี เพื่อกันลืมกำหนดลำดับการ seed
     */
    private function ensureDatabaseSeederExists(string $newSeederName): void
    {
        $databaseSeederPath = $this->path('database/seeders/DatabaseSeeder.php');

        if (file_exists($databaseSeederPath)) {
            $this->info("พบ DatabaseSeeder.php อยู่แล้ว อย่าลืมเพิ่ม {$newSeederName}::class เข้าไปในลำดับที่ถูกต้อง");
            return;
        }

        $template = $this->getDatabaseSeederTemplate($newSeederName);
        file_put_contents($databaseSeederPath, $template);

        $this->success("ไม่พบ DatabaseSeeder.php จึงสร้างให้อัตโนมัติ: database/seeders/DatabaseSeeder.php");
    }

    private function getDatabaseSeederTemplate(string $firstSeederName): string
    {
        return <<<PHP
<?php
/**
 * DatabaseSeeder
 *
 * จุดประสงค์: จุดรวมลำดับการรัน Seeder ทั้งหมด
 * แก้ลำดับการ seed ได้ที่ไฟล์นี้ไฟล์เดียว โดยไม่ต้องแก้ Seeder ตัวอื่น
 */

namespace Database\Seeders;

use App\Core\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        \$this->call([
            {$firstSeederName}::class,
            // TODO: เพิ่ม Seeder ตัวถัดไปที่นี่ เรียงตามลำดับ dependency (ตารางที่ถูกอ้างอิงต้องมาก่อน)
        ]);
    }
}

PHP;
    }

    private function getSeederTemplate(string $name): string
    {
        return <<<PHP
<?php
/**
 * {$name}
 *
 * จุดประสงค์: [อธิบายหน้าที่ของ seeder]
 */

namespace Database\Seeders;

use App\Core\Seeder;

class {$name} extends Seeder
{
    /**
     * รัน seeder
     */
    public function run(): void
    {
        \$this->log('Seeding data...');

        // ลบข้อมูลเก่า (ถ้าต้องการ)
        // \$this->truncate('table_name');

        // TODO: เพิ่มข้อมูลตัวอย่าง
        \$data = [
            // เพิ่มข้อมูลที่นี่
        ];

        // เพิ่มข้อมูลลงในตาราง
        \$this->insert('employees', \$data);

        \$this->log('✓ Seeded successfully!');
    }
}

PHP;
    }
}
