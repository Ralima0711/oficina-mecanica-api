<?php

namespace App\Domain\OrdemServico\Entities;

use App\Domain\OrdemServico\ValueObjects\StatusOrdem;

/**
 * CAMADA DE DOMÍNIO — Entidade
 * "O coração do software" (Evans, 2003).
 * Contém identidade própria e regras de negócio da Ordem de Serviço.
 * NÃO depende de framework, banco de dados ou infraestrutura.
 */
class OrdemServico
{
    public function __construct(
        private ?int $id,
        private int $clienteId,
        private int $veiculoId,
        private StatusOrdem $status,
        private string $descricaoProblema,
        private ?string $diagnostico,
        private ?float $valorTotal,
        private \DateTimeImmutable $criadaEm,
        private ?\DateTimeImmutable $concluidaEm = null,
    ) {}

    // ── Regras de negócio ──────────────────────────────────────────

    public function iniciar(): void
    {
        if (!$this->status->equals(StatusOrdem::ABERTA)) {
            throw new \DomainException('Somente ordens ABERTAS podem ser iniciadas.');
        }
        $this->status = StatusOrdem::EM_ANDAMENTO;
    }

    public function concluir(float $valorTotal): void
    {
        if (!$this->status->equals(StatusOrdem::EM_ANDAMENTO)) {
            throw new \DomainException('Somente ordens EM ANDAMENTO podem ser concluídas.');
        }
        $this->status      = StatusOrdem::CONCLUIDA;
        $this->valorTotal  = $valorTotal;
        $this->concluidaEm = new \DateTimeImmutable();
    }

    public function cancelar(): void
    {
        if ($this->status->equals(StatusOrdem::CONCLUIDA)) {
            throw new \DomainException('Ordens CONCLUÍDAS não podem ser canceladas.');
        }
        $this->status = StatusOrdem::CANCELADA;
    }

    // ── Getters ───────────────────────────────────────────────────

    public function getId(): ?int            { return $this->id; }
    public function getClienteId(): int      { return $this->clienteId; }
    public function getVeiculoId(): int      { return $this->veiculoId; }
    public function getStatus(): StatusOrdem { return $this->status; }
    public function getDescricao(): string   { return $this->descricaoProblema; }
    public function getValorTotal(): ?float  { return $this->valorTotal; }
    public function getCriadaEm(): \DateTimeImmutable { return $this->criadaEm; }
}
