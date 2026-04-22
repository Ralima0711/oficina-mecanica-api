<?php

namespace App\Domain\Cliente\Entities;

use App\Domain\Cliente\ValueObjects\DocumentoFiscal;

/**
 * CAMADA DE DOMÍNIO — Entidade Cliente
 */
class Cliente implements \JsonSerializable
{
    public function __construct(
        private ?int $id,
        private string $nome,
        private string $tipo,
        private DocumentoFiscal $documento,
        private string $telefone,
        private string $email,
        private \DateTimeImmutable $criadoEm,
    ) {}

    public function atualizar(
        string $nome,
        string $tipo,
        DocumentoFiscal $documento,
        string $telefone,
        string $email
    ): void
    {
        $this->nome     = $nome;
        $this->tipo     = $tipo;
        $this->documento = $documento;
        $this->telefone = $telefone;
        $this->email    = $email;
    }

    public function getId(): ?int     { return $this->id; }
    public function getNome(): string { return $this->nome; }
    public function getTipo(): string { return $this->tipo; }
    public function getDocumento(): DocumentoFiscal { return $this->documento; }
    public function getTelefone(): string { return $this->telefone; }
    public function getEmail(): string    { return $this->email; }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'tipo' => $this->tipo,
            'documento' => (string) $this->documento,
            'telefone' => $this->telefone,
            'email' => $this->email,
            'criado_em' => $this->criadoEm->format('Y-m-d H:i:s'),
        ];
    }
}
