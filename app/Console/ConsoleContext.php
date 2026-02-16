<?php

declare(strict_types=1);

namespace App\Console;

use Exception;
use PDO;

class ConsoleContext
{
    private string $rootPath;
    private ConsoleIO $io;

    public function __construct(string $rootPath, ConsoleIO $io)
    {
        $this->rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);
        $this->io = $io;
    }

    public function rootPath(): string
    {
        return $this->rootPath;
    }

    public function path(string $relative): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . ltrim($relative, DIRECTORY_SEPARATOR);
    }

    public function io(): ConsoleIO
    {
        return $this->io;
    }

    /**
     * @param string[] $args
     */
    public function hasForceFlag(array $args): bool
    {
        return in_array('--force', $args, true) || in_array('-f', $args, true);
    }

    public function checkDatabaseConnection(): bool
    {
        try {
            $config = require $this->path('config/database.php');

            if (isset($config['connections'])) {
                $dbConfig = $config['connections'][$config['default']];
            } else {
                $dbConfig = $config;
            }

            $pdo = new PDO(
                "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']}",
                $dbConfig['username'],
                $dbConfig['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            return true;
        } catch (Exception $e) {
            $this->io->error("ไม่สามารถเชื่อมต่อฐานข้อมูล");
            echo ConsoleColor::RED . "Error: " . $e->getMessage() . ConsoleColor::RESET . "\n\n";

            echo ConsoleColor::YELLOW . "[TIP] วิธีแก้ไข:" . ConsoleColor::RESET . "\n";
            echo "  1. ตรวจสอบว่า MySQL server ทำงานอยู่\n";
            echo "  2. ตรวจสอบค่า DB_* ในไฟล์ .env\n";
            echo "  3. สร้าง database: CREATE DATABASE database_name;\n";
            echo "  4. ตรวจสอบ username/password ในไฟล์ .env\n\n";

            return false;
        }
    }

    public function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    public function humanFilesize(int $bytes, int $decimals = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = $bytes > 0 ? floor((log($bytes) / log(1024))) : 0;
        $factor = min($factor, count($units) - 1);
        $size = $bytes / pow(1024, $factor);
        return round($size, $decimals) . ' ' . $units[$factor];
    }
}
