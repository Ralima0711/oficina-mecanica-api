<?php

namespace App\Domain\Cliente\ValueObjects;

/**
 * CAMADA DE DOMÍNIO — Value Object CPF
 * Garante que nenhum CPF inválido entre no sistema.
 */
final class Cpf implements DocumentoFiscal
{
    private string $value;

    public function __construct(string $cpf)
    {
        $clean = preg_replace('/\D/', '', $cpf);

        if (!$this->isValid($clean)) {
            throw new \InvalidArgumentException('CPF inválido.');
        }

        $this->value = $clean;
    }

    private function isValid(string $cpf): bool
    {
        if (strlen($cpf) !== 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += $cpf[$i] * ($t + 1 - $i);
            }
            $remainder = (10 * $sum) % 11;
            if ($cpf[$t] != ($remainder === 10 ? 0 : $remainder)) {
                return false;
            }
        }

        return true;
    }

    public function __toString(): string
    {
        return substr($this->value, 0, 3) . '.' .
               substr($this->value, 3, 3) . '.' .
               substr($this->value, 6, 3) . '-' .
               substr($this->value, 9, 2);
    }

    public function getRaw(): string { return $this->value; }
}
