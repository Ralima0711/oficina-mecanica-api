<?php

namespace App\Application\Services;

use App\Domain\OrdemServico\Entities\OrdemServico;
use App\Infrastructure\Persistence\Eloquent\Models\ClienteModel;
use App\Mail\OrdemServicoStatusMail;
use Illuminate\Support\Facades\Mail;

class OrdemServicoNotificacaoService
{
    public function enviarMudancaStatus(OrdemServico $ordem, array $links = [], array $orcamento = []): void
    {
        $cliente = ClienteModel::query()->find($ordem->getClienteId());

        if (!$cliente || empty($cliente->email)) {
            return;
        }

        Mail::to($cliente->email)->send(new OrdemServicoStatusMail($ordem, $links, $orcamento));
    }
}
