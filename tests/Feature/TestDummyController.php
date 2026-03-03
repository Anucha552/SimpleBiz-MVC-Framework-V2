<?php
declare(strict_types=1);

namespace Tests\Feature;

final class TestDummyController
{
    public function show($id = null)
    {
        return 'ITEM:' . ($id ?? 'none');
    }
}
