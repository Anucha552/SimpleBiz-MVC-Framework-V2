<?php

declare(strict_types=1);

namespace App\Console\Commands;

class OptimizeClearCommand extends BaseCommand
{
    public function name(): string
    {
        return 'optimize:clear';
    }

    protected function execute(array $args): void
    {
        $this->info("กำลังลบ optimization caches...");
        $clear = new CacheClearCommand();
        $clear->handle([], $this->context);
        $this->success("ลบ optimization caches เรียบร้อยแล้ว");
    }
}
