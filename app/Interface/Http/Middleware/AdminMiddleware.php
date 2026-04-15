<?php

namespace App\Interface\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $user = $request->user('api') ?? auth('api')->user();

        if (!$user) {
            return response()->json(['message' => 'Usuario nao autenticado.'], 401);
        }

        if (strtolower((string) $user->role) !== 'admin') {
            return response()->json(['message' => 'Acesso restrito a administradores.'], 403);
        }

        return $next($request);
    }
}
