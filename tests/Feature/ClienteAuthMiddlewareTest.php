<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Tests\TestCase;

class ClienteAuthMiddlewareTest extends TestCase
{
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
        $details = openssl_pkey_get_details($key);
        $this->publicKey = $details['key'];

        config()->set('jwt.keys.public', $this->publicKey);

        Route::middleware('cliente')->get('_teste/cliente', function () {
            return response()->json(['ok' => true]);
        });
    }

    private function assinarToken(array $claims = [], ?string $typ = 'cliente'): string
    {
        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->privateKey),
            InMemory::plainText($this->publicKey)
        );

        $now = new \DateTimeImmutable();

        $builder = $config->builder()
            ->withClaim('typ', $typ)
            ->issuedBy('oficina-lambda-auth')
            ->permittedFor('oficina-mecanica-api')
            ->issuedAt($now)
            ->expiresAt($now->modify('+3600 seconds'))
            ->withClaim('client_id', 1)
            ->withClaim('cpf', '52998224725')
            ->withClaim('status', 'ativo');

        if (isset($claims['sub'])) {
            $builder = $builder->relatedTo($claims['sub']);
        } else {
            $builder = $builder->relatedTo('1');
        }

        $token = $builder->getToken($config->signer(), $config->signingKey());

        return $token->toString();
    }

    public function test_sem_token_retorna_401_nao_autorizado(): void
    {
        $this->getJson('/_teste/cliente')
            ->assertStatus(401)
            ->assertJson(['error' => 'nao_autorizado']);
    }

    public function test_token_invalido_retorna_401_nao_autorizado(): void
    {
        $this->getJson('/_teste/cliente', ['Authorization' => 'Bearer token.invalido'])
            ->assertStatus(401)
            ->assertJson(['error' => 'nao_autorizado']);
    }

    public function test_token_valido_retorna_200(): void
    {
        $token = $this->assinarToken();

        $this->getJson('/_teste/cliente', ['Authorization' => 'Bearer ' . $token])
            ->assertStatus(200)
            ->assertJson(['ok' => true]);
    }

    public function test_token_com_iss_errado_retorna_401(): void
    {
        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->privateKey),
            InMemory::plainText($this->publicKey)
        );

        $now = new \DateTimeImmutable();
        $token = $config->builder()
            ->issuedBy('outro-emissor')
            ->permittedFor('oficina-mecanica-api')
            ->issuedAt($now)
            ->expiresAt($now->modify('+3600 seconds'))
            ->relatedTo('1')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        $this->getJson('/_teste/cliente', ['Authorization' => 'Bearer ' . $token])
            ->assertStatus(401)
            ->assertJson(['error' => 'nao_autorizado']);
    }

    public function test_token_com_typ_errado_retorna_401(): void
    {
        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->privateKey),
            InMemory::plainText($this->publicKey)
        );

        $now = new \DateTimeImmutable();
        $token = $config->builder()
            ->withClaim('typ', 'staff')
            ->issuedBy('oficina-lambda-auth')
            ->permittedFor('oficina-mecanica-api')
            ->issuedAt($now)
            ->expiresAt($now->modify('+3600 seconds'))
            ->relatedTo('1')
            ->getToken($config->signer(), $config->signingKey())
            ->toString();

        $this->getJson('/_teste/cliente', ['Authorization' => 'Bearer ' . $token])
            ->assertStatus(401)
            ->assertJson(['error' => 'nao_autorizado']);
    }
}
