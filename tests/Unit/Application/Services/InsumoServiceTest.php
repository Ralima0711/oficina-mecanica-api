<?php

namespace Tests\Unit\Application\Services;

use App\Application\Services\InsumoService;
use App\Domain\Insumo\Entities\Insumo;
use App\Domain\Insumo\Repositories\InsumoRepositoryInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class InsumoServiceTest extends TestCase
{
    private InsumoRepositoryInterface&MockObject $repository;
    private InsumoService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(InsumoRepositoryInterface::class);
        $this->service = new InsumoService($this->repository);
    }

    private function makeInsumo(int $id = 1): Insumo
    {
        return new Insumo(
            id: $id,
            nome: 'Óleo Motor 5W30',
            unidadeMedida: 'L',
            precoUnitario: 25.90,
            estoqueAtual: 50.0,
            estoqueMininmo: 10.0,
            criadoEm: new \DateTimeImmutable(),
        );
    }

    // ── listarTodas ───────────────────────────────────────────────

    public function test_listar_todas_retorna_array(): void
    {
        $insumos = [$this->makeInsumo(1), $this->makeInsumo(2)];

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($insumos);

        $result = $this->service->listarTodas();

        $this->assertCount(2, $result);
    }

    public function test_listar_todas_retorna_array_vazio(): void
    {
        $this->repository->method('findAll')->willReturn([]);

        $result = $this->service->listarTodas();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ── buscarPorId ───────────────────────────────────────────────

    public function test_buscar_por_id_retorna_insumo_existente(): void
    {
        $insumo = $this->makeInsumo(1);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($insumo);

        $result = $this->service->buscarPorId(1);

        $this->assertSame($insumo, $result);
    }

    public function test_buscar_por_id_lanca_excecao_quando_nao_encontrado(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insumo #99 não encontrado.');

        $this->service->buscarPorId(99);
    }

    // ── criar ─────────────────────────────────────────────────────

    public function test_criar_insumo_com_sucesso(): void
    {
        $insumo = $this->makeInsumo(1);

        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($insumo);

        $result = $this->service->criar([
            'nome'            => 'Óleo Motor 5W30',
            'unidade_medida'  => 'L',
            'preco_unitario'  => 25.90,
            'estoque_atual'   => 50.0,
            'estoque_minimo'  => 10.0,
        ]);

        $this->assertInstanceOf(Insumo::class, $result);
    }

    public function test_criar_insumo_sem_estoque_usa_zero_como_padrao(): void
    {
        $insumo = new Insumo(
            id: 1,
            nome: 'Graxa',
            unidadeMedida: 'KG',
            precoUnitario: 12.00,
            estoqueAtual: 0.0,
            estoqueMininmo: 0.0,
            criadoEm: new \DateTimeImmutable(),
        );

        $this->repository->method('save')->willReturn($insumo);

        $result = $this->service->criar([
            'nome'           => 'Graxa',
            'unidade_medida' => 'KG',
            'preco_unitario' => 12.00,
        ]);

        $this->assertEquals(0.0, $result->getEstoqueAtual());
        $this->assertEquals(0.0, $result->getEstoqueMininmo());
    }

    public function test_criar_insumo_unidade_litro(): void
    {
        $insumo = new Insumo(
            id: 1,
            nome: 'Produto',
            unidadeMedida: 'L',
            precoUnitario: 10.00,
            estoqueAtual: 5.0,
            estoqueMininmo: 1.0,
            criadoEm: new \DateTimeImmutable(),
        );

        $this->repository->method('save')->willReturn($insumo);

        $result = $this->service->criar([
            'nome'           => 'Produto',
            'unidade_medida' => 'L',
            'preco_unitario' => 10.00,
        ]);

        $this->assertEquals('L', $result->getUnidadeMedida());
    }

    public function test_criar_insumo_unidade_kg(): void
    {
        $repo = $this->createMock(InsumoRepositoryInterface::class);
        $insumo = new Insumo(
            id: 2,
            nome: 'Graxa',
            unidadeMedida: 'KG',
            precoUnitario: 15.00,
            estoqueAtual: 5.0,
            estoqueMininmo: 1.0,
            criadoEm: new \DateTimeImmutable(),
        );
        $repo->method('save')->willReturn($insumo);
        $service = new InsumoService($repo);

        $result = $service->criar([
            'nome'           => 'Graxa',
            'unidade_medida' => 'KG',
            'preco_unitario' => 15.00,
        ]);

        $this->assertEquals('KG', $result->getUnidadeMedida());
    }

    // ── atualizar ─────────────────────────────────────────────────

    public function test_atualizar_insumo_existente_com_sucesso(): void
    {
        $insumo = $this->makeInsumo(1);

        $this->repository->method('findById')->willReturn($insumo);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($insumo);

        $result = $this->service->atualizar(1, [
            'nome'           => 'Óleo Motor 10W40',
            'unidade_medida' => 'L',
            'preco_unitario' => 28.50,
            'estoque_atual'  => 45.0,
            'estoque_minimo' => 8.0,
        ]);

        $this->assertInstanceOf(Insumo::class, $result);
    }

    public function test_atualizar_insumo_mantém_estoque_atual_se_nao_informado(): void
    {
        $insumo = $this->makeInsumo(1);

        $this->repository->method('findById')->willReturn($insumo);
        $this->repository->method('save')->willReturn($insumo);

        $result = $this->service->atualizar(1, [
            'nome'           => 'Óleo Atualizado',
            'unidade_medida' => 'L',
            'preco_unitario' => 30.00,
        ]);

        $this->assertInstanceOf(Insumo::class, $result);
    }

    public function test_atualizar_insumo_inexistente_lanca_excecao(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(\RuntimeException::class);

        $this->service->atualizar(99, [
            'nome'           => 'X',
            'unidade_medida' => 'L',
            'preco_unitario' => 1.00,
        ]);
    }

    // ── remover ───────────────────────────────────────────────────

    public function test_remover_insumo_existente_com_sucesso(): void
    {
        $insumo = $this->makeInsumo(1);

        $this->repository->method('findById')->willReturn($insumo);
        $this->repository->expects($this->once())
            ->method('delete')
            ->with(1);

        $this->service->remover(1);
    }

    public function test_remover_insumo_inexistente_lanca_excecao(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(\RuntimeException::class);

        $this->service->remover(99);
    }
}
