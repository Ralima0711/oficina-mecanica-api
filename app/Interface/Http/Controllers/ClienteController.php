<?php

namespace App\Interface\Http\Controllers;

use App\Application\Services\ClienteService;
use App\Interface\Http\Requests\ClienteRequest;
use Illuminate\Http\JsonResponse;

/**
 * CAMADA DE INTERFACE
 */
class ClienteController extends Controller
{
    public function __construct(
        private ClienteService $service
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->listarTodos());
    }

    public function store(ClienteRequest $request): JsonResponse
    {
        $cliente = $this->service->criar($request->validated());
        return response()->json($cliente, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->service->buscarPorId($id));
    }

    public function update(ClienteRequest $request, int $id): JsonResponse
    {
        return response()->json($this->service->atualizar($id, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->remover($id);
        return response()->json(null, 204);
    }
}
