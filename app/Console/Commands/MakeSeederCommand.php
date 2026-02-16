<?php

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

        foreach (\$data as \$item) {
            \$this->insert('table_name', \$item);
        }

        \$this->log('✓ Seeded successfully!');
    }
}

PHP;
    }
}
