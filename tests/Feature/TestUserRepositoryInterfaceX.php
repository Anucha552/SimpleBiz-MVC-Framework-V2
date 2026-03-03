<?php
declare(strict_types=1);

namespace Tests\Feature;

interface TestUserRepositoryInterfaceX
{
    public function findUser(int $id): array;
}
