<?php

namespace App\Domain\Mecanico\Repositories;

interface MecanicoRepositoryInterface
{
    public function findIdByUserId(int $userId): ?int;
    public function findUserIdByMecanicoId(int $mecanicoId): ?int;
}
