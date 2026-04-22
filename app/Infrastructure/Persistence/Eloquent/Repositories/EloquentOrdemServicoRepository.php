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
            ->map(fn(OrdemServicoModel $m) => $this->toEntity($m))
            ->toArray();
    }

    public function findByCliente(int $clienteId): array
    {
        return OrdemServicoModel::where('cliente_id', $clienteId)->get()
            ->map(fn(OrdemServicoModel $m) => $this->toEntity($m))
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
            'mecanico_id'       => $ordem->getMecanicoId(),
            'status'            => (string) $ordem->getStatus(),
            'descricao_problema'=> $ordem->getDescricao(),
            'diagnostico'       => $ordem->getDiagnostico(),
            'valor_total'       => $ordem->getValorTotal(),
            'iniciada_em'       => $ordem->getIniciadaEm()?->format('Y-m-d H:i:s'),
            'concluida_em'      => $ordem->getConcluidaEm()?->format('Y-m-d H:i:s'),
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
            mecanicoId: $model->mecanico_id,
            status: StatusOrdem::from($model->status),
            descricaoProblema: $model->descricao_problema,
            diagnostico: $model->diagnostico,
            valorTotal: $model->valor_total !== null ? (float) $model->valor_total : null,
            iniciadaEm: $model->iniciada_em?->toDateTimeImmutable(),
            criadaEm: $model->created_at?->toDateTimeImmutable() ?? new \DateTimeImmutable(),
            atualizadaEm: $model->updated_at?->toDateTimeImmutable(),
            concluidaEm: $model->concluida_em?->toDateTimeImmutable(),
        );
    }
}
