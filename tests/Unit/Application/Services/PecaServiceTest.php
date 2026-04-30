<?php

namespace Tests\Unit\Application\Services;

use App\Application\Services\PecaService;
use App\Domain\Peca\Entities\Peca;
use App\Domain\Peca\Repositories\PecaRepositoryInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PecaServiceTest extends TestCase
{
    private PecaRepositoryInterface&MockObject $repository;
    private PecaService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PecaRepositoryInterface::class);
        $this->service = new PecaService($this->repository);
    }

    private function makePeca(int $id = 1): Peca
    {
        return new Peca(
            id: $id,
            nome: 'Filtro de Óleo',
            codigo: 'FO-001',
            categoria: 'Filtros',
            precoUnitario: 45.90,
            estoqueAtual: 10,
            estoqueMininmo: 2,
            criadoEm: new \DateTimeImmutable(),
        );
    }

    // ── listarTodas ───────────────────────────────────────────────

    public function test_listar_todas_retorna_array(): void
    {
        $pecas = [$this->makePeca(1), $this->makePeca(2)];

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($pecas);

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

    public function test_buscar_por_id_retorna_peca_existente(): void
    {
        $peca = $this->makePeca(1);

        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($peca);

        $result = $this->service->buscarPorId(1);

        $this->assertSame($peca, $result);
    }

    public function test_buscar_por_id_lanca_excecao_quando_nao_encontrada(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Peça #99 não encontrada.');

        $this->service->buscarPorId(99);
    }

    // ── buscarPorCodigo ───────────────────────────────────────────

    public function test_buscar_por_codigo_retorna_peca_existente(): void
    {
        $peca = $this->makePeca(1);

        $this->repository->expects($this->once())
            ->method('findByCodigo')
            ->with('FO-001')
            ->willReturn($peca);

        $result = $this->service->buscarPorCodigo('FO-001');

        $this->assertSame($peca, $result);
    }

    public function test_buscar_por_codigo_lanca_excecao_quando_nao_encontrada(): void
    {
        $this->repository->method('findByCodigo')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Peça com código 'INEXISTENTE' não encontrada.");

        $this->service->buscarPorCodigo('INEXISTENTE');
    }

    // ── criar ─────────────────────────────────────────────────────

    public function test_criar_peca_com_sucesso(): void
    {
        $peca = $this->makePeca(1);

        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($peca);

        $result = $this->service->criar([
            'nome'           => 'Filtro de Óleo',
            'codigo'         => 'FO-001',
            'categoria'      => 'Filtros',
            'preco_unitario' => 45.90,
            'estoque_atual'  => 10,
            'estoque_minimo' => 2,
        ]);

        $this->assertInstanceOf(Peca::class, $result);
    }

    public function test_criar_peca_sem_categoria_com_sucesso(): void
    {
        $peca = new Peca(
            id: 1,
            nome: 'Parafuso',
            codigo: 'PA-001',
            categoria: null,
            precoUnitario: 1.50,
            estoqueAtual: 100,
            estoqueMininmo: 10,
            criadoEm: new \DateTimeImmutable(),
        );

        $this->repository->method('save')->willReturn($peca);

        $result = $this->service->criar([
            'nome'           => 'Parafuso',
            'codigo'         => 'PA-001',
            'preco_unitario' => 1.50,
        ]);

        $this->assertNull($result->getCategoria());
    }

    public function test_criar_peca_sem_estoque_usa_zero_como_padrao(): void
    {
        $peca = new Peca(
            id: 1,
            nome: 'Porca',
            codigo: 'PC-001',
            categoria: null,
            precoUnitario: 0.50,
            estoqueAtual: 0,
            estoqueMininmo: 0,
            criadoEm: new \DateTimeImmutable(),
        );

        $this->repository->method('save')->willReturn($peca);

        $result = $this->service->criar([
            'nome'           => 'Porca',
            'codigo'         => 'PC-001',
            'preco_unitario' => 0.50,
        ]);

        $this->assertEquals(0, $result->getEstoqueAtual());
        $this->assertEquals(0, $result->getEstoqueMininmo());
    }

    // ── atualizar ─────────────────────────────────────────────────

    public function test_atualizar_peca_existente_com_sucesso(): void
    {
        $peca = $this->makePeca(1);

        $this->repository->method('findById')->willReturn($peca);
        $this->repository->expects($this->once())
            ->method('save')
            ->willReturn($peca);

        $result = $this->service->atualizar(1, [
            'nome'           => 'Filtro de Ar',
            'codigo'         => 'FA-001',
            'categoria'      => 'Filtros',
            'preco_unitario' => 35.00,
            'estoque_atual'  => 15,
            'estoque_minimo' => 3,
        ]);

        $this->assertInstanceOf(Peca::class, $result);
    }

    public function test_atualizar_peca_inexistente_lanca_excecao(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(\RuntimeException::class);

        $this->service->atualizar(99, [
            'nome'           => 'X',
            'codigo'         => 'X-001',
            'preco_unitario' => 1.00,
        ]);
    }

    // ── remover ───────────────────────────────────────────────────

    public function test_remover_peca_existente_com_sucesso(): void
    {
        $peca = $this->makePeca(1);

        $this->repository->method('findById')->willReturn($peca);
        $this->repository->expects($this->once())
            ->method('delete')
            ->with(1);

        $this->service->remover(1);
    }

    public function test_remover_peca_inexistente_lanca_excecao(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(\RuntimeException::class);

        $this->service->remover(99);
    }
}
