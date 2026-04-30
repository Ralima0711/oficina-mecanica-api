<?php

namespace Tests\Unit\Domain\Entities;

use App\Domain\Peca\Entities\Peca;
use PHPUnit\Framework\TestCase;

class PecaTest extends TestCase
{
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

    public function test_peca_getters_retornam_valores_corretos(): void
    {
        $peca = $this->makePeca(1);

        $this->assertEquals(1, $peca->getId());
        $this->assertEquals('Filtro de Óleo', $peca->getNome());
        $this->assertEquals('FO-001', $peca->getCodigo());
        $this->assertEquals('Filtros', $peca->getCategoria());
        $this->assertEquals(45.90, $peca->getPrecoUnitario());
        $this->assertEquals(10, $peca->getEstoqueAtual());
        $this->assertEquals(2, $peca->getEstoqueMininmo());
        $this->assertNotNull($peca->getCriadoEm());
        $this->assertNull($peca->getAtualizadoEm());
    }

    public function test_peca_sem_id_retorna_null(): void
    {
        $peca = new Peca(
            id: null,
            nome: 'Nova Peça',
            codigo: 'NP-001',
            categoria: null,
            precoUnitario: 10.00,
            estoqueAtual: 0,
            estoqueMininmo: 0,
            criadoEm: new \DateTimeImmutable(),
        );

        $this->assertNull($peca->getId());
    }

    public function test_peca_sem_categoria_retorna_null(): void
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

        $this->assertNull($peca->getCategoria());
    }

    public function test_peca_atualizar_muda_todos_os_campos(): void
    {
        $peca = $this->makePeca(1);

        $peca->atualizar(
            'Filtro de Ar',
            'FA-002',
            'Filtros',
            35.00,
            15,
            3
        );

        $this->assertEquals('Filtro de Ar', $peca->getNome());
        $this->assertEquals('FA-002', $peca->getCodigo());
        $this->assertEquals('Filtros', $peca->getCategoria());
        $this->assertEquals(35.00, $peca->getPrecoUnitario());
        $this->assertEquals(15, $peca->getEstoqueAtual());
        $this->assertEquals(3, $peca->getEstoqueMininmo());
    }

    public function test_peca_atualizar_define_atualizado_em(): void
    {
        $peca = $this->makePeca(1);

        $this->assertNull($peca->getAtualizadoEm());

        $peca->atualizar('Filtro de Ar', 'FA-002', null, 35.00, 15, 3);

        $this->assertNotNull($peca->getAtualizadoEm());
        $this->assertInstanceOf(\DateTimeImmutable::class, $peca->getAtualizadoEm());
    }

    public function test_peca_atualizar_categoria_para_null(): void
    {
        $peca = $this->makePeca(1);

        $peca->atualizar('Filtro', 'FO-001', null, 45.90, 10, 2);

        $this->assertNull($peca->getCategoria());
    }

    public function test_peca_atualizar_estoque_para_zero(): void
    {
        $peca = $this->makePeca(1);

        $peca->atualizar('Filtro', 'FO-001', 'Filtros', 45.90, 0, 0);

        $this->assertEquals(0, $peca->getEstoqueAtual());
        $this->assertEquals(0, $peca->getEstoqueMininmo());
    }

    public function test_peca_json_serialize_retorna_estrutura_correta(): void
    {
        $peca = $this->makePeca(1);
        $array = $peca->jsonSerialize();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('nome', $array);
        $this->assertArrayHasKey('codigo', $array);
        $this->assertArrayHasKey('categoria', $array);
        $this->assertArrayHasKey('preco_unitario', $array);
        $this->assertArrayHasKey('estoque_atual', $array);
        $this->assertArrayHasKey('estoque_minimo', $array);
        $this->assertArrayHasKey('criado_em', $array);
        $this->assertArrayHasKey('atualizado_em', $array);
    }

    public function test_peca_json_serialize_valores_corretos(): void
    {
        $peca = $this->makePeca(1);
        $array = $peca->jsonSerialize();

        $this->assertEquals(1, $array['id']);
        $this->assertEquals('Filtro de Óleo', $array['nome']);
        $this->assertEquals('FO-001', $array['codigo']);
        $this->assertEquals('Filtros', $array['categoria']);
        $this->assertEquals(45.90, $array['preco_unitario']);
        $this->assertEquals(10, $array['estoque_atual']);
        $this->assertEquals(2, $array['estoque_minimo']);
        $this->assertNull($array['atualizado_em']);
    }

    public function test_peca_json_serialize_apos_atualizar(): void
    {
        $peca = $this->makePeca(1);
        $peca->atualizar('Filtro de Ar', 'FA-002', null, 35.00, 15, 3);

        $array = $peca->jsonSerialize();

        $this->assertEquals('Filtro de Ar', $array['nome']);
        $this->assertNotNull($array['atualizado_em']);
    }

    public function test_peca_estoque_atual_inicial(): void
    {
        $peca = new Peca(
            id: 1,
            nome: 'Peça Nova',
            codigo: 'PN-001',
            categoria: null,
            precoUnitario: 20.00,
            estoqueAtual: 5,
            estoqueMininmo: 1,
            criadoEm: new \DateTimeImmutable(),
        );

        $this->assertEquals(5, $peca->getEstoqueAtual());
        $this->assertEquals(1, $peca->getEstoqueMininmo());
    }

    public function test_peca_preco_unitario_decimal(): void
    {
        $peca = new Peca(
            id: 1,
            nome: 'Peça Cara',
            codigo: 'PC-001',
            categoria: 'Premium',
            precoUnitario: 1299.99,
            estoqueAtual: 2,
            estoqueMininmo: 1,
            criadoEm: new \DateTimeImmutable(),
        );

        $this->assertEquals(1299.99, $peca->getPrecoUnitario());
    }
}
