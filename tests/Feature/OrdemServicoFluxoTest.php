<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Models\ClienteModel;
use App\Infrastructure\Persistence\Eloquent\Models\MecanicoModel;
use App\Infrastructure\Persistence\Eloquent\Models\OrdemServicoModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Infrastructure\Persistence\Eloquent\Models\VeiculoModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Tests\TestCase;

class OrdemServicoFluxoTest extends TestCase
{
    use RefreshDatabase;

    private string $privateKey;
    private string $publicKey;

    protected function setUp(): void
    {
        parent::setUp();

        $config = [];
        foreach (['C:/xampp/php/extras/ssl/openssl.cnf', 'C:/xampp/php/extras/openssl/openssl.cnf'] as $cnf) {
            if (is_file($cnf)) {
                $config['config'] = $cnf;
                break;
            }
        }

        $key = openssl_pkey_new($config + [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $privateKey = '';
        openssl_pkey_export($key, $privateKey, null, $config);
        $this->privateKey = $privateKey;
        $this->publicKey = openssl_pkey_get_details($key)['key'];

        config()->set('jwt.keys.public', $this->publicKey);
    }

    /**
     * Assina um JWT RS256 de CLIENTE (como o emitido pela Lambda), com o
     * client_id/cpf do cliente autenticado.
     */
    private function assinarTokenCliente(int $clientId, string $cpf = '52998224725'): string
    {
        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->privateKey),
            InMemory::plainText($this->publicKey)
        );

        $now = new \DateTimeImmutable();

        return $config->builder()
            ->withClaim('typ', 'cliente')
            ->issuedBy('oficina-lambda-auth')
            ->permittedFor('oficina-mecanica-api')
            ->issuedAt($now)
            ->expiresAt($now->modify('+3600 seconds'))
            ->relatedTo((string) $clientId)
            ->withClaim('client_id', $clientId)
            ->withClaim('cpf', $cpf)
            ->withClaim('status', 'ativo')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();
    }

    private function loginAs(string $role): string
    {
        $user = UserModel::factory()->create(['role' => $role]);
        return auth('api')->login($user);
    }

    private function criarCliente(string $documento = '52998224725', string $email = 'cliente@teste.com'): ClienteModel
    {
        return ClienteModel::create([
            'nome'      => 'Cliente Teste',
            'documento' => $documento,
            'tipo'      => 'pf',
            'email'     => $email,
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
        $response->assertJsonPath('status', 'AGUARDANDO_APROVACAO');
    }

    public function test_cliente_consulta_os_publico_sem_token_retorna_401(): void
    {
        $cliente = $this->criarCliente();
        $veiculo = $this->criarVeiculo($cliente->id);

        $os = OrdemServicoModel::create([
            'cliente_id'         => $cliente->id,
            'veiculo_id'         => $veiculo->id,
            'status'             => 'RECEBIDA',
            'descricao_problema' => 'Revisão geral',
        ]);

        $this->getJson("/api/public/ordens-servico/{$os->id}")
            ->assertStatus(401)
            ->assertJson(['error' => 'nao_autorizado']);
    }

    public function test_cliente_consulta_propria_os_publico_retorna_200(): void
    {
        $cliente = $this->criarCliente();
        $veiculo = $this->criarVeiculo($cliente->id);

        $os = OrdemServicoModel::create([
            'cliente_id'         => $cliente->id,
            'veiculo_id'         => $veiculo->id,
            'status'             => 'RECEBIDA',
            'descricao_problema' => 'Revisão geral',
        ]);

        $token = $this->assinarTokenCliente($cliente->id);

        $this->withToken($token)
            ->getJson("/api/public/ordens-servico/{$os->id}")
            ->assertStatus(200)
            ->assertJsonStructure(['id', 'status', 'descricao_problema']);
    }

    public function test_cliente_nao_consulta_os_de_outro_cliente_retorna_403(): void
    {
        $clienteDono = $this->criarCliente();
        $veiculo     = $this->criarVeiculo($clienteDono->id);

        $os = OrdemServicoModel::create([
            'cliente_id'         => $clienteDono->id,
            'veiculo_id'         => $veiculo->id,
            'status'             => 'RECEBIDA',
            'descricao_problema' => 'Revisão geral',
        ]);

        // Outro cliente (id diferente) tenta acessar a OS do dono.
        $outroCliente = $this->criarCliente('11144477735', 'outro@teste.com');
        $token = $this->assinarTokenCliente($outroCliente->id, '11144477735');

        $this->withToken($token)
            ->getJson("/api/public/ordens-servico/{$os->id}")
            ->assertStatus(403);
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
