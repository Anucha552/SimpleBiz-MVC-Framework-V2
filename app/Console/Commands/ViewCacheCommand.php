<?php

declare(strict_types=1);

namespace App\Console\Commands;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ViewCacheCommand extends BaseCommand
{
    public function name(): string
    {
        return 'view:cache';
    }

    protected function execute(array $args): void
    {
        $this->info("กำลัง compile view files...");

        $cacheDir = $this->path('storage/cache/views');
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $viewsDir = $this->path('app/Views');
        $viewFiles = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($viewsDir)
        );

        $count = 0;
        foreach ($viewFiles as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace($viewsDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $cacheFile = $cacheDir . '/' . md5($relativePath) . '.php';

                copy($file->getPathname(), $cacheFile);
                $count++;
            }
        }

        $this->success("Compile views เรียบร้อยแล้ว ({$count} files)");
    }
}
