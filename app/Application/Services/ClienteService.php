<?php

namespace App\Application\Services;

use App\Domain\Cliente\Repositories\ClienteRepositoryInterface;
use App\Domain\Cliente\Entities\Cliente;
use App\Domain\Cliente\ValueObjects\Cpf;

/**
 * CAMADA DE APLICAÇÃO — Application Service
 */
class ClienteService
{
    public function __construct(
        private ClienteRepositoryInterface $repository
    ) {}

    public function listarTodos(): array
    {
        return $this->repository->findAll();
    }

    public function buscarPorId(int $id): Cliente
    {
        $cliente = $this->repository->findById($id);

        if (!$cliente) {
            throw new \RuntimeException("Cliente #{$id} não encontrado.");
        }

        return $cliente;
    }

    public function criar(array $data): Cliente
    {
        $cliente = new Cliente(
            id: null,
            nome: $data['nome'],
            cpf: new Cpf($data['cpf']),
            telefone: $data['telefone'],
            email: $data['email'],
            criadoEm: new \DateTimeImmutable(),
        );

        return $this->repository->save($cliente);
    }

    public function atualizar(int $id, array $data): Cliente
    {
        $cliente = $this->buscarPorId($id);
        $cliente->atualizar($data['nome'], $data['telefone'], $data['email']);
        return $this->repository->save($cliente);
    }

    public function remover(int $id): void
    {
        $this->buscarPorId($id);
        $this->repository->delete($id);
    }
}
