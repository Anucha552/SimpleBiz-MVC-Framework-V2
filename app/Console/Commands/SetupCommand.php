<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\ConsoleColor;

class SetupCommand extends BaseCommand
{
    public function name(): string
    {
        return 'setup';
    }

    protected function execute(array $args): void
    {
        $this->info("[SETUP] SimpleBiz Framework Project Setup");
        echo "\n";

        echo ConsoleColor::CYAN . "ชื่อโปรเจค (เช่น mybookstore, restaurant-ordering): " . ConsoleColor::RESET;
        $projectName = trim(fgets(STDIN));

        if ($projectName === '') {
            $this->error("กรุณาระบุชื่อโปรเจค!");
            return;
        }

        $shouldRenameFolder = false;
        $currentFolderPath = realpath($this->context->rootPath()) ?: $this->context->rootPath();
        $currentFolderName = basename($currentFolderPath);

        $isSafeFolderName = (bool) preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]*$/', $projectName);

        if ($isSafeFolderName && $currentFolderName !== $projectName) {
            echo ConsoleColor::YELLOW . "ต้องการเปลี่ยนชื่อโฟลเดอร์จาก '{$currentFolderName}' เป็น '{$projectName}' หรือไม่? (y/n) [n]: " . ConsoleColor::RESET;
            $renameAnswer = strtolower(trim(fgets(STDIN)));
            $shouldRenameFolder = in_array($renameAnswer, ['y', 'yes', 'ใช่'], true);
        } elseif (!$isSafeFolderName) {
            $this->warning("ชื่อโปรเจคมีอักขระที่ไม่เหมาะกับชื่อโฟลเดอร์ จึงจะไม่เปลี่ยนชื่อโฟลเดอร์อัตโนมัติ");
        }

        echo ConsoleColor::CYAN . "คำอธิบายโปรเจค: " . ConsoleColor::RESET;
        $projectDescription = trim(fgets(STDIN));

        echo ConsoleColor::CYAN . "Vendor/Company name (เช่น mycompany): " . ConsoleColor::RESET;
        $vendorName = trim(fgets(STDIN));
        if ($vendorName === '') {
            $vendorName = 'mycompany';
        }

        echo ConsoleColor::CYAN . "ชื่อแอปพลิเคชัน (สำหรับแสดงผล เช่น My Bookstore): " . ConsoleColor::RESET;
        $appName = trim(fgets(STDIN));
        if ($appName === '') {
            $appName = ucwords(str_replace(['-', '_'], ' ', $projectName));
        }

        echo ConsoleColor::CYAN . "ชื่อ Database (ถ้าไม่ระบุจะใช้ชื่อโปรเจค): " . ConsoleColor::RESET;
        $dbName = trim(fgets(STDIN));
        if ($dbName === '') {
            $dbName = str_replace(['-', ' '], '_', strtolower($projectName));
        }

        echo ConsoleColor::CYAN . "Database Username [root]: " . ConsoleColor::RESET;
        $dbUser = trim(fgets(STDIN));
        if ($dbUser === '') {
            $dbUser = 'root';
        }

        echo ConsoleColor::CYAN . "Database Password [เว้นว่าง]: " . ConsoleColor::RESET;
        $dbPassword = trim(fgets(STDIN));

        echo "\n";

        echo ConsoleColor::YELLOW . "ต้องการจัดการ Git repository หรือไม่? (y/n) [n]: " . ConsoleColor::RESET;
        $manageGit = strtolower(trim(fgets(STDIN)));

        $gitMode = '';
        $gitRemoteUrl = '';

        if ($manageGit === 'y' || $manageGit === 'yes') {
            echo ConsoleColor::YELLOW . "\nเลือกวิธีการ:\n" . ConsoleColor::RESET;
            echo ConsoleColor::WHITE . "  1. เปลี่ยน remote URL (เก็บประวัติ commits เดิม)\n" . ConsoleColor::RESET;
            echo ConsoleColor::WHITE . "  2. เริ่ม Git ใหม่ทั้งหมด (ลบประวัติเก่า)\n" . ConsoleColor::RESET;
            echo ConsoleColor::CYAN . "เลือก (1/2) [1]: " . ConsoleColor::RESET;
            $gitChoice = trim(fgets(STDIN));

            $gitMode = ($gitChoice === '2') ? 'reinit' : 'change-remote';

            echo ConsoleColor::CYAN . "GitHub Repository URL (เช่น https://github.com/yourusername/yourrepo.git): " . ConsoleColor::RESET;
            $gitRemoteUrl = trim(fgets(STDIN));
        }

