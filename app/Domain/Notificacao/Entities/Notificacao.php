<?php

namespace App\Domain\Notificacao\Entities;

class Notificacao implements \JsonSerializable
{
    public function __construct(
        private ?int $id,
        private int $ordemServicoId,
        private int $userId,
        private string $tipo,
        private string $canal,
        private string $status,
        private ?\DateTimeImmutable $enviadaEm,
        private bool $lida,
        private \DateTimeImmutable $criadaEm,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrdemServicoId(): int
    {
        return $this->ordemServicoId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function getCanal(): string
    {
        return $this->canal;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getEnviadaEm(): ?\DateTimeImmutable
    {
        return $this->enviadaEm;
    }

    public function isLida(): bool
    {
        return $this->lida;
    }

    public function getCriadaEm(): \DateTimeImmutable
    {
        return $this->criadaEm;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ordem_servico_id' => $this->ordemServicoId,
            'user_id' => $this->userId,
            'tipo' => $this->tipo,
            'canal' => $this->canal,
            'status' => $this->status,
            'enviada_em' => $this->enviadaEm?->format('Y-m-d H:i:s'),
            'lida' => $this->lida,
            'created_at' => $this->criadaEm->format('Y-m-d H:i:s'),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
