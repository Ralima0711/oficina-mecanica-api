<?php

namespace App\Interface\Http\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Notificacoes', description: 'Notificações do usuário autenticado')]
class NotificacaoSwagger
{
    #[OA\Get(
        path: '/api/notificacoes/minhas',
        tags: ['Notificacoes'],
        summary: 'Lista as notificações do usuário autenticado',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Itens por página (padrão: 20)', schema: new OA\Schema(type: 'integer', example: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notificações retornadas com sucesso',
                content: new OA\JsonContent(properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'tipo', type: 'string', example: 'OS_CRIADA'),
                            new OA\Property(property: 'mensagem', type: 'string', example: 'Nova OS criada para diagnóstico'),
                            new OA\Property(property: 'lida', type: 'boolean', example: false),
                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        ])
                    ),
                    new OA\Property(property: 'total', type: 'integer', example: 5),
                    new OA\Property(property: 'per_page', type: 'integer', example: 20),
                ])
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function indexDoc(): void {}

    #[OA\Patch(
        path: '/api/notificacoes/{id}/lida',
        tags: ['Notificacoes'],
        summary: 'Marca uma notificação como lida',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID da notificação', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notificação marcada como lida',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'lida', type: 'boolean', example: true),
                ])
            ),
            new OA\Response(response: 404, description: 'Notificação não encontrada'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function marcarComoLidaDoc(): void {}
}
