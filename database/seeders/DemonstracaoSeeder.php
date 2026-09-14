<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Massa mínima para demonstrar a autenticação por CPF e o escopo por cliente.
 *
 * Por que existe: o ambiente do laboratório é efêmero (ADR-0006). Quando o RDS
 * é recriado, o banco sobe vazio — o `start.sh` roda apenas `migrate`, e os
 * seeders existentes criam somente usuários de staff (mecânicos e atendentes).
 * Sem nenhum cliente cadastrado a Lambda não encontra CPF algum, e o fluxo
 * ponta a ponta não tem contra o que autenticar.
 *
 * Os dois CPFs abaixo são os mesmos já usados no mock do repositório
 * `oficina-lambda-auth` (`src/repo.js`) e passam na validação de dígitos
 * verificadores da função. A Lambda consulta `clientes.documento` com 11
 * dígitos, sem máscara — é assim que eles são gravados aqui.
 *
 * O par de clientes é o que permite demonstrar os dois casos do requisito de
 * escopo: 200 na própria ordem de serviço e 403 na ordem do outro cliente.
 *
 * Idempotente: pode rodar a cada boot de pod sem duplicar registros.
 */
class DemonstracaoSeeder extends Seeder
{
    private const CLIENTES = [
        [
            'documento' => '52998224725',
            'nome' => 'Helena Prado',
            'email' => 'helena.prado@exemplo.local',
            'telefone' => '11988880001',
            'placa' => 'DEM1A23',
            'marca' => 'Volkswagen',
            'modelo' => 'Gol 1.6',
            'ano' => 2019,
            'cor' => 'Prata',
            'problema' => 'Revisao de 20.000 km e ruido na suspensao dianteira.',
        ],
        [
            'documento' => '11144477735',
            'nome' => 'Marcos Vieira',
            'email' => 'marcos.vieira@exemplo.local',
            'telefone' => '11988880002',
            'placa' => 'DEM2B34',
            'marca' => 'Fiat',
            'modelo' => 'Argo Drive',
            'ano' => 2021,
            'cor' => 'Branco',
            'problema' => 'Ar-condicionado nao gela e luz de injecao acesa.',
        ],
    ];

    public function run(): void
    {
        $mecanicoId = DB::table('mecanicos')->orderBy('id')->value('id');

        foreach (self::CLIENTES as $dados) {
            $clienteId = $this->clienteId($dados);
            $veiculoId = $this->veiculoId($clienteId, $dados);

            // Uma OS em aberto por cliente: é este par que demonstra
            // 200 na própria OS e 403 na OS do outro.
            $this->ordem($clienteId, $veiculoId, $mecanicoId, 'RECEBIDA', $dados['problema']);
        }

        $this->ordemHistorica($mecanicoId);
    }

    private function clienteId(array $dados): int
    {
        DB::table('clientes')->updateOrInsert(
            ['documento' => $dados['documento'], 'tipo' => 'pf'],
            [
                'nome' => $dados['nome'],
                'email' => $dados['email'],
                'telefone' => $dados['telefone'],
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return (int) DB::table('clientes')
            ->where('documento', $dados['documento'])
            ->where('tipo', 'pf')
            ->value('id');
    }

    private function veiculoId(int $clienteId, array $dados): int
    {
        DB::table('veiculos')->updateOrInsert(
            ['placa' => $dados['placa']],
            [
                'cliente_id' => $clienteId,
                'marca' => $dados['marca'],
                'modelo' => $dados['modelo'],
                'ano' => $dados['ano'],
                'cor' => $dados['cor'],
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return (int) DB::table('veiculos')->where('placa', $dados['placa'])->value('id');
    }

    private function ordem(
        int $clienteId,
        int $veiculoId,
        ?int $mecanicoId,
        string $status,
        string $problema,
        array $extra = []
    ): void {
        DB::table('ordens_servico')->updateOrInsert(
            ['cliente_id' => $clienteId, 'descricao_problema' => $problema],
            array_merge([
                'veiculo_id' => $veiculoId,
                'mecanico_id' => $mecanicoId,
                'status' => $status,
                'updated_at' => now(),
                'created_at' => now(),
            ], $extra)
        );
    }

    /**
     * Uma OS já concluída, com carimbos de tempo plausíveis, para que o
     * dashboard não comece completamente vazio enquanto o tráfego de
     * demonstração ainda não gerou transições.
     */
    private function ordemHistorica(?int $mecanicoId): void
    {
        $documento = self::CLIENTES[0]['documento'];

        $cliente = DB::table('clientes')
            ->where('documento', $documento)
            ->where('tipo', 'pf')
            ->first();

        if (! $cliente) {
            return;
        }

        $veiculo = DB::table('veiculos')->where('cliente_id', $cliente->id)->first();

        if (! $veiculo) {
            return;
        }

        $this->ordem(
            $cliente->id,
            $veiculo->id,
            $mecanicoId,
            'FINALIZADA',
            'Troca de oleo e filtros.',
            [
                'diagnostico' => 'Oleo e filtros trocados conforme plano de revisao.',
                'valor_total' => 480.00,
                'iniciada_em' => now()->subHours(6),
                'concluida_em' => now()->subHours(2),
                'created_at' => now()->subHours(7),
                'updated_at' => now()->subHours(2),
            ]
        );
    }
}
