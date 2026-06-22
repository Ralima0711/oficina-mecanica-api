<?php

namespace App\Domain\Servico\Repositories;

use App\Domain\Servico\Entities\Servico;

interface ServicoRepositoryInterface
{
    public function findAll(): array;
    public function findById(int $id): ?Servico;
    public function findByCodigo(string $codigo): ?Servico;
    public function save(Servico $servico): Servico;
    public function delete(int $id): void;
}
