<?php

namespace App\Domain\OrdemServico\Entities;

use App\Domain\OrdemServico\ValueObjects\StatusOrdem;

/**
 * CAMADA DE DOMÍNIO — Entidade
 * "O coração do software" (Evans, 2003).
 * Contém identidade própria e regras de negócio da Ordem de Serviço.
 * NÃO depende de framework, banco de dados ou infraestrutura.
 */
class OrdemServico implements \JsonSerializable
{
    public function __construct(
        private ?int $id,
        private int $clienteId,
        private int $veiculoId,
        private ?int $mecanicoId,
        private StatusOrdem $status,
        private string $descricaoProblema,
        private ?string $diagnostico,
        private ?float $valorTotal,
        private ?\DateTimeImmutable $iniciadaEm,
        private \DateTimeImmutable $criadaEm,
        private ?\DateTimeImmutable $atualizadaEm = null,
        private ?\DateTimeImmutable $concluidaEm = null,
    ) {}

    // ── Regras de negócio ──────────────────────────────────────────

    public function atualizar(array $dados): void
    {
        if (!$this->podeEditar()) {
            throw new \DomainException('Ordens FINALIZADAS ou CANCELADAS nao podem ser alteradas.');
        }

        if (array_key_exists('cliente_id', $dados)) {
            $this->clienteId = (int) $dados['cliente_id'];
        }

        if (array_key_exists('veiculo_id', $dados)) {
            $this->veiculoId = (int) $dados['veiculo_id'];
        }

        if (array_key_exists('mecanico_id', $dados)) {
            $this->mecanicoId = $dados['mecanico_id'] !== null
                ? (int) $dados['mecanico_id']
                : null;
        }

        if (array_key_exists('descricao_problema', $dados)) {
            $this->descricaoProblema = (string) $dados['descricao_problema'];
        }

        if (array_key_exists('diagnostico', $dados)) {
            $this->diagnostico = $dados['diagnostico'];

            if (
                $this->diagnostico !== null
                && $this->diagnostico !== ''
                && $this->status->equals(StatusOrdem::EM_DIAGNOSTICO)
            ) {
                $this->status = StatusOrdem::from(StatusOrdem::AGUARDANDO_APROVACAO);
            }
        }
    }

    public function iniciar(): void
    {
        if (!$this->status->equals(StatusOrdem::ABERTA)) {
            throw new \DomainException('Somente ordens ABERTAS podem ser iniciadas.');
        }

        $this->status = StatusOrdem::from(StatusOrdem::EM_DIAGNOSTICO);
        $this->iniciadaEm ??= new \DateTimeImmutable();
    }

    public function concluir(float $valorTotal): void
    {
        if ($valorTotal < 0) {
            throw new \DomainException('O valor total da ordem nao pode ser negativo.');
        }

        if ($this->status->equals(StatusOrdem::CANCELADA)) {
            throw new \DomainException('Ordens CANCELADAS nao podem ser concluidas.');
        }

        if ($this->status->equals(StatusOrdem::FINALIZADA)) {
            throw new \DomainException('A ordem ja esta FINALIZADA.');
        }

        $this->status      = StatusOrdem::from(StatusOrdem::FINALIZADA);
        $this->valorTotal  = $valorTotal;
        $this->iniciadaEm ??= new \DateTimeImmutable();
        $this->concluidaEm = new \DateTimeImmutable();
    }

    public function cancelar(): void
    {
        if ($this->status->equals(StatusOrdem::FINALIZADA)) {
            throw new \DomainException('Ordens FINALIZADAS nao podem ser canceladas.');
        }

        if ($this->status->equals(StatusOrdem::CANCELADA)) {
            throw new \DomainException('A ordem ja esta CANCELADA.');
        }

        $this->status = StatusOrdem::from(StatusOrdem::CANCELADA);
    }

    private function podeEditar(): bool
    {
        return !$this->status->equals(StatusOrdem::FINALIZADA)
            && !$this->status->equals(StatusOrdem::CANCELADA);
    }

    // ── Getters ───────────────────────────────────────────────────

    public function getId(): ?int            { return $this->id; }
    public function getClienteId(): int      { return $this->clienteId; }
    public function getVeiculoId(): int      { return $this->veiculoId; }
    public function getMecanicoId(): ?int    { return $this->mecanicoId; }
    public function getStatus(): StatusOrdem { return $this->status; }
    public function getDescricao(): string   { return $this->descricaoProblema; }
    public function getDiagnostico(): ?string { return $this->diagnostico; }
    public function getValorTotal(): ?float  { return $this->valorTotal; }
    public function getIniciadaEm(): ?\DateTimeImmutable { return $this->iniciadaEm; }
    public function getCriadaEm(): \DateTimeImmutable { return $this->criadaEm; }
    public function getAtualizadaEm(): ?\DateTimeImmutable { return $this->atualizadaEm; }
    public function getConcluidaEm(): ?\DateTimeImmutable { return $this->concluidaEm; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'cliente_id' => $this->clienteId,
            'veiculo_id' => $this->veiculoId,
            'mecanico_id' => $this->mecanicoId,
            'status' => (string) $this->status,
            'descricao_problema' => $this->descricaoProblema,
            'diagnostico' => $this->diagnostico,
            'valor_total' => $this->valorTotal,
            'iniciada_em' => $this->iniciadaEm?->format('Y-m-d H:i:s'),
            'concluida_em' => $this->concluidaEm?->format('Y-m-d H:i:s'),
            'created_at' => $this->criadaEm->format('Y-m-d H:i:s'),
            'updated_at' => $this->atualizadaEm?->format('Y-m-d H:i:s'),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
