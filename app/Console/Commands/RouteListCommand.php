<?php
/**
 * RouteListCommand
 *
 * จุดประสงค์: เป็นคำสั่ง CLI ที่ใช้สำหรับแสดงรายการ routes ที่มีอยู่ในโปรเจกต์ โดยจะแสดงข้อมูลเกี่ยวกับแต่ละ route เช่น HTTP method, path, handler, และ middleware ที่เกี่ยวข้อง เพื่อให้ผู้ใช้สามารถตรวจสอบและจัดการกับ routes ได้อย่างง่ายดายผ่านทางคอนโซล
 */

declare(strict_types=1);

namespace App\Console\Commands;

class RouteListCommand extends BaseCommand
{
    public function name(): string
    {
        return 'route:list';
    }

    protected function execute(array $args): void
    {
        $this->info("กำลังโหลด routes...");
        echo "\n";

        $collector = new class {
            public array $routes = [];
            public function get(string $path, string $handler, array $middleware = []): void { $this->routes[] = ['GET', $path, $handler, $middleware]; }
            public function post(string $path, string $handler, array $middleware = []): void { $this->routes[] = ['POST', $path, $handler, $middleware]; }
            public function put(string $path, string $handler, array $middleware = []): void { $this->routes[] = ['PUT', $path, $handler, $middleware]; }
            public function delete(string $path, string $handler, array $middleware = []): void { $this->routes[] = ['DELETE', $path, $handler, $middleware]; }
            public function patch(string $path, string $handler, array $middleware = []): void { $this->routes[] = ['PATCH', $path, $handler, $middleware]; }
        };

        $router = $collector;

        $loadedAny = false;
        foreach (['web' => $this->path('routes/web.php'), 'api' => $this->path('routes/api.php')] as $type => $file) {
            if (!file_exists($file)) {
                continue;
            }

            $loadedAny = true;
            try {
                require $file;
            } catch (\Throwable $e) {
                $this->error("โหลด routes/{$type}.php ไม่สำเร็จ: " . $e->getMessage());
                return;
            }
        }

        if (!$loadedAny) {
            $this->error("ไม่พบไฟล์ routes/web.php หรือ routes/api.php");
            return;
        }

        if (empty($collector->routes)) {
            $this->warning("ไม่พบการลงทะเบียน routes ในไฟล์ routes/");
            return;
        }

        $this->info("แสดงรายการ routes:");
        echo "\n";

        echo str_pad('METHOD', 8) . str_pad('PATH', 35) . str_pad('HANDLER', 45) . "MIDDLEWARE\n";
        echo str_repeat('─', 110) . "\n";

        foreach ($collector->routes as $route) {
            [$method, $path, $handler, $middleware] = $route;
            $middlewareText = '-';
            if (!empty($middleware)) {
                $middlewareText = implode(', ', array_map(fn($m) => is_string($m) ? $m : gettype($m), $middleware));
            }

            $methodOut = str_pad($method, 8);
            $pathOut = str_pad(strlen($path) > 34 ? substr($path, 0, 31) . '...' : $path, 35);
            $handlerOut = str_pad(strlen($handler) > 44 ? substr($handler, 0, 41) . '...' : $handler, 45);
            echo $methodOut . $pathOut . $handlerOut . $middlewareText . "\n";
        }

        echo str_repeat('─', 110) . "\n";
        $this->success("รวมทั้งหมด " . count($collector->routes) . " routes");
        echo "\n";
    }
}
