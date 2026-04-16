<?php

namespace App\Interface\Http\Controllers;

use App\Application\Services\OrdemServicoService;
use App\Interface\Http\Requests\OrdemServicoRequest;
use App\Interface\Http\Requests\SubmeterOrcamentoRequest;
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

    public function iniciarDiagnostico(Request $request, int $id): JsonResponse
    {
        try {
            $usuario = $request->user('api') ?? auth('api')->user();

            return response()->json(
                $this->service->iniciarDiagnostico($id, (int) $usuario->id)
            );
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function submeterOrcamento(SubmeterOrcamentoRequest $request, int $id): JsonResponse
    {
        try {
            $usuario = $request->user('api') ?? auth('api')->user();

            return response()->json(
                $this->service->submeterOrcamento($id, (int) $usuario->id, $request->validated())
            );
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function aprovarPublico(int $id, string $token): JsonResponse
    {
        try {
            return response()->json($this->service->aprovarPublico($id, $token));
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function reprovarPublico(int $id, string $token): JsonResponse
    {
        try {
            return response()->json($this->service->reprovarPublico($id, $token));
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function iniciarExecucao(int $id): JsonResponse
    {
        try {
            return response()->json($this->service->iniciarExecucao($id));
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function finalizar(Request $request, int $id): JsonResponse
    {
        try {
            $dados = $request->validate([
                'valor_total' => ['required', 'numeric', 'min:0'],
            ]);

            return response()->json(
                $this->service->finalizar($id, (float) $dados['valor_total'])
            );
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function entregar(int $id): JsonResponse
    {
        try {
            return response()->json($this->service->entregar($id));
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
