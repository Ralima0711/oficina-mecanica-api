<?php

namespace App\Domain\Peca\Entities;

class Peca implements \JsonSerializable
{
    public function __construct(
        private ?int $id,
        private string $nome,
        private string $codigo,
        private ?string $categoria,
        private float $precoUnitario,
        private int $estoqueAtual,
        private int $estoqueMininmo,
        private \DateTimeImmutable $criadoEm,
        private ?\DateTimeImmutable $atualizadoEm = null,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function getCategoria(): ?string
    {
        return $this->categoria;
    }

    public function getPrecoUnitario(): float
    {
        return $this->precoUnitario;
    }

    public function getEstoqueAtual(): int
    {
        return $this->estoqueAtual;
    }

    public function getEstoqueMininmo(): int
    {
        return $this->estoqueMininmo;
    }

    public function getCriadoEm(): \DateTimeImmutable
    {
        return $this->criadoEm;
    }

    public function getAtualizadoEm(): ?\DateTimeImmutable
    {
        return $this->atualizadoEm;
    }

    public function atualizar(
        string $nome,
        string $codigo,
        ?string $categoria,
        float $precoUnitario,
        int $estoqueAtual,
        int $estoqueMininmo
    ): void {
        $this->nome = $nome;
        $this->codigo = $codigo;
        $this->categoria = $categoria;
        $this->precoUnitario = $precoUnitario;
        $this->estoqueAtual = $estoqueAtual;
        $this->estoqueMininmo = $estoqueMininmo;
        $this->atualizadoEm = new \DateTimeImmutable();
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'codigo' => $this->codigo,
            'categoria' => $this->categoria,
            'preco_unitario' => $this->precoUnitario,
            'estoque_atual' => $this->estoqueAtual,
            'estoque_minimo' => $this->estoqueMininmo,
            'criado_em' => $this->criadoEm->format('Y-m-d H:i:s'),
            'atualizado_em' => $this->atualizadoEm?->format('Y-m-d H:i:s'),
        ];
    }
}
