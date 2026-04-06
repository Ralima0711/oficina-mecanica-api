<?php

namespace App\Domain\Cliente\Repositories;

use App\Domain\Cliente\Entities\Cliente;

interface ClienteRepositoryInterface
{
    public function findById(int $id): ?Cliente;
    public function findAll(): array;
    public function findByCpf(string $cpf): ?Cliente;
    public function save(Cliente $cliente): Cliente;
    public function delete(int $id): void;
}
