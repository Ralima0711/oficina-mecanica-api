<?php

namespace Tests\Unit\Infrastructure\Repositories;

use App\Infrastructure\Persistence\Eloquent\Models\ClienteModel;
use App\Infrastructure\Persistence\Eloquent\Models\VeiculoModel;
use App\Infrastructure\Persistence\Eloquent\Models\OrdemServicoModel;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrdemServicoRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes do repositório EloquentOrdemServico
 * Testa a métrica de tempo médio de execução
 */
class EloquentOrdemServicoRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentOrdemServicoRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentOrdemServicoRepository();
    }

    public function test_tempo_medio_sem_os_concluidas(): void
    {
        // Arrange: Criar uma OS sem concluida_em
        $cliente = ClienteModel::create([
            'nome' => 'Cliente Teste',
            'documento' => '12345678901',
            'tipo' => 'pf',
            'email' => 'teste@example.com',
            'telefone' => '1133334444',
        ]);

        $veiculo = VeiculoModel::create([
            'cliente_id' => $cliente->id,
            'placa' => 'ABC1234',
            'marca' => 'Volkswagen',
            'modelo' => 'Gol',
            'ano' => 2020,
        ]);

        OrdemServicoModel::create([
            'cliente_id' => $cliente->id,
            'veiculo_id' => $veiculo->id,
            'status' => 'RECEBIDA',
            'descricao_problema' => 'Carro não liga',
            'iniciada_em' => now(),
            'concluida_em' => null,
        ]);

        // Act
        $resultado = $this->repository->tempoMedioExecucao();

        // Assert
        $this->assertNull($resultado['media_minutos']);
        $this->assertEquals(0, $resultado['total_concluidas']);
    }

    public function test_tempo_medio_calcula_corretamente(): void
    {
        // Arrange: Criar cliente e veiculo
        $cliente = ClienteModel::create([
            'nome' => 'Cliente Teste',
            'documento' => '98765432100',
            'tipo' => 'pf',
            'email' => 'teste2@example.com',
            'telefone' => '1155556666',
        ]);

        $veiculo = VeiculoModel::create([
            'cliente_id' => $cliente->id,
            'placa' => 'XYZ5678',
            'marca' => 'Ford',
            'modelo' => 'Fox',
            'ano' => 2021,
        ]);

        // Criar 3 ordens concluídas com durações conhecidas
        $agora = now();
        
        // OS 1: 60 minutos de duração
        OrdemServicoModel::create([
            'cliente_id' => $cliente->id,
            'veiculo_id' => $veiculo->id,
            'status' => 'ENTREGUE',
            'descricao_problema' => 'Problema 1',
            'iniciada_em' => $agora->clone()->subMinutes(60),
            'concluida_em' => $agora->clone(),
        ]);

        // OS 2: 120 minutos de duração
        OrdemServicoModel::create([
            'cliente_id' => $cliente->id,
            'veiculo_id' => $veiculo->id,
            'status' => 'ENTREGUE',
            'descricao_problema' => 'Problema 2',
            'iniciada_em' => $agora->clone()->subMinutes(120),
            'concluida_em' => $agora->clone(),
        ]);

        // OS 3: 180 minutos de duração
        OrdemServicoModel::create([
            'cliente_id' => $cliente->id,
            'veiculo_id' => $veiculo->id,
            'status' => 'ENTREGUE',
            'descricao_problema' => 'Problema 3',
            'iniciada_em' => $agora->clone()->subMinutes(180),
            'concluida_em' => $agora->clone(),
        ]);

        // Act
        $resultado = $this->repository->tempoMedioExecucao();

        // Assert - média de (60 + 120 + 180) / 3 = 120 minutos
        $this->assertEquals(120, $resultado['media_minutos']);
        $this->assertEquals(3, $resultado['total_concluidas']);
    }
}
