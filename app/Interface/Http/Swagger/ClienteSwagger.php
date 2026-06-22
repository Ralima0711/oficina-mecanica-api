<?php

namespace App\Interface\Http\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Clientes', description: 'Gerenciamento de clientes (pessoa física e jurídica)')]
class ClienteSwagger
{
    #[OA\Get(
        path: '/api/clientes',
        tags: ['Clientes'],
        summary: 'Lista todos os clientes',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de clientes retornada com sucesso',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'nome', type: 'string', example: 'João Silva'),
                        new OA\Property(property: 'email', type: 'string', example: 'joao@email.com'),
                        new OA\Property(property: 'telefone', type: 'string', example: '11999999999'),
                        new OA\Property(property: 'documento_tipo', type: 'string', example: 'cpf'),
                        new OA\Property(property: 'documento', type: 'string', example: '123.456.789-00'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                    ])
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function indexDoc(): void {}

    #[OA\Post(
        path: '/api/clientes',
        tags: ['Clientes'],
        summary: 'Cria um novo cliente',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nome', 'email', 'telefone', 'documento_tipo', 'documento'],
                properties: [
                    new OA\Property(property: 'nome', type: 'string', example: 'João Silva'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'joao@email.com'),
                    new OA\Property(property: 'telefone', type: 'string', example: '11999999999'),
                    new OA\Property(property: 'documento_tipo', type: 'string', enum: ['cpf', 'cnpj'], example: 'cpf'),
                    new OA\Property(property: 'documento', type: 'string', example: '123.456.789-00'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Cliente criado com sucesso'),
            new OA\Response(response: 422, description: 'Erro de validação'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function storeDoc(): void {}

    #[OA\Get(
        path: '/api/clientes/{id}',
        tags: ['Clientes'],
        summary: 'Busca um cliente pelo ID',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID do cliente', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cliente encontrado'),
            new OA\Response(response: 404, description: 'Cliente não encontrado'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function showDoc(): void {}

    #[OA\Put(
        path: '/api/clientes/{id}',
        tags: ['Clientes'],
        summary: 'Atualiza um cliente',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID do cliente', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nome', type: 'string', example: 'João Silva Atualizado'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'joao@email.com'),
                    new OA\Property(property: 'telefone', type: 'string', example: '11999999999'),
                    new OA\Property(property: 'documento_tipo', type: 'string', enum: ['cpf', 'cnpj'], example: 'cpf'),
                    new OA\Property(property: 'documento', type: 'string', example: '123.456.789-00'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cliente atualizado com sucesso'),
            new OA\Response(response: 404, description: 'Cliente não encontrado'),
            new OA\Response(response: 422, description: 'Erro de validação'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function updateDoc(): void {}

    #[OA\Delete(
        path: '/api/clientes/{id}',
        tags: ['Clientes'],
        summary: 'Remove um cliente',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID do cliente', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Cliente removido com sucesso'),
            new OA\Response(response: 404, description: 'Cliente não encontrado'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function destroyDoc(): void {}
}
