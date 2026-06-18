<?php

namespace App\Application\Services;

use App\Domain\Cliente\Entities\Cliente;
use App\Domain\Cliente\Repositories\ClienteRepositoryInterface;
use App\Domain\Cliente\ValueObjects\Cnpj;
use App\Domain\Cliente\ValueObjects\Cpf;
use App\Domain\OrdemServico\Repositories\OrdemServicoRepositoryInterface;
use App\Domain\OrdemServico\Entities\OrdemServico;
use App\Domain\OrdemServico\ValueObjects\StatusOrdem;
use App\Domain\Veiculo\Repositories\VeiculoRepositoryInterface;
use App\Domain\Mecanico\Repositories\MecanicoRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * CAMADA DE APLICAÇÃO — Application Service
 * Orquestra os casos de uso. NÃO contém regra de negócio.
 * Não altera o estado dos objetos diretamente — delega ao Domínio.
 */
class OrdemServicoService
{
    private const TOKEN_TTL_SECONDS = 86400;

    public function __construct(
        private OrdemServicoRepositoryInterface $repository,
        private ClienteRepositoryInterface $clienteRepository,
        private VeiculoRepositoryInterface $veiculoRepository,
        private MecanicoRepositoryInterface $mecanicoRepository,
        private OrdemServicoNotificacaoService $notificacaoService,
        private SistemaNotificacaoService $sistemaNotificacaoService,
    ) {}

    public function listarTodas(): array
    {
        return $this->repository->findAll();
    }

    public function buscarPorId(int $id): OrdemServico
    {
        $os = $this->repository->findById($id);

        if (!$os) {
            throw new \RuntimeException("Ordem de Serviço #{$id} não encontrada.");
        }

        return $os;
    }

    public function criar(array $data, ?string $abertoPorRole = null): OrdemServico
    {
        $clienteId = null;

        if (array_key_exists('cliente_id', $data) && $data['cliente_id'] !== null) {
            $clienteId = (int) $data['cliente_id'];
        }

        if ($clienteId === null && array_key_exists('cliente_documento', $data) && $data['cliente_documento'] !== null) {
            $cliente = $this->getClienteByDocumento((string) $data['cliente_documento']);
            $clienteId = $cliente->getId();
        }

        if ($clienteId === null) {
            throw new \DomainException('Informe cliente_id ou cliente_documento para criar a ordem.');
        }

        $veiculoId = (int) $data['veiculo_id'];
        $this->veiculoPertenceAoCliente($veiculoId, $clienteId);

        $os = new OrdemServico(
            id: null,
            clienteId: $clienteId,
            veiculoId: $veiculoId,
            mecanicoId: $data['mecanico_id'] ?? null,
            status: StatusOrdem::from('RECEBIDA'),
            descricaoProblema: $data['descricao_problema'],
            diagnostico: $data['diagnostico'] ?? null,
            valorTotal: null,
            iniciadaEm: null,
            criadaEm: new \DateTimeImmutable(),
        );

        $salva = $this->repository->save($os);
        $this->notificarMudancaStatus($salva);

        if (strtolower((string) $abertoPorRole) === 'atendente') {
            $this->notificarSistema(
                fn() => $this->sistemaNotificacaoService->notificarOsRecebidaParaMecanicos($salva),
                $salva,
                'OS_RECEBIDA'
            );
        }

        return $salva;
    }

    public function atualizar(int $id, array $data): OrdemServico
    {
        $os = $this->buscarPorId($id);
        $os->atualizar($data);
        $salva = $this->repository->save($os);
        $this->notificarMudancaStatus($salva);

        return $salva;
    }

    public function iniciarDiagnostico(int $id, int $mecanicoId): OrdemServico
    {
        $os = $this->buscarPorId($id);
        $os->iniciarDiagnostico($mecanicoId);
        $salva = $this->repository->save($os);
        $this->notificarMudancaStatus($salva);

        return $salva;
    }

    public function resolverMecanicoId(int $usuarioId): int
    {
        $mecanicoId = $this->mecanicoRepository->findIdByUserId($usuarioId);

        if ($mecanicoId === null) {
            throw new \DomainException('Usuário autenticado não possui cadastro de mecanico.');
        }

        return (int) $mecanicoId;
    }

