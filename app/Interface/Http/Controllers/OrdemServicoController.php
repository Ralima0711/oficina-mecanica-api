<?php

namespace App\Interface\Http\Controllers;

use App\Application\Services\OrdemServicoService;
use App\Interface\Http\Requests\OrdemServicoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CAMADA DE INTERFACE
 * Responsável por receber as requisições HTTP e devolver respostas.
 * NÃO contém regra de negócio — delega tudo ao Application Service.
 */
class OrdemServicoController extends Controller
{
    public function __construct(
        private OrdemServicoService $service
    ) {}

    public function index(): JsonResponse
    {
        try {
            return response()->json($this->service->listarTodas());
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function store(OrdemServicoRequest $request): JsonResponse
    {
        try {
            $os = $this->service->criar($request->validated());
            return response()->json($os, 201);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            return response()->json($this->service->buscarPorId($id));
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update(OrdemServicoRequest $request, int $id): JsonResponse
    {
        try {
            return response()->json($this->service->atualizar($id, $request->validated()));
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->remover($id);
            return response()->json(null, 204);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function iniciar(int $id): JsonResponse
    {
        try {
            return response()->json($this->service->iniciar($id));
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function concluir(Request $request, int $id): JsonResponse
    {
        try {
            $dados = $request->validate([
                'valor_total' => ['required', 'numeric', 'min:0'],
            ]);

            return response()->json(
                $this->service->concluir($id, (float) $dados['valor_total'])
            );
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function cancelar(int $id): JsonResponse
    {
        try {
            return response()->json($this->service->cancelar($id));
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

        return response()->json(['message' => 'Erro interno ao processar Ordem de Servico.'], 500);
    }
}
