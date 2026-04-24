<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Insumo\Entities\Insumo;
use App\Domain\Insumo\Repositories\InsumoRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\InsumoModel;

class EloquentInsumoRepository implements InsumoRepositoryInterface
{
    public function findAll(): array
    {
        return InsumoModel::all()->map(fn($model) => $this->toDomain($model))->toArray();
    }

    public function findById(int $id): ?Insumo
    {
        $model = InsumoModel::find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function save(Insumo $insumo): Insumo
    {
        if ($insumo->getId()) {
            $model = InsumoModel::find($insumo->getId());
            $model->update([
                'nome' => $insumo->getNome(),
                'unidade_medida' => $insumo->getUnidadeMedida(),
                'preco_unitario' => $insumo->getPrecoUnitario(),
                'estoque_atual' => $insumo->getEstoqueAtual(),
                'estoque_minimo' => $insumo->getEstoqueMininmo(),
            ]);
        } else {
            $model = InsumoModel::create([
                'nome' => $insumo->getNome(),
                'unidade_medida' => $insumo->getUnidadeMedida(),
                'preco_unitario' => $insumo->getPrecoUnitario(),
                'estoque_atual' => $insumo->getEstoqueAtual(),
                'estoque_minimo' => $insumo->getEstoqueMininmo(),
            ]);
        }

        return $this->toDomain($model);
    }

    public function delete(int $id): void
    {
        InsumoModel::destroy($id);
    }

    private function toDomain(InsumoModel $model): Insumo
    {
        return new Insumo(
            id: $model->id,
            nome: $model->nome,
            unidadeMedida: $model->unidade_medida,
            precoUnitario: $model->preco_unitario,
            estoqueAtual: $model->estoque_atual,
            estoqueMininmo: $model->estoque_minimo,
            criadoEm: $model->created_at->toDateTimeImmutable(),
            atualizadoEm: $model->updated_at->toDateTimeImmutable(),
        );
    }
}
