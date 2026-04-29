<?php

namespace App\Domain\Estoque\Events;

class EstoqueAlertado
{
    private int $itemId;
    private string $itemTipo;

    public function __construct(
        ?int $itemId = null,
        ?string $itemTipo = null,
        private float $estoqueAtual = 0,
        private float $estoqueMinimo = 0,
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

    public function getEstoqueAtual(): float
    {
        return $this->estoqueAtual;
    }

    public function getEstoqueMinimo(): float
    {
        return $this->estoqueMinimo;
    }

    public function getDeficit(): float
    {
        return $this->estoqueMinimo - $this->estoqueAtual;
    }
}
