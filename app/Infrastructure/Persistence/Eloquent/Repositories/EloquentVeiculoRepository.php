<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Veiculo\Entities\Veiculo;
use App\Domain\Veiculo\Repositories\VeiculoRepositoryInterface;
use App\Domain\Veiculo\ValueObjects\Placa;
use App\Infrastructure\Persistence\Eloquent\Models\VeiculoModel;

class EloquentVeiculoRepository implements VeiculoRepositoryInterface
{
    public function findById(int $id): ?Veiculo
    {
        $model = VeiculoModel::query()->find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(): array
    {
        return VeiculoModel::query()
            ->get()
            ->map(fn(VeiculoModel $model) => $this->toEntity($model))
            ->toArray();
    }

    public function findByPlaca(string $placa): ?Veiculo
    {
        $placa = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim($placa)));
        $model = VeiculoModel::query()->where('placa', $placa)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function save(Veiculo $veiculo): Veiculo
    {
        $model = $veiculo->getId()
            ? VeiculoModel::query()->findOrFail($veiculo->getId())
            : new VeiculoModel();

        $model->fill([
            'cliente_id' => $veiculo->getClienteId(),
            'placa' => $veiculo->getPlaca()->getRaw(),
            'marca' => $veiculo->getMarca(),
            'modelo' => $veiculo->getModelo(),
            'ano' => $veiculo->getAno(),
            'cor' => $veiculo->getCor(),
        ]);

        $model->save();

        return $this->toEntity($model->refresh());
    }

    public function delete(int $id): void
    {
        VeiculoModel::query()->findOrFail($id)->delete();
    }

    private function toEntity(VeiculoModel $model): Veiculo
    {
        return new Veiculo(
            id: $model->id,
            clienteId: $model->cliente_id,
            placa: new Placa($model->placa),
            marca: $model->marca,
            modelo: $model->modelo,
            ano: $model->ano,
            cor: $model->cor,
            criadoEm: new \DateTimeImmutable($model->created_at),
        );
    }
}
