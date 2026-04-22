<?php

namespace App\Application\Services;

use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * CAMADA DE APLICAÇÃO — Application Service
 * Orquestra os casos de uso de autenticação.
 */
class AuthService
{
    public function login(string $email, string $password): array
    {
        // JWTAuth::attempt() valida e gera o token em um passo
        $token = JWTAuth::attempt(['email' => $email, 'password' => $password]);

        if (!$token) {
            throw new \RuntimeException('Credenciais inválidas.');
        }

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => (int) config('jwt.ttl', 60) * 60,
        ];
    }

    public function me(): object
    {
        return auth('api')->user();
    }

    public function logout(): void
    {
        auth('api')->logout();
    }

    public function refresh(): array
    {
        $token = JWTAuth::refresh(JWTAuth::getToken());

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => (int) config('jwt.ttl', 60) * 60,
        ];
    }
}


