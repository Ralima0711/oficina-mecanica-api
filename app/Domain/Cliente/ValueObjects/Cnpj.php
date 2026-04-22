<?php

namespace App\Domain\Cliente\ValueObjects;

/**
 * CAMADA DE DOMÍNIO — Value Object CNPJ
 * Garante que nenhum CNPJ inválido entre no sistema.
 */
final class Cnpj implements DocumentoFiscal
{
    private string $value;

    public function __construct(string $cnpj)
    {
        $clean = preg_replace('/\D/', '', $cnpj);

        if (!$this->isValid($clean)) {
            throw new \InvalidArgumentException('CNPJ inválido.');
        }

        $this->value = $clean;
    }

    private function isValid(string $cnpj): bool
    {
        if (strlen($cnpj) !== 14 || preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        $base = substr($cnpj, 0, 12);
        $digit1 = $this->calculateDigit($base, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $digit2 = $this->calculateDigit($base . $digit1, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return $cnpj === ($base . $digit1 . $digit2);
    }

    private function calculateDigit(string $value, array $weights): int
    {
        $sum = 0;

        foreach (str_split($value) as $index => $number) {
            $sum += ((int) $number) * $weights[$index];
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }

    public function __toString(): string
    {
        return substr($this->value, 0, 2) . '.' .
               substr($this->value, 2, 3) . '.' .
               substr($this->value, 5, 3) . '/' .
               substr($this->value, 8, 4) . '-' .
               substr($this->value, 12, 2);
    }

    public function getRaw(): string
    {
        return $this->value;
    }
}