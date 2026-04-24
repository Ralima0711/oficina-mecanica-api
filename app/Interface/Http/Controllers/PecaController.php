<?php

namespace App\Interface\Http\Controllers;

use App\Application\Services\PecaService;
use App\Interface\Http\Requests\PecaRequest;
use Illuminate\Http\JsonResponse;

class PecaController extends Controller
{
    public function __construct(
        private PecaService $service
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->listarTodas());
    }

    public function store(PecaRequest $request): JsonResponse
    {
        $peca = $this->service->criar($request->validated());
        return response()->json($peca, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->service->buscarPorId($id));
    }

    public function update(PecaRequest $request, int $id): JsonResponse
    {
        return response()->json($this->service->atualizar($id, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->remover($id);
        return response()->json(null, 204);
    }
}
