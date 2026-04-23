<?php

namespace App\Domain\User\Repositories;

use App\Domain\User\Entities\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function findByRole(string $role): array;

    public function findAll(): array;

    public function save(User $user): User;

    public function delete(int $id): void;
}
