<?php
declare(strict_types=1);

namespace Tests\Feature;

final class TestUserRepositoryX implements TestUserRepositoryInterfaceX
{
    private TestDatabaseX $db;

    public function __construct(TestDatabaseX $db)
    {
        $this->db = $db;
    }

    public function findUser(int $id): array
    {
        return ['id' => $id, 'name' => 'User ' . $id];
    }
}
