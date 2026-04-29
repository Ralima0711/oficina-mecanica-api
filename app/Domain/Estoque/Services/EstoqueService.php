<?php

namespace App\Domain\Estoque\Services;

use App\Domain\Estoque\ValueObjects\EstoqueInfo;
use App\Domain\Estoque\Events\EstoqueAlertado;
use App\Domain\Estoque\Events\EstoqueDiminuido;
use App\Domain\Estoque\Events\EstoqueAumentado;

class EstoqueService
{
    private array $eventos = [];

    public function diminuir(
        EstoqueInfo $estoque,
        float $quantidade,
        string $motivo = null
    ): EstoqueInfo {
        if ($quantidade <= 0) {
            throw new \InvalidArgumentException("Quantidade deve ser maior que zero");
        }

        if (!$estoque->temDisponibilidade($quantidade)) {
            throw new \DomainException(
                "Estoque insuficiente. Disponível: {$estoque->getAtual()}, Solicitado: {$quantidade}"
            );
        }

        $estoqueAnterior = $estoque->getAtual();
        $novoEstoque = $estoqueAnterior - $quantidade;
        $novaInfo = EstoqueInfo::criar($novoEstoque, $estoque->getMinimo(), $estoque->getMaximo());

        // Registra evento (será passado para a Application Service)
        $this->eventos[] = new EstoqueDiminuido(
            itemId: null, // Será preenchido pela Application Service
            itemTipo: '', // Será preenchido pela Application Service
            quantidade: $quantidade,
            estoqueAnterior: $estoqueAnterior,
            estoqueAtual: $novoEstoque,
            motivo: $motivo,
        );

        // Se caiu abaixo do mínimo, dispara alerta
        if ($novaInfo->estaAbaixoDoMinimo()) {
            $this->eventos[] = new EstoqueAlertado(
                itemId: null,
                itemTipo: '',
                estoqueAtual: $novoEstoque,
                estoqueMinimo: $estoque->getMinimo(),
            );
        }

        return $novaInfo;
    }

    public function aumentar(
        EstoqueInfo $estoque,
        float $quantidade,
        string $motivo = null
    ): EstoqueInfo {
        if ($quantidade <= 0) {
            throw new \InvalidArgumentException("Quantidade deve ser maior que zero");
        }

        $estoqueAnterior = $estoque->getAtual();
        $novoEstoque = $estoqueAnterior + $quantidade;

        if ($novoEstoque > $estoque->getMaximo()) {
            throw new \DomainException(
                "Estoque ultrapassaria o máximo permitido. Máximo: {$estoque->getMaximo()}, Total: {$novoEstoque}"
            );
        }

        $novaInfo = EstoqueInfo::criar($novoEstoque, $estoque->getMinimo(), $estoque->getMaximo());

        $this->eventos[] = new EstoqueAumentado(
            itemId: null,
            itemTipo: '',
            quantidade: $quantidade,
            estoqueAnterior: $estoqueAnterior,
            estoqueAtual: $novoEstoque,
            motivo: $motivo,
        );

        return $novaInfo;
    }

    public function ajustar(
        EstoqueInfo $estoque,
        float $novaQuantidade,
        string $motivo = null
    ): EstoqueInfo {
        $estoqueAnterior = $estoque->getAtual();

        if ($novaQuantidade < 0) {
            throw new \InvalidArgumentException("Quantidade não pode ser negativa");
        }

        if ($novaQuantidade > $estoque->getMaximo()) {
            throw new \DomainException("Quantidade ultrapassa o máximo permitido");
        }

        $novaInfo = EstoqueInfo::criar($novaQuantidade, $estoque->getMinimo(), $estoque->getMaximo());

        if ($novaQuantidade > $estoqueAnterior) {
            $quantidade = $novaQuantidade - $estoqueAnterior;
            $this->eventos[] = new EstoqueAumentado(
                itemId: null,
                itemTipo: '',
                quantidade: $quantidade,
                estoqueAnterior: $estoqueAnterior,
                estoqueAtual: $novaQuantidade,
                motivo: $motivo,
            );
        } elseif ($novaQuantidade < $estoqueAnterior) {
            $quantidade = $estoqueAnterior - $novaQuantidade;
            $this->eventos[] = new EstoqueDiminuido(
                itemId: null,
                itemTipo: '',
                quantidade: $quantidade,
                estoqueAnterior: $estoqueAnterior,
                estoqueAtual: $novaQuantidade,
                motivo: $motivo,
            );
        }

        if ($novaInfo->estaAbaixoDoMinimo()) {
            $this->eventos[] = new EstoqueAlertado(
                itemId: null,
                itemTipo: '',
                estoqueAtual: $novaQuantidade,
                estoqueMinimo: $estoque->getMinimo(),
            );
        }

        return $novaInfo;
    }

    public function estaAbaixoDoMinimo(EstoqueInfo $estoque): bool
    {
        return $estoque->estaAbaixoDoMinimo();
    }

    public function estaAcimaDoMaximo(EstoqueInfo $estoque): bool
    {
        return $estoque->estaAcimaDoMaximo();
    }

    public function calcularDisponibilidade(
        EstoqueInfo $estoque,
        float $emProcessamento = 0
    ): float {
        return max(0, $estoque->getAtual() - $emProcessamento);
    }

    public function getEventos(): array
    {
        return $this->eventos;
    }

    public function limparEventos(): void
    {
        $this->eventos = [];
    }
}
