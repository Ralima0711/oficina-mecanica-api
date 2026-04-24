<?php

namespace App\Domain\Estoque\ValueObjects;

class EstoqueInfo
{
    public function __construct(
        private float $atual,
        private float $minimo,
        private float $maximo = PHP_FLOAT_MAX,
    ) {
        $this->validar();
    }

    private function validar(): void
    {
        if ($this->atual < 0) {
            throw new \InvalidArgumentException("Estoque atual não pode ser negativo");
        }
        if ($this->minimo < 0) {
            throw new \InvalidArgumentException("Estoque mínimo não pode ser negativo");
        }
        if ($this->maximo <= 0) {
            throw new \InvalidArgumentException("Estoque máximo deve ser positivo");
        }
        if ($this->minimo > $this->maximo) {
            throw new \InvalidArgumentException("Estoque mínimo não pode ser maior que o máximo");
        }
    }

    public function getAtual(): float
    {
        return $this->atual;
    }

    public function getMinimo(): float
    {
        return $this->minimo;
    }

    public function getMaximo(): float
    {
        return $this->maximo;
    }

    public function estaAbaixoDoMinimo(): bool
    {
        return $this->atual < $this->minimo;
    }

    public function estaAcimaDoMaximo(): bool
    {
        return $this->atual > $this->maximo;
    }

    public function temDisponibilidade(float $quantidade = 0): bool
    {
        return ($this->atual - $quantidade) >= 0;
    }

    public function percentualDisponibilidade(): float
    {
        if ($this->maximo === 0) {
            return 0;
        }
        return ($this->atual / $this->maximo) * 100;
    }

    public static function criar(
        float $atual,
        float $minimo,
        float $maximo = PHP_FLOAT_MAX
    ): self {
        return new self($atual, $minimo, $maximo);
    }
}
