<?php

namespace App\Application\Services;

use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\User\Entities\User;

/**
 * CAMADA DE APLICAÇÃO — Application Service
 */
class UserService
{
    public function __construct(
        private UserRepositoryInterface $repository
    ) {}

    public function listarTodos(): array
    {
        // Para segurança, pode listar apenas dados básicos sem senhas
        return $this->repository->findAll();
    }

    public function buscarPorId(int $id): User
    {
        $user = $this->repository->findById($id);

        if (!$user) {
            throw new \RuntimeException("Usuário #{$id} não encontrado.");
        }

        return $user;
    }

    public function buscarPorEmail(string $email): User
    {
        $user = $this->repository->findByEmail($email);

        if (!$user) {
            throw new \RuntimeException("Usuário com email '{$email}' não encontrado.");
        }

        return $user;
    }

    public function criar(array $data): User
    {
        // Verifica se email já existe
        try {
            $this->buscarPorEmail($data['email']);
            throw new \InvalidArgumentException('Email já cadastrado no sistema.');
        } catch (\RuntimeException) {
            // Email não existe, pode prosseguir
        }

        $user = new User(
            id: null,
            name: $data['name'],
            email: $data['email'],
            password: bcrypt($data['password']),
            role: $data['role'] ?? 'user',
        );

        return $this->repository->save($user);
    }

    public function atualizar(int $id, array $data): User
    {
        $user = $this->buscarPorId($id);

        $user = new User(
            id: $user->getId(),
            name: $data['name'] ?? $user->getName(),
            email: $data['email'] ?? $user->getEmail(),
            password: isset($data['password']) ? bcrypt($data['password']) : $user->getPassword(),
            role: $data['role'] ?? $user->getRole(),
        );

        return $this->repository->save($user);
    }

    public function remover(int $id): void
    {
        $this->buscarPorId($id);
        $this->repository->delete($id);
    }
}
