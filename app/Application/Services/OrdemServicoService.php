<?php

namespace App\Application\Services;

use App\Domain\OrdemServico\Repositories\OrdemServicoRepositoryInterface;
use App\Domain\OrdemServico\Entities\OrdemServico;
use App\Domain\OrdemServico\ValueObjects\StatusOrdem;
use App\Infrastructure\Persistence\Eloquent\Models\InsumoModel;
use App\Infrastructure\Persistence\Eloquent\Models\InsumoOsModel;
use App\Infrastructure\Persistence\Eloquent\Models\ItemOsModel;
use App\Infrastructure\Persistence\Eloquent\Models\PecaModel;
use Illuminate\Support\Facades\DB;
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
        private OrdemServicoNotificacaoService $notificacaoService,
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

    public function criar(array $data): OrdemServico
    {
        $os = new OrdemServico(
            id: null,
            clienteId: $data['cliente_id'],
            veiculoId: $data['veiculo_id'],
            mecanicoId: $data['mecanico_id'] ?? null,
            status: StatusOrdem::from('ABERTA'),
            descricaoProblema: $data['descricao_problema'],
            diagnostico: $data['diagnostico'] ?? null,
            valorTotal: null,
            iniciadaEm: null,
            criadaEm: new \DateTimeImmutable(),
        );

        $salva = $this->repository->save($os);
        $this->notificarMudancaStatus($salva);

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

    public function submeterOrcamento(int $id, int $mecanicoId, array $dados): OrdemServico
    {
        $diagnostico = trim((string) ($dados['diagnostico'] ?? ''));
        $maoDeObra = (float) ($dados['mao_de_obra'] ?? 0);

        if ($diagnostico === '') {
            throw new \DomainException('Diagnostico deve ser informado.');
        }

        if ($maoDeObra < 0) {
            throw new \DomainException('Mao de obra nao pode ser negativa.');
        }

        $ordem = $this->buscarPorId($id);

        if (!$ordem->getStatus()->equals(StatusOrdem::EM_DIAGNOSTICO)) {
            throw new \DomainException('Somente ordens EM_DIAGNOSTICO podem submeter orcamento.');
        }

        if ($ordem->getMecanicoId() !== null && $ordem->getMecanicoId() !== $mecanicoId) {
            throw new \DomainException('Apenas o mecanico responsavel pode submeter este orcamento.');
        }

        $pecasInput = $dados['pecas'] ?? [];
        $insumosInput = $dados['insumos'] ?? [];

        $orcamentoDetalhado = DB::transaction(function () use ($id, $ordem, $mecanicoId, $diagnostico, $maoDeObra, $pecasInput, $insumosInput) {
            // Reenvio da rotina sobrescreve itens anteriores da OS.
            ItemOsModel::query()->where('ordem_servico_id', $id)->delete();
            InsumoOsModel::query()->where('ordem_servico_id', $id)->delete();

            $itensPecas = [];
            $itensInsumos = [];
            $totalPecas = 0.0;
            $totalInsumos = 0.0;

            foreach ($pecasInput as $pecaInput) {
                $peca = PecaModel::query()->find($pecaInput['peca_id']);

                if (!$peca) {
                    throw new \DomainException('Peca informada nao encontrada para composicao do orcamento.');
                }

                $quantidade = (int) $pecaInput['quantidade'];
                $precoUnitario = (float) $peca->preco_unitario;
                $subtotal = round($quantidade * $precoUnitario, 2);

                ItemOsModel::query()->create([
                    'ordem_servico_id' => $id,
                    'peca_id' => $peca->id,
                    'quantidade' => $quantidade,
                    'preco_unitario' => $precoUnitario,
                    'subtotal' => $subtotal,
                ]);

                $itensPecas[] = [
                    'id' => $peca->id,
                    'nome' => $peca->nome,
                    'quantidade' => $quantidade,
                    'preco_unitario' => $precoUnitario,
                    'subtotal' => $subtotal,
                ];

                $totalPecas += $subtotal;
            }

            foreach ($insumosInput as $insumoInput) {
                $insumo = InsumoModel::query()->find($insumoInput['insumo_id']);

                if (!$insumo) {
                    throw new \DomainException('Insumo informado nao encontrado para composicao do orcamento.');
                }

                $quantidade = (float) $insumoInput['quantidade'];
                $precoUnitario = (float) $insumo->preco_unitario;
                $subtotal = round($quantidade * $precoUnitario, 2);

                InsumoOsModel::query()->create([
                    'ordem_servico_id' => $id,
                    'insumo_id' => $insumo->id,
                    'quantidade' => $quantidade,
                    'preco_unitario' => $precoUnitario,
                    'subtotal' => $subtotal,
                ]);

                $itensInsumos[] = [
                    'id' => $insumo->id,
                    'nome' => $insumo->nome,
                    'quantidade' => $quantidade,
                    'preco_unitario' => $precoUnitario,
                    'subtotal' => $subtotal,
                    'unidade_medida' => $insumo->unidade_medida,
                ];

                $totalInsumos += $subtotal;
            }

            $totalOrcamento = round($totalPecas + $totalInsumos + $maoDeObra, 2);

            $ordem->atualizar([
                'mecanico_id' => $mecanicoId,
                'diagnostico' => $diagnostico,
                'valor_total' => $totalOrcamento,
            ]);
            $ordem->gerarOrcamento();

            $ordemSalva = $this->repository->save($ordem);

            return [
                'ordem' => $ordemSalva,
                'orcamento' => [
                    'diagnostico' => $diagnostico,
                    'mao_de_obra' => $maoDeObra,
                    'pecas' => $itensPecas,
                    'insumos' => $itensInsumos,
                    'total_pecas' => round($totalPecas, 2),
                    'total_insumos' => round($totalInsumos, 2),
                    'valor_total' => $totalOrcamento,
                ],
            ];
        });

        $links = $this->gerarLinksAprovacao($orcamentoDetalhado['ordem']);
        $this->notificarMudancaStatus($orcamentoDetalhado['ordem'], $links, $orcamentoDetalhado['orcamento']);

        return $orcamentoDetalhado['ordem'];
    }

    public function aprovarPublico(int $id, string $token): OrdemServico
    {
        $this->validarTokenAcaoPublica($token, $id, 'aprovar');

        $os = $this->buscarPorId($id);
        $os->aprovar();
        $salva = $this->repository->save($os);
        $this->notificarMudancaStatus($salva);

        return $salva;
    }

    public function reprovarPublico(int $id, string $token): OrdemServico
    {
        $this->validarTokenAcaoPublica($token, $id, 'reprovar');

        $os = $this->buscarPorId($id);
        $os->reprovar();
        $salva = $this->repository->save($os);
        $this->notificarMudancaStatus($salva);

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

    public function finalizar(int $id, float $valorTotal): OrdemServico
    {
        $os = $this->buscarPorId($id);
        $os->finalizarServico($valorTotal);
        $salva = $this->repository->save($os);
        $this->notificarMudancaStatus($salva);

        return $salva;
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
            throw new \InvalidArgumentException('Token publico invalido.');
        }

        [$encodedPayload, $signature] = $parts;
        $expected = hash_hmac('sha256', $encodedPayload, $this->tokenSecret());

        if (!hash_equals($expected, $signature)) {
            throw new \InvalidArgumentException('Token publico invalido.');
        }

        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);

        if (!is_array($payload)) {
            throw new \InvalidArgumentException('Token publico invalido.');
        }

        if (($payload['ordem_id'] ?? null) !== $ordemId || ($payload['acao'] ?? null) !== $acao) {
            throw new \InvalidArgumentException('Token publico invalido para esta acao.');
        }

        if ((int) ($payload['exp'] ?? 0) < time()) {
            throw new \InvalidArgumentException('Token publico expirado.');
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
            Log::error('Falha ao enviar notificacao de status da OS.', [
                'ordem_servico_id' => $ordem->getId(),
                'erro' => $e->getMessage(),
            ]);
        }
    }
}
