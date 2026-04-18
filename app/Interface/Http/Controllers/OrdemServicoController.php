<?php

namespace App\Interface\Http\Controllers;

use App\Application\Services\OrdemServicoService;
use App\Interface\Http\Requests\OrdemServicoRequest;
use App\Interface\Http\Requests\SubmeterOrcamentoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            $mecanicoId = $this->getMecanicoUserId((int) $usuario->id);

            return response()->json(
                $this->service->iniciarDiagnostico($id, $mecanicoId)
            );
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function submeterOrcamento(SubmeterOrcamentoRequest $request, int $id): JsonResponse
    {
        try {
            $usuario = $request->user('api') ?? auth('api')->user();
            $mecanicoId = $this->getMecanicoUserId((int) $usuario->id);

            return response()->json(
                $this->service->submeterOrcamento($id, $mecanicoId, $request->validated())
            );
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function aprovarPublico(int $id, string $token): JsonResponse
    {
        try {
            $os = $this->service->aprovarPublico($id, $token);

            return response()->json([
                'message' => 'Status atual: ' . $os->getStatus(),
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function reprovarPublico(int $id, string $token): JsonResponse
    {
        try {
            $os = $this->service->reprovarPublico($id, $token);

            return response()->json([
                'message' => 'Status atual: ' . $os->getStatus(),
            ]);
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

    public function finalizar(int $id): JsonResponse
    {
        try {
            return response()->json(
                $this->service->finalizar($id)
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

    private function getMecanicoUserId(int $usuarioId): int
    {
        $mecanicoId = DB::table('mecanicos')
            ->where('user_id', $usuarioId)
            ->value('id');

        if ($mecanicoId === null) {
            throw new \DomainException('Usuario autenticado nao possui cadastro de mecanico.');
        }

        return (int) $mecanicoId;
    }
}
