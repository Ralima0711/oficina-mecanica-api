<?php

namespace App\Application\Services;

use App\Domain\Cliente\Entities\Cliente;
use App\Domain\Cliente\Repositories\ClienteRepositoryInterface;
use App\Domain\Cliente\ValueObjects\Cpf;
use App\Domain\OrdemServico\Repositories\OrdemServicoRepositoryInterface;
use App\Domain\OrdemServico\Entities\OrdemServico;
use App\Domain\OrdemServico\ValueObjects\StatusOrdem;
use App\Infrastructure\Persistence\Eloquent\Models\InsumoModel;
use App\Infrastructure\Persistence\Eloquent\Models\InsumoOsModel;
use App\Infrastructure\Persistence\Eloquent\Models\ItemOsModel;
use App\Infrastructure\Persistence\Eloquent\Models\PecaModel;
use App\Infrastructure\Persistence\Eloquent\Models\VeiculoModel;
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
        private ClienteRepositoryInterface $clienteRepository,
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

        if ($clienteId === null && array_key_exists('cliente_cpf', $data) && $data['cliente_cpf'] !== null) {
            $cliente = $this->getClienteByCpf((string) $data['cliente_cpf']);
            $clienteId = $cliente->getId();
        }

        if ($clienteId === null) {
            throw new \DomainException('Informe cliente_id ou cliente_cpf para criar a ordem.');
        }

        $veiculoId = (int) $data['veiculo_id'];
        $this->veiculoPertenceAoCliente($veiculoId, $clienteId);

        $os = new OrdemServico(
            id: null,
            clienteId: $clienteId,
            veiculoId: $veiculoId,
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

        if (strtolower((string) $abertoPorRole) === 'atendente') {
            $this->notificarSistema(
                fn() => $this->sistemaNotificacaoService->notificarOsAbertaParaMecanicos($salva),
                $salva,
                'OS_ABERTA'
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

        $orcamentoDetalhado = DB::transaction(function () use ($id, $ordem, $mecanicoId, $diagnostico, $maoDeObra, $pecasInput, $insumosInput) {
            $itensPecasAnteriores = ItemOsModel::query()->where('ordem_servico_id', $id)->get();
            foreach ($itensPecasAnteriores as $itemAnterior) {
                $pecaAnterior = PecaModel::query()->whereKey($itemAnterior->peca_id)->lockForUpdate()->first();

                if ($pecaAnterior) {
                    $pecaAnterior->increment('estoque_atual', (int) $itemAnterior->quantidade);
                }
            }

            $itensInsumosAnteriores = InsumoOsModel::query()->where('ordem_servico_id', $id)->get();
            foreach ($itensInsumosAnteriores as $itemAnterior) {
                $insumoAnterior = InsumoModel::query()->whereKey($itemAnterior->insumo_id)->lockForUpdate()->first();

                if ($insumoAnterior) {
                    $insumoAnterior->increment('estoque_atual', (float) $itemAnterior->quantidade);
                }
            }

            ItemOsModel::query()->where('ordem_servico_id', $id)->delete();
            InsumoOsModel::query()->where('ordem_servico_id', $id)->delete();

            $itensPecas = [];
            $itensInsumos = [];
            $totalPecas = 0.0;
            $totalInsumos = 0.0;

            foreach ($pecasInput as $pecaInput) {
                $peca = PecaModel::query()
                    ->whereKey($pecaInput['peca_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$peca) {
                    throw new \DomainException('Peça informada não encontrada para composição do orçamento.');
                }

                $quantidade = (int) $pecaInput['quantidade'];

                if ((int) $peca->estoque_atual < $quantidade) {
                    throw new \DomainException("Estoque insuficiente para peça {$peca->nome}.");
                }

                $precoUnitario = (float) $peca->preco_unitario;
                $subtotal = round($quantidade * $precoUnitario, 2);

                $peca->decrement('estoque_atual', $quantidade);

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
                $insumo = InsumoModel::query()
                    ->whereKey($insumoInput['insumo_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$insumo) {
                    throw new \DomainException('Insumo informado não encontrado para composição do orçamento.');
                }

                $quantidade = (float) $insumoInput['quantidade'];

                if ((float) $insumo->estoque_atual < $quantidade) {
                    throw new \DomainException("Estoque insuficiente para insumo {$insumo->nome}.");
                }

                $precoUnitario = (float) $insumo->preco_unitario;
                $subtotal = round($quantidade * $precoUnitario, 2);

                $insumo->decrement('estoque_atual', $quantidade);

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

        $salva = DB::transaction(function () use ($id) {
            $os = $this->buscarPorId($id);
            $os->reprovar();

            $itensPeca = ItemOsModel::query()
                ->where('ordem_servico_id', $id)
                ->get();

            foreach ($itensPeca as $item) {
                $peca = PecaModel::query()->whereKey($item->peca_id)->lockForUpdate()->first();

                if ($peca) {
                    $peca->increment('estoque_atual', (int) $item->quantidade);
                }
            }

            $itensInsumo = InsumoOsModel::query()
                ->where('ordem_servico_id', $id)
                ->get();

            foreach ($itensInsumo as $item) {
                $insumo = InsumoModel::query()->whereKey($item->insumo_id)->lockForUpdate()->first();

                if ($insumo) {
                    $insumo->increment('estoque_atual', (float) $item->quantidade);
                }
            }

            return $this->repository->save($os);
        });

        $this->notificarMudancaStatus($salva);
        $this->notificarSistema(
            fn() => $this->sistemaNotificacaoService->notificarMecanicoResponsavel($salva, 'OS_CANCELADA'),
            $salva,
            'OS_CANCELADA'
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

    public function processarLembretesOsAbertasSemDiagnostico(): int
    {
        return $this->sistemaNotificacaoService->processarLembretesOsAbertasSemDiagnostico();
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

    private function getClienteByCpf(string $cpf): Cliente
    {
        $cpfValidado = new Cpf($cpf);
        $cliente = $this->clienteRepository->findByCpf($cpfValidado->getRaw());

        if (!$cliente) {
            throw new \DomainException('Cliente não encontrado para o CPF informado.');
        }

        return $cliente;
    }

    private function veiculoPertenceAoCliente(int $veiculoId, int $clienteId): void
    {
        $veiculoPertenceAoCliente = VeiculoModel::query()
            ->whereKey($veiculoId)
            ->where('cliente_id', $clienteId)
            ->exists();

        if (!$veiculoPertenceAoCliente) {
            throw new \DomainException('O veiculo informado não pertence ao cliente selecionado.');
        }
    }
}