        echo "\n";
        $this->info("กำลังตั้งค่าโปรเจค...");
        echo "\n";

        $this->info("1. กำลังแก้ไข composer.json...");
        $this->updateComposerJson($vendorName, $projectName, $projectDescription);

        $this->info("2. กำลังสร้างไฟล์ .env...");
        $this->createEnvFile($appName, $dbName, $dbUser, $dbPassword);

        $this->info("3. กำลังสร้าง APP_KEY...");
        $appKey = $this->generateAppKey();
        $this->updateEnvKey($appKey);

        $step = 4;

        $this->info("{$step}. กำลังอัปเดต README.md...");
        $this->updateReadme($projectName, $appName, $projectDescription);
        $step++;

        $this->info("{$step}. กำลังตรวจสอบ .gitignore...");
        $this->ensureGitignore();
        $step++;

        if ($gitRemoteUrl !== '') {
            $this->info("{$step}. กำลังจัดการ Git repository...");
            if ($gitMode === 'reinit') {
                $this->reinitGit($gitRemoteUrl);
            } else {
                $this->changeGitRemote($gitRemoteUrl);
            }
            $step++;
        }

        $this->info("{$step}. กำลังอัปเดต Composer dependencies...");
        passthru("composer update --quiet");
        $step++;

        if ($gitRemoteUrl !== '' && is_dir($this->path('.git'))) {
            echo "\n";
            echo ConsoleColor::YELLOW . "ต้องการ commit และ push การเปลี่ยนแปลงหรือไม่? (y/n) [y]: " . ConsoleColor::RESET;
            $shouldCommit = strtolower(trim(fgets(STDIN)));

            if ($shouldCommit !== 'n' && $shouldCommit !== 'no') {
                $this->info("{$step}. กำลัง commit และ push...");
                $this->commitAndPush($projectName);
            }
        }

        echo "\n";
        $this->success("✓ ตั้งค่าโปรเจคเสร็จสมบูรณ์!");
        echo "\n";

        echo ConsoleColor::GREEN . ConsoleColor::BOLD . "สรุปข้อมูลโปรเจค:\n" . ConsoleColor::RESET;
        echo ConsoleColor::WHITE . "  ชื่อโปรเจค: " . ConsoleColor::CYAN . $projectName . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "  Composer name: " . ConsoleColor::CYAN . "{$vendorName}/{$projectName}" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "  ชื่อแอป: " . ConsoleColor::CYAN . $appName . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "  Database: " . ConsoleColor::CYAN . $dbName . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "  APP_KEY: " . ConsoleColor::CYAN . $appKey . ConsoleColor::RESET . "\n";
        echo "\n";

