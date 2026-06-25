<?php

use App\Core\Logger;
use PHPUnit\Framework\TestCase;

final class LoggerTest extends TestCase
{
    private string $logDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logDir = dirname(__DIR__) . '/tmp/logger-disabled-' . bin2hex(random_bytes(4));
        putenv('LOG_ENABLED=true');
        $_ENV['LOG_ENABLED'] = 'true';
        $_SERVER['LOG_ENABLED'] = 'true';
    }

    protected function tearDown(): void
    {
        putenv('LOG_ENABLED=true');
        $_ENV['LOG_ENABLED'] = 'true';
        $_SERVER['LOG_ENABLED'] = 'true';
        $this->removeDirectory($this->logDir);
        parent::tearDown();
    }

    public function testLoggerDoesNotCreateOrWriteLogFileWhenDisabled(): void
    {
        putenv('LOG_ENABLED=false');
        $_ENV['LOG_ENABLED'] = 'false';
        $_SERVER['LOG_ENABLED'] = 'false';

        $logger = new Logger($this->logDir . '/');
        $logger->info('disabled.test', ['message' => 'should not be written']);

        $this->assertDirectoryDoesNotExist($this->logDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = glob($dir . '/*');
        if ($files !== false) {
            foreach ($files as $file) {
                is_dir($file) ? $this->removeDirectory($file) : @unlink($file);
            }
        }

        @rmdir($dir);
    }
}