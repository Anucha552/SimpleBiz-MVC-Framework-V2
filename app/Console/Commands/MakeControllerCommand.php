<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\ConsoleColor;

class MakeControllerCommand extends BaseCommand
{
    public function name(): string
    {
        return 'make:controller';
    }

    protected function execute(array $args): void
    {
        if (empty($args)) {
            $this->error("กรุณาระบุชื่อ controller");
            $this->info("วิธีใช้: php console make:controller ControllerName \n");
            return;
        }

        echo ConsoleColor::CYAN . "เลือกประเภท Controller:\n" . ConsoleColor::RESET;
        echo ConsoleColor::WHITE . "  1. Web Controller (จัดการหน้าเว็บ)\n" . ConsoleColor::RESET;
        echo ConsoleColor::WHITE . "  2. API Controller (จัดการ API)\n" . ConsoleColor::RESET;
        echo ConsoleColor::CYAN . "เลือก (1/2): " . ConsoleColor::RESET;
        $choice = trim(fgets(STDIN));

        $apiOrWeb = '';
        if ($choice === '1') {
            $apiOrWeb = 'Web';
        } elseif ($choice === '2') {
            $apiOrWeb = 'Api';
        } else {
            echo "\n";
            $this->error("ตัวเลือกไม่ถูกต้อง, กรุณาเลือก 1 หรือ 2 \n");
            return;
        }

        $name = $args[0];
        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        $path = $this->path("app/Controllers/{$apiOrWeb}/{$name}.php");

        if (file_exists($path)) {
            echo "\n";
            $this->error("Controller นี้มีอยู่แล้ว!\n");
            return;
        }

        try {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $template = $apiOrWeb === 'Web'
                ? $this->getWebControllerTemplate($name)
                : $this->getApiControllerTemplate($name);

            if (file_put_contents($path, $template) === false) {
                throw new \Exception("ไม่สามารถเขียนไฟล์ได้ กรุณาตรวจสอบสิทธิ์การเขียนไฟล์");
            }

            echo "\n";
            $this->success("สร้าง Controller สำเร็จ: app/Controllers/{$apiOrWeb}/{$name}.php \n");
        } catch (\Exception $e) {
            echo "\n";
            echo "┌─ Create Controller Error ───────────────────────────┐\n";
            $this->error("เกิดข้อผิดพลาด: " . $e->getMessage());
            $this->warning("[TIP] คำแนะนำ: ตรวจสอบสิทธิ์การเขียนไฟล์");
            echo "└────────────────────────────────────────────────────┘\n";
            echo "\n";
        }
    }

    private function getWebControllerTemplate(string $name): string
    {
        return <<<PHP
<?php
/**
 * {$name}
 *
 * จุดประสงค์: [อธิบายหน้าที่ของ controller ได้ที่นี่ว่า controller นี้ทำอะไร]
 */

namespace App\Controllers\Web;

use App\Core\Controller;

class {$name} extends Controller
{
    /**
     * แสดงหน้าหลัก
     */
    public function index(): void
    {
        // เขียนโค้ดของคุณที่นี่ หรือสร้าง method อื่นๆ ตามต้องการ
        echo "Hello from {$name}!";
    }
}

PHP;
    }

    private function getApiControllerTemplate(string $name): string
    {
        return <<<PHP
<?php
/**
 * {$name}
 *
 * จุดประสงค์: [อธิบายหน้าที่ของ controller ได้ที่นี่ว่า controller นี้ทำอะไร]
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Response;

class {$name} extends Controller
{
    /**
     * แสดงหน้าหลัก
     */
    public function index(): Response
    {
        // เขียนโค้ดของคุณที่นี่ หรือสร้าง method อื่นๆ ตามต้องการ
        return Response::apiSuccess([
            'status' => 'ok',
            'timestamp' => date('c'),
        ], 'Hello from {$name}!');
    }
}

PHP;
    }
}
