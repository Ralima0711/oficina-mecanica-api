<?php

namespace App\Domain\Estoque\Events;

class EstoqueAumentado
{
    private int $itemId;
    private string $itemTipo;

    public function __construct(
        ?int $itemId = null,
        ?string $itemTipo = null,
        private float $quantidade = 0,
        private float $estoqueAnterior = 0,
        private float $estoqueAtual = 0,
        private ?string $motivo = null,
    ) {
        $this->itemId = $itemId ?? 0;
        $this->itemTipo = $itemTipo ?? '';
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId): void
    {
        $this->itemId = $itemId;
    }

    public function getItemTipo(): string
    {
        return $this->itemTipo;
    }

    public function setItemTipo(string $itemTipo): void
    {
        $this->itemTipo = $itemTipo;
    }

    public function getQuantidade(): float
    {
        return $this->quantidade;
    }

    public function getEstoqueAnterior(): float
    {
        return $this->estoqueAnterior;
    }

    public function getEstoqueAtual(): float
    {
        return $this->estoqueAtual;
    }

    public function getMotivo(): ?string
    {
        return $this->motivo;
    }
}
