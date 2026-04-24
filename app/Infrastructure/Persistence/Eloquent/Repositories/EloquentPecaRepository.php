<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Peca\Entities\Peca;
use App\Domain\Peca\Repositories\PecaRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\PecaModel;

class EloquentPecaRepository implements PecaRepositoryInterface
{
    public function findAll(): array
    {
        return PecaModel::all()->map(fn($model) => $this->toDomain($model))->toArray();
    }

    public function findById(int $id): ?Peca
    {
        $model = PecaModel::find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function findByCodigo(string $codigo): ?Peca
    {
        $model = PecaModel::where('codigo', $codigo)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function save(Peca $peca): Peca
    {
        if ($peca->getId()) {
            $model = PecaModel::find($peca->getId());
            $model->update([
                'nome' => $peca->getNome(),
                'codigo' => $peca->getCodigo(),
                'categoria' => $peca->getCategoria(),
                'preco_unitario' => $peca->getPrecoUnitario(),
                'estoque_atual' => $peca->getEstoqueAtual(),
                'estoque_minimo' => $peca->getEstoqueMininmo(),
            ]);
        } else {
            $model = PecaModel::create([
                'nome' => $peca->getNome(),
                'codigo' => $peca->getCodigo(),
                'categoria' => $peca->getCategoria(),
                'preco_unitario' => $peca->getPrecoUnitario(),
                'estoque_atual' => $peca->getEstoqueAtual(),
                'estoque_minimo' => $peca->getEstoqueMininmo(),
            ]);
        }

        return $this->toDomain($model);
    }

    public function delete(int $id): void
    {
        PecaModel::destroy($id);
    }

    private function toDomain(PecaModel $model): Peca
    {
        return new Peca(
            id: $model->id,
            nome: $model->nome,
            codigo: $model->codigo,
            categoria: $model->categoria,
            precoUnitario: $model->preco_unitario,
            estoqueAtual: $model->estoque_atual,
            estoqueMininmo: $model->estoque_minimo,
            criadoEm: $model->created_at->toDateTimeImmutable(),
            atualizadoEm: $model->updated_at->toDateTimeImmutable(),
        );
    }
}
