<?php

namespace App\Domain\Servico\Entities;

/**
 * CAMADA DE DOMÍNIO — Entidade
 * Representa um serviço oferecido pela oficina.
 */
class Servico implements \JsonSerializable
{
    public function __construct(
        private ?int $id,
        private string $nome,
        private string $codigo,
        private ?string $descricao,
        private ?string $categoria,
        private float $precoBase,
        private int $duracaoEstimadaMinutos,
        private \DateTimeImmutable $criadoEm,
        private ?\DateTimeImmutable $atualizadoEm = null,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getNome(): string { return $this->nome; }
    public function getCodigo(): string { return $this->codigo; }
    public function getDescricao(): ?string { return $this->descricao; }
    public function getCategoria(): ?string { return $this->categoria; }
    public function getPrecoBase(): float { return $this->precoBase; }
    public function getDuracaoEstimadaMinutos(): int { return $this->duracaoEstimadaMinutos; }
    public function getCriadoEm(): \DateTimeImmutable { return $this->criadoEm; }
    public function getAtualizadoEm(): ?\DateTimeImmutable { return $this->atualizadoEm; }

    public function atualizar(
        string $nome,
        string $codigo,
        ?string $descricao,
        ?string $categoria,
        float $precoBase,
        int $duracaoEstimadaMinutos,
    ): void {
        $this->nome = $nome;
        $this->codigo = $codigo;
        $this->descricao = $descricao;
        $this->categoria = $categoria;
        $this->precoBase = $precoBase;
        $this->duracaoEstimadaMinutos = $duracaoEstimadaMinutos;
        $this->atualizadoEm = new \DateTimeImmutable();
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'codigo' => $this->codigo,
            'descricao' => $this->descricao,
            'categoria' => $this->categoria,
            'preco_base' => $this->precoBase,
            'duracao_estimada_minutos' => $this->duracaoEstimadaMinutos,
            'criado_em' => $this->criadoEm->format('Y-m-d H:i:s'),
            'atualizado_em' => $this->atualizadoEm?->format('Y-m-d H:i:s'),
        ];
    }
}
