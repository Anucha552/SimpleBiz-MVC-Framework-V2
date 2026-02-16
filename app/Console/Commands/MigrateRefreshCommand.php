<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\ConsoleColor;

class MigrateRefreshCommand extends BaseCommand
{
    public function name(): string
    {
        return 'migrate:refresh';
    }

    public function aliases(): array
    {
        return ['m:refresh'];
    }

    protected function execute(array $args): void
    {
        if (!$this->checkDatabaseConnection()) {
            exit(1);
        }

        echo ConsoleColor::RED . "[WARNING] จะ reset และ migrate ใหม่ทั้งหมด!" . ConsoleColor::RESET . "\n\n";

        if (!$this->hasForceFlag($args)) {
            if (!$this->confirm("คุณแน่ใจหรือไม่?")) {
                $this->warning("ยกเลิกการทำงาน");
                return;
            }
            echo "\n";
        }

        $this->info("กำลัง refresh migrations (reset + migrate)...");

        $seed = in_array('--seed', $args, true);
        $force = $this->hasForceFlag($args);

        $resetCommand = new MigrateResetCommand();
        $resetCommand->handle($force ? ['--force'] : [], $this->context);

        echo "\n";

        $migrateCommand = new MigrateCommand();
        $migrateCommand->handle([], $this->context);

        if ($seed) {
            echo "\n";
            $this->info("กำลัง seed ข้อมูล...");
            $seedCommand = new SeedCommand();
            $seedCommand->handle([], $this->context);
        }

        echo "\n";
        $this->success("Refresh migrations เรียบร้อยแล้ว" . ($seed ? " (รวม seeding)" : ""));
    }
}
