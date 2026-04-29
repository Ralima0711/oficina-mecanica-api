<?php

namespace App\Domain\OrdemServico\ValueObjects;

/**
 * CAMADA DE DOMÍNIO — Value Object
 * Imutável. Sem identidade própria — seu valor É sua identidade.
 */
final class StatusOrdem
{
    public const RECEBIDA = 'RECEBIDA';
    public const EM_DIAGNOSTICO = 'EM_DIAGNOSTICO';
    public const AGUARDANDO_APROVACAO = 'AGUARDANDO_APROVACAO';
    public const APROVADA = 'APROVADA';
    public const EM_EXECUCAO = 'EM_EXECUCAO';
    public const FINALIZADA = 'FINALIZADA';
    public const ENTREGUE = 'ENTREGUE';

    private const VALID = [
        self::RECEBIDA,
        self::EM_DIAGNOSTICO,
        self::AGUARDANDO_APROVACAO,
        self::APROVADA,
        self::EM_EXECUCAO,
        self::FINALIZADA,
        self::ENTREGUE,
    ];

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
