<?php

use App\Application\Services\OrdemServicoService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notificacoes:processar-lembretes-os', function (OrdemServicoService $service) {
    $processadas = $service->processarLembretesOsRecebidasSemDiagnostico();

    $this->info("Lembretes processados: {$processadas}");
})->purpose('Processa lembretes de OS recebida sem diagnostico a cada 24h');

Schedule::command('notificacoes:processar-lembretes-os')
    ->hourly()
    ->withoutOverlapping();
