<?php

namespace App\Domain\OrdemServico\Repositories;

use App\Domain\OrdemServico\Entities\OrdemServico;

/**
 * CAMADA DE DOMÍNIO — Interface de Repositório
 * O domínio define O CONTRATO; a infraestrutura implementa.
 * Isso mantém o domínio independente do banco de dados.
 */
interface OrdemServicoRepositoryInterface
{
    public function findById(int $id): ?OrdemServico;
    public function findAll(): array;
    public function findByCliente(int $clienteId): array;
    public function findRecebidas24Horas(): array;
    public function save(OrdemServico $ordem): OrdemServico;
    public function delete(int $id): void;
    public function processarOrcamento(
        int $id,
        OrdemServico $ordem,
        int $mecanicoId,
        string $diagnostico,
        float $maoDeObra,
        array $pecasInput,
        array $insumosInput
    ): array;

    public function reprovarOrcamento(int $id, OrdemServico $ordem): OrdemServico;
}
