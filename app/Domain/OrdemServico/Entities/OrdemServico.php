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
            throw new \DomainException('Ordens FINALIZADAS ou CANCELADAS não podem ser alteradas.');
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
        }

        if (array_key_exists('valor_total', $dados) && $dados['valor_total'] !== null) {
            $this->valorTotal = (float) $dados['valor_total'];
        }
    }

    public function iniciarDiagnostico(int $mecanicoId): void
    {
        if (!$this->status->equals(StatusOrdem::ABERTA)) {
            throw new \DomainException('Somente ordens ABERTAS podem iniciar diagnostico.');
        }

        if ($mecanicoId <= 0) {
            throw new \DomainException('Mecânico invalido para iniciar diagnostico.');
        }

        $this->mecanicoId = $mecanicoId;
        $this->status = StatusOrdem::from(StatusOrdem::EM_DIAGNOSTICO);
        $this->iniciadaEm ??= new \DateTimeImmutable();
    }

    public function gerarOrcamento(): void
    {
        if (!$this->status->equals(StatusOrdem::EM_DIAGNOSTICO)) {
            throw new \DomainException('Somente ordens EM_DIAGNOSTICO podem gerar orçamento.');
        }

        if ($this->diagnostico === null || trim($this->diagnostico) === '') {
            throw new \DomainException('Informe um diagnostico antes de gerar orçamento.');
        }

        $this->status = StatusOrdem::from(StatusOrdem::AGUARDANDO_APROVACAO);
    }

    public function aprovar(): void
    {
        if (!$this->status->equals(StatusOrdem::AGUARDANDO_APROVACAO)) {
            throw new \DomainException('Somente ordens AGUARDANDO_APROVACAO podem ser aprovadas.');
        }

        $this->status = StatusOrdem::from(StatusOrdem::APROVADA);
    }

    public function reprovar(): void
    {
        if (!$this->status->equals(StatusOrdem::AGUARDANDO_APROVACAO)) {
            throw new \DomainException('Somente ordens AGUARDANDO_APROVACAO podem ser reprovadas.');
        }

        $this->status = StatusOrdem::from(StatusOrdem::CANCELADA);
    }

    public function iniciarExecucao(): void
    {
        if (!$this->status->equals(StatusOrdem::APROVADA)) {
            throw new \DomainException('Somente ordens APROVADAS podem iniciar execução.');
        }

        $this->status = StatusOrdem::from(StatusOrdem::EM_EXECUCAO);
    }

    public function finalizarServico(float $valorTotal): void
    {
        if ($valorTotal < 0) {
            throw new \DomainException('O valor total da ordem não pode ser negativo.');
        }

        if (!$this->status->equals(StatusOrdem::EM_EXECUCAO)) {
            throw new \DomainException('Somente ordens EM_EXECUCAO podem ser finalizadas.');
        }

        $this->status      = StatusOrdem::from(StatusOrdem::FINALIZADA);
        $this->valorTotal  = $valorTotal;
        $this->iniciadaEm ??= new \DateTimeImmutable();
        $this->concluidaEm = new \DateTimeImmutable();
    }

    public function entregarVeiculo(): void
    {
        if (!$this->status->equals(StatusOrdem::FINALIZADA)) {
            throw new \DomainException('Somente ordens FINALIZADAS podem ser entregues.');
        }

        $this->status = StatusOrdem::from(StatusOrdem::ENTREGUE);
    }

    // Compatibilidade com metodos antigos
    public function iniciar(): void
    {
        if ($this->mecanicoId === null) {
            throw new \DomainException('Mecânico deve ser informado para iniciar a ordem.');
        }

        $this->iniciarDiagnostico($this->mecanicoId);
    }

    public function concluir(float $valorTotal): void
    {
        $this->finalizarServico($valorTotal);
    }

    public function cancelar(): void
    {
        if ($this->status->equals(StatusOrdem::FINALIZADA)) {
            throw new \DomainException('Ordens FINALIZADAS não podem ser canceladas.');
        }

        if ($this->status->equals(StatusOrdem::ENTREGUE)) {
            throw new \DomainException('Ordens ENTREGUES não podem ser canceladas.');
        }

        if ($this->status->equals(StatusOrdem::CANCELADA)) {
            throw new \DomainException('A ordem já está CANCELADA.');
        }

        $this->status = StatusOrdem::from(StatusOrdem::CANCELADA);
    }

    private function podeEditar(): bool
    {
        return !$this->status->equals(StatusOrdem::FINALIZADA)
            && !$this->status->equals(StatusOrdem::CANCELADA)
            && !$this->status->equals(StatusOrdem::ENTREGUE);
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
