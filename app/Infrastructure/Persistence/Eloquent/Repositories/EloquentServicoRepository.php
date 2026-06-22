<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Servico\Entities\Servico;
use App\Domain\Servico\Repositories\ServicoRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\ServicoModel;

class EloquentServicoRepository implements ServicoRepositoryInterface
{
    public function findAll(): array
    {
        return ServicoModel::all()->map(fn($m) => $this->toDomain($m))->toArray();
    }

    public function findById(int $id): ?Servico
    {
        $model = ServicoModel::find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function findByCodigo(string $codigo): ?Servico
    {
        $model = ServicoModel::where('codigo', $codigo)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function save(Servico $servico): Servico
    {
        if ($servico->getId()) {
            $model = ServicoModel::find($servico->getId());
            $model->update([
                'nome' => $servico->getNome(),
                'codigo' => $servico->getCodigo(),
                'descricao' => $servico->getDescricao(),
                'categoria' => $servico->getCategoria(),
                'preco_base' => $servico->getPrecoBase(),
                'duracao_estimada_minutos' => $servico->getDuracaoEstimadaMinutos(),
            ]);
        } else {
            $model = ServicoModel::create([
                'nome' => $servico->getNome(),
                'codigo' => $servico->getCodigo(),
                'descricao' => $servico->getDescricao(),
                'categoria' => $servico->getCategoria(),
                'preco_base' => $servico->getPrecoBase(),
                'duracao_estimada_minutos' => $servico->getDuracaoEstimadaMinutos(),
            ]);
        }

        return $this->toDomain($model);
    }

    public function delete(int $id): void
    {
        ServicoModel::destroy($id);
    }

    private function toDomain(ServicoModel $model): Servico
    {
        return new Servico(
            id: $model->id,
            nome: $model->nome,
            codigo: $model->codigo,
            descricao: $model->descricao,
            categoria: $model->categoria,
            precoBase: (float) $model->preco_base,
            duracaoEstimadaMinutos: (int) $model->duracao_estimada_minutos,
            criadoEm: $model->created_at->toDateTimeImmutable(),
            atualizadoEm: $model->updated_at?->toDateTimeImmutable(),
        );
    }
}
