<?php

namespace App\Application\Services;

use App\Domain\Servico\Entities\Servico;
use App\Domain\Servico\Repositories\ServicoRepositoryInterface;

class ServicoService
{
    public function __construct(
        private ServicoRepositoryInterface $repository
    ) {}

    public function listarTodos(): array
    {
        return $this->repository->findAll();
    }

    public function buscarPorId(int $id): Servico
    {
        $servico = $this->repository->findById($id);
        if (!$servico) {
            throw new \RuntimeException("Serviço #{$id} não encontrado.");
        }
        return $servico;
    }

    public function criar(array $data): Servico
    {
        $servico = new Servico(
            id: null,
            nome: $data['nome'],
            codigo: $data['codigo'],
            descricao: $data['descricao'] ?? null,
            categoria: $data['categoria'] ?? null,
            precoBase: (float) $data['preco_base'],
            duracaoEstimadaMinutos: (int) ($data['duracao_estimada_minutos'] ?? 0),
            criadoEm: new \DateTimeImmutable(),
        );

        return $this->repository->save($servico);
    }

    public function atualizar(int $id, array $data): Servico
    {
        $servico = $this->buscarPorId($id);

        $servico->atualizar(
            nome: $data['nome'],
            codigo: $data['codigo'],
            descricao: $data['descricao'] ?? null,
            categoria: $data['categoria'] ?? null,
            precoBase: (float) $data['preco_base'],
            duracaoEstimadaMinutos: (int) ($data['duracao_estimada_minutos'] ?? $servico->getDuracaoEstimadaMinutos()),
        );

        return $this->repository->save($servico);
    }

    public function remover(int $id): void
    {
        $this->buscarPorId($id);
        $this->repository->delete($id);
    }
}
