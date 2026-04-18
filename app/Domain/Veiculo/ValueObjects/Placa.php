<?php

namespace App\Domain\Veiculo\ValueObjects;

final class Placa
{
    private string $value;

    public function __construct(string $placa)
    {
        $placa = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim($placa)));

        if (!$this->isValid($placa)) {
            throw new \InvalidArgumentException('Placa inválida.');
        }

        $this->value = $placa;
    }

    private function isValid(string $placa): bool
    {
        return (bool) preg_match('/^[A-Z]{3}-?(?:\d{4}|\d[A-Z]\d{2})$/', $placa);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function getRaw(): string
    {
        return $this->value;
    }
}
