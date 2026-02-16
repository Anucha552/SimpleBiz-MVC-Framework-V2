<?php

declare(strict_types=1);

namespace App\Console\Commands;

class ConfigCacheCommand extends BaseCommand
{
    public function name(): string
    {
        return 'config:cache';
    }

    protected function execute(array $args): void
    {
        $this->info("กำลัง cache configuration files...");

        $cacheDir = $this->path('storage/cache/config');
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $configFiles = glob($this->path('config/*.php'));
        $cachedConfig = [];

        foreach ($configFiles as $file) {
            $key = basename($file, '.php');
            $cachedConfig[$key] = require $file;
            $this->info("  - Cached: {$key}.php");
        }

        file_put_contents(
            $cacheDir . '/config_cached.php',
            "<?php\nreturn " . var_export($cachedConfig, true) . ";"
        );

        $this->success("Cache config files เรียบร้อยแล้ว");
    }
}
