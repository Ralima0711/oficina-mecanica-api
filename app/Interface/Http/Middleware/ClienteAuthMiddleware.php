<?php

namespace App\Interface\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use Symfony\Component\HttpFoundation\Response;

/**
 * CAMADA DE INTERFACE — Middleware
 * Guard de CLIENTE: valida o JWT RS256 emitido pela Lambda (por CPF).
 * NÃO consulta a tabela users — a identidade vem dos claims do token.
 */
class ClienteAuthMiddleware
{
    private const ISS = 'oficina-lambda-auth';
    private const AUD = 'oficina-mecanica-api';
    private const TYP = 'cliente';

    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $token = $this->extrairToken($request);

        if ($token === null) {
            return $this->naoAutorizado();
        }

        try {
            $claims = $this->validar($token);
        } catch (\Throwable $e) {
            return $this->naoAutorizado();
        }

        // Expõe os claims do cliente para os controllers (client_id, cpf, status).
        $request->attributes->set('cliente', $claims);

        return $next($request);
    }

    private function extrairToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    /**
     * Valida assinatura (RS256), exp, iss, aud e typ.
     *
     * @return array<string, mixed> claims do token
     */
    private function validar(string $token): array
    {
        $publicKey = $this->obterChavePublica();

        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText('ignored'),
            $publicKey
        );

        $config->setValidationConstraints(
            new SignedWith(new Sha256(), $publicKey),
            new IssuedBy(self::ISS),
            new PermittedFor(self::AUD),
            new ValidAt(SystemClock::fromUTC())
        );

        $parsed = $config->parser()->parse($token);

        $config->validator()->assert($parsed, ...$config->validationConstraints());

        // Valida o claim typ = cliente (domínio do token, conforme o contrato).
        $claims = $parsed->claims()->all();
        if (($claims['typ'] ?? null) !== self::TYP) {
            throw new \RuntimeException('Tipo de token inválido.');
        }

        return $claims;
    }

    /**
     * Lê a chave pública JWT. Aceita o PEM inline ou um caminho de arquivo
     * no formato ile:///caminho/para/chave.pem.
     */
    private function obterChavePublica(): InMemory
    {
        $publicKey = (string) config('jwt.keys.public');

        if ($publicKey === '') {
            throw new \RuntimeException('Chave pública JWT não configurada.');
        }

        if (str_starts_with($publicKey, 'file://')) {
            return InMemory::file(substr($publicKey, 7));
        }

        return InMemory::plainText($publicKey);
    }

    private function naoAutorizado(): JsonResponse
    {
        return response()->json(['error' => 'nao_autorizado'], 401);
    }
}
