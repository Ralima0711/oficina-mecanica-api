<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Notificacao\Entities\Notificacao;
use App\Domain\Notificacao\Repositories\NotificacaoRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\NotificacaoModel;

class EloquentNotificacaoRepository implements NotificacaoRepositoryInterface
{
    public function createForUsers(int $ordemServicoId, array $userIds, string $tipo): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $userIds), fn(int $id) => $id > 0)));

        if ($ids === []) {
            return 0;
        }

        $now = now();

        $payload = array_map(fn(int $userId) => [
            'ordem_servico_id' => $ordemServicoId,
            'user_id' => $userId,
            'tipo' => $tipo,
            'canal' => 'push',//TODO: mockado para fins do MPV
            'status' => 'enviada',//TODO: mockado para fins do MPV
            'enviada_em' => $now,
            'lida' => false,
            'created_at' => $now,
        ], $ids);

        NotificacaoModel::query()->insert($payload);

        return count($payload);
    }

    public function findByUser(int $userId, int $perPage = 20): array
    {
        $paginator = NotificacaoModel::query()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate(max(1, min($perPage, 100)));

        return [
            'data' => $paginator->getCollection()->map(fn(NotificacaoModel $model) => $this->toEntity($model))->toArray(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    public function findByIdAndUser(int $id, int $userId): ?Notificacao
    {
        $model = NotificacaoModel::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function markAsRead(int $id): ?Notificacao
    {
        $model = NotificacaoModel::query()->find($id);

        if (!$model) {
            return null;
        }

        if (!$model->lida) {
            $model->lida = true;
            $model->save();
        }

        return $this->toEntity($model->refresh());
    }

    public function findLastByOrdemAndTipo(int $ordemServicoId, string $tipo): ?Notificacao
    {
        $model = NotificacaoModel::query()
            ->where('ordem_servico_id', $ordemServicoId)
            ->where('tipo', $tipo)
            ->orderByDesc('created_at')
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    private function toEntity(NotificacaoModel $model): Notificacao
    {
        return new Notificacao(
            id: $model->id,
            ordemServicoId: (int) $model->ordem_servico_id,
            userId: (int) $model->user_id,
            tipo: (string) $model->tipo,
            canal: (string) $model->canal,
            status: (string) $model->status,
            enviadaEm: $model->enviada_em?->toDateTimeImmutable(),
            lida: (bool) $model->lida,
            criadaEm: $model->created_at?->toDateTimeImmutable() ?? new \DateTimeImmutable(),
        );
    }
}
