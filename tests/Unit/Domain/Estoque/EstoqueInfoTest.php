<?php

namespace Tests\Unit\Domain\Estoque;

use App\Domain\Estoque\ValueObjects\EstoqueInfo;
use PHPUnit\Framework\TestCase;

class EstoqueInfoTest extends TestCase
{
    // ── construtor e getters ──────────────────────────────────────

    public function test_criar_estoque_info_com_valores_validos(): void
    {
        $info = new EstoqueInfo(10.0, 2.0, 100.0);

        $this->assertEquals(10.0, $info->getAtual());
        $this->assertEquals(2.0, $info->getMinimo());
        $this->assertEquals(100.0, $info->getMaximo());
    }

    public function test_criar_estoque_info_sem_maximo_usa_php_float_max(): void
    {
        $info = new EstoqueInfo(10.0, 2.0);

        $this->assertEquals(PHP_FLOAT_MAX, $info->getMaximo());
    }

    public function test_criar_via_factory_criar(): void
    {
        $info = EstoqueInfo::criar(5.0, 1.0, 50.0);

        $this->assertEquals(5.0, $info->getAtual());
        $this->assertEquals(1.0, $info->getMinimo());
        $this->assertEquals(50.0, $info->getMaximo());
    }

    public function test_criar_via_factory_sem_maximo(): void
    {
        $info = EstoqueInfo::criar(5.0, 1.0);

        $this->assertEquals(PHP_FLOAT_MAX, $info->getMaximo());
    }

    // ── validações ────────────────────────────────────────────────

    public function test_estoque_atual_negativo_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Estoque atual não pode ser negativo');

        new EstoqueInfo(-1.0, 2.0, 100.0);
    }

    public function test_estoque_minimo_negativo_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Estoque mínimo não pode ser negativo');

        new EstoqueInfo(10.0, -1.0, 100.0);
    }

    public function test_estoque_maximo_zero_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Estoque máximo deve ser positivo');

        new EstoqueInfo(10.0, 2.0, 0.0);
    }

    public function test_estoque_maximo_negativo_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Estoque máximo deve ser positivo');

        new EstoqueInfo(10.0, 2.0, -5.0);
    }

    public function test_minimo_maior_que_maximo_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Estoque mínimo não pode ser maior que o máximo');

        new EstoqueInfo(10.0, 50.0, 20.0);
    }

    public function test_estoque_atual_zero_e_valido(): void
    {
        $info = new EstoqueInfo(0.0, 0.0, 100.0);

        $this->assertEquals(0.0, $info->getAtual());
    }

    // ── estaAbaixoDoMinimo ────────────────────────────────────────

    public function test_esta_abaixo_do_minimo_retorna_true(): void
    {
        $info = new EstoqueInfo(1.0, 5.0, 100.0);

        $this->assertTrue($info->estaAbaixoDoMinimo());
    }

    public function test_esta_abaixo_do_minimo_retorna_false_quando_igual(): void
    {
        $info = new EstoqueInfo(5.0, 5.0, 100.0);

        $this->assertFalse($info->estaAbaixoDoMinimo());
    }

    public function test_esta_abaixo_do_minimo_retorna_false_quando_acima(): void
    {
        $info = new EstoqueInfo(10.0, 5.0, 100.0);

        $this->assertFalse($info->estaAbaixoDoMinimo());
    }

    // ── estaAcimaDoMaximo ─────────────────────────────────────────

    public function test_esta_acima_do_maximo_retorna_true(): void
    {
        $info = new EstoqueInfo(150.0, 5.0, 100.0);

        $this->assertTrue($info->estaAcimaDoMaximo());
    }

    public function test_esta_acima_do_maximo_retorna_false_quando_igual(): void
    {
        $info = new EstoqueInfo(100.0, 5.0, 100.0);

        $this->assertFalse($info->estaAcimaDoMaximo());
    }

    public function test_esta_acima_do_maximo_retorna_false_quando_abaixo(): void
    {
        $info = new EstoqueInfo(50.0, 5.0, 100.0);

        $this->assertFalse($info->estaAcimaDoMaximo());
    }

    // ── temDisponibilidade ────────────────────────────────────────

    public function test_tem_disponibilidade_sem_quantidade_retorna_true(): void
    {
        $info = new EstoqueInfo(10.0, 2.0, 100.0);

        $this->assertTrue($info->temDisponibilidade());
    }

    public function test_tem_disponibilidade_com_quantidade_menor_retorna_true(): void
    {
        $info = new EstoqueInfo(10.0, 2.0, 100.0);

        $this->assertTrue($info->temDisponibilidade(5.0));
    }

    public function test_tem_disponibilidade_com_quantidade_igual_retorna_true(): void
    {
        $info = new EstoqueInfo(10.0, 2.0, 100.0);

        $this->assertTrue($info->temDisponibilidade(10.0));
    }

    public function test_tem_disponibilidade_com_quantidade_maior_retorna_false(): void
    {
        $info = new EstoqueInfo(10.0, 2.0, 100.0);

        $this->assertFalse($info->temDisponibilidade(15.0));
    }

    public function test_tem_disponibilidade_estoque_zero_retorna_true_sem_quantidade(): void
    {
        $info = new EstoqueInfo(0.0, 0.0, 100.0);

        $this->assertTrue($info->temDisponibilidade(0.0));
    }

    // ── percentualDisponibilidade ─────────────────────────────────

    public function test_percentual_disponibilidade_metade(): void
    {
        $info = new EstoqueInfo(50.0, 0.0, 100.0);

        $this->assertEquals(50.0, $info->percentualDisponibilidade());
    }

    public function test_percentual_disponibilidade_cheio(): void
    {
        $info = new EstoqueInfo(100.0, 0.0, 100.0);

        $this->assertEquals(100.0, $info->percentualDisponibilidade());
    }

    public function test_percentual_disponibilidade_vazio(): void
    {
        $info = new EstoqueInfo(0.0, 0.0, 100.0);

        $this->assertEquals(0.0, $info->percentualDisponibilidade());
    }

    public function test_percentual_disponibilidade_fracionado(): void
    {
        $info = new EstoqueInfo(25.0, 0.0, 100.0);

        $this->assertEquals(25.0, $info->percentualDisponibilidade());
    }
}
