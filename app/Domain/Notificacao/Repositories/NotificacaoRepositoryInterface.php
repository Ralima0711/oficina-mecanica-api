<?php

namespace App\Domain\Notificacao\Repositories;

use App\Domain\Notificacao\Entities\Notificacao;

interface NotificacaoRepositoryInterface
{
    public function createForUsers(int $ordemServicoId, array $userIds, string $tipo): int;

    public function findByUser(int $userId, int $perPage = 20): array;

    public function findByIdAndUser(int $id, int $userId): ?Notificacao;

    public function markAsRead(int $id): ?Notificacao;

    public function findLastByOrdemAndTipo(int $ordemServicoId, string $tipo): ?Notificacao;
}
