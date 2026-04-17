<?php

namespace App\Domain\Cliente\Entities;

use App\Domain\Cliente\ValueObjects\Cpf;

/**
 * CAMADA DE DOMÍNIO — Entidade Cliente
 */
class Cliente implements \JsonSerializable
{
    public function __construct(
        private ?int $id,
        private string $nome,
        private Cpf $cpf,
        private string $telefone,
        private string $email,
        private \DateTimeImmutable $criadoEm,
    ) {}

    public function atualizar(string $nome, string $telefone, string $email): void
    {
        $this->nome     = $nome;
        $this->telefone = $telefone;
        $this->email    = $email;
    }

    public function getId(): ?int     { return $this->id; }
    public function getNome(): string { return $this->nome; }
    public function getCpf(): Cpf     { return $this->cpf; }
    public function getTelefone(): string { return $this->telefone; }
    public function getEmail(): string    { return $this->email; }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'cpf' => (string) $this->cpf,
            'telefone' => $this->telefone,
            'email' => $this->email,
            'criado_em' => $this->criadoEm->format('Y-m-d H:i:s'),
        ];
    }
}
