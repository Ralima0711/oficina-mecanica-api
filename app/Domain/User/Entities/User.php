<?php

namespace App\Domain\User\Entities;

/**
 * CAMADA DE DOMÍNIO — Entidade User
 * Representa um usuário autenticado no sistema.
 */
class User implements \JsonSerializable
{
    public function __construct(
        private ?int $id,
        private string $name,
        private string $email,
        private string $password,
        private string $role = 'user',
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];
    }
}
