<?php

namespace Tests\Unit\Domain\Estoque;

use App\Domain\Estoque\Services\EstoqueService;
use App\Domain\Estoque\ValueObjects\EstoqueInfo;
use App\Domain\Estoque\Events\EstoqueDiminuido;
use App\Domain\Estoque\Events\EstoqueAumentado;
use App\Domain\Estoque\Events\EstoqueAlertado;
use PHPUnit\Framework\TestCase;

class EstoqueServiceTest extends TestCase
{
    private EstoqueService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EstoqueService();
    }

    public function test_nao_permite_diminuir_estoque_negativo(): void
    {
        $estoque = EstoqueInfo::criar(10, 5);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("Estoque insuficiente");

        $this->service->diminuir($estoque, 15);
    }

    public function test_nao_permite_quantidade_zero_ou_negativa(): void
    {
        $estoque = EstoqueInfo::criar(10, 5);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->diminuir($estoque, 0);
    }

    public function test_diminui_estoque_corretamente(): void
    {
        $estoque = EstoqueInfo::criar(20, 5);

        $novoEstoque = $this->service->diminuir($estoque, 5, 'Venda');

        $this->assertEquals(15, $novoEstoque->getAtual());
    }

    public function test_dispara_alerta_quando_atinge_minimo(): void
    {
        $estoque = EstoqueInfo::criar(8, 5);

        $novoEstoque = $this->service->diminuir($estoque, 4);

        $eventos = $this->service->getEventos();
        $temAlerta = false;
        foreach ($eventos as $evento) {
            if ($evento instanceof EstoqueAlertado) {
                $temAlerta = true;
                break;
            }
        }

        $this->assertTrue($temAlerta, "Deve disparar evento EstoqueAlertado");
        $this->assertEquals(4, $novoEstoque->getAtual());
    }

    public function test_aumenta_estoque_corretamente(): void
    {
        $estoque = EstoqueInfo::criar(10, 5);

        $novoEstoque = $this->service->aumentar($estoque, 5, 'Compra');

        $this->assertEquals(15, $novoEstoque->getAtual());
    }

    public function test_nao_permite_estoque_acima_do_maximo(): void
    {
        $estoque = EstoqueInfo::criar(18, 5, 20);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("ultrapassaria o máximo");

        $this->service->aumentar($estoque, 5);
    }

    public function test_ajusta_estoque_para_cima(): void
    {
        $estoque = EstoqueInfo::criar(10, 5);

        $novoEstoque = $this->service->ajustar($estoque, 15);

        $this->assertEquals(15, $novoEstoque->getAtual());
    }

    public function test_ajusta_estoque_para_baixo(): void
    {
        $estoque = EstoqueInfo::criar(15, 5);

        $novoEstoque = $this->service->ajustar($estoque, 10);

        $this->assertEquals(10, $novoEstoque->getAtual());
    }

    public function test_calcula_disponibilidade_corretamente(): void
    {
        $estoque = EstoqueInfo::criar(20, 5);

        $disponivel = $this->service->calcularDisponibilidade($estoque, 5);

        $this->assertEquals(15, $disponivel);
    }

    public function test_registra_evento_ao_diminuir(): void
    {
        $estoque = EstoqueInfo::criar(20, 5);

        $this->service->diminuir($estoque, 5);

        $eventos = $this->service->getEventos();
        $this->assertCount(1, $eventos);
        $this->assertInstanceOf(EstoqueDiminuido::class, $eventos[0]);
    }

    public function test_registra_evento_ao_aumentar(): void
    {
        $estoque = EstoqueInfo::criar(10, 5);

        $this->service->aumentar($estoque, 5);

        $eventos = $this->service->getEventos();
        $this->assertCount(1, $eventos);
        $this->assertInstanceOf(EstoqueAumentado::class, $eventos[0]);
    }

    public function test_limpa_eventos(): void
    {
        $estoque = EstoqueInfo::criar(20, 5);

        $this->service->diminuir($estoque, 5);
        $this->assertNotEmpty($this->service->getEventos());

        $this->service->limparEventos();
        $this->assertEmpty($this->service->getEventos());
    }

    public function test_detecta_estoque_abaixo_minimo(): void
    {
        $estoque = EstoqueInfo::criar(4, 5);

        $estaAbaixo = $this->service->estaAbaixoDoMinimo($estoque);

        $this->assertTrue($estaAbaixo);
    }

    public function test_value_object_nao_permite_criar_invalido(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        EstoqueInfo::criar(-5, 10);
    }
}
