<?php

namespace App\Application\Services;

use App\Domain\Veiculo\Entities\Veiculo;
use App\Domain\Veiculo\Repositories\VeiculoRepositoryInterface;
use App\Domain\Veiculo\ValueObjects\Placa;

class VeiculoService
{
    public function __construct(
        private VeiculoRepositoryInterface $repository
    ) {}

    public function listarTodos(): array
    {
        return $this->repository->findAll();
    }

    public function buscarPorId(int $id): Veiculo
    {
        $veiculo = $this->repository->findById($id);

        if (!$veiculo) {
            throw new \RuntimeException("Veículo #{$id} não encontrado.");
        }

        return $veiculo;
    }

    public function criar(array $data): Veiculo
    {
        $veiculo = new Veiculo(
            id: null,
            clienteId: $data['cliente_id'],
            placa: new Placa($data['placa']),
            marca: $data['marca'],
            modelo: $data['modelo'],
            ano: (int) $data['ano'],
            cor: $data['cor'] ?? null,
            criadoEm: new \DateTimeImmutable(),
        );

        return $this->repository->save($veiculo);
    }

    public function atualizar(int $id, array $data): Veiculo
    {
        $veiculo = $this->buscarPorId($id);
        $veiculo->atualizar(
            $data['cliente_id'], 
            new Placa($data['placa']),
            $data['marca'],
            $data['modelo'],
            (int) $data['ano'],
            $data['cor'] ?? null,
        );

        return $this->repository->save($veiculo);
    }

    public function remover(int $id): void
    {
        $this->buscarPorId($id);
        $this->repository->delete($id);
    }
}
