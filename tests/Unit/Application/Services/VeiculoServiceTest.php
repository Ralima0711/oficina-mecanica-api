<?php

namespace Tests\Unit\Application\Services;

use App\Application\Services\VeiculoService;
use App\Domain\Veiculo\Entities\Veiculo;
use App\Domain\Veiculo\Repositories\VeiculoRepositoryInterface;
use App\Domain\Veiculo\ValueObjects\Placa;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class VeiculoServiceTest extends TestCase
{
    private VeiculoRepositoryInterface&MockObject $repository;
    private VeiculoService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(VeiculoRepositoryInterface::class);
        $this->service = new VeiculoService($this->repository);
    }

    private function makeVeiculo(int $id = 1): Veiculo
    {
        return new Veiculo(
            id: $id,
            clienteId: 10,
            placa: new Placa('ABC1234'),
            marca: 'Toyota',
            modelo: 'Corolla',
            ano: 2020,
            cor: 'Prata',
            criadoEm: new \DateTimeImmutable(),
        );
    }

    // ── listarTodos ───────────────────────────────────────────────

    public function test_listar_todos_retorna_array(): void
    {
        $veiculos = [$this->makeVeiculo(1), $this->makeVeiculo(2)];

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($veiculos);

        $result = $this->service->listarTodos();

        $this->assertCount(2, $result);
    }

    public function test_listar_todos_retorna_array_vazio(): void
    {
        $this->repository->method('findAll')->willReturn([]);

        $result = $this->service->listarTodos();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ── buscarPorId ───────────────────────────────────────────────

    public function test_buscar_por_id_retorna_veiculo_existente(): void
    {
        $veiculo = $this->makeVeiculo(1);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($veiculo);

        $result = $this->service->buscarPorId(1);

        $this->assertSame($veiculo, $result);
    }

    public function test_buscar_por_id_lanca_excecao_quando_nao_encontrado(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Veículo #99 não encontrado.');

        $this->service->buscarPorId(99);
    }

    // ── criar ─────────────────────────────────────────────────────

    public function test_criar_veiculo_com_sucesso(): void
    {
        $veiculo = $this->makeVeiculo(1);

        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($veiculo);

        $result = $this->service->criar([
            'cliente_id' => 10,
            'placa'      => 'ABC1234',
            'marca'      => 'Toyota',
            'modelo'     => 'Corolla',
            'ano'        => 2020,
            'cor'        => 'Prata',
        ]);

        $this->assertInstanceOf(Veiculo::class, $result);
    }

    public function test_criar_veiculo_sem_cor_com_sucesso(): void
    {
        $veiculo = new Veiculo(
            id: 1,
            clienteId: 10,
            placa: new Placa('ABC1234'),
            marca: 'Honda',
            modelo: 'Civic',
            ano: 2021,
            cor: null,
            criadoEm: new \DateTimeImmutable(),
        );

        $this->repository->method('save')->willReturn($veiculo);

        $result = $this->service->criar([
            'cliente_id' => 10,
            'placa'      => 'ABC1234',
            'marca'      => 'Honda',
            'modelo'     => 'Civic',
            'ano'        => 2021,
        ]);

        $this->assertNull($result->getCor());
    }

    public function test_criar_veiculo_com_placa_invalida_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Placa inválida.');

        $this->service->criar([
            'cliente_id' => 10,
            'placa'      => 'INVALIDA',
            'marca'      => 'Toyota',
            'modelo'     => 'Corolla',
            'ano'        => 2020,
        ]);
    }

    public function test_criar_veiculo_placa_mercosul_com_sucesso(): void
    {
        $veiculo = new Veiculo(
            id: 1,
            clienteId: 10,
            placa: new Placa('ABC1D23'),
            marca: 'Ford',
            modelo: 'Ka',
            ano: 2022,
            cor: 'Azul',
            criadoEm: new \DateTimeImmutable(),
        );

        $this->repository->method('save')->willReturn($veiculo);

        $result = $this->service->criar([
            'cliente_id' => 10,
            'placa'      => 'ABC1D23',
            'marca'      => 'Ford',
            'modelo'     => 'Ka',
            'ano'        => 2022,
            'cor'        => 'Azul',
        ]);

        $this->assertInstanceOf(Veiculo::class, $result);
    }

    // ── atualizar ─────────────────────────────────────────────────

    public function test_atualizar_veiculo_existente_com_sucesso(): void
    {
        $veiculo = $this->makeVeiculo(1);

        $this->repository->method('findById')->willReturn($veiculo);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($veiculo);

        $result = $this->service->atualizar(1, [
            'cliente_id' => 10,
            'placa'      => 'XYZ9876',
            'marca'      => 'Honda',
            'modelo'     => 'Fit',
            'ano'        => 2019,
            'cor'        => 'Branco',
        ]);

        $this->assertInstanceOf(Veiculo::class, $result);
    }

    public function test_atualizar_veiculo_inexistente_lanca_excecao(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(\RuntimeException::class);

        $this->service->atualizar(99, [
            'cliente_id' => 10,
            'placa'      => 'ABC1234',
            'marca'      => 'Toyota',
            'modelo'     => 'Corolla',
            'ano'        => 2020,
        ]);
    }

    // ── remover ───────────────────────────────────────────────────

    public function test_remover_veiculo_existente_com_sucesso(): void
    {
        $veiculo = $this->makeVeiculo(1);

        $this->repository->method('findById')->willReturn($veiculo);
        $this->repository->expects($this->once())
            ->method('delete')
            ->with(1);

        $this->service->remover(1);
    }

    public function test_remover_veiculo_inexistente_lanca_excecao(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(\RuntimeException::class);

        $this->service->remover(99);
    }
}
