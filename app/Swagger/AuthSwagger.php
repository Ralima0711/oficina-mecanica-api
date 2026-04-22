<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Oficina Mecanica API',
    description: 'Documentacao da API da Oficina Mecanica'
)]
#[OA\Tag(name: 'Auth', description: 'Endpoints de autenticacao JWT')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
class AuthSwagger
{
    #[OA\Post(
        path: '/api/auth/login',
        tags: ['Auth'],
        summary: 'Realiza login e retorna o JWT',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@oficina.local'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'admin123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login realizado com sucesso',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'access_token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'),
                    new OA\Property(property: 'token_type', type: 'string', example: 'bearer'),
                    new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                ])
            ),
            new OA\Response(response: 401, description: 'Credenciais invalidas'),
            new OA\Response(response: 422, description: 'Erro de validacao'),
        ]
    )]
    public function loginDoc(): void
    {
    }

    #[OA\Get(
        path: '/api/auth/me',
        tags: ['Auth'],
        summary: 'Retorna o usuario autenticado',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Usuario autenticado'),
            new OA\Response(response: 401, description: 'Nao autenticado'),
        ]
    )]
    public function meDoc(): void
    {
    }

    #[OA\Post(
        path: '/api/auth/logout',
        tags: ['Auth'],
        summary: 'Invalida o token JWT atual',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Logout realizado com sucesso'),
            new OA\Response(response: 401, description: 'Nao autenticado'),
        ]
    )]
    public function logoutDoc(): void
    {
    }

    #[OA\Post(
        path: '/api/auth/refresh',
        tags: ['Auth'],
        summary: 'Gera um novo token JWT a partir do token atual',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token renovado com sucesso',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'access_token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'),
                    new OA\Property(property: 'token_type', type: 'string', example: 'bearer'),
                    new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                ])
            ),
            new OA\Response(response: 401, description: 'Nao autenticado'),
        ]
    )]
    public function refreshDoc(): void
    {
    }
}
