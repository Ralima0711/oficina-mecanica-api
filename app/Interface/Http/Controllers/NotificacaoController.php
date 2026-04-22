<?php

namespace App\Interface\Http\Controllers;

use App\Application\Services\SistemaNotificacaoService;
use App\Interface\Http\Requests\NotificacaoIndexRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacaoController extends Controller
{
    public function __construct(
        private SistemaNotificacaoService $service,
    ) {}

    public function index(NotificacaoIndexRequest $request): JsonResponse
    {
        try {
            $usuario = $request->user('api') ?? auth('api')->user();

            if (!$usuario) {
                return response()->json(['message' => 'Usuario nao autenticado.'], 401);
            }

            $perPage = (int) ($request->validated()['per_page'] ?? 20);

            return response()->json($this->service->listarMinhas((int) $usuario->id, $perPage));
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function marcarComoLida(Request $request, int $id): JsonResponse
    {
        try {
            $usuario = $request->user('api') ?? auth('api')->user();

            if (!$usuario) {
                return response()->json(['message' => 'Usuario nao autenticado.'], 401);
            }

            $notificacao = $this->service->marcarComoLida($id, (int) $usuario->id);

            return response()->json($notificacao);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    private function handleException(\Throwable $e): JsonResponse
    {
        if ($e instanceof \RuntimeException) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        if ($e instanceof \DomainException || $e instanceof \InvalidArgumentException) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Erro interno ao processar notificacoes.'], 500);
    }
}
