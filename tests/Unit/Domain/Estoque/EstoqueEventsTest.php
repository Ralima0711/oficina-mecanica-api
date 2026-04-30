<?php

namespace Tests\Unit\Domain\Estoque;

use App\Domain\Estoque\Events\EstoqueAlertado;
use App\Domain\Estoque\Events\EstoqueAumentado;
use App\Domain\Estoque\Events\EstoqueDiminuido;
use PHPUnit\Framework\TestCase;

class EstoqueEventsTest extends TestCase
{
    // ── EstoqueAumentado ──────────────────────────────────────────

    public function test_estoque_aumentado_getters_retornam_valores_corretos(): void
    {
        $evento = new EstoqueAumentado(
            itemId: 1,
            itemTipo: 'peca',
            quantidade: 5.0,
            estoqueAnterior: 10.0,
            estoqueAtual: 15.0,
            motivo: 'Reposição',
        );

        $this->assertEquals(1, $evento->getItemId());
        $this->assertEquals('peca', $evento->getItemTipo());
        $this->assertEquals(5.0, $evento->getQuantidade());
        $this->assertEquals(10.0, $evento->getEstoqueAnterior());
        $this->assertEquals(15.0, $evento->getEstoqueAtual());
        $this->assertEquals('Reposição', $evento->getMotivo());
    }

    public function test_estoque_aumentado_sem_motivo_retorna_null(): void
    {
        $evento = new EstoqueAumentado(1, 'peca', 5.0, 10.0, 15.0);

        $this->assertNull($evento->getMotivo());
    }

    public function test_estoque_aumentado_para_insumo(): void
    {
        $evento = new EstoqueAumentado(2, 'insumo', 10.5, 0.0, 10.5);

        $this->assertEquals(2, $evento->getItemId());
        $this->assertEquals('insumo', $evento->getItemTipo());
        $this->assertEquals(10.5, $evento->getQuantidade());
        $this->assertEquals(0.0, $evento->getEstoqueAnterior());
        $this->assertEquals(10.5, $evento->getEstoqueAtual());
    }

    public function test_estoque_aumentado_sem_parametros_usa_defaults(): void
    {
        $evento = new EstoqueAumentado();

        $this->assertEquals(0, $evento->getItemId());
        $this->assertEquals('', $evento->getItemTipo());
        $this->assertEquals(0.0, $evento->getQuantidade());
    }

    public function test_estoque_aumentado_set_item_id(): void
    {
        $evento = new EstoqueAumentado();
        $evento->setItemId(99);

        $this->assertEquals(99, $evento->getItemId());
    }

    public function test_estoque_aumentado_set_item_tipo(): void
    {
        $evento = new EstoqueAumentado();
        $evento->setItemTipo('insumo');

        $this->assertEquals('insumo', $evento->getItemTipo());
    }

    // ── EstoqueDiminuido ──────────────────────────────────────────

    public function test_estoque_diminuido_getters_retornam_valores_corretos(): void
    {
        $evento = new EstoqueDiminuido(
            itemId: 1,
            itemTipo: 'peca',
            quantidade: 5.0,
            estoqueAnterior: 15.0,
            estoqueAtual: 10.0,
            motivo: 'Uso em OS',
        );

        $this->assertEquals(1, $evento->getItemId());
        $this->assertEquals('peca', $evento->getItemTipo());
        $this->assertEquals(5.0, $evento->getQuantidade());
        $this->assertEquals(15.0, $evento->getEstoqueAnterior());
        $this->assertEquals(10.0, $evento->getEstoqueAtual());
        $this->assertEquals('Uso em OS', $evento->getMotivo());
    }

    public function test_estoque_diminuido_sem_motivo_retorna_null(): void
    {
        $evento = new EstoqueDiminuido(1, 'peca', 5.0, 15.0, 10.0);

        $this->assertNull($evento->getMotivo());
    }

