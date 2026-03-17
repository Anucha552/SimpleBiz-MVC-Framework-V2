<?php
/**
 * SetupCommand
 *
 * จุดประสงค์: เป็นคำสั่ง CLI ที่ใช้สำหรับตั้งค่าโปรเจกต์ SimpleBiz MVC Framework ใหม่ โดยจะช่วยให้ผู้ใช้สามารถกำหนดค่าต่างๆ เช่น ชื่อโปรเจกต์, ชื่อแอปพลิเคชัน, การตั้งค่า database, และการจัดการ Git repository ได้อย่างง่ายดายผ่านทางคอนโซล
 */

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

        $alreadySetup = $this->isAlreadySetup();
        $minimalSetup = false;
        if ($alreadySetup) {
            $this->warning("ตรวจพบว่ามีการตั้งค่าแล้ว (พบไฟล์ .setup)");
            echo ConsoleColor::YELLOW . "ต้องการรัน setup ใหม่หรือไม่? (y/n) [n]: " . ConsoleColor::RESET;
            $confirmSetup = strtolower(trim(fgets(STDIN)));
            if ($confirmSetup !== 'y' && $confirmSetup !== 'yes') {
                $this->info("ยกเลิกการรัน setup");
                return;
            }
            echo "\n";
            $this->info("กำลังรัน setup ใหม่ (ตั้งค่าเฉพาะที่จำเป็น)...");
            echo "\n";
            $minimalSetup = true;
        } else {
            $this->success("ยังไม่เคยตั้งค่าโปรเจค (ไม่พบไฟล์ .setup)");
        }

        if ($minimalSetup) {
            $envExists = $this->envExists();
            $appName = '';

            if (!$envExists) {
                echo ConsoleColor::CYAN . "ชื่อแอปพลิเคชัน (สำหรับแสดงผล เช่น My Bookstore) [My App]: " . ConsoleColor::RESET;
                $appName = trim(fgets(STDIN));
                if ($appName === '') {
                    $appName = 'My App';
                }
            }

            echo ConsoleColor::CYAN . "เลือกชนิดฐานข้อมูล (1) MySQL/MariaDB (2) SQLite [1]: " . ConsoleColor::RESET;
            $dbChoice = trim(fgets(STDIN));
            $dbConnection = ($dbChoice === '2') ? 'sqlite' : 'mysql';

            if ($dbConnection === 'sqlite') {
                $dbName =  'storage/database.sqlite';
                $dbUser = '';
                $dbPassword = '';
            } else {
                echo ConsoleColor::CYAN . "ชื่อ Database [app_db]: " . ConsoleColor::RESET;
                $dbName = trim(fgets(STDIN));
                if ($dbName === '') {
                    $dbName = 'app_db';
                }

                echo ConsoleColor::CYAN . "Database Username [root]: " . ConsoleColor::RESET;
                $dbUser = trim(fgets(STDIN));
                if ($dbUser === '') {
                    $dbUser = 'root';
                }

                echo ConsoleColor::CYAN . "Database Password [เว้นว่าง]: " . ConsoleColor::RESET;
                $dbPassword = trim(fgets(STDIN));
            }

            echo "\n";
            $this->info("กำลังตั้งค่าเฉพาะที่จำเป็น...");
            echo "\n";

            if ($envExists) {
                $this->info("1. กำลังอัปเดตค่า DB ใน .env...");
                $this->updateEnvDbValues($dbConnection, $dbName, $dbUser, $dbPassword);
            } else {
                $this->info("1. กำลังสร้างไฟล์ .env...");
                $this->createEnvFile($appName, $dbConnection, $dbName, $dbUser, $dbPassword);
            }

            $this->info("2. กำลังสร้าง APP_KEY...");
            $appKey = $this->generateAppKey();
            $this->updateEnvKey($appKey);

            echo "\n";
            $this->success("✓ ตั้งค่าเฉพาะที่จำเป็นเสร็จสมบูรณ์!");
            echo "\n";
            return;
        }

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

        echo ConsoleColor::CYAN . "เลือกชนิดฐานข้อมูล (1) MySQL/MariaDB (2) SQLite [1]: " . ConsoleColor::RESET;
        $dbChoice = trim(fgets(STDIN));
        $dbConnection = ($dbChoice === '2') ? 'sqlite' : 'mysql';

        if ($dbConnection === 'sqlite') {
            $dbName =  'storage/database.sqlite';
            $dbUser = '';
            $dbPassword = '';
        } else {
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
        }

        echo "\n";

        echo ConsoleColor::YELLOW . "ต้องการจัดการ Git repository หรือไม่? (y/n) [n]: " . ConsoleColor::RESET;
        $manageGit = strtolower(trim(fgets(STDIN)));

        $gitMode = '';
        $gitRemoteUrl = '';

        if ($manageGit !== 'y' && $manageGit !== 'yes') {
            if (is_dir($this->path('.git'))) {
                echo ConsoleColor::YELLOW . "ไม่ตั้งค่า GitHub แต่ลบโฟลเดอร์ .git ทิ้งหรือไม่? (y/n) [n]: " . ConsoleColor::RESET;
                $removeGitAnswer = strtolower(trim(fgets(STDIN)));
                if ($removeGitAnswer === 'y' || $removeGitAnswer === 'yes' || $removeGitAnswer === 'ใช่') {
                    $this->removeDirectory($this->path('.git'));
                    $this->success("  [OK] ลบโฟลเดอร์ .git แล้ว (เริ่มใหม่แบบไม่ผูกประวัติเดิม)");
                }
            }
        } else {
            echo ConsoleColor::YELLOW . "\nเลือกวิธีการ:\n" . ConsoleColor::RESET;
            echo ConsoleColor::WHITE . "  1. เปลี่ยน remote URL (เก็บประวัติ commits เดิม)\n" . ConsoleColor::RESET;
            echo ConsoleColor::WHITE . "  2. เริ่ม Git ใหม่ทั้งหมด (ลบประวัติเก่า)\n" . ConsoleColor::RESET;
            echo ConsoleColor::CYAN . "เลือก (1/2) [1]: " . ConsoleColor::RESET;
            $gitChoice = trim(fgets(STDIN));

            $gitMode = ($gitChoice === '2') ? 'reinit' : 'change-remote';

            while (true) {
                echo ConsoleColor::CYAN . "GitHub Repository URL (เช่น https://github.com/yourusername/yourrepo.git): " . ConsoleColor::RESET;
                $gitRemoteUrl = trim(fgets(STDIN));
                if ($gitRemoteUrl === '') {
                    $this->warning("กรุณาระบุ Git remote URL");
                    continue;
                }
                if (!$this->isValidGitRemoteUrl($gitRemoteUrl)) {
                    $this->warning("รูปแบบ Git remote URL ไม่ถูกต้อง (รองรับ https หรือ ssh)");
                    continue;
                }
                break;
            }
        }

        echo "\n";
        $this->info("กำลังตั้งค่าโปรเจค...");
        echo "\n";

        $this->info("1. กำลังแก้ไข composer.json...");
        $this->updateComposerJson($vendorName, $projectName, $projectDescription);

        $this->info("2. กำลังสร้างไฟล์ .env...");
        $this->createEnvFile($appName, $dbConnection, $dbName, $dbUser, $dbPassword);

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
        if (!file_exists($this->path('composer.json'))) {
            $this->warning("  [!] ไม่พบ composer.json ข้ามการอัปเดต dependencies");
        } elseif ($this->hasComposer()) {
            passthru("composer update --quiet", $composerExitCode);
            if ($composerExitCode !== 0) {
                $this->warning("  [!] Composer update ไม่สำเร็จ");
            }
        } else {
            $this->warning("  [!] ไม่พบ composer ข้ามการอัปเดต dependencies");
        }
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
        $this->writeSetupMarker($projectName);
        echo "\n";

        echo ConsoleColor::GREEN . ConsoleColor::BOLD . "สรุปข้อมูลโปรเจค:\n" . ConsoleColor::RESET;
        echo ConsoleColor::WHITE . "  ชื่อโปรเจค: " . ConsoleColor::CYAN . $projectName . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "  Composer name: " . ConsoleColor::CYAN . "{$vendorName}/{$projectName}" . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "  ชื่อแอป: " . ConsoleColor::CYAN . $appName . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "  DB Connection: " . ConsoleColor::CYAN . $dbConnection . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "  Database: " . ConsoleColor::CYAN . $dbName . ConsoleColor::RESET . "\n";
        echo ConsoleColor::WHITE . "  APP_KEY: " . ConsoleColor::CYAN . $appKey . ConsoleColor::RESET . "\n";
        echo "\n";

        if ($shouldRenameFolder) {
            $parentDir = dirname($currentFolderPath);
            $newFolderPath = $parentDir . DIRECTORY_SEPARATOR . $projectName;

            echo "\n";
            $this->info("กำลังเปลี่ยนชื่อโฟลเดอร์โปรเจค...");

            if (file_exists($newFolderPath)) {
                $this->warning("มีโฟลเดอร์ชื่อ '{$projectName}' อยู่แล้วใน {$parentDir} (ข้ามการเปลี่ยนชื่อ)");
                return;
            }

            if (@rename($currentFolderPath, $newFolderPath)) {
                $this->updateAppUrlForRename($currentFolderName, $projectName, $newFolderPath);
                $this->updateRootHtaccessForRename($currentFolderName, $projectName, $newFolderPath);
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
        if (!is_array($composer) || json_last_error() !== JSON_ERROR_NONE) {
            $this->error("ไม่สามารถอ่าน composer.json ได้ (JSON ผิดรูปแบบ)");
            return;
        }

        $composer['name'] = strtolower($vendor) . '/' . strtolower($project);
        if ($description !== '') {
            $composer['description'] = $description;
        }

        file_put_contents($composerFile, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->success("  [OK] อัปเดต composer.json แล้ว");
    }

    private function isAlreadySetup(): bool
    {
        return file_exists($this->getSetupMarkerPath());
    }

    private function getSetupMarkerPath(): string
    {
        return $this->path('.setup');
    }

    private function writeSetupMarker(string $projectName): void
    {
        $markerPath = $this->getSetupMarkerPath();
        if (file_exists($markerPath)) {
            return;
        }

        $content = "project_name=" . $projectName . "\n";
        $content .= "setup_at=" . date('Y-m-d H:i:s') . "\n";
        @file_put_contents($markerPath, $content);
    }

    private function createEnvFile(string $appName, string $dbConnection, string $dbName, string $dbUser, string $dbPassword): void
    {
        $envExample = $this->path('.env.example');
        $envFile = $this->path('.env');

        if (!file_exists($envExample)) {
            $this->error("ไม่พบไฟล์ .env.example");
            return;
        }

        $content = file_get_contents($envExample);
        $content = preg_replace('/APP_NAME=.*/', 'APP_NAME="' . $appName . '"', $content);
        $content = preg_replace('/DB_CONNECTION=.*/', 'DB_CONNECTION=' . $dbConnection, $content);
        $content = preg_replace('/DB_DATABASE=.*/', 'DB_DATABASE=' . $dbName, $content);
        $content = preg_replace('/DB_USERNAME=.*/', 'DB_USERNAME=' . $dbUser, $content);
        $content = preg_replace('/DB_PASSWORD=.*/', 'DB_PASSWORD=' . $dbPassword, $content);

        file_put_contents($envFile, $content);

        $this->success("  [OK] สร้างไฟล์ .env แล้ว");
    }

    private function envExists(): bool
    {
        return file_exists($this->path('.env'));
    }

    private function updateEnvDbValues(string $dbConnection, string $dbName, string $dbUser, string $dbPassword): void
    {
        $envFile = $this->path('.env');

        if (!file_exists($envFile)) {
            $this->error("ไม่พบไฟล์ .env");
            return;
        }

        $content = file_get_contents($envFile);
        if ($content === false) {
            $this->error("ไม่สามารถอ่านไฟล์ .env ได้");
            return;
        }

        $content = $this->upsertEnvValue($content, 'DB_CONNECTION', $dbConnection);
        $content = $this->upsertEnvValue($content, 'DB_DATABASE', $dbName);
        $content = $this->upsertEnvValue($content, 'DB_USERNAME', $dbUser);
        $content = $this->upsertEnvValue($content, 'DB_PASSWORD', $dbPassword);

        file_put_contents($envFile, $content);

        $this->success("  [OK] อัปเดตค่า DB ใน .env แล้ว");
    }

    private function upsertEnvValue(string $content, string $key, string $value): string
    {
        $pattern = '/^' . preg_quote($key, '/') . '=.*/m';
        $replacement = $key . '=' . $value;

        if (preg_match($pattern, $content) === 1) {
            return preg_replace($pattern, $replacement, $content) ?? $content;
        }

        return rtrim($content, "\n") . "\n" . $replacement . "\n";
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

    private function updateAppUrlForRename(string $oldFolder, string $newFolder, ?string $basePath = null): void
    {
        $envFile = $basePath === null
            ? $this->path('.env')
            : $basePath . DIRECTORY_SEPARATOR . '.env';
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

    private function updateRootHtaccessForRename(string $oldFolder, string $newFolder, ?string $basePath = null): void
    {
        $htaccess = $basePath === null
            ? $this->path('.htaccess')
            : $basePath . DIRECTORY_SEPARATOR . '.htaccess';
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
        $safeAppName = $this->escapePregReplacement($appName);
        $safeDescription = $this->escapePregReplacement($description);
        $content = preg_replace('/# SimpleBiz MVC Framework V2/', '# ' . $safeAppName, $content, 1);

        if ($description !== '') {
            $content = preg_replace('/\*\*เฟรมเวิร์ก MVC สำหรับระบบอีคอมเมิร์ซ[^*]+\*\*/', '**' . $safeDescription . '**', $content, 1);
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
# SimpleBiz MVC Framework V2 - .gitignore
#
# จุดประสงค์: ป้องกันไม่ให้ไฟล์ที่ละเอียดอ่อนและไฟล์ที่สร้างขึ้นถูก commit

# การตั้งค่าสภาพแวดล้อม (เก็บข้อมูลการเข้าถึง)
.env
.env.*
!.env.example
.env.testing
!.env.testing.example

# Composer dependencies
/vendor/
composer.lock

# บันทึก (อาจเก็บข้อมูลที่ละเอียดอ่อน)
/storage/logs/*.log
/storage/logs/*.txt

# Cache files
/storage/cache/*
!/storage/cache/.gitkeep

# ไฟล์ฐานข้อมูล (อาจเก็บข้อมูลที่ละเอียดอ่อน)
storage/database.sqlite

# ไฟล์ระบบ
.DS_Store
Thumbs.db
desktop.ini

# ไฟล์ IDE
.vscode/
.idea/
*.sublime-project
*.sublime-workspace

# PHP
*.cache
.phpunit.result.cache
/.phpunit.cache/

# ไฟล์ชั่วคราว
*.tmp
*.temp
*.swp
*.swo
*~
EOT;
    }

    private function changeGitRemote(string $newUrl): void
    {
        if (!is_dir($this->path('.git'))) {
            $this->warning("  [!] ไม่พบ Git repository, ข้ามขั้นตอนนี้");
            return;
        }

        $safeUrl = escapeshellarg($newUrl);
        exec("git remote remove origin 2>&1", $output, $returnCode);
        if ($returnCode === 0) {
            $this->success("  [OK] ลบ Git remote เดิมแล้ว");
        } else {
            $this->warning("  [!] ไม่สามารถลบ Git remote เดิมได้ (อาจไม่มี origin อยู่แล้ว)");
        }

        exec("git remote add origin {$safeUrl} 2>&1", $output, $returnCode);

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
        $this->success("  [OK] เริ่มต้น Git repository แล้ว");

        $safeUrl = escapeshellarg($newUrl);
        exec("git remote add origin {$safeUrl} 2>&1", $output, $returnCode);
        if ($returnCode === 0) {
            $this->success("  [OK] ตั้งค่า Git remote แล้ว");
        } else {
            $this->error("  [X] ไม่สามารถตั้งค่า Git remote ได้");
        }

        exec("git branch -M main 2>&1", $output, $returnCode);
        if ($returnCode === 0) {
            $this->success("  [OK] ตั้งค่า branch main แล้ว");
        } else {
            $this->error("  [X] ไม่สามารถตั้งค่า branch main ได้");
        }
    }

    private function isValidGitRemoteUrl(string $url): bool
    {
        if (preg_match('/^https?:\/\//i', $url) === 1) {
            return preg_match('/^https?:\/\/[\w.-]+(?::\d+)?\/[\w.\/-]+(?:\.git)?\/?$/i', $url) === 1;
        }

        if (preg_match('/^ssh:\/\//i', $url) === 1) {
            return preg_match('/^ssh:\/\/git@[\w.-]+(?::\d+)?\/[\w.\/-]+(?:\.git)?\/?$/i', $url) === 1;
        }

        return preg_match('/^git@[\w.-]+:[\w.\/-]+(?:\.git)?\/?$/i', $url) === 1;
    }

    private function escapePregReplacement(string $value): string
    {
        return str_replace(['\\', '$'], ['\\\\', '\\$'], $value);
    }

    private function hasComposer(): bool
    {
        exec("composer --version 2>&1", $output, $returnCode);
        return $returnCode === 0;
    }

    private function commitAndPush(string $projectName): void
    {
        exec("git add . 2>&1", $output, $returnCode);
        if ($returnCode !== 0) {
            $this->error("  [X] ไม่สามารถ add ไฟล์ได้");
            return;
        }

        $commitMessage = "Initial setup for {$projectName}";
        $safeCommitMessage = escapeshellarg($commitMessage);
        exec("git commit -m {$safeCommitMessage} 2>&1", $output, $returnCode);
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
