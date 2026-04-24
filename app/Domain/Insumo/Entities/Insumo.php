<?php

namespace App\Domain\Insumo\Entities;

class Insumo implements \JsonSerializable
{
    public function __construct(
        private ?int $id,
        private string $nome,
        private string $unidadeMedida,
        private float $precoUnitario,
        private float $estoqueAtual,
        private float $estoqueMininmo,
        private \DateTimeImmutable $criadoEm,
        private ?\DateTimeImmutable $atualizadoEm = null,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getUnidadeMedida(): string
    {
        return $this->unidadeMedida;
    }

    public function getPrecoUnitario(): float
    {
        return $this->precoUnitario;
    }

    public function getEstoqueAtual(): float
    {
        return $this->estoqueAtual;
    }

    public function getEstoqueMininmo(): float
    {
        return $this->estoqueMininmo;
    }

    public function getCriadoEm(): \DateTimeImmutable
    {
        return $this->criadoEm;
    }

    public function getAtualizadoEm(): ?\DateTimeImmutable
    {
        return $this->atualizadoEm;
    }

    public function atualizar(
        string $nome,
        string $unidadeMedida,
        float $precoUnitario,
        float $estoqueAtual,
        float $estoqueMininmo
    ): void {
        $this->nome = $nome;
        $this->unidadeMedida = $unidadeMedida;
        $this->precoUnitario = $precoUnitario;
        $this->estoqueAtual = $estoqueAtual;
        $this->estoqueMininmo = $estoqueMininmo;
        $this->atualizadoEm = new \DateTimeImmutable();
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'unidade_medida' => $this->unidadeMedida,
            'preco_unitario' => $this->precoUnitario,
            'estoque_atual' => $this->estoqueAtual,
            'estoque_minimo' => $this->estoqueMininmo,
            'criado_em' => $this->criadoEm->format('Y-m-d H:i:s'),
            'atualizado_em' => $this->atualizadoEm?->format('Y-m-d H:i:s'),
        ];
    }
}
