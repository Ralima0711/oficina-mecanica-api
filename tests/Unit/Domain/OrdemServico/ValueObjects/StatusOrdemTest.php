<?php

namespace Tests\Unit\Domain\OrdemServico\ValueObjects;

use App\Domain\OrdemServico\ValueObjects\StatusOrdem;
use PHPUnit\Framework\TestCase;

/**
 * Testes do Value Object StatusOrdem
 * Valida criação, comparação e todos os status possíveis
 */
class StatusOrdemTest extends TestCase
{
    public function test_transicao_valida_recebida_para_diagnostico(): void
    {
        $statusRecebida = StatusOrdem::from(StatusOrdem::RECEBIDA);
        $statusDiagnostico = StatusOrdem::from(StatusOrdem::EM_DIAGNOSTICO);

        $this->assertFalse($statusRecebida->equals(StatusOrdem::EM_DIAGNOSTICO));
        $this->assertTrue($statusDiagnostico->equals(StatusOrdem::EM_DIAGNOSTICO));
    }

    public function test_transicao_invalida_lanca_excecao(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Status inválido: INVALIDO');

        StatusOrdem::from('INVALIDO');
    }

    public function test_todos_os_status_existem(): void
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

        foreach ($statuses as $status) {
            $this->assertEquals($status, (string) StatusOrdem::from($status));
        }
    }

    public function test_status_from_com_string_valida(): void
    {
        $status = StatusOrdem::from('RECEBIDA');
        $this->assertEquals('RECEBIDA', (string) $status);
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

    public function test_status_to_string(): void
    {
        $status = StatusOrdem::from(StatusOrdem::EM_DIAGNOSTICO);
        $this->assertEquals('EM_DIAGNOSTICO', (string) $status);
    }

    public function test_status_staticCall_magic_method(): void
    {
        $status = StatusOrdem::RECEBIDA();
        $this->assertTrue($status->equals(StatusOrdem::RECEBIDA));
    }
}
