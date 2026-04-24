<?php

namespace App\Application\Services;

use App\Domain\Insumo\Entities\Insumo;
use App\Domain\Insumo\Repositories\InsumoRepositoryInterface;

class InsumoService
{
    public function __construct(
        private InsumoRepositoryInterface $repository
    ) {}

    public function listarTodas(): array
    {
        return $this->repository->findAll();
    }

    public function buscarPorId(int $id): Insumo
    {
        $insumo = $this->repository->findById($id);
        if (!$insumo) {
            throw new \RuntimeException("Insumo #{$id} não encontrado.");
        }
        return $insumo;
    }

    public function criar(array $data): Insumo
    {
        $insumo = new Insumo(
            id: null,
            nome: $data['nome'],
            unidadeMedida: $data['unidade_medida'],
            precoUnitario: (float) $data['preco_unitario'],
            estoqueAtual: (float) ($data['estoque_atual'] ?? 0),
            estoqueMininmo: (float) ($data['estoque_minimo'] ?? 0),
            criadoEm: new \DateTimeImmutable(),
        );

        return $this->repository->save($insumo);
    }

    public function atualizar(int $id, array $data): Insumo
    {
        $insumo = $this->buscarPorId($id);

        $insumo->atualizar(
            $data['nome'],
            $data['unidade_medida'],
            (float) $data['preco_unitario'],
            (float) ($data['estoque_atual'] ?? $insumo->getEstoqueAtual()),
            (float) ($data['estoque_minimo'] ?? $insumo->getEstoqueMininmo()),
        );

        return $this->repository->save($insumo);
    }

    public function remover(int $id): void
    {
        $this->buscarPorId($id);
        $this->repository->delete($id);
    }
}
