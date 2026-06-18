<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\OrdemServico\Entities\OrdemServico;
use App\Domain\OrdemServico\Repositories\OrdemServicoRepositoryInterface;
use App\Domain\OrdemServico\ValueObjects\StatusOrdem;
use App\Infrastructure\Persistence\Eloquent\Models\InsumoModel;
use App\Infrastructure\Persistence\Eloquent\Models\InsumoOsModel;
use App\Infrastructure\Persistence\Eloquent\Models\ItemOsModel;
use App\Infrastructure\Persistence\Eloquent\Models\OrdemServicoModel;
use App\Infrastructure\Persistence\Eloquent\Models\PecaModel;

/**
 * CAMADA DE INFRAESTRUTURA — Implementação do Repositório
 * Implementa a interface definida no Domínio usando Eloquent ORM.
 * Faz o mapeamento: Model Eloquent ↔ Entidade de Domínio.
 */
class EloquentOrdemServicoRepository implements OrdemServicoRepositoryInterface
{
    public function findById(int $id): ?OrdemServico
    {
        $model = OrdemServicoModel::find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(): array
    {
        return OrdemServicoModel::all()
            ->map(fn(OrdemServicoModel $m) => $this->toEntity($m))
            ->toArray();
    }

    public function findByCliente(int $clienteId): array
    {
        return OrdemServicoModel::where('cliente_id', $clienteId)->get()
            ->map(fn(OrdemServicoModel $m) => $this->toEntity($m))
            ->toArray();
    }

    public function findRecebidas24Horas(): array
    {
        return OrdemServicoModel::query()
            ->where('status', StatusOrdem::RECEBIDA)
            ->where('created_at', '<=', now()->subHours(24))
            ->get()
            ->map(fn(OrdemServicoModel $m) => $this->toEntity($m))
            ->toArray();
    }

    public function save(OrdemServico $ordem): OrdemServico
    {
        $model = $ordem->getId()
            ? OrdemServicoModel::findOrFail($ordem->getId())
            : new OrdemServicoModel();

        $model->fill([
            'cliente_id'        => $ordem->getClienteId(),
            'veiculo_id'        => $ordem->getVeiculoId(),
            'mecanico_id'       => $ordem->getMecanicoId(),
            'status'            => (string) $ordem->getStatus(),
            'descricao_problema'=> $ordem->getDescricao(),
            'diagnostico'       => $ordem->getDiagnostico(),
            'valor_total'       => $ordem->getValorTotal(),
            'iniciada_em'       => $ordem->getIniciadaEm()?->format('Y-m-d H:i:s'),
            'concluida_em'      => $ordem->getConcluidaEm()?->format('Y-m-d H:i:s'),
        ]);

        $model->save();
        return $this->toEntity($model);
    }

    public function delete(int $id): void
    {
        OrdemServicoModel::destroy($id);
    }

    public function processarOrcamento(
        int $id,
        OrdemServico $ordem,
        int $mecanicoId,
        string $diagnostico,
        float $maoDeObra,
        array $pecasInput,
        array $insumosInput
    ): array
    {
        return 
            \Illuminate\Support\Facades\DB::transaction(function () use (
                $id,
                $ordem,
                $mecanicoId,
                $diagnostico,
                $maoDeObra,
                $pecasInput,
                $insumosInput
            ) {
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

                $ordemSalva = $this->save($ordem);

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
    }

    public function reprovarOrcamento(int $id, OrdemServico $ordem): OrdemServico
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id, $ordem) {
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

            return $this->save($ordem);
        });
    }

    private function toEntity(OrdemServicoModel $model): OrdemServico
    {
        return new OrdemServico(
            id: $model->id,
            clienteId: $model->cliente_id,
            veiculoId: $model->veiculo_id,
            mecanicoId: $model->mecanico_id,
            status: StatusOrdem::from($model->status),
            descricaoProblema: $model->descricao_problema,
            diagnostico: $model->diagnostico,
            valorTotal: $model->valor_total !== null ? (float) $model->valor_total : null,
            iniciadaEm: $model->iniciada_em?->toDateTimeImmutable(),
            criadaEm: $model->created_at?->toDateTimeImmutable() ?? new \DateTimeImmutable(),
            atualizadaEm: $model->updated_at?->toDateTimeImmutable(),
            concluidaEm: $model->concluida_em?->toDateTimeImmutable(),
        );
    }
}
