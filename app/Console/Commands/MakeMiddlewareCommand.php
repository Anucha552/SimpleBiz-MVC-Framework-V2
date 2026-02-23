<?php
/**
 * MakeMiddlewareCommand
 *
 * จุดประสงค์: คำสั่งสำหรับสร้าง Middleware ใหม่ในโครงสร้างของแอปพลิเคชัน
 */

declare(strict_types=1);

namespace App\Console\Commands;

class MakeMiddlewareCommand extends BaseCommand
{
    public function name(): string
    {
        return 'make:middleware';
    }

    protected function execute(array $args): void
    {
        if (empty($args)) {
            $this->error("กรุณาระบุชื่อ middleware");
            $this->info("วิธีใช้: php console make:middleware MiddlewareName");
            return;
        }

        $name = $args[0];
        if (!str_ends_with($name, 'Middleware')) {
            $name .= 'Middleware';
        }

        $path = $this->path("app/Middleware/{$name}.php");

        if (file_exists($path)) {
            $this->error("Middleware นี้มีอยู่แล้ว!");
            return;
        }

        try {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $template = $this->getMiddlewareTemplate($name);
            if (file_put_contents($path, $template) === false) {
                throw new \Exception("ไม่สามารถเขียนไฟล์ได้");
            }

            $this->success("สร้าง Middleware สำเร็จ: app/Middleware/{$name}.php");
            echo "\n";
        } catch (\Exception $e) {
            echo "\n";
            echo "┌─ Create Middleware Error ───────────────────────────┐\n";
            $this->error("เกิดข้อผิดพลาด: " . $e->getMessage());
            $this->warning("[TIP] คำแนะนำ: ตรวจสอบสิทธิ์การเขียนไฟล์");
            echo "└────────────────────────────────────────────────────┘\n";
            echo "\n";
        }
    }

    private function getMiddlewareTemplate(string $name): string
    {
        $template = <<<'PHP'
<?php
/**
 * __NAME__
 *
 * จุดประสงค์: [อธิบายหน้าที่ของ middleware]
 */

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;

class __NAME__ extends Middleware
{
    /**
     * จัดการคำขอ
     *
     * @param Request|null $request
     * @return bool|Response คืนค่า true เพื่อดำเนินการต่อ, false เพื่อหยุด หรือ Response เพื่อส่งกลับทันที
     */
    public function handle(?Request $request = null): bool|Response
    {
        // TODO: Implement middleware logic

        return true;
    }
}

PHP;

        return str_replace('__NAME__', $name, $template);
    }
}
