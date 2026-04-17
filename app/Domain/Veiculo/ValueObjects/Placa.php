<?php

namespace App\Domain\Veiculo\ValueObjects;

final class Placa
{
    private string $value;

    public function __construct(string $placa)
    {
        $placa = strtoupper(str_replace(' ', '', trim($placa)));

        if (!$this->isValid($placa)) {
            throw new \InvalidArgumentException('Placa inválida.');
        }

        $this->value = $placa;
    }

    private function isValid(string $placa): bool
    {
        return (bool) preg_match('/^[A-Z]{3}-?\d{4}$/', $placa);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
