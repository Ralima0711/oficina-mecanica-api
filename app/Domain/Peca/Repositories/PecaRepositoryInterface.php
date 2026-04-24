<?php

namespace App\Domain\Peca\Repositories;

use App\Domain\Peca\Entities\Peca;

interface PecaRepositoryInterface
{
    public function findAll(): array;

    public function findById(int $id): ?Peca;

    public function findByCodigo(string $codigo): ?Peca;

    public function save(Peca $peca): Peca;

    public function delete(int $id): void;
}
