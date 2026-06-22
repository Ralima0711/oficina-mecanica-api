<?php

namespace App\Interface\Http\Controllers;

use App\Application\Services\ServicoService;
use App\Interface\Http\Requests\ServicoRequest;
use Illuminate\Http\JsonResponse;

/**
 * CAMADA DE INTERFACE
 * Gerencia o CRUD de serviços oferecidos pela oficina.
 */
class ServicoController extends Controller
{
    public function __construct(
        private ServicoService $service
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->listarTodos());
    }

    public function store(ServicoRequest $request): JsonResponse
    {
        try {
            return response()->json($this->service->criar($request->validated()), 201);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            return response()->json($this->service->buscarPorId($id));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function update(ServicoRequest $request, int $id): JsonResponse
    {
        try {
            return response()->json($this->service->atualizar($id, $request->validated()));
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->remover($id);
            return response()->json(null, 204);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
