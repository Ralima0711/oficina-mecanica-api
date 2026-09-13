<?php

namespace App\Interface\Http\Middleware;

use App\Infrastructure\Observability\OpenTelemetryProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use Symfony\Component\HttpFoundation\Response;

/**
 * CAMADA DE INTERFACE — Middleware
 *
 * Correlação de requisições e tracing:
 *  - garante um X-Request-Id por requisição (reaproveita o recebido ou gera um novo);
 *  - abre um span de servidor por request (OpenTelemetry), quando o SDK está ativo;
 *  - injeta request.id, trace.id e span.id no contexto de todos os logs da requisição,
 *    permitindo o "logs in context" (clique do log direto para o trace no New Relic);
 *  - devolve o X-Request-Id no header da resposta.
 */
class CorrelacaoRequisicaoMiddleware
{
    public const HEADER = 'X-Request-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->resolverRequestId($request);
        $request->attributes->set('request_id', $requestId);

        $span = $this->iniciarSpan($request);

        $contexto = [
            'request.id'  => $requestId,
            'http.method' => $request->method(),
            'http.route'  => $request->path(),
        ];

        $spanContext = $span?->getContext();
        if ($spanContext !== null && $spanContext->isValid()) {
            $contexto['trace.id'] = $spanContext->getTraceId();
            $contexto['span.id']  = $spanContext->getSpanId();
        }

        Log::withContext($contexto);

        $inicio = microtime(true);

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $span?->recordException($e);
            $span?->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            $span?->end();

            throw $e;
        }

        $duracaoMs = round((microtime(true) - $inicio) * 1000, 2);

        $span?->setAttribute('http.status_code', $response->getStatusCode());
        $span?->setAttribute('http.duration_ms', $duracaoMs);

        if ($response->getStatusCode() >= 500) {
            $span?->setStatus(StatusCode::STATUS_ERROR);
        }

        $span?->end();

        $response->headers->set(self::HEADER, $requestId);

        // Log estruturado de acesso — base para latência, alertas e dashboards.
        Log::info('requisicao_processada', [
            'http.status_code' => $response->getStatusCode(),
            'http.duration_ms' => $duracaoMs,
        ]);

        return $response;
    }

    private function resolverRequestId(Request $request): string
    {
        $recebido = $request->header(self::HEADER);

        if (is_string($recebido) && trim($recebido) !== '') {
            return trim($recebido);
        }

        return (string) Str::uuid();
    }

    /**
     * Envia a fila de spans depois que a resposta já foi entregue ao cliente.
     * Sem isto o BatchSpanProcessor pode morrer com o processo sem exportar nada.
     */
    public function terminate(Request $request, Response $response): void
    {
        OpenTelemetryProvider::flush();
    }

    /**
     * Abre o span de servidor do request. Se o SDK do OpenTelemetry não estiver
     * instalado/ativo, devolve null e a requisição segue sem tracing.
     */
    private function iniciarSpan(Request $request): ?\OpenTelemetry\API\Trace\SpanInterface
    {
        if (!class_exists(Span::class)) {
            return null;
        }

        // Usa o padrão da rota (ordens-servico/{id}) em vez do caminho concreto,
        // para não criar uma operação diferente por id no APM.
        $rota = $request->route()?->uri() ?? $request->path();

        $builder = OpenTelemetryProvider::tracer()
            ->spanBuilder(sprintf('%s /%s', $request->method(), ltrim($rota, '/')))
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->setAttribute('http.method', $request->method())
            ->setAttribute('http.route', $rota)
            ->setAttribute('http.target', $request->getRequestUri());

        $parent = Span::fromContext(Context::getCurrent())->getContext();
        if ($parent->isValid()) {
            $builder->setParent($parent);
        }

        return $builder->startSpan();
    }
}
