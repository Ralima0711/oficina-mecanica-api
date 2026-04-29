<?php

namespace Tests\Unit\Domain\Estoque;

use App\Domain\Estoque\Services\EstoqueService;
use App\Domain\Estoque\ValueObjects\EstoqueInfo;
use App\Domain\Peca\Entities\Peca;
use App\Domain\Estoque\Events\EstoqueDiminuido;
use App\Domain\Estoque\Events\EstoqueAlertado;
use PHPUnit\Framework\TestCase;

class EstoqueIntegracaoTest extends TestCase
{
    private EstoqueService $estoqueService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->estoqueService = new EstoqueService();
    }

    public function test_integra_estoque_com_entidade_peca(): void
    {
        $peca = new Peca(
            id: 1,
            nome: 'Pneu Michelin',
            codigo: 'PN-001',
            categoria: 'Pneus',
            precoUnitario: 150.50,
            estoqueAtual: 20,
            estoqueMininmo: 5,
            criadoEm: new \DateTimeImmutable(),
        );

        $this->assertEquals(20, $peca->getEstoqueAtual());
        $this->assertEquals(5, $peca->getEstoqueMininmo());
    }

    public function test_estoque_service_diminui_quantidade_corretamente(): void
    {
        $estoqueInfo = EstoqueInfo::criar(30, 5, 100);

        $novoEstoque = $this->estoqueService->diminuir($estoqueInfo, 10, 'Venda OS #123');

        $this->assertEquals(20, $novoEstoque->getAtual());
        $this->assertEquals(5, $novoEstoque->getMinimo());
    }

    public function test_estoque_service_detecta_minimo_e_dispara_alerta(): void
    {
        $estoqueInfo = EstoqueInfo::criar(8, 5, 100);

        $novoEstoque = $this->estoqueService->diminuir($estoqueInfo, 4, 'Venda');

        $this->assertTrue($novoEstoque->estaAbaixoDoMinimo());

        $eventos = $this->estoqueService->getEventos();
        $temAlerta = false;

        foreach ($eventos as $evento) {
            if ($evento instanceof EstoqueAlertado) {
                $temAlerta = true;
                $this->assertEquals(4, $evento->getEstoqueAtual());
                $this->assertEquals(5, $evento->getEstoqueMinimo());
            }
        }

        $this->assertTrue($temAlerta, 'Deve disparar evento EstoqueAlertado');
    }

    public function test_multiplos_movimentos_registram_eventos(): void
    {
        $estoque = EstoqueInfo::criar(50, 10, 100);

        $estoque = $this->estoqueService->aumentar($estoque, 10, 'Compra');
        $estoque = $this->estoqueService->diminuir($estoque, 5, 'Venda');

        $eventos = $this->estoqueService->getEventos();

        $this->assertGreaterThanOrEqual(2, count($eventos));
    }

    public function test_evento_contem_informacoes_corretas(): void
    {
        $estoque = EstoqueInfo::criar(20, 5, 100);

        $novoEstoque = $this->estoqueService->diminuir($estoque, 5, 'Venda OS #456');

        $eventos = $this->estoqueService->getEventos();
        $evento = $eventos[0];

        $this->assertInstanceOf(EstoqueDiminuido::class, $evento);
        $this->assertEquals(5, $evento->getQuantidade());
        $this->assertEquals(20, $evento->getEstoqueAnterior());
        $this->assertEquals(15, $evento->getEstoqueAtual());
        $this->assertEquals('Venda OS #456', $evento->getMotivo());
    }

    public function test_calcula_disponibilidade_com_itens_em_processamento(): void
    {
        $estoque = EstoqueInfo::criar(50, 10, 100);

        $disponivel = $this->estoqueService->calcularDisponibilidade($estoque, 20);

        $this->assertEquals(30, $disponivel);
    }

    public function test_ajusta_estoque_para_baixo_dispara_evento_diminucao(): void
    {
        $estoque = EstoqueInfo::criar(50, 10, 100);

        $novoEstoque = $this->estoqueService->ajustar($estoque, 30, 'Ajuste de inventário');

        $this->assertEquals(30, $novoEstoque->getAtual());

        $eventos = $this->estoqueService->getEventos();
        $temDiminuicao = false;

        foreach ($eventos as $evento) {
            if ($evento instanceof EstoqueDiminuido) {
                $temDiminuicao = true;
                $this->assertEquals(20, $evento->getQuantidade());
            }
        }

        $this->assertTrue($temDiminuicao);
    }
}
