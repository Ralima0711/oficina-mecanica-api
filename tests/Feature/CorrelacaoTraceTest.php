<?php

namespace Tests\Feature;

use App\Infrastructure\Observability\OpenTelemetryProvider;
use App\Infrastructure\Observability\Tracing;
use Illuminate\Support\Facades\Route;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Exportação e hierarquia dos spans (observabilidade — Fase 3).
 *
 * Estes testes cobrem o caminho de instrumentação que só é executado quando
 * OTEL_EXPORTER_OTLP_ENDPOINT está configurado: no CI e no ambiente local, com
 * o endpoint vazio, o provider vira no-op e esse trecho nunca roda — foi assim
 * que três defeitos de exportação passaram despercebidos.
 *
 * Usa o InMemoryExporter do próprio SDK no lugar do transporte OTLP, então roda
 * sem rede e sem coletor.
 */
class CorrelacaoTraceTest extends TestCase
{
    private InMemoryExporter $exportador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->exportador = new InMemoryExporter();
        $this->usarProviderDeTeste($this->exportador);

        Route::middleware('api')->get('/api/_teste/correlacao-trace', function () {
            $span = Tracing::iniciar('OrdemServicoService::criar', ['os.cliente_id' => 10]);
            Tracing::sucesso($span);
            Tracing::finalizar($span);

            return response()->json(['ok' => true]);
        });
    }

    protected function tearDown(): void
    {
        $this->substituirProvider(null);

        parent::tearDown();
    }

    public function test_span_de_negocio_e_filho_do_span_do_request(): void
    {
        $this->getJson('/api/_teste/correlacao-trace')->assertOk();

        $spans = $this->exportador->getSpans();

        $spanRequest = null;
        $spanNegocio = null;

        foreach ($spans as $span) {
            if ($span->getName() === 'GET /api/_teste/correlacao-trace') {
                $spanRequest = $span;
            } elseif ($span->getName() === 'OrdemServicoService::criar') {
                $spanNegocio = $span;
            }
        }

        $this->assertNotNull($spanRequest, 'O span do request não foi exportado.');
        $this->assertNotNull($spanNegocio, 'O span da operação de negócio não foi exportado.');

        $this->assertSame(
            $spanRequest->getTraceId(),
            $spanNegocio->getTraceId(),
            'O span de negócio caiu em outro trace: o span do request não está sendo ativado.'
        );

        $this->assertSame(
            $spanRequest->getSpanId(),
            $spanNegocio->getParentSpanId(),
            'O span de negócio não ficou filho do span do request.'
        );
    }

    public function test_provider_constroi_exportador_com_endpoint_configurado(): void
    {
        $this->substituirProvider(null);

        $_ENV['OTEL_EXPORTER_OTLP_ENDPOINT'] = 'http://127.0.0.1:4318';
        $_SERVER['OTEL_EXPORTER_OTLP_ENDPOINT'] = 'http://127.0.0.1:4318';
        putenv('OTEL_EXPORTER_OTLP_ENDPOINT=http://127.0.0.1:4318');

        try {
            $this->assertInstanceOf(TracerInterface::class, OpenTelemetryProvider::tracer());
        } finally {
            // Zera o provider antes do shutdown para o flush não tentar rede.
            $this->substituirProvider(null);

            unset($_ENV['OTEL_EXPORTER_OTLP_ENDPOINT'], $_SERVER['OTEL_EXPORTER_OTLP_ENDPOINT']);
            putenv('OTEL_EXPORTER_OTLP_ENDPOINT');
        }
    }

    private function usarProviderDeTeste(InMemoryExporter $exportador): void
    {
        $this->substituirProvider(new TracerProvider(
            [new SimpleSpanProcessor($exportador)],
            new AlwaysOnSampler()
        ));
    }

    private function substituirProvider(?object $provider): void
    {
        $propriedade = new ReflectionProperty(OpenTelemetryProvider::class, 'provider');
        $propriedade->setValue(null, $provider);
    }
}
