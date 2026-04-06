<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\OrdemServico\Entities\OrdemServico;
use App\Domain\OrdemServico\Repositories\OrdemServicoRepositoryInterface;
use App\Domain\OrdemServico\ValueObjects\StatusOrdem;
use App\Infrastructure\Persistence\Eloquent\Models\OrdemServicoModel;

/**
 * CAMADA DE INFRAESTRUTURA — Implementação do Repositório
 * Implementa a interface definida no Domínio usando Eloquent ORM.
 * Faz o mapeamento: Model Eloquent ↔ Entidade de Domínio.
 */
class EloquentOrdemServicoRepository implements OrdemServicoRepositoryInterface
{
    public function findById(int $id): ?OrdemServico
    {
        $model = OrdemServicoModel::find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(): array
    {
        return OrdemServicoModel::all()
            ->map(fn($m) => $this->toEntity($m))
            ->toArray();
    }

    public function findByCliente(int $clienteId): array
    {
        return OrdemServicoModel::where('cliente_id', $clienteId)->get()
            ->map(fn($m) => $this->toEntity($m))
            ->toArray();
    }

    public function save(OrdemServico $ordem): OrdemServico
    {
        $model = $ordem->getId()
            ? OrdemServicoModel::findOrFail($ordem->getId())
            : new OrdemServicoModel();

        $model->fill([
            'cliente_id'        => $ordem->getClienteId(),
            'veiculo_id'        => $ordem->getVeiculoId(),
            'status'            => (string) $ordem->getStatus(),
            'descricao_problema'=> $ordem->getDescricao(),
            'valor_total'       => $ordem->getValorTotal(),
        ]);

        $model->save();
        return $this->toEntity($model);
    }

    public function delete(int $id): void
    {
        OrdemServicoModel::destroy($id);
    }

    private function toEntity(OrdemServicoModel $model): OrdemServico
    {
        return new OrdemServico(
            id: $model->id,
            clienteId: $model->cliente_id,
            veiculoId: $model->veiculo_id,
            status: StatusOrdem::from($model->status),
            descricaoProblema: $model->descricao_problema,
            diagnostico: $model->diagnostico,
            valorTotal: $model->valor_total,
            criadaEm: new \DateTimeImmutable($model->created_at),
            concluidaEm: $model->concluida_em
                ? new \DateTimeImmutable($model->concluida_em)
                : null,
        );
    }
}