        if ($shouldRenameFolder) {
            $this->updateAppUrlForRename($currentFolderName, $projectName);
            $this->updateRootHtaccessForRename($currentFolderName, $projectName);

            $parentDir = dirname($currentFolderPath);
            $newFolderPath = $parentDir . DIRECTORY_SEPARATOR . $projectName;

            echo "\n";
            $this->info("กำลังเปลี่ยนชื่อโฟลเดอร์โปรเจค...");

            if (file_exists($newFolderPath)) {
                $this->warning("มีโฟลเดอร์ชื่อ '{$projectName}' อยู่แล้วใน {$parentDir} (ข้ามการเปลี่ยนชื่อ)");
                return;
            }

            if (@rename($currentFolderPath, $newFolderPath)) {
                $this->success("เปลี่ยนชื่อโฟลเดอร์สำเร็จ: {$newFolderPath}");
                $this->info("ขั้นตอนถัดไป: cd \"{$newFolderPath}\" แล้วรันคำสั่งต่อไปตามต้องการ");
            } else {
                $error = error_get_last();
                $detail = $error['message'] ?? 'unknown error';
                $this->warning("ไม่สามารถเปลี่ยนชื่อโฟลเดอร์อัตโนมัติได้ ({$detail})");
                $this->info("คุณสามารถเปลี่ยนชื่อโฟลเดอร์ด้วยตัวเองได้ภายหลัง: '{$currentFolderName}' -> '{$projectName}'");
            }
        }
    }

    private function updateComposerJson(string $vendor, string $project, string $description): void
    {
        $composerFile = $this->path('composer.json');

        if (!file_exists($composerFile)) {
            $this->error("ไม่พบไฟล์ composer.json");
            return;
        }

        $composer = json_decode(file_get_contents($composerFile), true);

        $composer['name'] = strtolower($vendor) . '/' . strtolower($project);
        if ($description !== '') {
            $composer['description'] = $description;
        }

        file_put_contents($composerFile, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->success("  [OK] อัปเดต composer.json แล้ว");
    }

    private function createEnvFile(string $appName, string $dbName, string $dbUser, string $dbPassword): void
    {
        $envExample = $this->path('.env.example');
        $envFile = $this->path('.env');

        if (!file_exists($envExample)) {
            $this->error("ไม่พบไฟล์ .env.example");
            return;
        }

        $content = file_get_contents($envExample);
        $content = preg_replace('/APP_NAME=.*/', 'APP_NAME="' . $appName . '"', $content);
        $content = preg_replace('/DB_DATABASE=.*/', 'DB_DATABASE=' . $dbName, $content);
        $content = preg_replace('/DB_USERNAME=.*/', 'DB_USERNAME=' . $dbUser, $content);
        $content = preg_replace('/DB_PASSWORD=.*/', 'DB_PASSWORD=' . $dbPassword, $content);

        file_put_contents($envFile, $content);

        $this->success("  [OK] สร้างไฟล์ .env แล้ว");
    }

    private function generateAppKey(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function updateEnvKey(string $key): void
    {
        $envFile = $this->path('.env');

        if (!file_exists($envFile)) {
            $this->error("ไม่พบไฟล์ .env");
            return;
        }

        $content = file_get_contents($envFile);
        $content = preg_replace('/APP_KEY=.*/', 'APP_KEY=' . $key, $content);

        file_put_contents($envFile, $content);

        $this->success("  [OK] สร้าง APP_KEY แล้ว");
    }

    private function updateAppUrlForRename(string $oldFolder, string $newFolder): void
    {
        $envFile = $this->path('.env');
        if (!file_exists($envFile)) {
            return;
        }

        $content = file_get_contents($envFile);
        if (!preg_match('/^APP_URL=(.*)$/m', $content, $m)) {
            return;
        }

        $appUrlRaw = trim($m[1]);
        $appUrl = trim($appUrlRaw, " \t\n\r\"'");

        if (strpos($appUrl, $oldFolder) !== false) {
            $newUrl = str_replace($oldFolder, $newFolder, $appUrl);
        } else {
            if (strpos($appUrl, 'localhost') !== false || strpos($appUrl, '127.') === 0) {
                $newUrl = rtrim($appUrl, '/') . '/' . $newFolder;
            } else {
                $newUrl = $appUrl;
            }
        }

        if ($newUrl !== $appUrl) {
            $content = preg_replace('/^APP_URL=.*$/m', 'APP_URL=' . $newUrl, $content);
            file_put_contents($envFile, $content);
            $this->success("  [OK] อัปเดต APP_URL ใน .env เป็น {$newUrl}");
        }
    }

    private function updateRootHtaccessForRename(string $oldFolder, string $newFolder): void
    {
        $htaccess = $this->path('.htaccess');
        if (!file_exists($htaccess)) {
            return;
        }

        $content = file_get_contents($htaccess);

        $replacements = [
            '/' . $oldFolder . '/' => '/' . $newFolder . '/',
            $oldFolder . '/' => $newFolder . '/',
        ];

        $newContent = str_replace(array_keys($replacements), array_values($replacements), $content);

        if ($newContent !== $content) {
            file_put_contents($htaccess, $newContent);
            $this->success("  [OK] อัปเดตไฟล์ .htaccess ที่รากโปรเจคให้ใช้ชื่อโฟลเดอร์ใหม่");
        }
    }

    private function updateReadme(string $projectName, string $appName, string $description): void
    {
        $readmeFile = $this->path('README.md');

        if (!file_exists($readmeFile)) {
            $this->warning("  [!] ไม่พบไฟล์ README.md");
            return;
        }

        $content = file_get_contents($readmeFile);
        $content = preg_replace('/# SimpleBiz MVC Framework V2/', '# ' . $appName, $content, 1);

        if ($description !== '') {
            $content = preg_replace('/\*\*เฟรมเวิร์ก MVC สำหรับระบบอีคอมเมิร์ซ[^*]+\*\*/', '**' . $description . '**', $content, 1);
        }

        file_put_contents($readmeFile, $content);

        $this->success("  [OK] อัปเดต README.md แล้ว");
    }

    private function ensureGitignore(): void
    {
        $gitignoreFile = $this->path('.gitignore');

        if (!file_exists($gitignoreFile)) {
            $content = $this->getDefaultGitignore();
            file_put_contents($gitignoreFile, $content);
            $this->success("  [OK] สร้างไฟล์ .gitignore แล้ว");
            return;
        }

        $this->success("  [OK] .gitignore มีอยู่แล้ว");
    }

    private function getDefaultGitignore(): string
    {
        return <<<'EOT'
}

# Environment files
.env
.env.*
!.env.example

# Composer
/vendor/
composer.lock

# Logs
/storage/logs/*.log
/storage/logs/*.txt

# Cache
/storage/cache/*
!/storage/cache/.gitkeep

# System files
.DS_Store
Thumbs.db
desktop.ini

# IDE
.vscode/
.idea/
*.sublime-project
*.sublime-workspace

# PHP
*.cache
.phpunit.result.cache
/.phpunit.cache/

# Temporary files
*.tmp
*.temp
*.swp
*.swo
*~

# Node modules (if using)
node_modules/

# Build files
/build/
/dist/
EOT;
    }

    private function changeGitRemote(string $newUrl): void
    {
        if (!is_dir($this->path('.git'))) {
            $this->warning("  [!] ไม่พบ Git repository, ข้ามขั้นตอนนี้");
            return;
        }

        exec("git remote remove origin 2>&1", $output, $returnCode);
        exec("git remote add origin {$newUrl} 2>&1", $output, $returnCode);

        if ($returnCode === 0) {
            $this->success("  [OK] เปลี่ยน Git remote เป็น {$newUrl} แล้ว");
        } else {
            $this->error("  [X] ไม่สามารถเปลี่ยน Git remote ได้");
        }
    }

    private function reinitGit(string $newUrl): void
    {
        if (is_dir($this->path('.git'))) {
            $this->removeDirectory($this->path('.git'));
            $this->success("  [OK] ลบ Git repository เดิมแล้ว");
        }

        exec("git init 2>&1", $output, $returnCode);
        if ($returnCode !== 0) {
            $this->error("  [X] ไม่สามารถเริ่มต้น Git ได้");
            return;
        }

        exec("git remote add origin {$newUrl} 2>&1");
        exec("git branch -M main 2>&1");

        $this->success("  [OK] เริ่มต้น Git repository ใหม่แล้ว");
    }

    private function commitAndPush(string $projectName): void
    {
        exec("git add . 2>&1", $output, $returnCode);
        if ($returnCode !== 0) {
            $this->error("  [X] ไม่สามารถ add ไฟล์ได้");
            return;
        }

        $commitMessage = "Initial setup for {$projectName}";
        exec('git commit -m "' . $commitMessage . '" 2>&1', $output, $returnCode);
        if ($returnCode !== 0) {
            $this->warning("  [!] ไม่มีการเปลี่ยนแปลงให้ commit หรือเกิดข้อผิดพลาด");
            return;
        }

        $this->success("  [OK] Commit สำเร็จ");

        echo ConsoleColor::CYAN . "  กำลัง push ไปยัง GitHub..." . ConsoleColor::RESET . "\n";
        passthru("git push -u origin main 2>&1", $returnCode);

        if ($returnCode === 0) {
            $this->success("  [OK] Push สำเร็จ!");
        } else {
            $this->warning("  [!] Push ไม่สำเร็จ (อาจต้อง authenticate หรือสร้าง repository บน GitHub ก่อน)");
            $this->info("    คุณสามารถ push เองภายหลังด้วย: git push -u origin main");
        }
    }
}
