<?php

declare(strict_types=1);

namespace App\Console\Commands;

class CacheWarmCommand extends BaseCommand
{
    public function name(): string
    {
        return 'cache:warm';
    }

    protected function execute(array $args): void
    {
        $this->info("กำลังเตรียม cache...");

        $cacheDir = $this->path('storage/cache');

        $dirs = ['routes', 'config', 'views'];
        foreach ($dirs as $dir) {
            $path = $cacheDir . '/' . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }

        $this->info("  - กำลัง cache routes...");
        $routeMetadata = [
            'web' => file_exists($this->path('routes/web.php')),
            'api' => file_exists($this->path('routes/api.php')),
            'cached_at' => time(),
        ];

        file_put_contents(
            $cacheDir . '/routes/routes_cached.php',
            "<?php\nreturn " . var_export($routeMetadata, true) . ";"
        );

        $this->info("  - กำลัง cache config...");
        $configFiles = glob($this->path('config/*.php'));
        $cachedConfig = [];

        foreach ($configFiles as $file) {
            $key = basename($file, '.php');
            $cachedConfig[$key] = require $file;
        }

        file_put_contents(
            $cacheDir . '/config/config_cached.php',
            "<?php\nreturn " . var_export($cachedConfig, true) . ";"
        );

        $this->success("เตรียม cache เรียบร้อยแล้ว");
    }
}
