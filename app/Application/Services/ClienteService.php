<?php

namespace App\Application\Services;

use App\Domain\Cliente\Repositories\ClienteRepositoryInterface;
use App\Domain\Cliente\Entities\Cliente;
use App\Domain\Cliente\ValueObjects\Cpf;
use App\Domain\Cliente\ValueObjects\Cnpj;
use App\Domain\Cliente\ValueObjects\DocumentoFiscal;

/**
 * CAMADA DE APLICAÇÃO — Application Service
 */
class ClienteService
{
    public function __construct(
        private ClienteRepositoryInterface $repository
    ) {}

    public function listarTodos(): array
    {
        return $this->repository->findAll();
    }

    public function buscarPorId(int $id): Cliente
    {
        $cliente = $this->repository->findById($id);

        if (!$cliente) {
            throw new \RuntimeException("Cliente #{$id} não encontrado.");
        }

        return $cliente;
    }

    public function criar(array $data): Cliente
    {
        $tipo = (string) $data['tipo'];
        $documento = $this->buildDocumentoFiscal($tipo, (string) $data['documento']);

        $cliente = new Cliente(
            id: null,
            nome: $data['nome'],
            tipo: $tipo,
            documento: $documento,
            telefone: $data['telefone'],
            email: $data['email'],
            criadoEm: new \DateTimeImmutable(),
        );

        return $this->repository->save($cliente);
    }

    public function atualizar(int $id, array $data): Cliente
    {
        $cliente = $this->buscarPorId($id);
        $tipo = (string) $data['tipo'];
        $documento = $this->buildDocumentoFiscal($tipo, (string) $data['documento']);

        $cliente->atualizar(
            $data['nome'],
            $tipo,
            $documento,
            $data['telefone'],
            $data['email']
        );

        return $this->repository->save($cliente);
    }

    public function remover(int $id): void
    {
        $this->buscarPorId($id);
        $this->repository->delete($id);
    }

    private function buildDocumentoFiscal(string $tipo, string $documento): DocumentoFiscal
    {
        return match ($tipo) {
            'pf' => new Cpf($documento),
            'pj' => new Cnpj($documento),
            default => throw new \InvalidArgumentException('Tipo de cliente inválido. Use pf ou pj.'),
        };
    }
}
