<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Correlação de requisições e logs estruturados (observabilidade — Fase 3).
 */
class CorrelacaoRequisicaoTest extends TestCase
{
    public function test_resposta_devolve_header_x_request_id_gerado(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertHeader('X-Request-Id');

        $requestId = $response->headers->get('X-Request-Id');
        $this->assertNotEmpty($requestId);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $requestId
        );
    }

    public function test_reaproveita_x_request_id_recebido(): void
    {
        $enviado = 'meu-request-id-123';

        $response = $this->withHeader('X-Request-Id', $enviado)
            ->getJson('/api/health');

        $response->assertStatus(200);
        $this->assertSame($enviado, $response->headers->get('X-Request-Id'));
    }

    public function test_log_de_acesso_e_emitido_com_contexto_de_correlacao(): void
    {
        $capturado = [];

        Log::listen(function ($evento) use (&$capturado) {
            $capturado[] = $evento;
        });

        $this->withHeader('X-Request-Id', 'correlacao-teste-456')
            ->getJson('/api/health')
            ->assertStatus(200);

        $acesso = null;
        foreach ($capturado as $evento) {
            if ($evento->message === 'requisicao_processada') {
                $acesso = $evento;
                break;
            }
        }

        $this->assertNotNull($acesso, 'Log de acesso "requisicao_processada" não foi emitido.');
        $this->assertSame('correlacao-teste-456', $acesso->context['request.id'] ?? null);
        $this->assertArrayHasKey('http.status_code', $acesso->context);
        $this->assertArrayHasKey('http.duration_ms', $acesso->context);
    }

    public function test_canal_stdout_json_esta_configurado(): void
    {
        $canal = config('logging.channels.stdout_json');

        $this->assertNotNull($canal, 'Canal stdout_json não configurado.');
        $this->assertSame('monolog', $canal['driver']);
        $this->assertSame('php://stdout', $canal['handler_with']['stream']);
        $this->assertSame(\Monolog\Formatter\JsonFormatter::class, $canal['formatter']);
    }
}
