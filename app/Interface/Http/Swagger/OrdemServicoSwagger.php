<?php

namespace App\Interface\Http\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Ordens de Servico', description: 'Ciclo de vida completo das Ordens de Serviço')]
class OrdemServicoSwagger
{
    #[OA\Get(
        path: '/api/ordens-servico',
        tags: ['Ordens de Servico'],
        summary: 'Lista todas as ordens de serviço',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de OS retornada com sucesso',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'cliente_id', type: 'integer', example: 1),
                        new OA\Property(property: 'veiculo_id', type: 'integer', example: 1),
                        new OA\Property(property: 'status', type: 'string', enum: ['em_diagnostico', 'aguardando_aprovacao', 'aprovada', 'em_execucao', 'finalizada', 'entregue', 'recusada'], example: 'em_diagnostico'),
                        new OA\Property(property: 'descricao_problema', type: 'string', example: 'Carro não liga'),
                        new OA\Property(property: 'valor_total', type: 'number', format: 'float', example: 350.00),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                    ])
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function indexDoc(): void {}

    #[OA\Post(
        path: '/api/ordens-servico',
        tags: ['Ordens de Servico'],
        summary: 'Cria uma nova ordem de serviço',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cliente_id', 'veiculo_id', 'descricao_problema'],
                properties: [
                    new OA\Property(property: 'cliente_id', type: 'integer', example: 1),
                    new OA\Property(property: 'veiculo_id', type: 'integer', example: 1),
                    new OA\Property(property: 'descricao_problema', type: 'string', example: 'Carro não liga, possível problema na bateria'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'OS criada com sucesso'),
            new OA\Response(response: 422, description: 'Erro de validação'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function storeDoc(): void {}

    #[OA\Get(
        path: '/api/ordens-servico/{id}',
        tags: ['Ordens de Servico'],
        summary: 'Busca uma OS pelo ID',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID da OS', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OS encontrada'),
            new OA\Response(response: 404, description: 'OS não encontrada'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function showDoc(): void {}

    #[OA\Put(
        path: '/api/ordens-servico/{id}',
        tags: ['Ordens de Servico'],
        summary: 'Atualiza uma OS',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID da OS', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'descricao_problema', type: 'string', example: 'Problema na bateria e alternador'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'OS atualizada com sucesso'),
            new OA\Response(response: 404, description: 'OS não encontrada'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function updateDoc(): void {}

    #[OA\Delete(
        path: '/api/ordens-servico/{id}',
        tags: ['Ordens de Servico'],
        summary: 'Remove uma OS',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID da OS', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 204, description: 'OS removida com sucesso'),
            new OA\Response(response: 404, description: 'OS não encontrada'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function destroyDoc(): void {}

    #[OA\Patch(
        path: '/api/ordens-servico/{id}/iniciar-diagnostico',
        tags: ['Ordens de Servico'],
        summary: 'Inicia o diagnóstico — status muda para em_diagnostico',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID da OS', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Diagnóstico iniciado com sucesso'),
            new OA\Response(response: 404, description: 'OS não encontrada'),
            new OA\Response(response: 422, description: 'Usuário não possui cadastro de mecânico'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function iniciarDiagnosticoDoc(): void {}

    #[OA\Post(
        path: '/api/ordens-servico/{id}/submeter-orcamento',
        tags: ['Ordens de Servico'],
        summary: 'Submete orçamento — status muda para aguardando_aprovacao',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID da OS', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['valor_total', 'itens'],
                properties: [
                    new OA\Property(property: 'valor_total', type: 'number', format: 'float', example: 350.00),
                    new OA\Property(
                        property: 'itens',
                        type: 'array',
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'peca_id', type: 'integer', example: 1),
                            new OA\Property(property: 'quantidade', type: 'integer', example: 2),
                            new OA\Property(property: 'valor_unitario', type: 'number', example: 50.00),
                        ])
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Orçamento submetido com sucesso'),
            new OA\Response(response: 404, description: 'OS não encontrada'),
            new OA\Response(response: 422, description: 'Erro de validação'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function submeterOrcamentoDoc(): void {}

    #[OA\Get(
        path: '/api/public/ordens-servico/{id}/aprovar/{token}',
        tags: ['Ordens de Servico'],
        summary: 'Aprovação pública da OS pelo cliente via link de e-mail',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID da OS', schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'token', in: 'path', required: true, description: 'Token de aprovação enviado por e-mail', schema: new OA\Schema(type: 'string', example: 'abc123token')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OS aprovada com sucesso'),
            new OA\Response(response: 404, description: 'OS ou token inválido'),
        ]
    )]
    public function aprovarPublicoDoc(): void {}

    #[OA\Get(
        path: '/api/public/ordens-servico/{id}/reprovar/{token}',
        tags: ['Ordens de Servico'],
        summary: 'Reprovação pública da OS pelo cliente via link de e-mail',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID da OS', schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'token', in: 'path', required: true, description: 'Token de reprovação enviado por e-mail', schema: new OA\Schema(type: 'string', example: 'abc123token')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OS reprovada com sucesso'),
            new OA\Response(response: 404, description: 'OS ou token inválido'),
        ]
    )]
    public function reprovarPublicoDoc(): void {}

    #[OA\Patch(
        path: '/api/ordens-servico/{id}/iniciar-execucao',
        tags: ['Ordens de Servico'],
        summary: 'Inicia execução — status muda para em_execucao',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID da OS', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Execução iniciada com sucesso'),
            new OA\Response(response: 404, description: 'OS não encontrada'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function iniciarExecucaoDoc(): void {}

    #[OA\Patch(
        path: '/api/ordens-servico/{id}/finalizar',
        tags: ['Ordens de Servico'],
        summary: 'Finaliza a OS — status muda para finalizada',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID da OS', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OS finalizada com sucesso'),
            new OA\Response(response: 404, description: 'OS não encontrada'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function finalizarDoc(): void {}

    #[OA\Patch(
        path: '/api/ordens-servico/{id}/entregar',
        tags: ['Ordens de Servico'],
        summary: 'Registra entrega do veículo — status muda para entregue',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID da OS', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Veículo entregue com sucesso'),
            new OA\Response(response: 404, description: 'OS não encontrada'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function entregarDoc(): void {}
}
