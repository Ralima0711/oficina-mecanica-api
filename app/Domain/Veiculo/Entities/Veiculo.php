<?php

namespace App\Domain\Veiculo\Entities;

use App\Domain\Veiculo\ValueObjects\Placa;

class Veiculo implements \JsonSerializable
{
    public function __construct(
        private ?int $id,
        private int $clienteId,
        private Placa $placa,
        private string $marca,
        private string $modelo,
        private int $ano,
        private ?string $cor,
        private \DateTimeImmutable $criadoEm,
    ) {}

    public function atualizar(int $clienteId, Placa $placa, string $marca, string $modelo, int $ano, ?string $cor): void
    {
        $this->clienteId = $clienteId;
        $this->placa = $placa;
        $this->marca = $marca;
        $this->modelo = $modelo;
        $this->ano = $ano;
        $this->cor = $cor;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClienteId(): int
    {
        return $this->clienteId;
    }

    public function getPlaca(): Placa
    {
        return $this->placa;
    }

    public function getMarca(): string
    {
        return $this->marca;
    }

    public function getModelo(): string
    {
        return $this->modelo;
    }

    public function getAno(): int
    {
        return $this->ano;
    }

    public function getCor(): ?string
    {
        return $this->cor;
    }

    public function getCriadoEm(): \DateTimeImmutable
    {
        return $this->criadoEm;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'cliente_id' => $this->clienteId,
            'placa' => (string) $this->placa,
            'marca' => $this->marca,
            'modelo' => $this->modelo,
            'ano' => $this->ano,
            'cor' => $this->cor,
            'criado_em' => $this->criadoEm->format('Y-m-d H:i:s'),
        ];
    }
}