    public function test_estoque_diminuido_ate_zero(): void
    {
        $evento = new EstoqueDiminuido(1, 'peca', 5.0, 5.0, 0.0);

        $this->assertEquals(0.0, $evento->getEstoqueAtual());
    }

    public function test_estoque_diminuido_para_insumo_fracionado(): void
    {
        $evento = new EstoqueDiminuido(3, 'insumo', 2.5, 10.0, 7.5);

        $this->assertEquals('insumo', $evento->getItemTipo());
        $this->assertEquals(7.5, $evento->getEstoqueAtual());
    }

    public function test_estoque_diminuido_sem_parametros_usa_defaults(): void
    {
        $evento = new EstoqueDiminuido();

        $this->assertEquals(0, $evento->getItemId());
        $this->assertEquals('', $evento->getItemTipo());
        $this->assertEquals(0.0, $evento->getQuantidade());
    }

    public function test_estoque_diminuido_set_item_id(): void
    {
        $evento = new EstoqueDiminuido();
        $evento->setItemId(42);

        $this->assertEquals(42, $evento->getItemId());
    }

    public function test_estoque_diminuido_set_item_tipo(): void
    {
        $evento = new EstoqueDiminuido();
        $evento->setItemTipo('peca');

        $this->assertEquals('peca', $evento->getItemTipo());
    }

    // ── EstoqueAlertado ───────────────────────────────────────────

    public function test_estoque_alertado_getters_retornam_valores_corretos(): void
    {
        $evento = new EstoqueAlertado(
            itemId: 1,
            itemTipo: 'peca',
            estoqueAtual: 2.0,
            estoqueMinimo: 5.0,
        );

        $this->assertEquals(1, $evento->getItemId());
        $this->assertEquals('peca', $evento->getItemTipo());
        $this->assertEquals(2.0, $evento->getEstoqueAtual());
        $this->assertEquals(5.0, $evento->getEstoqueMinimo());
    }

    public function test_estoque_alertado_deficit_calculado_corretamente(): void
    {
        $evento = new EstoqueAlertado(1, 'peca', 2.0, 10.0);

        $this->assertEquals(8.0, $evento->getDeficit());
    }

    public function test_estoque_alertado_deficit_zero_quando_no_minimo(): void
    {
        $evento = new EstoqueAlertado(1, 'peca', 5.0, 5.0);

        $this->assertEquals(0.0, $evento->getDeficit());
    }

    public function test_estoque_alertado_estoque_zero(): void
    {
        $evento = new EstoqueAlertado(1, 'peca', 0.0, 3.0);

        $this->assertEquals(0.0, $evento->getEstoqueAtual());
        $this->assertEquals(3.0, $evento->getDeficit());
    }

    public function test_estoque_alertado_para_insumo(): void
    {
        $evento = new EstoqueAlertado(5, 'insumo', 1.5, 10.0);

        $this->assertEquals(5, $evento->getItemId());
        $this->assertEquals('insumo', $evento->getItemTipo());
        $this->assertEquals(1.5, $evento->getEstoqueAtual());
        $this->assertEquals(10.0, $evento->getEstoqueMinimo());
        $this->assertEquals(8.5, $evento->getDeficit());
    }

    public function test_estoque_alertado_sem_parametros_usa_defaults(): void
    {
        $evento = new EstoqueAlertado();

        $this->assertEquals(0, $evento->getItemId());
        $this->assertEquals('', $evento->getItemTipo());
        $this->assertEquals(0.0, $evento->getEstoqueAtual());
    }

    public function test_estoque_alertado_set_item_id(): void
    {
        $evento = new EstoqueAlertado();
        $evento->setItemId(10);

        $this->assertEquals(10, $evento->getItemId());
    }

    public function test_estoque_alertado_set_item_tipo(): void
    {
        $evento = new EstoqueAlertado();
        $evento->setItemTipo('insumo');

        $this->assertEquals('insumo', $evento->getItemTipo());
    }
}

