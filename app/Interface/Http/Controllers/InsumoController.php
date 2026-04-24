<?php

namespace App\Interface\Http\Controllers;

use App\Application\Services\InsumoService;
use App\Interface\Http\Requests\InsumoRequest;
use Illuminate\Http\JsonResponse;

class InsumoController extends Controller
{
    public function __construct(
        private InsumoService $service
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->listarTodas());
    }

    public function store(InsumoRequest $request): JsonResponse
    {
        $insumo = $this->service->criar($request->validated());
        return response()->json($insumo, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->service->buscarPorId($id));
    }

    public function update(InsumoRequest $request, int $id): JsonResponse
    {
        return response()->json($this->service->atualizar($id, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->remover($id);
        return response()->json(null, 204);
    }
}
