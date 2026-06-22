<?php

namespace App\Interface\Http\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Usuarios', description: 'Gerenciamento de usuários do sistema')]
class UserSwagger
{
    #[OA\Get(
        path: '/api/usuarios',
        tags: ['Usuarios'],
        summary: 'Lista todos os usuários',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de usuários retornada com sucesso',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Admin'),
                        new OA\Property(property: 'email', type: 'string', example: 'admin@oficina.local'),
                        new OA\Property(property: 'role', type: 'string', enum: ['admin', 'atendente', 'mecanico'], example: 'admin'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                    ])
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function indexDoc(): void {}

    #[OA\Post(
        path: '/api/usuarios',
        tags: ['Usuarios'],
        summary: 'Cria um novo usuário',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'role'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'João Mecânico'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'joao@oficina.local'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'senha123'),
                    new OA\Property(property: 'role', type: 'string', enum: ['admin', 'atendente', 'mecanico'], example: 'mecanico'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Usuário criado com sucesso'),
            new OA\Response(response: 422, description: 'Erro de validação'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function storeDoc(): void {}

    #[OA\Get(
        path: '/api/usuarios/{id}',
        tags: ['Usuarios'],
        summary: 'Busca um usuário pelo ID',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID do usuário', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Usuário encontrado'),
            new OA\Response(response: 404, description: 'Usuário não encontrado'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function showDoc(): void {}

    #[OA\Put(
        path: '/api/usuarios/{id}',
        tags: ['Usuarios'],
        summary: 'Atualiza um usuário',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID do usuário', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'João Mecânico'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'joao@oficina.local'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'novasenha123'),
                    new OA\Property(property: 'role', type: 'string', enum: ['admin', 'atendente', 'mecanico'], example: 'mecanico'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Usuário atualizado com sucesso'),
            new OA\Response(response: 404, description: 'Usuário não encontrado'),
            new OA\Response(response: 422, description: 'Erro de validação'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function updateDoc(): void {}

    #[OA\Delete(
        path: '/api/usuarios/{id}',
        tags: ['Usuarios'],
        summary: 'Remove um usuário',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID do usuário', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Usuário removido com sucesso'),
            new OA\Response(response: 404, description: 'Usuário não encontrado'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function destroyDoc(): void {}
}
