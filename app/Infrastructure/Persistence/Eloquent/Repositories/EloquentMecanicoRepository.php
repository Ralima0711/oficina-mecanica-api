<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Mecanico\Repositories\MecanicoRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\MecanicoModel;

class EloquentMecanicoRepository implements MecanicoRepositoryInterface
{
    public function findIdByUserId(int $userId): ?int
    {
        return MecanicoModel::query()
            ->where('user_id', $userId)
            ->value('id');
    }

    public function findUserIdByMecanicoId(int $mecanicoId): ?int
    {
        return MecanicoModel::query()
            ->where('id', $mecanicoId)
            ->value('user_id');
    }
}
