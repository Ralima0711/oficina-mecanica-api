<?php

namespace App\Domain\OrdemServico\ValueObjects;

/**
 * CAMADA DE DOMÍNIO — Value Object
 * Imutável. Sem identidade própria — seu valor É sua identidade.
 */
final class StatusOrdem
{
    private const VALID = ['ABERTA', 'EM_ANDAMENTO', 'CONCLUIDA', 'CANCELADA'];

    public const ABERTA       = 'ABERTA';
    public const EM_ANDAMENTO = 'EM_ANDAMENTO';
    public const CONCLUIDA    = 'CONCLUIDA';
    public const CANCELADA    = 'CANCELADA';

    private function __construct(private string $value) {}

    public static function from(string $value): self
    {
        if (!in_array($value, self::VALID, true)) {
            throw new \InvalidArgumentException("Status inválido: {$value}");
        }
        return new self($value);
    }

    public static function __callStatic(string $name, array $args): self
    {
        return self::from($name);
    }

    public function equals(string $other): bool
    {
        return $this->value === $other;
    }

    public function __toString(): string { return $this->value; }
}
