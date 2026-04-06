<?php

namespace App\Application\Services;

use App\Domain\OrdemServico\Repositories\OrdemServicoRepositoryInterface;
use App\Domain\OrdemServico\Entities\OrdemServico;
use App\Domain\OrdemServico\ValueObjects\StatusOrdem;

/**
 * CAMADA DE APLICAÇÃO — Application Service
 * Orquestra os casos de uso. NÃO contém regra de negócio.
 * Não altera o estado dos objetos diretamente — delega ao Domínio.
 */
class OrdemServicoService
{
    public function __construct(
        private OrdemServicoRepositoryInterface $repository
    ) {}

    public function listarTodas(): array
    {
        return $this->repository->findAll();
    }

    public function buscarPorId(int $id): OrdemServico
    {
        $os = $this->repository->findById($id);

        if (!$os) {
            throw new \RuntimeException("Ordem de Serviço #{$id} não encontrada.");
        }

        return $os;
    }

    public function criar(array $data): OrdemServico
    {
        $os = new OrdemServico(
            id: null,
            clienteId: $data['cliente_id'],
            veiculoId: $data['veiculo_id'],
            status: StatusOrdem::from('ABERTA'),
            descricaoProblema: $data['descricao_problema'],
            diagnostico: null,
            valorTotal: null,
            criadaEm: new \DateTimeImmutable(),
        );

        return $this->repository->save($os);
    }

    public function atualizar(int $id, array $data): OrdemServico
    {
        $os = $this->buscarPorId($id);
        // TODO: mapear campos permitidos para atualização
        return $this->repository->save($os);
    }

    public function iniciar(int $id): OrdemServico
    {
        $os = $this->buscarPorId($id);
        $os->iniciar(); // regra de negócio no domínio
        return $this->repository->save($os);
    }

    public function concluir(int $id, float $valorTotal): OrdemServico
    {
        $os = $this->buscarPorId($id);
        $os->concluir($valorTotal);
        return $this->repository->save($os);
    }

    public function cancelar(int $id): void
    {
        $os = $this->buscarPorId($id);
        $os->cancelar();
        $this->repository->save($os);
    }
}
