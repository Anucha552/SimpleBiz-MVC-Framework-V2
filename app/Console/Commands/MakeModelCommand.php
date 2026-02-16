<?php

declare(strict_types=1);

namespace App\Console\Commands;

class MakeModelCommand extends BaseCommand
{
    public function name(): string
    {
        return 'make:model';
    }

    protected function execute(array $args): void
    {
        if (empty($args)) {
            $this->error("กรุณาระบุชื่อ model");
            $this->info("วิธีใช้: php console make:model ModelName");
            return;
        }

        $name = $args[0];
        $path = $this->path("app/Models/{$name}.php");

        if (file_exists($path)) {
            $this->error("Model นี้มีอยู่แล้ว!");
            return;
        }

        try {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $template = $this->getModelTemplate($name);
            if (file_put_contents($path, $template) === false) {
                throw new \Exception("ไม่สามารถเขียนไฟล์ได้ กรุณาตรวจสอบสิทธิ์การเขียนไฟล์");
            }

            $this->success("สร้าง Model สำเร็จ: app/Models/{$name}.php");
        } catch (\Exception $e) {
            echo "\n";
            echo "┌─ Create Model Error ────────────────────────────────┐\n";
            $this->error("เกิดข้อผิดพลาด: " . $e->getMessage());
            $this->warning("[TIP] คำแนะนำ: ตรวจสอบสิทธิ์การเขียนไฟล์");
            echo "└────────────────────────────────────────────────────┘\n";
            echo "\n";
        }
    }

    private function getModelTemplate(string $name): string
    {
        $table = strtolower($name) . 's';

        return <<<PHP
<?php
/**
 * {$name} Model
 *
 * จุดประสงค์: [อธิบายหน้าที่ของ model]
 */

namespace App\Models;

use App\Core\Model;

class {$name} extends Model
{
    protected string \$table = '{$table}';

    protected array \$fillable = [
        // TODO: กำหนด fillable fields
    ];

    protected array \$guarded = ['id'];

    protected bool \$timestamps = true;
}

PHP;
    }
}
