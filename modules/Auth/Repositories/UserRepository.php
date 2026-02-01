<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use App\Core\Database;

final class UserRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $row = $this->db->fetch('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public function findByEmail(string $email): ?array
    {
        $row = $this->db->fetch('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public function findByUsername(string $username): ?array
    {
        $row = $this->db->fetch('SELECT * FROM users WHERE username = ? LIMIT 1', [$username]);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO users (username, email, password, status, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())',
            [
                (string)($data['username'] ?? ''),
                (string)($data['email'] ?? ''),
                (string)($data['password'] ?? ''),
                (string)($data['status'] ?? 'active'),
            ]
        );

        return (int)$this->db->lastInsertId();
    }
}
