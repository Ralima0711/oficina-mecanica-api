<?php

namespace App\Interface\Http\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Insumos', description: 'Gerenciamento de insumos da oficina')]
class InsumoSwagger
{
    #[OA\Get(
        path: '/api/insumos',
        tags: ['Insumos'],
        summary: 'Lista todos os insumos',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de insumos retornada com sucesso',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'nome', type: 'string', example: 'Óleo de Motor'),
                            new OA\Property(property: 'unidade_medida', type: 'string', example: 'L'),
                            new OA\Property(property: 'preco_unitario', type: 'number', format: 'float', example: 25.90),
                            new OA\Property(property: 'estoque_atual', type: 'number', format: 'float', example: 50.0),
                            new OA\Property(property: 'estoque_minimo', type: 'number', format: 'float', example: 10.0),
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
        path: '/api/insumos',
        tags: ['Insumos'],
        summary: 'Cadastra um novo insumo',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nome', 'unidade_medida', 'preco_unitario', 'estoque_atual', 'estoque_minimo'],
                properties: [
                    new OA\Property(property: 'nome', type: 'string', maxLength: 255, example: 'Óleo de Motor'),
                    new OA\Property(property: 'unidade_medida', type: 'string', maxLength: 20, example: 'L'),
                    new OA\Property(property: 'preco_unitario', type: 'number', format: 'float', minimum: 0.01, example: 25.90),
                    new OA\Property(property: 'estoque_atual', type: 'number', format: 'float', minimum: 0, example: 50.0),
                    new OA\Property(property: 'estoque_minimo', type: 'number', format: 'float', minimum: 0, example: 10.0),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Insumo criado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'nome', type: 'string', example: 'Óleo de Motor'),
                        new OA\Property(property: 'unidade_medida', type: 'string', example: 'L'),
                        new OA\Property(property: 'preco_unitario', type: 'number', format: 'float', example: 25.90),
                        new OA\Property(property: 'estoque_atual', type: 'number', format: 'float', example: 50.0),
                        new OA\Property(property: 'estoque_minimo', type: 'number', format: 'float', example: 10.0),
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
        path: '/api/insumos/{id}',
        tags: ['Insumos'],
        summary: 'Busca um insumo pelo ID',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Insumo encontrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'nome', type: 'string', example: 'Óleo de Motor'),
                        new OA\Property(property: 'unidade_medida', type: 'string', example: 'L'),
                        new OA\Property(property: 'preco_unitario', type: 'number', format: 'float', example: 25.90),
                        new OA\Property(property: 'estoque_atual', type: 'number', format: 'float', example: 50.0),
                        new OA\Property(property: 'estoque_minimo', type: 'number', format: 'float', example: 10.0),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Nao autenticado'),
            new OA\Response(response: 404, description: 'Insumo nao encontrado'),
        ]
    )]
    public function showDoc(): void
    {
    }

    #[OA\Put(
        path: '/api/insumos/{id}',
        tags: ['Insumos'],
        summary: 'Atualiza um insumo existente',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nome', 'unidade_medida', 'preco_unitario', 'estoque_atual', 'estoque_minimo'],
                properties: [
                    new OA\Property(property: 'nome', type: 'string', maxLength: 255, example: 'Óleo de Motor 5W30'),
                    new OA\Property(property: 'unidade_medida', type: 'string', maxLength: 20, example: 'L'),
                    new OA\Property(property: 'preco_unitario', type: 'number', format: 'float', minimum: 0.01, example: 27.50),
                    new OA\Property(property: 'estoque_atual', type: 'number', format: 'float', minimum: 0, example: 45.0),
                    new OA\Property(property: 'estoque_minimo', type: 'number', format: 'float', minimum: 0, example: 10.0),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Insumo atualizado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'nome', type: 'string', example: 'Óleo de Motor 5W30'),
                        new OA\Property(property: 'unidade_medida', type: 'string', example: 'L'),
                        new OA\Property(property: 'preco_unitario', type: 'number', format: 'float', example: 27.50),
                        new OA\Property(property: 'estoque_atual', type: 'number', format: 'float', example: 45.0),
                        new OA\Property(property: 'estoque_minimo', type: 'number', format: 'float', example: 10.0),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Nao autenticado'),
            new OA\Response(response: 404, description: 'Insumo nao encontrado'),
            new OA\Response(response: 422, description: 'Erro de validacao'),
        ]
    )]
    public function updateDoc(): void
    {
    }

    #[OA\Delete(
        path: '/api/insumos/{id}',
        tags: ['Insumos'],
        summary: 'Remove um insumo',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), example: 1),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Insumo removido com sucesso'),
            new OA\Response(response: 401, description: 'Nao autenticado'),
            new OA\Response(response: 404, description: 'Insumo nao encontrado'),
        ]
    )]
    public function destroyDoc(): void
    {
    }
}
