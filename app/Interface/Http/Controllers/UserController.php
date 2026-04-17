<?php

namespace App\Interface\Http\Controllers;

use App\Application\Services\UserService;
use App\Interface\Http\Requests\UserRequest;
use Illuminate\Http\JsonResponse;

/**
 * CAMADA DE INTERFACE — Controller
 */
class UserController extends Controller
{
    public function __construct(
        private UserService $service
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->listarTodos());
    }

    public function store(UserRequest $request): JsonResponse
    {
        $user = $this->service->criar($request->validated());
        return response()->json($user, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->service->buscarPorId($id));
    }

    public function update(UserRequest $request, int $id): JsonResponse
    {
        return response()->json($this->service->atualizar($id, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->remover($id);
        return response()->json(null, 204);
    }
}
