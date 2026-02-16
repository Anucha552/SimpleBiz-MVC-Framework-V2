<?php

declare(strict_types=1);

namespace App\Console\Commands;

class TestFeatureCommand extends BaseCommand
{
    public function name(): string
    {
        return 'test:feature';
    }

    public function aliases(): array
    {
        return ['t:f'];
    }

    protected function execute(array $args): void
    {
        $this->info("กำลังรัน Feature Tests...");
        echo "\n";

        array_unshift($args, '--testsuite=Feature');
        $runner = new TestCommand();
        $runner->handle($args, $this->context);
    }
}
