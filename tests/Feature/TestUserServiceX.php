<?php
declare(strict_types=1);

namespace Tests\Feature;

final class TestUserServiceX
{
    private TestUserRepositoryInterfaceX $repo;
    private TestDatabaseX $db;

    public function __construct(TestUserRepositoryInterfaceX $repo, TestDatabaseX $db)
    {
        $this->repo = $repo;
        $this->db = $db;
    }

    public function getUser(int $id): array
    {
        return $this->repo->findUser($id);
    }
}