    public function submeterOrcamento(int $id, int $mecanicoId, array $dados): OrdemServico
    {

        $diagnostico = trim((string) ($dados['diagnostico'] ?? ''));
        $maoDeObra = (float) ($dados['mao_de_obra'] ?? 0);

        if ($diagnostico === '') {
            throw new \DomainException('Diagnostico deve ser informado.');
        }

        if ($maoDeObra < 0) {
            throw new \DomainException('Mão de obra não pode ser negativa.');
        }

        $ordem = $this->buscarPorId($id);
        
        if (!$ordem->getStatus()->equals(StatusOrdem::EM_DIAGNOSTICO)) {
            throw new \DomainException('Somente ordens EM_DIAGNOSTICO podem submeter orçamento.');
        }

        if ($ordem->getMecanicoId() !== null && $ordem->getMecanicoId() !== $mecanicoId) {
            throw new \DomainException('Apenas o mecânico responsavel pode submeter este orçamento.');
        }

        $pecasInput = $dados['pecas'] ?? [];
        $insumosInput = $dados['insumos'] ?? [];

        $orcamentoDetalhado = $this->repository->processarOrcamento(
            $id,
            $ordem,
            $mecanicoId,
            $diagnostico,
            $maoDeObra,
            $pecasInput,
            $insumosInput
        );

        $links = $this->gerarLinksAprovacao($orcamentoDetalhado['ordem']);
        $this->notificarMudancaStatus($orcamentoDetalhado['ordem'], $links, $orcamentoDetalhado['orcamento']);
        $this->notificarSistema(
            fn() => $this->sistemaNotificacaoService->notificarOsAguardandoAprovacaoParaAtendentes($orcamentoDetalhado['ordem']),
            $orcamentoDetalhado['ordem'],
            'OS_AGUARDANDO_APROVACAO'
        );

        return $orcamentoDetalhado['ordem'];
    }

    public function aprovarPublico(int $id, string $token): OrdemServico
    {
        $this->validarTokenAcaoPublica($token, $id, 'aprovar');

        $os = $this->buscarPorId($id);
        $os->aprovar();
        $salva = $this->repository->save($os);
        $this->notificarMudancaStatus($salva);
        $this->notificarSistema(
            fn() => $this->sistemaNotificacaoService->notificarMecanicoResponsavel($salva, 'OS_APROVADA'),
            $salva,
            'OS_APROVADA'
        );

        return $salva;
    }

    public function reprovarPublico(int $id, string $token): OrdemServico
    {
        $this->validarTokenAcaoPublica($token, $id, 'reprovar');

        $os = $this->buscarPorId($id);
        $os->reprovar();

        $salva = $this->repository->reprovarOrcamento($id, $os);

        $this->notificarMudancaStatus($salva);
        $this->notificarSistema(
            fn() => $this->sistemaNotificacaoService->notificarMecanicoResponsavel($salva, 'OS_RECUSADA'),
            $salva,
            'OS_RECUSADA'
        );

        return $salva;
    }

    public function iniciarExecucao(int $id): OrdemServico
    {
        $os = $this->buscarPorId($id);
        $os->iniciarExecucao();
        $salva = $this->repository->save($os);
        $this->notificarMudancaStatus($salva);

        return $salva;
    }

    public function finalizar(int $id): OrdemServico
    {
        $os = $this->buscarPorId($id);

        $valorAtual = $os->getValorTotal();

        if ($valorAtual === null) {
            throw new \DomainException('Não e possível finalizar sem valor_total definido no orçamento.');
        }

        $os->finalizarServico($valorAtual);
        $salva = $this->repository->save($os);
        $this->notificarMudancaStatus($salva);
        $this->notificarSistema(
            fn() => $this->sistemaNotificacaoService->notificarOsFinalizadaParaAtendentes($salva),
            $salva,
            'OS_FINALIZADA'
        );

        return $salva;
    }

    public function processarLembretesOsRecebidasSemDiagnostico(): int
    {
        return $this->sistemaNotificacaoService->processarLembretesOsRecebidasSemDiagnostico();
    }

    public function entregar(int $id): OrdemServico
    {
        $os = $this->buscarPorId($id);
        $os->entregarVeiculo();
        $salva = $this->repository->save($os);
        $this->notificarMudancaStatus($salva);

        return $salva;
    }

