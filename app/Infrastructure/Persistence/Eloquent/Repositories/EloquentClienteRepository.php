<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Cliente\Entities\Cliente;
use App\Domain\Cliente\Repositories\ClienteRepositoryInterface;
use App\Domain\Cliente\ValueObjects\Cpf;
use App\Infrastructure\Persistence\Eloquent\Models\ClienteModel;

class EloquentClienteRepository implements ClienteRepositoryInterface
{
    public function findById(int $id): ?Cliente
    {
        $model = ClienteModel::query()->find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(): array
    {
        return ClienteModel::query()
            ->get()
            ->map(fn($model) => $this->toEntity($model))
            ->toArray();
    }

    public function findByCpf(string $cpf): ?Cliente
    {
        $model = ClienteModel::query()->where('cpf', $cpf)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function save(Cliente $cliente): Cliente
    {
        $model = $cliente->getId()
            ? ClienteModel::query()->findOrFail($cliente->getId())
            : new ClienteModel();

        $model->fill([
            'nome' => $cliente->getNome(),
            'cpf' => (string) $cliente->getCpf(),
            'telefone' => $cliente->getTelefone(),
            'email' => $cliente->getEmail(),
        ]);

        $model->save();

        return $this->toEntity($model->refresh());
    }

    public function delete(int $id): void
    {
        ClienteModel::query()->findOrFail($id)->delete();
    }

    private function toEntity(ClienteModel $model): Cliente
    {
        return new Cliente(
            id: $model->id,
            nome: $model->nome,
            cpf: new Cpf($model->cpf),
            telefone: $model->telefone,
            email: $model->email,
            criadoEm: new \DateTimeImmutable($model->created_at),
        );
    }
}
