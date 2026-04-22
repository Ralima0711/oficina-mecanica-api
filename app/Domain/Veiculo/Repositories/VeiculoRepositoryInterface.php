<?php

namespace App\Domain\Veiculo\Repositories;

use App\Domain\Veiculo\Entities\Veiculo;

interface VeiculoRepositoryInterface
{
    public function findById(int $id): ?Veiculo;
    public function findAll(): array;
    public function findByPlaca(string $placa): ?Veiculo;
    public function save(Veiculo $veiculo): Veiculo;
    public function delete(int $id): void;
}
