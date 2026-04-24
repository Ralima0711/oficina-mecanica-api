<?php

namespace App\Domain\Insumo\Repositories;

use App\Domain\Insumo\Entities\Insumo;

interface InsumoRepositoryInterface
{
    public function findAll(): array;

    public function findById(int $id): ?Insumo;

    public function save(Insumo $insumo): Insumo;

    public function delete(int $id): void;
}
