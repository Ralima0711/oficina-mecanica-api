<?php

namespace App\Mail;

use App\Domain\OrdemServico\Entities\OrdemServico;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrdemServicoStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OrdemServico $ordem,
        public array $links = [],
        public array $orcamento = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Atualizacao da Ordem de Servico #' . $this->ordem->getId(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ordem-servico-status',
            with: [
                'ordem' => $this->ordem,
                'links' => $this->links,
                'orcamento' => $this->orcamento,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
