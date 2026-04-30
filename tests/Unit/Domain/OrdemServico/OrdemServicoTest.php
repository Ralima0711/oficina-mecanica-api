<?php

namespace Tests\Unit\Domain\OrdemServico;

use App\Domain\OrdemServico\Entities\OrdemServico;
use App\Domain\OrdemServico\ValueObjects\StatusOrdem;
use PHPUnit\Framework\TestCase;

class OrdemServicoTest extends TestCase
{
    private function makeOS(string $status = StatusOrdem::RECEBIDA): OrdemServico
    {
        return new OrdemServico(
            id: 1,
            clienteId: 10,
            veiculoId: 20,
            mecanicoId: null,
            status: StatusOrdem::from($status),
            descricaoProblema: 'Carro não liga',
            diagnostico: null,
            valorTotal: null,
            iniciadaEm: null,
            criadaEm: new \DateTimeImmutable(),
        );
    }

    // ── StatusOrdem ───────────────────────────────────────────────

    public function test_status_ordem_from_valido(): void
    {
        $status = StatusOrdem::from('RECEBIDA');
        $this->assertEquals('RECEBIDA', (string) $status);
    }

    public function test_status_ordem_invalido_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        StatusOrdem::from('INVALIDO');
    }

    public function test_status_equals_retorna_true_para_mesmo_status(): void
    {
        $status = StatusOrdem::from(StatusOrdem::RECEBIDA);
        $this->assertTrue($status->equals(StatusOrdem::RECEBIDA));
    }

    public function test_status_equals_retorna_false_para_status_diferente(): void
    {
        $status = StatusOrdem::from(StatusOrdem::RECEBIDA);
        $this->assertFalse($status->equals(StatusOrdem::FINALIZADA));
    }

    public function test_todos_os_status_validos_sao_criados_corretamente(): void
    {
        $statuses = [
            StatusOrdem::RECEBIDA,
            StatusOrdem::EM_DIAGNOSTICO,
            StatusOrdem::AGUARDANDO_APROVACAO,
            StatusOrdem::APROVADA,
            StatusOrdem::EM_EXECUCAO,
            StatusOrdem::FINALIZADA,
            StatusOrdem::ENTREGUE,
        ];

        foreach ($statuses as $s) {
            $this->assertEquals($s, (string) StatusOrdem::from($s));
        }
    }

    // ── iniciarDiagnostico ────────────────────────────────────────

    public function test_iniciar_diagnostico_em_os_recebida(): void
    {
        $os = $this->makeOS(StatusOrdem::RECEBIDA);
        $os->iniciarDiagnostico(5);

        $this->assertTrue($os->getStatus()->equals(StatusOrdem::EM_DIAGNOSTICO));
        $this->assertEquals(5, $os->getMecanicoId());
    }

    public function test_iniciar_diagnostico_em_os_nao_recebida_lanca_excecao(): void
    {
        $os = $this->makeOS(StatusOrdem::EM_DIAGNOSTICO);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Somente ordens RECEBIDAS podem iniciar diagnostico.');

        $os->iniciarDiagnostico(5);
    }

    public function test_iniciar_diagnostico_com_mecanico_invalido_lanca_excecao(): void
    {
        $os = $this->makeOS(StatusOrdem::RECEBIDA);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Mecânico invalido para iniciar diagnostico.');

        $os->iniciarDiagnostico(0);
    }

    // ── gerarOrcamento ────────────────────────────────────────────

    public function test_gerar_orcamento_em_os_em_diagnostico(): void
    {
        $os = new OrdemServico(
            id: 1,
            clienteId: 10,
            veiculoId: 20,
            mecanicoId: 5,
            status: StatusOrdem::from(StatusOrdem::EM_DIAGNOSTICO),
            descricaoProblema: 'Carro não liga',
            diagnostico: 'Problema na bateria',
            valorTotal: null,
            iniciadaEm: new \DateTimeImmutable(),
            criadaEm: new \DateTimeImmutable(),
        );

        $os->gerarOrcamento();

        $this->assertTrue($os->getStatus()->equals(StatusOrdem::AGUARDANDO_APROVACAO));
    }

    public function test_gerar_orcamento_sem_diagnostico_lanca_excecao(): void
    {
        $os = $this->makeOS(StatusOrdem::EM_DIAGNOSTICO);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Informe um diagnostico antes de gerar orçamento.');

        $os->gerarOrcamento();
    }

    public function test_gerar_orcamento_em_status_errado_lanca_excecao(): void
    {
        $os = $this->makeOS(StatusOrdem::RECEBIDA);

        $this->expectException(\DomainException::class);

        $os->gerarOrcamento();
    }

    // ── aprovar ───────────────────────────────────────────────────

    public function test_aprovar_os_aguardando_aprovacao(): void
    {
        $os = $this->makeOS(StatusOrdem::AGUARDANDO_APROVACAO);
        $os->aprovar();

        $this->assertTrue($os->getStatus()->equals(StatusOrdem::APROVADA));
    }

    public function test_aprovar_os_em_status_errado_lanca_excecao(): void
    {
        $os = $this->makeOS(StatusOrdem::RECEBIDA);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Somente ordens AGUARDANDO_APROVACAO podem ser aprovadas.');

        $os->aprovar();
    }

    // ── reprovar ──────────────────────────────────────────────────

    public function test_reprovar_os_aguardando_aprovacao(): void
    {
        $os = $this->makeOS(StatusOrdem::AGUARDANDO_APROVACAO);
        $os->reprovar();

        $this->assertTrue($os->getStatus()->equals(StatusOrdem::FINALIZADA));
    }

    public function test_reprovar_os_em_status_errado_lanca_excecao(): void
    {
        $os = $this->makeOS(StatusOrdem::RECEBIDA);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Somente ordens AGUARDANDO_APROVACAO podem ser reprovadas.');

        $os->reprovar();
    }

    // ── iniciarExecucao ───────────────────────────────────────────

    public function test_iniciar_execucao_em_os_aprovada(): void
    {
        $os = $this->makeOS(StatusOrdem::APROVADA);
        $os->iniciarExecucao();

        $this->assertTrue($os->getStatus()->equals(StatusOrdem::EM_EXECUCAO));
    }

    public function test_iniciar_execucao_em_status_errado_lanca_excecao(): void
    {
        $os = $this->makeOS(StatusOrdem::RECEBIDA);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Somente ordens APROVADAS podem iniciar execução.');

        $os->iniciarExecucao();
    }

    // ── finalizarServico ──────────────────────────────────────────

    public function test_finalizar_servico_em_os_em_execucao(): void
    {
        $os = $this->makeOS(StatusOrdem::EM_EXECUCAO);
        $os->finalizarServico(500.00);

        $this->assertTrue($os->getStatus()->equals(StatusOrdem::FINALIZADA));
        $this->assertEquals(500.00, $os->getValorTotal());
        $this->assertNotNull($os->getConcluidaEm());
    }

    public function test_finalizar_servico_com_valor_negativo_lanca_excecao(): void
    {
        $os = $this->makeOS(StatusOrdem::EM_EXECUCAO);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('O valor total da ordem não pode ser negativo.');

        $os->finalizarServico(-1.00);
    }

    public function test_finalizar_servico_em_status_errado_lanca_excecao(): void
    {
        $os = $this->makeOS(StatusOrdem::RECEBIDA);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Somente ordens EM_EXECUCAO podem ser finalizadas.');

        $os->finalizarServico(100.00);
    }

    // ── entregarVeiculo ───────────────────────────────────────────

    public function test_entregar_veiculo_em_os_finalizada(): void
    {
        $os = $this->makeOS(StatusOrdem::FINALIZADA);
        $os->entregarVeiculo();

        $this->assertTrue($os->getStatus()->equals(StatusOrdem::ENTREGUE));
    }

    public function test_entregar_veiculo_em_status_errado_lanca_excecao(): void
    {
        $os = $this->makeOS(StatusOrdem::RECEBIDA);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Somente ordens FINALIZADAS podem ser entregues.');

        $os->entregarVeiculo();
    }

    // ── atualizar ─────────────────────────────────────────────────

    public function test_atualizar_os_nao_finalizada(): void
    {
        $os = $this->makeOS(StatusOrdem::RECEBIDA);
        $os->atualizar(['descricao_problema' => 'Motor falhando']);

        $this->assertEquals('Motor falhando', $os->getDescricao());
    }

    public function test_atualizar_os_finalizada_lanca_excecao(): void
    {
        $os = $this->makeOS(StatusOrdem::FINALIZADA);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Ordens FINALIZADAS não podem ser alteradas.');

        $os->atualizar(['descricao_problema' => 'Teste']);
    }

    public function test_atualizar_os_entregue_lanca_excecao(): void
    {
        $os = $this->makeOS(StatusOrdem::ENTREGUE);

        $this->expectException(\DomainException::class);

        $os->atualizar(['descricao_problema' => 'Teste']);
    }

    // ── getters ───────────────────────────────────────────────────

    public function test_getters_retornam_valores_corretos(): void
    {
        $os = $this->makeOS();

        $this->assertEquals(1, $os->getId());
        $this->assertEquals(10, $os->getClienteId());
        $this->assertEquals(20, $os->getVeiculoId());
        $this->assertNull($os->getMecanicoId());
        $this->assertEquals('Carro não liga', $os->getDescricao());
        $this->assertNull($os->getDiagnostico());
        $this->assertNull($os->getValorTotal());
        $this->assertNull($os->getIniciadaEm());
        $this->assertNotNull($os->getCriadaEm());
    }

    public function test_to_array_retorna_estrutura_correta(): void
    {
        $os = $this->makeOS();
        $array = $os->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('cliente_id', $array);
        $this->assertArrayHasKey('veiculo_id', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('descricao_problema', $array);
        $this->assertEquals('RECEBIDA', $array['status']);
    }
}
