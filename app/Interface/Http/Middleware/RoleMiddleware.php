<?php

namespace App\Interface\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response|JsonResponse
    {
        $user = $request->user('api') ?? auth('api')->user();

        if (!$user) {
            return response()->json(['message' => 'Usuario nao autenticado.'], 401);
        }

        $normalized = array_map(
            static fn(string $role): string => strtolower(trim($role)),
            $roles
        );

        $hasRole = in_array(strtolower((string) $user->role), $normalized, true);

        if (!$hasRole) {
            return response()->json(['message' => 'Acesso nao autorizado para o perfil informado.'], 403);
        }

        return $next($request);
    }
}