    public function remover(int $id): void
    {
        $this->buscarPorId($id);
        $this->repository->delete($id);
    }

    private function gerarLinksAprovacao(OrdemServico $ordem): array
    {
        $id = $ordem->getId();

        if ($id === null) {
            return [];
        }

        $aprovarToken = $this->gerarTokenAcaoPublica($id, 'aprovar');
        $reprovarToken = $this->gerarTokenAcaoPublica($id, 'reprovar');

        return [
            'aprovar' => url("/api/public/ordens-servico/{$id}/aprovar/{$aprovarToken}"),
            'reprovar' => url("/api/public/ordens-servico/{$id}/reprovar/{$reprovarToken}"),
        ];
    }

    private function gerarTokenAcaoPublica(int $ordemId, string $acao): string
    {
        $payload = [
            'ordem_id' => $ordemId,
            'acao' => $acao,
            'exp' => time() + self::TOKEN_TTL_SECONDS,
        ];

        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = hash_hmac('sha256', $encodedPayload, $this->tokenSecret());

        return $encodedPayload . '.' . $signature;
    }

    private function validarTokenAcaoPublica(string $token, int $ordemId, string $acao): void
    {
        $parts = explode('.', $token, 2);

        if (count($parts) !== 2) {
            throw new \InvalidArgumentException('Token público invalido.');
        }

        [$encodedPayload, $signature] = $parts;
        $expected = hash_hmac('sha256', $encodedPayload, $this->tokenSecret());

        if (!hash_equals($expected, $signature)) {
            throw new \InvalidArgumentException('Token público invalido.');
        }

        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);

        if (!is_array($payload)) {
            throw new \InvalidArgumentException('Token público invalido.');
        }

        if (($payload['ordem_id'] ?? null) !== $ordemId || ($payload['acao'] ?? null) !== $acao) {
            throw new \InvalidArgumentException('Token público invalido para esta ação.');
        }

        if ((int) ($payload['exp'] ?? 0) < time()) {
            throw new \InvalidArgumentException('Token público expirado.');
        }
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return (string) base64_decode(strtr($value, '-_', '+/'));
    }

    private function tokenSecret(): string
    {
        return (string) config('app.key', 'ordem-servico-public-token-secret');
    }

    private function notificarMudancaStatus(OrdemServico $ordem, array $links = [], array $orcamento = []): void
    {
        try {
            $this->notificacaoService->enviarMudancaStatus($ordem, $links, $orcamento);
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar notificação de status da OS.', [
                'ordem_servico_id' => $ordem->getId(),
                'erro' => $e->getMessage(),
            ]);
        }
    }

    private function notificarSistema(callable $callback, OrdemServico $ordem, string $tipo): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::error('Falha ao persistir notificação de sistema da OS.', [
                'ordem_servico_id' => $ordem->getId(),
                'tipo' => $tipo,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    
    private function getClienteByDocumento(string $documento): Cliente
    {
        $documentoLimpo = preg_replace('/\D/', '', $documento);

        if ($documentoLimpo === null) {
            throw new \DomainException('Documento do cliente inválido.');
        }

        if (strlen($documentoLimpo) === 11) {
            $cpfValidado = new Cpf($documentoLimpo);
            $cliente = $this->clienteRepository->findByDocumentoTipo($cpfValidado->getRaw(), 'pf');

            if (!$cliente) {
                throw new \DomainException('Cliente não encontrado para o CPF informado.');
            }

            return $cliente;
        }

        if (strlen($documentoLimpo) === 14) {
            $cnpjValidado = new Cnpj($documentoLimpo);
            $cliente = $this->clienteRepository->findByDocumentoTipo($cnpjValidado->getRaw(), 'pj');

            if (!$cliente) {
                throw new \DomainException('Cliente não encontrado para o CNPJ informado.');
            }

            return $cliente;
        }

        throw new \DomainException('Documento do cliente deve conter 11 dígitos (CPF) ou 14 dígitos (CNPJ).');
    }

    private function veiculoPertenceAoCliente(int $veiculoId, int $clienteId): void
    {
        if (!$this->veiculoRepository->existsByIdAndClienteId($veiculoId, $clienteId)) {
            throw new \DomainException('O veiculo informado não pertence ao cliente selecionado.');
        }
    }
}
