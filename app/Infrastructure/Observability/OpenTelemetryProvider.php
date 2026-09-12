<?php

namespace App\Infrastructure\Observability;

use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SemConv\ResourceAttributes;

/**
 * CAMADA DE INFRAESTRUTURA — Observabilidade
 *
 * Configura o TracerProvider do OpenTelemetry exportando via OTLP/HTTP.
 * Endpoint, headers (api-key do New Relic) e nome do serviço vêm de variáveis
 * de ambiente — nenhum segredo no código:
 *
 *   OTEL_EXPORTER_OTLP_ENDPOINT  (ex.: https://otlp.nr-data.net:4318)
 *   OTEL_EXPORTER_OTLP_HEADERS   (ex.: api-key=<LICENSE_KEY>)
 *   OTEL_SERVICE_NAME            (ex.: oficina-mecanica-api)
 *
 * Se o endpoint não estiver configurado, devolve um provider no-op: a aplicação
 * segue funcionando normalmente, apenas sem exportar traces.
 */
class OpenTelemetryProvider
{
    private static ?TracerProviderInterface $provider = null;

    public static function tracer(): TracerInterface
    {
        return self::provider()->getTracer(
            (string) (getenv('OTEL_SERVICE_NAME') ?: 'oficina-mecanica-api')
        );
    }

    public static function provider(): TracerProviderInterface
    {
        if (self::$provider !== null) {
            return self::$provider;
        }

        $endpoint = (string) (getenv('OTEL_EXPORTER_OTLP_ENDPOINT') ?: '');

        if ($endpoint === '') {
            // Sem endpoint configurado: provider no-op (não exporta, não quebra).
            self::$provider = new TracerProvider([], new AlwaysOnSampler());

            return self::$provider;
        }

        $headers = self::parseHeaders((string) (getenv('OTEL_EXPORTER_OTLP_HEADERS') ?: ''));

        $transport = (new OtlpHttpTransportFactory())->create(
            $endpoint . '/v1/traces',
            'application/x-protobuf',
            $headers
        );

        $resource = ResourceInfoFactory::emptyResource()->merge(
            ResourceInfo::create(Attributes::create([
                ResourceAttributes::SERVICE_NAME => (string) (getenv('OTEL_SERVICE_NAME') ?: 'oficina-mecanica-api'),
            ]))
        );

        self::$provider = new TracerProvider(
            [new BatchSpanProcessor(new SpanExporter($transport))],
            new AlwaysOnSampler(),
            $resource
        );

        return self::$provider;
    }

    /**
     * Converte "api-key=abc,outro=xyz" em ['api-key' => 'abc', 'outro' => 'xyz'].
     *
     * @return array<string, string>
     */
    private static function parseHeaders(string $raw): array
    {
        $headers = [];

        foreach (explode(',', $raw) as $par) {
            $par = trim($par);
            if ($par === '' || !str_contains($par, '=')) {
                continue;
            }

            [$chave, $valor] = explode('=', $par, 2);
            $headers[trim($chave)] = trim($valor);
        }

        return $headers;
    }
}
