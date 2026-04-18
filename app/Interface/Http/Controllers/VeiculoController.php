<?php

namespace App\Interface\Http\Controllers;

use App\Application\Services\VeiculoService;
use App\Interface\Http\Requests\VeiculoRequest;
use Illuminate\Http\JsonResponse;

class VeiculoController extends Controller
{
    public function __construct(
        private VeiculoService $service
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->listarTodos());
    }

    public function store(VeiculoRequest $request): JsonResponse
    {
        $veiculo = $this->service->criar($request->validated());
        return response()->json($veiculo, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->service->buscarPorId($id));
    }

    public function update(VeiculoRequest $request, int $id): JsonResponse
    {
        return response()->json($this->service->atualizar($id, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->remover($id);
        return response()->json(null, 204);
    }
}
