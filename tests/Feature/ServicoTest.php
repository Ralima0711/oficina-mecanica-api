<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Models\ServicoModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes de Feature para CRUD de Serviços
 * Valida endpoints de criação, listagem, atualização e remoção
 */
class ServicoTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsAdmin(): string
    {
        $admin = UserModel::factory()->create(['role' => 'admin']);
        return auth('api')->login($admin);
    }

    public function test_admin_cria_servico(): void
    {
        // Arrange
        $token = $this->loginAsAdmin();
        $dados = [
            'nome' => 'Troca de Óleo',
            'codigo' => 'TROCA_OLEO_001',
            'descricao' => 'Serviço de troca de óleo do motor',
            'categoria' => 'Manutenção',
            'preco_base' => 150.00,
            'duracao_estimada_minutos' => 45,
        ];

        // Act
        $response = $this->withToken($token)->postJson('/api/servicos', $dados);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'nome', 'codigo', 'preco_base', 'created_at'])
            ->assertJson([
                'nome' => 'Troca de Óleo',
                'codigo' => 'TROCA_OLEO_001',
                'preco_base' => 150.00,
            ]);

        $this->assertDatabaseHas('servicos', [
            'codigo' => 'TROCA_OLEO_001',
            'nome' => 'Troca de Óleo',
        ]);
    }

    public function test_admin_lista_servicos(): void
    {
        // Arrange
        $token = $this->loginAsAdmin();
        ServicoModel::create([
            'nome' => 'Alinhamento',
            'codigo' => 'ALIN_001',
            'preco_base' => 100.00,
        ]);
        ServicoModel::create([
            'nome' => 'Balanceamento',
            'codigo' => 'BALAN_001',
            'preco_base' => 80.00,
        ]);

        // Act
        $response = $this->withToken($token)->getJson('/api/servicos');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => ['id', 'nome', 'codigo', 'preco_base', 'created_at'],
            ]);
    }

    public function test_admin_atualiza_servico(): void
    {
        // Arrange
        $token = $this->loginAsAdmin();
        $servico = ServicoModel::create([
            'nome' => 'Diagnóstico',
            'codigo' => 'DIAG_001',
            'preco_base' => 200.00,
            'duracao_estimada_minutos' => 60,
        ]);

        $dadosAtualizados = [
            'nome' => 'Diagnóstico Completo',
            'codigo' => 'DIAG_001',
            'preco_base' => 250.00,
            'duracao_estimada_minutos' => 90,
        ];

        // Act
        $response = $this->withToken($token)->putJson("/api/servicos/{$servico->id}", $dadosAtualizados);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'nome' => 'Diagnóstico Completo',
                'preco_base' => 250.00,
                'duracao_estimada_minutos' => 90,
            ]);

        $this->assertDatabaseHas('servicos', [
            'id' => $servico->id,
            'nome' => 'Diagnóstico Completo',
            'preco_base' => 250.00,
        ]);
    }

    public function test_admin_remove_servico(): void
    {
        // Arrange
        $token = $this->loginAsAdmin();
        $servico = ServicoModel::create([
            'nome' => 'Pintura',
            'codigo' => 'PINT_001',
            'preco_base' => 500.00,
        ]);

        // Act
        $response = $this->withToken($token)->deleteJson("/api/servicos/{$servico->id}");

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing('servicos', ['id' => $servico->id]);
    }

    public function test_codigo_unico_por_servico(): void
    {
        // Arrange
        $token = $this->loginAsAdmin();
        ServicoModel::create([
            'nome' => 'Serviço A',
            'codigo' => 'CODIGO_DUPLICADO',
            'preco_base' => 100.00,
        ]);

        $dados = [
            'nome' => 'Serviço B',
            'codigo' => 'CODIGO_DUPLICADO',
            'preco_base' => 150.00,
        ];

        // Act
        $response = $this->withToken($token)->postJson('/api/servicos', $dados);

        // Assert
        $response->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_servico_requer_campos_obrigatorios(): void
    {
        // Arrange
        $token = $this->loginAsAdmin();
        $dados = [
            'nome' => 'Serviço Incompleto',
            // Falta 'codigo' e 'preco_base'
        ];

        // Act
        $response = $this->withToken($token)->postJson('/api/servicos', $dados);

        // Assert
        $response->assertStatus(422);
    }

    public function test_listar_servicos_sem_autenticacao(): void
    {
        // Act
        $response = $this->getJson('/api/servicos');

        // Assert
        $response->assertStatus(401);
    }
}
