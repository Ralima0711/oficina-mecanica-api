<?php

namespace App\Application\Services;

use App\Domain\Peca\Entities\Peca;
use App\Domain\Peca\Repositories\PecaRepositoryInterface;

class PecaService
{
    public function __construct(
        private PecaRepositoryInterface $repository
    ) {}

    public function listarTodas(): array
    {
        return $this->repository->findAll();
    }

    public function buscarPorId(int $id): Peca
    {
        $peca = $this->repository->findById($id);
        if (!$peca) {
            throw new \RuntimeException("Peça #{$id} não encontrada.");
        }
        return $peca;
    }

    public function buscarPorCodigo(string $codigo): Peca
    {
        $peca = $this->repository->findByCodigo($codigo);
        if (!$peca) {
            throw new \RuntimeException("Peça com código '{$codigo}' não encontrada.");
        }
        return $peca;
    }

    public function criar(array $data): Peca
    {
        $peca = new Peca(
            id: null,
            nome: $data['nome'],
            codigo: $data['codigo'],
            categoria: $data['categoria'] ?? null,
            precoUnitario: (float) $data['preco_unitario'],
            estoqueAtual: $data['estoque_atual'] ?? 0,
            estoqueMininmo: $data['estoque_minimo'] ?? 0,
            criadoEm: new \DateTimeImmutable(),
        );

        return $this->repository->save($peca);
    }

    public function atualizar(int $id, array $data): Peca
    {
        $peca = $this->buscarPorId($id);

        $peca->atualizar(
            $data['nome'],
            $data['codigo'],
            $data['categoria'] ?? null,
            (float) $data['preco_unitario'],
            $data['estoque_atual'] ?? $peca->getEstoqueAtual(),
            $data['estoque_minimo'] ?? $peca->getEstoqueMininmo(),
        );

        return $this->repository->save($peca);
    }

    public function remover(int $id): void
    {
        $this->buscarPorId($id);
        $this->repository->delete($id);
    }
}
