<?php

namespace App\Application\Services;

use App\Domain\Cliente\Repositories\ClienteRepositoryInterface;
use App\Domain\OrdemServico\Entities\OrdemServico;
use App\Infrastructure\Mail\OrdemServicoStatusMail;
use Illuminate\Support\Facades\Mail;

class OrdemServicoNotificacaoService
{
    public function __construct(
        private ClienteRepositoryInterface $clienteRepository,
    ) {}

    public function enviarMudancaStatus(OrdemServico $ordem, array $links = [], array $orcamento = []): void
    {
        $cliente = $this->clienteRepository->findById($ordem->getClienteId());

        if (!$cliente || empty($cliente->getEmail())) {
            return;
        }

        Mail::to($cliente->getEmail())->send(new OrdemServicoStatusMail($ordem, $links, $orcamento));
    }
}
