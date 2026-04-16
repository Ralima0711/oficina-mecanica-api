<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Atualização da Ordem de Serviço</title>
</head>
<body>
    <h2>Ordem de Serviço #{{ $ordem->getId() }}</h2>
    <p>Status atual: <strong>{{ (string) $ordem->getStatus() }}</strong></p>
    <p>Descriçao do problema: {{ $ordem->getDescricao() }}</p>

    @if (!empty($orcamento))
        <hr>
        <h3>Detalhes do Orçamento</h3>
        <p><strong>Diagnóstico:</strong> {{ $orcamento['diagnostico'] ?? '-' }}</p>
        <p><strong>Mão de obra:</strong> R$ {{ number_format((float) ($orcamento['mao_de_obra'] ?? 0), 2, ',', '.') }}</p>

        <h4>Peças</h4>
        @if (!empty($orcamento['pecas']))
            <ul>
                @foreach ($orcamento['pecas'] as $peca)
                    <li>
                        {{ $peca['nome'] }} -
                        Qtd: {{ $peca['quantidade'] }} -
                        Unitário: R$ {{ number_format((float) $peca['preco_unitario'], 2, ',', '.') }} -
                        Subtotal: R$ {{ number_format((float) $peca['subtotal'], 2, ',', '.') }}
                    </li>
                @endforeach
            </ul>
        @else
            <p>Sem peças adicionadas.</p>
        @endif

        <h4>Insumos</h4>
        @if (!empty($orcamento['insumos']))
            <ul>
                @foreach ($orcamento['insumos'] as $insumo)
                    <li>
                        {{ $insumo['nome'] }} -
                        Qtd: {{ $insumo['quantidade'] }} {{ $insumo['unidade_medida'] ?? '' }} -
                        Unitário: R$ {{ number_format((float) $insumo['preco_unitario'], 2, ',', '.') }} -
                        Subtotal: R$ {{ number_format((float) $insumo['subtotal'], 2, ',', '.') }}
                    </li>
                @endforeach
            </ul>
        @else
            <p>Sem insumos adicionados.</p>
        @endif

        <p><strong>Total peças:</strong> R$ {{ number_format((float) ($orcamento['total_pecas'] ?? 0), 2, ',', '.') }}</p>
        <p><strong>Total insumos:</strong> R$ {{ number_format((float) ($orcamento['total_insumos'] ?? 0), 2, ',', '.') }}</p>
        <p><strong>Valor total do orçamento:</strong> R$ {{ number_format((float) ($orcamento['valor_total'] ?? 0), 2, ',', '.') }}</p>
    @endif

    @if (!empty($links['aprovar']) && !empty($links['reprovar']))
        <p>Seu veículo esta aguardando aprovação do orçamento:</p>
        <p><a href="{{ $links['aprovar'] }}">Aprovar Orçamento</a></p>
        <p><a href="{{ $links['reprovar'] }}">Reprovar Orçamento</a></p>
        <p>Os links expiram em 24 horas.</p>
    @endif

    <p>Equipe Oficina Mecanica</p>
</body>
</html>
