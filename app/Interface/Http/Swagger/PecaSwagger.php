<?php

namespace App\Interface\Http\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Pecas', description: 'Gerenciamento de pecas da oficina')]
class PecaSwagger
{
    #[OA\Get(
        path: '/api/pecas',
        tags: ['Pecas'],
        summary: 'Lista todas as pecas',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de pecas retornada com sucesso',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'nome', type: 'string', example: 'Filtro de Óleo'),
                            new OA\Property(property: 'codigo', type: 'string', example: 'FO-001'),
                            new OA\Property(property: 'categoria', type: 'string', nullable: true, example: 'Filtros'),
                            new OA\Property(property: 'preco_unitario', type: 'number', format: 'float', example: 35.90),
                            new OA\Property(property: 'estoque_atual', type: 'integer', example: 20),
                            new OA\Property(property: 'estoque_minimo', type: 'integer', example: 5),
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: 'Nao autenticado'),
        ]
    )]
    public function indexDoc(): void
    {
    }

    #[OA\Post(
        path: '/api/pecas',
        tags: ['Pecas'],
        summary: 'Cadastra uma nova peca',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nome', 'codigo', 'preco_unitario', 'estoque_atual', 'estoque_minimo'],
                properties: [
                    new OA\Property(property: 'nome', type: 'string', maxLength: 255, example: 'Filtro de Óleo'),
                    new OA\Property(property: 'codigo', type: 'string', maxLength: 255, example: 'FO-001'),
                    new OA\Property(property: 'categoria', type: 'string', maxLength: 255, nullable: true, example: 'Filtros'),
                    new OA\Property(property: 'preco_unitario', type: 'number', format: 'float', minimum: 0.01, example: 35.90),
                    new OA\Property(property: 'estoque_atual', type: 'integer', minimum: 0, example: 20),
                    new OA\Property(property: 'estoque_minimo', type: 'integer', minimum: 0, example: 5),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Peca criada com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'nome', type: 'string', example: 'Filtro de Óleo'),
                        new OA\Property(property: 'codigo', type: 'string', example: 'FO-001'),
                        new OA\Property(property: 'categoria', type: 'string', nullable: true, example: 'Filtros'),
                        new OA\Property(property: 'preco_unitario', type: 'number', format: 'float', example: 35.90),
                        new OA\Property(property: 'estoque_atual', type: 'integer', example: 20),
                        new OA\Property(property: 'estoque_minimo', type: 'integer', example: 5),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Nao autenticado'),
            new OA\Response(response: 422, description: 'Erro de validacao'),
        ]
    )]
    public function storeDoc(): void
    {
    }

    #[OA\Get(
        path: '/api/pecas/{id}',
        tags: ['Pecas'],
        summary: 'Busca uma peca pelo ID',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Peca encontrada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'nome', type: 'string', example: 'Filtro de Óleo'),
                        new OA\Property(property: 'codigo', type: 'string', example: 'FO-001'),
                        new OA\Property(property: 'categoria', type: 'string', nullable: true, example: 'Filtros'),
                        new OA\Property(property: 'preco_unitario', type: 'number', format: 'float', example: 35.90),
                        new OA\Property(property: 'estoque_atual', type: 'integer', example: 20),
                        new OA\Property(property: 'estoque_minimo', type: 'integer', example: 5),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Nao autenticado'),
            new OA\Response(response: 404, description: 'Peca nao encontrada'),
        ]
    )]
    public function showDoc(): void
    {
    }

    #[OA\Put(
        path: '/api/pecas/{id}',
        tags: ['Pecas'],
        summary: 'Atualiza uma peca existente',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nome', 'codigo', 'preco_unitario', 'estoque_atual', 'estoque_minimo'],
                properties: [
                    new OA\Property(property: 'nome', type: 'string', maxLength: 255, example: 'Filtro de Óleo Premium'),
                    new OA\Property(property: 'codigo', type: 'string', maxLength: 255, example: 'FO-001'),
                    new OA\Property(property: 'categoria', type: 'string', maxLength: 255, nullable: true, example: 'Filtros'),
                    new OA\Property(property: 'preco_unitario', type: 'number', format: 'float', minimum: 0.01, example: 39.90),
                    new OA\Property(property: 'estoque_atual', type: 'integer', minimum: 0, example: 18),
                    new OA\Property(property: 'estoque_minimo', type: 'integer', minimum: 0, example: 5),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Peca atualizada com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'nome', type: 'string', example: 'Filtro de Óleo Premium'),
                        new OA\Property(property: 'codigo', type: 'string', example: 'FO-001'),
                        new OA\Property(property: 'categoria', type: 'string', nullable: true, example: 'Filtros'),
                        new OA\Property(property: 'preco_unitario', type: 'number', format: 'float', example: 39.90),
                        new OA\Property(property: 'estoque_atual', type: 'integer', example: 18),
                        new OA\Property(property: 'estoque_minimo', type: 'integer', example: 5),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Nao autenticado'),
            new OA\Response(response: 404, description: 'Peca nao encontrada'),
            new OA\Response(response: 422, description: 'Erro de validacao'),
        ]
    )]
    public function updateDoc(): void
    {
    }

    #[OA\Delete(
        path: '/api/pecas/{id}',
        tags: ['Pecas'],
        summary: 'Remove uma peca',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Peca removida com sucesso'),
            new OA\Response(response: 401, description: 'Nao autenticado'),
            new OA\Response(response: 404, description: 'Peca nao encontrada'),
        ]
    )]
    public function destroyDoc(): void
    {
    }
}
