<?php

namespace App\Infrastructure\Observability;

use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ScopeInterface;

/**
 * CAMADA DE INFRAESTRUTURA — Observabilidade
 *
 * Helper para instrumentar operações de negócio com spans filhos do OpenTelemetry.
 * Uso:
 *
 *   $span = Tracing::iniciar('OrdemServicoService::criar', ['os.cliente_id' => 1]);
 *   try {
 *       // ... operação
 *       Tracing::sucesso($span);
 *   } catch (\Throwable $e) {
 *       Tracing::erro($span, $e);
 *       throw $e;
 *   } finally {
 *       Tracing::finalizar($span);
 *   }
 *
 * Se o SDK não estiver disponível, tudo vira no-op.
 */
class Tracing
{
    public static function iniciar(string $nome, array $atributos = []): ?SpanInterface
    {
        if (!class_exists(Span::class)) {
            return null;
        }

        $builder = OpenTelemetryProvider::tracer()
            ->spanBuilder($nome)
            ->setSpanKind(SpanKind::KIND_INTERNAL);

        foreach ($atributos as $chave => $valor) {
            $builder->setAttribute($chave, $valor);
        }

        $parent = Span::fromContext(Context::getCurrent())->getContext();
        if ($parent->isValid()) {
            $builder->setParent($parent);
        }

        $span = $builder->startSpan();

        // Torna o span corrente para que spans internos virem filhos dele.
        self::$escopos[] = $span->activate();

        return $span;
    }

    public static function sucesso(?SpanInterface $span): void
    {
        $span?->setStatus(StatusCode::STATUS_OK);
    }

    public static function erro(?SpanInterface $span, \Throwable $e): void
    {
        if ($span === null) {
            return;
        }

        $span->recordException($e);
        $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
    }

    public static function finalizar(?SpanInterface $span): void
    {
        if ($span === null) {
            return;
        }

        $escopo = array_pop(self::$escopos);
        if ($escopo instanceof ScopeInterface) {
            $escopo->detach();
        }

        $span->end();
    }

    /** @var ScopeInterface[] */
    private static array $escopos = [];
}
