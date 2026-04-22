<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Cliente\Entities\Cliente;
use App\Domain\Cliente\Repositories\ClienteRepositoryInterface;
use App\Domain\Cliente\ValueObjects\Cnpj;
use App\Domain\Cliente\ValueObjects\Cpf;
use App\Domain\Cliente\ValueObjects\DocumentoFiscal;
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
            ->map(fn(ClienteModel $model) => $this->toEntity($model))
            ->toArray();
    }

    public function findByDocumentoTipo(string $documento, string $tipo): ?Cliente
    {
        $model = ClienteModel::query()
            ->where('documento', preg_replace('/\D/', '', $documento))
            ->where('tipo', $tipo)
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findByCpf(string $cpf): ?Cliente
    {
        $cpfValidado = new Cpf($cpf);
        $model = ClienteModel::query()
            ->where('documento', $cpfValidado->getRaw())
            ->where('tipo', 'pf')
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function save(Cliente $cliente): Cliente
    {
        $model = $cliente->getId()
            ? ClienteModel::query()->findOrFail($cliente->getId())
            : new ClienteModel();

        $model->fill([
            'nome' => $cliente->getNome(),
            'tipo' => $cliente->getTipo(),
            'documento' => $cliente->getDocumento()->getRaw(),
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
        $tipo = $model->tipo ?? 'pf';
        $documento = $this->toDocumentoFiscal($model->documento, $tipo);

        return new Cliente(
            id: $model->id,
            nome: $model->nome,
            tipo: $tipo,
            documento: $documento,
            telefone: $model->telefone,
            email: $model->email,
            criadoEm: new \DateTimeImmutable($model->created_at),
        );
    }

    private function toDocumentoFiscal(string $documento, string $tipo): DocumentoFiscal
    {
        return $tipo === 'pj' ? new Cnpj($documento) : new Cpf($documento);
    }
}
