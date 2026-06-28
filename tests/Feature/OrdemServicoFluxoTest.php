<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Models\ClienteModel;
use App\Infrastructure\Persistence\Eloquent\Models\MecanicoModel;
use App\Infrastructure\Persistence\Eloquent\Models\OrdemServicoModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Infrastructure\Persistence\Eloquent\Models\VeiculoModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdemServicoFluxoTest extends TestCase
{
    use RefreshDatabase;

    private function loginAs(string $role): string
    {
        $user = UserModel::factory()->create(['role' => $role]);
        return auth('api')->login($user);
    }

    private function criarCliente(): ClienteModel
    {
        return ClienteModel::create([
            'nome'      => 'Cliente Teste',
            'documento' => '52998224725',
            'tipo'      => 'pf',
            'email'     => 'cliente@teste.com',
            'telefone'  => '11999999999',
        ]);
    }

    private function criarVeiculo(int $clienteId): VeiculoModel
    {
        return VeiculoModel::create([
            'cliente_id' => $clienteId,
            'placa'      => 'TST1234',
            'marca'      => 'Volkswagen',
            'modelo'     => 'Gol',
            'ano'        => 2020,
        ]);
    }

    private function criarMecanico(string $role = 'mecanico'): array
    {
        $user     = UserModel::factory()->create(['role' => $role]);
        $mecanico = MecanicoModel::create([
            'user_id'       => $user->id,
            'especialidade' => 'Motor',
        ]);
        $token = auth('api')->login($user);

        return ['user' => $user, 'mecanico' => $mecanico, 'token' => $token];
    }

    public function test_atendente_cria_os(): void
    {
        $token   = $this->loginAs('atendente');
        $cliente = $this->criarCliente();
        $veiculo = $this->criarVeiculo($cliente->id);

        $response = $this->withToken($token)->postJson('/api/ordens-servico', [
            'cliente_id'         => $cliente->id,
            'veiculo_id'         => $veiculo->id,
            'descricao_problema' => 'Carro não liga',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['id', 'status', 'cliente_id']);
        $response->assertJson(['status' => 'RECEBIDA']);
    }

    public function test_mecanico_inicia_diagnostico(): void
    {
        $mecanico = $this->criarMecanico();
        $cliente  = $this->criarCliente();
        $veiculo  = $this->criarVeiculo($cliente->id);

        $os = OrdemServicoModel::create([
            'cliente_id'         => $cliente->id,
            'veiculo_id'         => $veiculo->id,
            'status'             => 'RECEBIDA',
            'descricao_problema' => 'Carro não liga',
        ]);

        $response = $this->withToken($mecanico['token'])
            ->patchJson("/api/ordens-servico/{$os->id}/iniciar-diagnostico");

        $response->assertStatus(200);
        $response->assertJson(['status' => 'EM_DIAGNOSTICO']);
    }

    public function test_mecanico_submete_orcamento(): void
    {
        $mecanico = $this->criarMecanico();
        $cliente  = $this->criarCliente();
        $veiculo  = $this->criarVeiculo($cliente->id);

        $os = OrdemServicoModel::create([
            'cliente_id'         => $cliente->id,
            'veiculo_id'         => $veiculo->id,
            'mecanico_id'        => $mecanico['mecanico']->id,
            'status'             => 'EM_DIAGNOSTICO',
            'descricao_problema' => 'Carro não liga',
            'iniciada_em'        => now(),
        ]);

        $response = $this->withToken($mecanico['token'])
            ->postJson("/api/ordens-servico/{$os->id}/submeter-orcamento", [
                'diagnostico' => 'Bobina do motor queimada',
                'mao_de_obra' => 200.00,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'AGUARDANDO_APROVACAO']);
    }

    public function test_cliente_consulta_os_publico_sem_auth(): void
    {
        $cliente = $this->criarCliente();
        $veiculo = $this->criarVeiculo($cliente->id);

        $os = OrdemServicoModel::create([
            'cliente_id'         => $cliente->id,
            'veiculo_id'         => $veiculo->id,
            'status'             => 'RECEBIDA',
            'descricao_problema' => 'Revisão geral',
        ]);

        $response = $this->getJson("/api/public/ordens-servico/{$os->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure(['id', 'status', 'descricao_problema']);
    }

    public function test_admin_ve_tempo_medio(): void
    {
        $token = $this->loginAs('admin');

        $response = $this->withToken($token)
            ->getJson('/api/ordens-servico/metricas/tempo-medio');

        $response->assertStatus(200);
        $response->assertJsonStructure(['media_minutos', 'total_concluidas']);
    }

    public function test_nao_admin_nao_ve_tempo_medio(): void
    {
        $token = $this->loginAs('atendente');

        $response = $this->withToken($token)
            ->getJson('/api/ordens-servico/metricas/tempo-medio');

        $response->assertStatus(403);
    }
}
