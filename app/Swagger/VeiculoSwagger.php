<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Veiculos', description: 'Gerenciamento de veículos dos clientes')]
class VeiculoSwagger
{
    #[OA\Get(
        path: '/api/veiculos',
        tags: ['Veiculos'],
        summary: 'Lista todos os veículos',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de veículos retornada com sucesso',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'cliente_id', type: 'integer', example: 1),
                        new OA\Property(property: 'placa', type: 'string', example: 'ABC1D23'),
                        new OA\Property(property: 'marca', type: 'string', example: 'Toyota'),
                        new OA\Property(property: 'modelo', type: 'string', example: 'Corolla'),
                        new OA\Property(property: 'ano', type: 'integer', example: 2020),
                        new OA\Property(property: 'cor', type: 'string', example: 'Prata'),
                    ])
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function indexDoc(): void {}

    #[OA\Post(
        path: '/api/veiculos',
        tags: ['Veiculos'],
        summary: 'Cadastra um novo veículo',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cliente_id', 'placa', 'marca', 'modelo', 'ano'],
                properties: [
                    new OA\Property(property: 'cliente_id', type: 'integer', example: 1),
                    new OA\Property(property: 'placa', type: 'string', example: 'ABC1D23'),
                    new OA\Property(property: 'marca', type: 'string', example: 'Toyota'),
                    new OA\Property(property: 'modelo', type: 'string', example: 'Corolla'),
                    new OA\Property(property: 'ano', type: 'integer', example: 2020),
                    new OA\Property(property: 'cor', type: 'string', example: 'Prata'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Veículo cadastrado com sucesso'),
            new OA\Response(response: 422, description: 'Erro de validação'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function storeDoc(): void {}

    #[OA\Get(
        path: '/api/veiculos/{id}',
        tags: ['Veiculos'],
        summary: 'Busca um veículo pelo ID',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID do veículo', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Veículo encontrado'),
            new OA\Response(response: 404, description: 'Veículo não encontrado'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function showDoc(): void {}

    #[OA\Put(
        path: '/api/veiculos/{id}',
        tags: ['Veiculos'],
        summary: 'Atualiza um veículo',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID do veículo', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'cliente_id', type: 'integer', example: 1),
                    new OA\Property(property: 'placa', type: 'string', example: 'ABC1D23'),
                    new OA\Property(property: 'marca', type: 'string', example: 'Toyota'),
                    new OA\Property(property: 'modelo', type: 'string', example: 'Corolla'),
                    new OA\Property(property: 'ano', type: 'integer', example: 2020),
                    new OA\Property(property: 'cor', type: 'string', example: 'Prata'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Veículo atualizado com sucesso'),
            new OA\Response(response: 404, description: 'Veículo não encontrado'),
            new OA\Response(response: 422, description: 'Erro de validação'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function updateDoc(): void {}

    #[OA\Delete(
        path: '/api/veiculos/{id}',
        tags: ['Veiculos'],
        summary: 'Remove um veículo',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID do veículo', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Veículo removido com sucesso'),
            new OA\Response(response: 404, description: 'Veículo não encontrado'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function destroyDoc(): void {}
}
