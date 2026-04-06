<?php

namespace App\Interface\Http\Controllers;

use App\Application\Services\OrdemServicoService;
use App\Interface\Http\Requests\OrdemServicoRequest;
use Illuminate\Http\JsonResponse;

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
        return response()->json($this->service->listarTodas());
    }

    public function store(OrdemServicoRequest $request): JsonResponse
    {
        $os = $this->service->criar($request->validated());
        return response()->json($os, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->service->buscarPorId($id));
    }

    public function update(OrdemServicoRequest $request, int $id): JsonResponse
    {
        return response()->json($this->service->atualizar($id, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->cancelar($id);
        return response()->json(null, 204);
    }
}
