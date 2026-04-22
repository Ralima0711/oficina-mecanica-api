<?php

namespace App\Interface\Http\Controllers;

use App\Application\Services\AuthService;
use App\Interface\Http\Requests\AuthRequest;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * CAMADA DE INTERFACE
 * Autenticação JWT — apenas orquestra requisições/respostas
 * Toda a lógica de negócio está em AuthService
 *
 * @OA\Info(
 *     title="Oficina Mecanica API",
 *     version="1.0.0",
 *     description="Documentacao da API da Oficina Mecanica"
 * )
 *
 * @OA\Tag(
 *     name="Auth",
 *     description="Endpoints de autenticacao JWT"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class AuthController extends Controller
{
    public function __construct(
        private AuthService $service,
    ) {}

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"Auth"},
     *     summary="Realiza login e retorna o JWT",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@oficina.local"),
     *             @OA\Property(property="password", type="string", format="password", example="admin123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Credenciais invalidas"),
     *     @OA\Response(response=422, description="Erro de validacao")
     * )
     */
    public function login(AuthRequest $request): JsonResponse
    {
        try {
            $result = $this->service->login(
                $request->validated()['email'],
                $request->validated()['password']
            );

            return response()->json($result, 200);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auth/me",
     *     tags={"Auth"},
     *     summary="Retorna o usuario autenticado",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Usuario autenticado"),
     *     @OA\Response(response=401, description="Nao autenticado")
     * )
     */
    public function me(): JsonResponse
    {
        return response()->json($this->service->me());
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"Auth"},
     *     summary="Invalida o token JWT atual",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Logout realizado com sucesso"),
     *     @OA\Response(response=401, description="Nao autenticado")
     * )
     */
    public function logout(): JsonResponse
    {
        $this->service->logout();
        return response()->json(['message' => 'Logout realizado com sucesso']);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/refresh",
     *     tags={"Auth"},
     *     summary="Gera um novo token JWT a partir do token atual",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token renovado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Nao autenticado")
     * )
     */
    public function refresh(): JsonResponse
    {
        return response()->json($this->service->refresh());
    }
}
